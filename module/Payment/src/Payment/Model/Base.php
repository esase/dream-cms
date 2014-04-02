<?php

namespace Payment\Model;

use Application\Model\AbstractBase;
use Zend\Db\Sql\Predicate\NotIn as NotInPredicate;
use Zend\Db\Sql\Predicate\In as InPredicate;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression;
use Application\Utility\ErrorLogger;
use  User\Service\Service as UserService;

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