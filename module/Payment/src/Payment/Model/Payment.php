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
use Payment\Service\Service as PaymentService;
use User\Model\Base as UserBaseModel;

class Payment extends Base
{
    /**
     * Add a new transaction
     *
     * @param integer $userId
     * @param array $transactionInfo
     *      integer payment_type - required
     *      string comments - optional
     *      string first_name - required
     *      string last_name - required
     *      string email - required
     *      string phone - required
     *      string address - optional
     * @param array $items
     *      integer object_id
     *      integer module
     *      string title
     *      string slug
     *      float cost
     *      float discount
     *      integer count
     * @return integer|string
     */
    public function addTransaction($userId, array $transactionInfo, array $items)
    {
        $transactionId = 0;

        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $basicData = array(
                'date' => new Expression('NOW()'),
                'currency' => PaymentService::getPrimaryCurrency()['id'],
                'clear_date' => time() + (int) ApplicationService::getSetting('payment_clearing_time')
            );

            // add the user id
            if (UserBaseModel::DEFAULT_GUEST_ID != $userId) {
                $basicData['user_id'] = $userId;
            }

            // add the discount id
            if (PaymentService::getDiscountCouponInfo()) {
                $basicData['discount_cupon'] = PaymentService::getDiscountCouponInfo()['id'];    
            }

            $insert = $this->insert()
                ->into('payment_transaction')
                ->values(array_merge($transactionInfo, $basicData));

            $statement = $this->prepareStatementForSqlObject($insert);
            $statement->execute();
            $transactionId = $this->adapter->getDriver()->getLastGeneratedValue();

            // update the discount coupon info
            if (PaymentService::getDiscountCouponInfo()) {
                $update = $this->update()
                    ->table('payment_discount_cupon')
                    ->set(array(
                        'used' => self::COUPON_USED
                    ))
                    ->where(array(
                        'id' => PaymentService::getDiscountCouponInfo()['id']
                    ));

                $statement = $this->prepareStatementForSqlObject($update);
                $statement->execute();
            }

            // add items
            foreach ($items as $item) {
                $insert = $this->insert()
                    ->into('payment_transaction_item')
                    ->values(array(
                        'transaction_id' => $transactionId,
                        'object_id' => $item['object_id'],
                        'module' => $item['module'],
                        'title' => $item['title'],
                        'slug' => $item['slug'],
                        'cost' => $item['cost'],
                        'discount' => $item['discount'],
                        'count' => $item['count']
                    ));

                $statement = $this->prepareStatementForSqlObject($insert);
                $statement->execute();
            }

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ErrorLogger::log($e);

            return $e->getMessage();
        }

        return $transactionId;
    }

    /**
     * Get payments types
     *
     * @return array
     */
    public function getPaymentsTypes()
    {
        $paymentsTypes = array();

        $select = $this->select();
        $select->from('payment_type')
            ->columns(array(
                'id',
                'description'
            ));

        $statement = $this->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        foreach ($result as $payment) {
            $paymentsTypes[$payment['id']] = $payment['description'];
        }

        return $paymentsTypes;
    }

    /**
     * Update the shopping cart's item
     *
     * @param integer $id
     * @param array $itemInfo
     * @return boolean|string
     */
    public function updateShoppingCartItem($id, array $itemInfo)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $update = $this->update()
                ->table('payment_shopping_cart')
                ->set($itemInfo)
                ->where(array(
                    'id' => $id,
                    'shopping_cart_id' => $this->getShoppingCartId()
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
     * Get the shopping cart's item info
     *
     * @param integer $itemId
     * @param boolean $onlyActive
     * @return array
     */
    public function getShoppingCartItemInfo($itemId, $onlyActive = false)
    {
        $select = $this->select();
        $select->from(array('a' => 'payment_shopping_cart'))
            ->columns(array(
                'id',
                'object_id',
                'cost',
                'discount',
                'count'
            ))
            ->join(
                array('b' => 'payment_module'),
                'a.module = b.module',
                array(
                    'module',
                    'countable',
                    'multi_costs',
                    'must_login',
                    'handler'
                )
            )
            ->where(array(
                'id' => $itemId,
                'shopping_cart_id' => $this->getShoppingCartId()
            ));

        if ($onlyActive) {
            $select->where(array(
                'active' => self::ITEM_ACTIVE,
                'available' => self::ITEM_AVAILABLE,
                'deleted' => self::ITEM_NOT_DELETED
            ));
        }

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
                    'multi_costs',
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
                'object_id',
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
                    'multi_costs',
                    'must_login',
                    'handler'
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