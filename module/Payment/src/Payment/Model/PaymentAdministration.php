<?php

namespace Payment\Model;

use Application\Utility\Pagination as PaginationUtility;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect as DbSelectPaginator;
use Application\Service\Service as ApplicationService;
use Application\Utility\ErrorLogger;
use Zend\Db\Sql\Predicate\NotIn as NotInPredicate;
use Zend\Db\Sql\Expression;
use Zend\Db\ResultSet\ResultSet;

class PaymentAdministration extends Base
{
    /**
     * Skip the previously activated primary currency
     *
     * @param integer $currencyId
     * @return boolean
     */
    protected function skippActivatedPrimaryCurrency($currencyId)
    {
        $update = $this->update()
            ->table('payment_currency')
            ->set(array(
                'primary_currency' => self::NOT_PRIMARY_CURRENCY
            ))
            ->where(array(
               new NotInPredicate('id', array($currencyId))
            ));

        $statement = $this->prepareStatementForSqlObject($update);
        $result = $statement->execute();

        return $result->count() ? true : false;
    }

    /**
     * Delete not paid transactions
     *
     * @return integer
     */
    protected function deleteNotPaidTransactions()
    {
        $delete = $this->delete()
            ->from('payment_transaction')
            ->where(array(
                'paid' => self::TRANSACTION_NOT_PAID
            ));

        $statement = $this->prepareStatementForSqlObject($delete);
        $result = $statement->execute();

        return $result->count() ? true : false;
    }

    /**
     * Clear exchange rates
     *
     * @return integer
     */
    protected function clearExchangeRates()
    {
        $delete = $this->delete()->from('payment_exchange_rate');

        $statement = $this->prepareStatementForSqlObject($delete);
        $result = $statement->execute();

        return $result->count() ? true : false;
    }

    /**
     * Edit exchange rates
     *
     * @param array $exchangeRatesInfo
     *      integer id
     *      string code
     *      sting name
     *      float rate
     * @param array $exchangeRates
     *      float rate
     * @return boolean|string
     */
    public function editExchangeRates(array $exchangeRatesInfo, array $exchangeRates)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            // delete old rates
            $this->clearExchangeRates();

            // insert new rates
            foreach ($exchangeRates as $code => $rate) {
                if (!(float) $rate) {
                    continue;
                }

                $insert = $this->insert()
                    ->into('payment_exchange_rate')
                    ->values(array(
                        'rate' => $rate,
                        'currency' => $exchangeRatesInfo[$code]['id']
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

        return true;
    }

    /**
     * Edit currency
     *
     * @param array $oldCurrencyInfo
     *      string code
     *      sting name
     *      integer primary_currency
     * @param array $currencyInfo
     *      string code
     *      sting name
     *      integer primary_currency
     * @return boolean|string
     */
    public function editCurrency(array $oldCurrencyInfo, array $currencyInfo)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $update = $this->update()
                ->table('payment_currency')
                ->set($currencyInfo)
                ->where(array(
                    'id' => $oldCurrencyInfo['id']
                ));

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();

            // skip the previously activated primary currency
            if ((int) $currencyInfo['primary_currency'] == self::PRIMARY_CURRENCY &&
                        $oldCurrencyInfo['primary_currency'] == self::NOT_PRIMARY_CURRENCY) {

                $this->skippActivatedPrimaryCurrency($oldCurrencyInfo['id']);
                $this->deleteNotPaidTransactions();
                $this->clearExchangeRates();
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
     * Add a new currency
     *
     * @param array $currencyInfo
     *      string code
     *      sting name
     *      integer primary_currency
     * @return integer|string
     */
    public function addCurrency(array $currencyInfo)
    {
        $insertId = 0;

        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $insert = $this->insert()
                ->into('payment_currency')
                ->values($currencyInfo);

            $statement = $this->prepareStatementForSqlObject($insert);
            $statement->execute();
            $insertId = $this->adapter->getDriver()->getLastGeneratedValue();

            // skip the previously activated primary currency
            if ((int) $currencyInfo['primary_currency'] == self::PRIMARY_CURRENCY) {
                $this->skippActivatedPrimaryCurrency($insertId);
                $this->deleteNotPaidTransactions();
                $this->clearExchangeRates();
            }

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
     * Delete currency
     *
     * @param integer $currencyId
     * @return boolean|string
     */
    public function deleteCurrency($currencyId)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $delete = $this->delete()
                ->from('payment_currency')
                ->where(array(
                    'id' => $currencyId
                ))
                ->where(array(
                    new NotInPredicate('primary_currency', array(self::PRIMARY_CURRENCY))
                ));

            $statement = $this->prepareStatementForSqlObject($delete);
            $result = $statement->execute();

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ErrorLogger::log($e);

            return $e->getMessage();
        }

        return $result->count() ? true : false;
    }

    /**
     * Get currencies count
     *
     * @return integer
     */
    public function getCurrenciesCount()
    {
        $select = $this->select();
        $select->from('payment_currency')
            ->columns(array(
               'count' => new Expression('count(*)')
            ));

        $statement = $this->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        return $result->current()['count'];
    }

    /**
     * Get currencies
     *
     * @param integer $page
     * @param integer $perPage
     * @param string $orderBy
     * @param string $orderType
     * @return object
     */
    public function getCurrencies($page = 1, $perPage = 0, $orderBy = null, $orderType = null)
    {
        $orderFields = array(
            'id',
            'code',
            'primary'
        );

        $orderType = !$orderType || $orderType == 'desc'
            ? 'desc'
            : 'asc';

        $orderBy = $orderBy && in_array($orderBy, $orderFields)
            ? $orderBy
            : 'id';

        $select = $this->select();
        $select->from('payment_currency')
            ->columns(array(
                'id',
                'code',
                'name',
                'primary' => 'primary_currency'
            ))
            ->order($orderBy . ' ' . $orderType);

        $paginator = new Paginator(new DbSelectPaginator($select, $this->adapter));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(PaginationUtility::processPerPage($perPage));
        $paginator->setPageRange(ApplicationService::getSetting('application_page_range'));

        return $paginator;
    }
}