<?php
namespace Payment\Model;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Predicate\Predicate as Predicate;
use Application\Service\Service as ApplicationService;

class PaymentConsole extends Base
{
    /**
     * Get all expired shopping cart items
     *
     * @return array
     */
    public function getExpiredShoppingCartItems()
    {
        $predicate = new Predicate();
        $select = $this->select();
        $select->from('payment_shopping_cart')
            ->columns(array(
                'id'
            ))
            ->where(array(
                $predicate->lessThanOrEqualTo('date',
                        time() - (int) ApplicationService::getSetting('payment_clearing_time'))
            ));

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        return $resultSet->toArray();
    }

    /**
     * Get all expired not paid transactions
     *
     * @return array
     */
    public function getExpiredTransactions()
    {
        $predicate = new Predicate();
        $select = $this->select();
        $select->from('payment_transaction')
            ->columns(array(
                'id',
                'slug'
            ))
            ->where(array(
                'paid' => self::TRANSACTION_NOT_PAID,
                $predicate->lessThanOrEqualTo('date',
                        time() - (int) ApplicationService::getSetting('payment_clearing_time'))
            ));

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        return $resultSet->toArray();
    }
}