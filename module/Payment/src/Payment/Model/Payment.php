<?php

namespace Payment\Model;

use Application\Utility\ErrorLogger;
use Exception;
use Zend\Db\ResultSet\ResultSet;
use Application\Service\Service as ApplicationService;
use Application\Utility\Pagination as PaginationUtility;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect as DbSelectPaginator;
use Zend\Db\Sql\Expression as Expression;
use Zend\Db\Sql\Predicate\Literal as LiteralPredicate;

class Payment extends Base
{
    /**
     * Activate a discount coupon
     *
     * @param string $code
     * @return boolean|string
     */
    public function activateDiscountCoupon($code)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $update = $this->update()
                ->table('payment_discount_cupon')
                ->set(array(
                    'used' => self::COUPON_USED
                ))
                ->where(array(
                    'slug' => $code
                ));

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();

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
     * Is a coupon active
     *
     * @param string $code
     * @return boolean
     */
    public function isCouponActive($code)
    {
        $select = $this->select();
        $select->from('payment_discount_cupon')
            ->columns(array(
                'id'
            ))
            ->where(array(
                'slug' => $code,
                'used' => self::COUPON_NOT_USED
            ))
            ->where(array(
                new LiteralPredicate('(date_start = 0 or
                    (unix_timestamp() >= date_start)) and (date_end = 0 or (unix_timestamp() <= date_end))')
            ));

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        return $resultSet->current() ? true : false;
    }

    /**
     * Get the shopping cart's item info
     *
     * @param integer $itemId
     * @return array
     */
    public function getShoppingCartItemInfo($itemId)
    {
        $select = $this->select();
        $select->from(array('a' => 'payment_shopping_cart'))
            ->columns(array(
                'id',
                'cost',
                'discount',
                'count'
            ))
            ->join(
                array('b' => 'payment_module'),
                'a.module = b.module',
                array(
                    'countable',
                    'must_login',
                    'handler'
                )
            )
            ->where(array(
                'id' => $itemId,
                'shopping_cart_id' => $this->getShoppingCartId()
            ));

        $statement = $this->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        return $result->current();
    }

    /**
     * Check an item in shopping cart
     *
     * @param integer $objectId
     * @param integer $module
     * @return boolean
     */
    public function inShoppingCart($objectId, $module)
    {
        $select = $this->select();
        $select->from('payment_shopping_cart')
            ->columns(array(
                'id'
            ))
            ->where(array(
                'object_id' => $objectId,
                'module' => $module,
                'shopping_cart_id' => $this->getShoppingCartId()            
            ));

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        return $resultSet->current() ? true : false;
    }

    /**
     * Add to shopping cart
     *
     * @param array $itemInfo
     *      integer object_id - required
     *      integer module - required
     *      string title - required
     *      string slug - optional
     *      float cost - required
     *      integer|float discount - optional
     *      integer count - required
     * @return integer|string
     */
    public function addToShoppingCart(array $itemInfo)
    {
        $insertId = 0;

        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $insert = $this->insert()
                ->into('payment_shopping_cart')
                ->values(array_merge($itemInfo, array(
                    'shopping_cart_id' => $this->getShoppingCartId(),
                    'clear_date' => time() + (int) ApplicationService::getSetting('payment_clearing_time')
                )));

            $statement = $this->prepareStatementForSqlObject($insert);
            $statement->execute();
            $insertId = $this->adapter->getDriver()->getLastGeneratedValue();

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
     * Get the module info
     *
     * @param string $moduleName
     * @return array
     */
    public function getModuleInfo($moduleName)
    {
        $select = $this->select();
        $select->from(array('a' => 'module'))
            ->columns(array(
                'id',
                'name'
            ))
            ->join(
                array('b' => 'payment_module'),
                'a.id = b.module',
                array(
                    'countable',
                    'must_login',
                    'handler'
                )
            )
            ->where(array(
                'name' => $moduleName,
                'active' => self::MODULE_ACTIVE
            ));

        $statement = $this->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        return $result->current();
    }

    /**
     * Get shopping cart items
     *
     * @param integer $page
     * @param integer $perPage
     * @param string $orderBy
     * @param string $orderType
     * @return object
     */
    public function getShoppingCartItems($page = 1, $perPage = 0, $orderBy = null, $orderType = null)
    {
        $orderFields = array(
            'id',
            'title',
            'cost',
            'discount',
            'count',
            'total'
        );

        $orderType = !$orderType || $orderType == 'desc'
            ? 'desc'
            : 'asc';

        $orderBy = $orderBy && in_array($orderBy, $orderFields)
            ? $orderBy
            : 'id';

        $select = $this->select();
        $select->from(array('a' => 'payment_shopping_cart'))
            ->columns(array(
                'id',
                'title',
                'cost',
                'discount',
                'count',
                'total' => new Expression('cost * count - discount'),
                'active',
                'available',
                'deleted',
                'slug',
            ))
            ->join(
                array('b' => 'payment_module'),
                'a.module = b.module',
                array(
                    'view_controller',
                    'view_action',
                    'countable',
                    'must_login'
                )
            )
            ->order($orderBy . ' ' . $orderType)
            ->where(array(
                'shopping_cart_id' => $this->getShoppingCartId()    
            ));

        $paginator = new Paginator(new DbSelectPaginator($select, $this->adapter));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(PaginationUtility::processPerPage($perPage));
        $paginator->setPageRange(ApplicationService::getSetting('application_page_range'));

        return $paginator;
    }
}