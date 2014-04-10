<?php

namespace Payment\Model;

use Application\Model\AbstractBase;
use Zend\Db\Sql\Predicate\NotIn as NotInPredicate;
use Zend\Db\Sql\Predicate\In as InPredicate;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression;
use Application\Utility\ErrorLogger;
use User\Service\Service as UserService;
use Zend\Http\Header\SetCookie;
use Application\Service\Service as ApplicationService;
use Exception;
use Application\Utility\Cache as CacheUtility;
use Payment\Handler\InterfaceHandler as PaymentInterfaceHandler;
use Zend\Form\Exception\InvalidArgumentException;

class Base extends AbstractBase
{
    /**
     * Transaction paid
     */
    CONST TRANSACTION_PAID = 1;

    /**
     * Transaction not paid
     */
    CONST TRANSACTION_NOT_PAID = 0;

    /**
     * Primary currency
     */
    CONST PRIMARY_CURRENCY = 1;

    /**
     * Not primary currency
     */
    CONST NOT_PRIMARY_CURRENCY = 0;

    /**
     * Coupon activated
     */
    CONST COUPON_ACTIVATED = 1;

    /**
     * Coupon not activated
     */
    CONST COUPON_NOT_ACTIVATED = 0;

    /**
     * Coupon min slug length
     */
    const COUPON_MIN_SLUG_LENGTH = 15;

    /**
     * Coupon slug chars
     */
    const COUPON_SLUG_CHARS = 'abcdefghijklmnopqrstuvwxyz0123456789';

    /**
     * Shopping cart cookie
     */ 
    CONST SHOPPING_CART_COOKIE = 'shopping_cart';

    /**
     * Basket id length
     */
    const SHOPPING_CART_ID_LENGTH = 50;

    /**
     * Item count limit reached flag
     */ 
    CONST ITEM_COUNT_LIMIT_REACHED = 1;

    /**
     * Item count limit not reached flag
     */ 
    CONST ITEM_COUNT_LIMIT_NOT_REACHED = 0;

    /**
     * Item is not available flag
     */ 
    CONST ITEM_NOT_AVAILABLE = 0;

    /**
     * Item is available flag
     */ 
    CONST ITEM_AVAILABLE = 1;

    /**
     * Item deleted flag
     */ 
    CONST ITEM_DELETED = 1;

    /**
     * Item not deleted flag
     */ 
    CONST ITEM_NOT_DELETED = 0;

    /**
     * Payment modules cache
     */
    const CACHE_PAYMENT_MODULES = 'Payment_Modules';

    /**
     * Payment exchange rates cache
     */
    const CACHE_EXCHANGE_RATES = 'Payment_Exchange_Rates';

    /**
     * Get all active shopping cart items
     *
     * @return array
     */
    public function getAllShoppingCartItems()
    {
        $select = $this->select();
        $select->from('payment_shopping_cart')
            ->columns(array(
                'cost',
                'discount',
                'count'
            ))
            ->where(array(
                'available' => self::ITEM_AVAILABLE,
                'count_limit' => self::ITEM_COUNT_LIMIT_NOT_REACHED,
                'deleted' => self::ITEM_NOT_DELETED,
                'shopping_cart_id' => $this->getShoppingCartId()
            ));

        $statement = $this->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        return $result;
    }

    /**
     * Mark items as deleted
     *
     * @param integer $objectId
     * @param array $moduleInfo
     *      integer module
     *      string update_event
     *      string delete_event
     *      string view_controller
     *      string view_action
     *      integer countable
     *      integer must_login
     *      string handler
     * @return boolean|string
     */
    public function updateItemsInfo($objectId, $moduleInfo)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            // create a payment handler class instance
            $object = new $moduleInfo['handler']($this->serviceManager);
            if (!$object instanceof PaymentInterfaceHandler) {
                throw new InvalidArgumentException(sprintf('The file "%s" must be an object implementing Payment\Handler\InterfaceHandler',
                        $moduleInfo['handler']));
            }

            // object is not active
            if (null == ($objectInfo = $object->getItemInfo($objectId))) {
                $update = $this->update()
                    ->table('payment_shopping_cart')
                    ->set(array(
                        'available'  => self::ITEM_NOT_AVAILABLE
                    ))
                    ->where(array(
                        'object_id' => $objectId,
                        'module' => $moduleInfo['module']
                    ));

                $statement = $this->prepareStatementForSqlObject($update);
                $statement->execute();

                $update = $this->update()
                    ->table('payment_transaction_item')
                    ->set(array(
                        'available'  => self::ITEM_NOT_AVAILABLE
                    ))
                    ->where(array(
                        'object_id' => $objectId,
                        'module' => $moduleInfo['module']
                    ));

                $statement = $this->prepareStatementForSqlObject($update);
                $statement->execute();
            }
            else {
                $data = array(
                    'title' =>  $objectInfo['title'],
                    'slug'  =>  $objectInfo['slug'],
                    'count_limit' => $objectInfo['count'] <= 0 && $moduleInfo['countable']
                            ? self::ITEM_COUNT_LIMIT_REACHED : self::ITEM_COUNT_LIMIT_NOT_REACHED,
                    'available' => self::ITEM_AVAILABLE
                );

                $update = $this->update()
                    ->table('payment_shopping_cart')
                    ->set($data)
                    ->where(array(
                        'object_id' => $objectId,
                        'module' => $moduleInfo['module']
                    ));

                $statement = $this->prepareStatementForSqlObject($update);
                $statement->execute();

                $update = $this->update()
                    ->table('payment_transaction_item')
                    ->set($data)
                    ->where(array(
                        'object_id' => $objectId,
                        'module' => $moduleInfo['module']
                    ));

                $statement = $this->prepareStatementForSqlObject($update);
                $statement->execute();
            }

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
     * Mark items as deleted
     *
     * @param integer $objectId
     * @param integer $moduleId
     * @return boolean|string
     */
    public function markItemsDeleted($objectId, $moduleId)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $update = $this->update()
                ->table('payment_shopping_cart')
                ->set(array(
                    'deleted'  => self::ITEM_DELETED
                ))
                ->where(array(
                    'object_id' => $objectId,
                    'module' => $moduleId
                ));

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();

            $update = $this->update()
                ->table('payment_transaction_item')
                ->set(array(
                    'deleted'  => self::ITEM_DELETED
                ))
                ->where(array(
                    'object_id' => $objectId,
                    'module' => $moduleId
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
     * Get payment modules
     *
     * @return array
     */
    public function getPaymentModules()
    {
        // generate cache name
        $cacheName = CacheUtility::getCacheName(self::CACHE_PAYMENT_MODULES);

        // check data in cache
        if (null === ($modules = $this->staticCacheInstance->getItem($cacheName))) {
            $select = $this->select();
            $select->from('payment_module')
                ->columns(array(
                    'module',
                    'update_event',
                    'delete_event',
                    'view_controller',
                    'view_action',
                    'countable',
                    'must_login',
                    'handler'
                ));

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());
            $modules = $resultSet->toArray();

            // save data in cache
            $this->staticCacheInstance->setItem($cacheName, $modules);
        }

        return $modules;
    }

    /**
     * Get shopping cart id
     *
     * @return string
     */
    public function getShoppingCartId()
    {
        return current(explode( '|', $this->_getShoppingCartId()));
    }

    /**
     * Get shopping cart uid
     *
     * @return string
     */
    private function _getShoppingCartId()
    {
        $request  = $this->serviceManager->get('Request');
        $shoppingCartId = !empty($request->getCookie()->{self::SHOPPING_CART_COOKIE})
            ? $request->getCookie()->{self::SHOPPING_CART_COOKIE}
            : null;

        // generate a new shopping cart id
        if (!$shoppingCartId) {
            // generate a new hash
            $shoppingCartId =  md5(time() . '_' . $this->generateRandString(self::SHOPPING_CART_ID_LENGTH));
            $this->_saveShoppingCartCookie($shoppingCartId);
        }

        return $shoppingCartId;
    }

    /**
     * Save a shopping cart cookie
     *
     * @param string $value
     * @return void
     */
    private function _saveShoppingCartCookie($value)
    {
        $header = new SetCookie();
        $header->setName(self::SHOPPING_CART_COOKIE)
            ->setValue($value)
            ->setPath('/')
            ->setExpires(time() + (int) ApplicationService::getSetting('payment_shopping_cart_session_time'));

        $this->serviceManager->get('Response')->getHeaders()->addHeader($header);
    }

    /**
     * Save shopping cart currency
     *
     * @param string $currency
     * @return void
     */
    public function setShoppingCartCurrency($currency)
    {
        $shoppingCartId = $this->getShoppingCartId();
        $value = $shoppingCartId . '|' . $currency;

        $this->_saveShoppingCartCookie($value);
    }

    /**
     * Get shopping cart currency
     *
     * @return sting
     */
    public function getShoppingCartCurrency()
    {
        $currencyId = explode( '|', $this->_getShoppingCartId());
        return count($currencyId) == 2
            ? end($currencyId)
            : null;
    }

    /**
     * Update a user transactions info
     *
     * @param integer $userId
     * @return boolean|string
     */
    public function updateUserTransactionsInfo($userId)
    {
        try {
            // get the updated user's info
            $userInfo = UserService::getUserInfo($userId);

            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $update = $this->update()
                ->table('payment_transaction')
                ->set(array(
                    'user_name'  => $userInfo->nick_name,
                    'user_email' => $userInfo->email,
                    'user_phone' => $userInfo->phone
                ))
                ->where(array(
                    'user_id' => $userId
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
     * Get exchange rates
     *
     * @return array
     */
    public function getExchangeRates()
    {
        // generate cache name
        $cacheName = CacheUtility::getCacheName(self::CACHE_EXCHANGE_RATES);

        // check data in cache
        if (null === ($rates = $this->staticCacheInstance->getItem($cacheName))) {
            $select = $this->select();
            $select->from(array('a' => 'payment_currency'))
                ->columns(array(
                    'id',
                    'code',
                    'name',
                    'primary_currency'
                ))
                ->join(
                    array('b' => 'payment_exchange_rate'),
                    new Expression('a.id = b.currency'),
                    array(
                        'rate'
                    ),
                    'left'
                )
                ->where(array(
                    new NotInPredicate('primary_currency', array(self::PRIMARY_CURRENCY))
                ));
    
            $statement = $this->prepareStatementForSqlObject($select);
            $result = $statement->execute();
    
            foreach ($result as $rate) {
                $rates[$rate['code']] = array(
                    'id' => $rate['id'],
                    'code' => $rate['code'],
                    'name' => $rate['name'],
                    'rate' => $rate['rate']
                );    
            }

            // save data in cache
            $this->staticCacheInstance->setItem($cacheName, $rates);
        }

        return $rates;        
    }

    /**
     * Get the currency info
     *
     * @param integer $id
     * @param boolean $primary
     * @return array
     */
    public function getCurrencyInfo($id, $primary = false)
    {
        $select = $this->select();
        $select->from('payment_currency')
            ->columns(array(
                'id',
                'code',
                'name',
                'primary_currency'
            ))
            ->where(array(
                'id' => $id
            ));

        if ($primary) {
            $select->where(array(
                new InPredicate('primary_currency ', array(self::PRIMARY_CURRENCY))
            ));
        }

        $statement = $this->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        return $result->current();
    }

    /**
     * Is the currency code free
     *
     * @param string $code
     * @param integer $currencyCodeId
     * @return boolean
     */
    public function isCurrencyCodeFree($code, $currencyCodeId = 0)
    {
        $select = $this->select();
        $select->from('payment_currency')
            ->columns(array(
                'id'
            ))
            ->where(array('code' => $code));

        if ($currencyCodeId) {
            $select->where(array(
                new NotInPredicate('id', array($currencyCodeId))
            ));
        }

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        return $resultSet->current() ? false : true;
    }
}