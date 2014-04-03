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
     * Basket cookie
     */ 
    CONST BASKET_COOKIE = 'basket';

    //TODO: move this into settings
    /**
     * Basket cookie life time
     */ 
    const BASKET_COOKIE_LIFE_TIME = 18000000;

    /**
     * Basket id length
     */
    const BASKET_ID_LENGTH = 50;

    /**
     * Get basket id
     *
     * @return string
     */
    public function getBasketId()
    {
        return current(explode( '|', $this->_getBasketId()));
    }

    /**
     * Get basket uid
     *
     * @return string
     */
    private function _getBasketId()
    {
        $request  = $this->serviceManager->get('Request');
        $basketId = !empty($request->getCookie()->{self::BASKET_COOKIE})
            ? $request->getCookie()->{self::BASKET_COOKIE}
            : null;

        // generate a new basket id
        if (!$basketId) {
            // generate a new hash
            $basketId =  md5(time() . '_' . $this->generateRandString(self::BASKET_ID_LENGTH));
            $this->_saveBasketCookie($basketId);
        }

        return $basketId;
    }

    /**
     * Save a basket cookie
     *
     * @param string $value
     * @return void
     */
    private function _saveBasketCookie($value)
    {
        $header = new SetCookie();
        $header->setName(self::BASKET_COOKIE)
            ->setValue($value)
            ->setPath('/')
            ->setExpires(time() + self::BASKET_COOKIE_LIFE_TIME);

        $this->serviceManager->get('Response')->getHeaders()->addHeader($header);
    }

    /**
     * Save basket currency
     *
     * @param string $currency
     * @return void
     */
    public function setBasketCurrency($currency)
    {
        $basketId = $this->getBasketId();
        $value = $basketId . '|' . $currency;

        $this->_saveBasketCookie($value);
    }

    /**
     * Get basket currency
     *
     * @return sting
     */
    public function getBasketCurrency()
    {
        $currencyId = explode( '|', $this->_getBasketId());
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

        $processedRates = array();
        foreach ($result as $rate) {
            $processedRates[$rate['code']] = array(
                'id' => $rate['id'],
                'code' => $rate['code'],
                'name' => $rate['name'],
                'rate' => $rate['rate']
            );    
        }

        return $processedRates;
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