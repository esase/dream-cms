<?php
namespace Membership\Model;

use Exception;
use Membership\Exception\MembershipException;
use Application\Service\Service as ApplicationService;
use Application\Utility\ErrorLogger;
use Application\Utility\FileSystem as FileSystemUtility;
use Application\Utility\Image as ImageUtility;
use Membership\Event\Event as MembershipEvent;

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
     *      string image - required
     * @param array $image
     * @return boolean|string
     */
    public function editRole($roleInfo, array $formData, array $image = array())
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $update = $this->update()
                ->table('membership_level')
                ->set(array_merge($formData, array(
                    'language' => ApplicationService::getCurrentLocalization()['language']
                )))
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

        // fire the edit membership role event
        MembershipEvent::fireEditMembershipRoleEvent($roleInfo['id']);
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
     * @param array $image
     * @return integer|string
     */
    public function addRole(array $formData, array $image = array())
    {
        $insertId = 0;

        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $insert = $this->insert()
                ->into('membership_level')
                ->values(array_merge($formData, array(
                    'language' => ApplicationService::getCurrentLocalization()['language']
                )));

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

        // fire the add membership role event
        MembershipEvent::fireAddMembershipRoleEvent($insertId);
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
     * @throws Membership\Exception\MembershipException
     * @return void
     */
    protected function uploadImage($membershipId, array $image, $oldImage = null, $deleteImage = false)
    {
        // upload the membership's image
        if (!empty($image['name'])) {
            // delete old image
            if ($oldImage) {
                if (true !== ($result = $this->deleteImage($oldImage))) {
                    throw new MembershipException('Image deleting failed');
                }
            }

            // upload a new one
            if (false === ($imageName =
                    FileSystemUtility::uploadResourceFile($membershipId, $image, self::$imagesDir))) {

                throw new MembershipException('Avatar uploading failed');
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
                throw new MembershipException('Image deleting failed');
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
}