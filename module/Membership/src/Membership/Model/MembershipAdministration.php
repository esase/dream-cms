<?php

namespace Membership\Model;

use Exception;
use Application\Utility\Pagination as PaginationUtility;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect as DbSelectPaginator;
use Application\Service\Service as ApplicationService;
use Payment\Model\Base as PaymentBaseModel;
use Zend\Db\Sql\Expression;
use Application\Utility\ErrorLogger;
use Zend\Db\Sql\Predicate;
use Application\Utility\FileSystem as FileSystemUtility;
use Application\Utility\Image as ImageUtility;

class MembershipAdministration extends Base
{
    /**
     * Edit role
     *
     * @param array $roleInfo
     * @param array $formData
     *      integer role_id - required
     *      integer cost - required
     *      integer lifetime - required
     *      string description - required
     *      string language - optional
     *      string image - required
     * @param array $image
     * @return boolean|string
     */
    public function editRole($roleInfo, array $formData, array $image = array())
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            // check the language
            if (isset($formData['language']) && !$formData['language']) {
                $formData['language'] = null;
            }

            $update = $this->update()
                ->table('membership_level')
                ->set($formData)
                ->where(array(
                    'id' => $roleInfo['id']
                ));

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();

            $this->uploadImage($roleInfo['id'], $image, $roleInfo['image']);
            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ErrorLogger::log($e);

            return $e->getMessage();
        }

        return true;
    }

    /**
     * Add a new role
     *
     * @param array $formData
     *      integer role_id - required
     *      integer cost - required
     *      integer lifetime - required
     *      string description - required
     *      string language - optional
     * @param array $image
     * @return integer|string
     */
    public function addRole(array $formData, array $image = array())
    {
        $insertId = 0;

        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            // check the language
            if (isset($formData['language']) && !$formData['language']) {
                unset($formData['language']);
            }

            $insert = $this->insert()
                ->into('membership_level')
                ->values($formData);

            $statement = $this->prepareStatementForSqlObject($insert);
            $statement->execute();
            $insertId = $this->adapter->getDriver()->getLastGeneratedValue();

            $this->uploadImage($insertId, $image);
            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ErrorLogger::log($e);

            return $e->getMessage();
        }

        return $insertId;
    }

    /**
     * Upload an image
     *
     * @param integer $membershipId
     * @param array $image
     *      string name
     *      string type
     *      string tmp_name
     *      integer error
     *      integer size
     * @param string $oldImage
     * @param boolean $deleteImage
     * @return void
     */
    protected function uploadImage($membershipId, array $image, $oldImage = null, $deleteImage = false)
    {
        // upload the membership's image
        if (!empty($image['name'])) {
            // delete old image
            if ($oldImage) {
                if (true !== ($result = $this->deleteImage($oldImage))) {
                    throw new Exception('Image deleting failed');
                }
            }

            // upload a new one
            if (false === ($imageName =
                    FileSystemUtility::uploadResourceFile($membershipId, $image, self::$imagesDir))) {

                throw new Exception('Avatar uploading failed');
            }

            // resize the image
            ImageUtility::resizeResourceImage($imageName, self::$imagesDir,
                    (int) ApplicationService::getSetting('membership_image_width'),
                    (int) ApplicationService::getSetting('membership_image_height'));

            $update = $this->update()
                ->table('membership_level')
                ->set(array(
                    'image' => $imageName
                ))
                ->where(array('id' => $membershipId));

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();
        }
        elseif ($deleteImage && $oldImage) {
            // just delete the membership's image
            if (true !== ($result = $this->deleteImage($oldImage))) {
                throw new Exception('Image deleting failed');
            }

            $update = $this->update()
                ->table('membership_level')
                ->set(array(
                    'image' => ''
                ))
                ->where(array('id' => $membershipId));

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();
        }
    }

    /**
     * Get membership levels
     *
     * @param integer $page
     * @param integer $perPage
     * @param string $orderBy
     * @param string $orderType
     * @param array $filters
     *      float cost
     *      integer lifetime
     *      integer role
     *      integer status
     * @return object
     */
    public function getMembershipLevels($page = 1, $perPage = 0, $orderBy = null, $orderType = null, array $filters = array())
    {
        $orderFields = array(
            'id',
            'cost',
            'lifetime',
            'status',
            'subscribers'
        );

        $orderType = !$orderType || $orderType == 'desc'
            ? 'desc'
            : 'asc';

        $orderBy = $orderBy && in_array($orderBy, $orderFields)
            ? $orderBy
            : 'id';

        $select = $this->select();
        $select->from(array('a' => 'membership_level'))
            ->columns(array(
                'id',
                'cost',
                'lifetime',
                'status'
            ))
            ->join(
                array('b' => 'membership_level_connection'),
                'b.membership_id = a.id',
                array(
                    'subscribers' => new Expression('count(b.id)'),
                ),
                'left'
            )
            ->join(
                array('c' => 'acl_role'),
                'a.role_id = c.id',
                array(
                    'role' => 'name'
                )
            )
            ->join(
                array('d' => 'payment_currency'),
                new Expression('d.primary_currency = ?', array(PaymentBaseModel::PRIMARY_CURRENCY)),
                array(
                    'currency' => 'code'
                )
            )
            ->group('a.id')
            ->order($orderBy . ' ' . $orderType)
            ->where(array(
                new Predicate\PredicateSet(array(
                    new Predicate\isNull('a.language'),
                    new Predicate\Operator('a.language', '=', ApplicationService::getCurrentLocalization()['language']),
                ), Predicate\PredicateSet::COMBINED_BY_OR),
            ));

        // filter by a cost
        if (!empty($filters['cost'])) {
            $select->where(array(
                'a.cost' => $filters['cost']
            ));
        }

        // filter by a lifetime
        if (!empty($filters['lifetime'])) {
            $select->where(array(
                'a.lifetime' => $filters['lifetime']
            ));
        }

        // filter by a role
        if (!empty($filters['role'])) {
            $select->where(array(
                'c.id' => $filters['role']
            ));
        }

        // filter by a status
        if (isset($filters['status']) && $filters['status'] != null) {
            $select->where(array(
                'a.status' => ((int) $filters['status'] == self::MEMBERSHIP_LEVEL_STATUS_ACTIVE ? $filters['status'] : self::MEMBERSHIP_LEVEL_STATUS_NOT_ACTIVE)
            ));
        }

        $paginator = new Paginator(new DbSelectPaginator($select, $this->adapter));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(PaginationUtility::processPerPage($perPage));
        $paginator->setPageRange(ApplicationService::getSetting('application_page_range'));

        return $paginator;
    }
}