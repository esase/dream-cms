<?php
namespace Payment\Model;

use Application\Utility\Pagination as PaginationUtility;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect as DbSelectPaginator;
use Application\Service\Service as ApplicationService;
use Application\Utility\ErrorLogger;
use Zend\Db\Sql\Predicate\NotIn as NotInPredicate;
use Zend\Db\Sql\Expression;
use Exception;

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
     * Clear exchange rates
     *
     * @return integer
     */
    protected function clearExchangeRates()
    {
        $delete = $this->delete()->from('payment_exchange_rate');

        $statement = $this->prepareStatementForSqlObject($delete);
        $result = $statement->execute();
        $this->removeExchangeRatesCache();

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
                $this->clearExchangeRates();
                $this->cleanShoppingCart();
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
     * Clean shopping cart
     *
     * @return boolean
     */
    protected function cleanShoppingCart()
    {
        $delete = $this->delete()->from('payment_shopping_cart');
        $statement = $this->prepareStatementForSqlObject($delete);
        $result = $statement->execute();

        return $result->count() ? true : false;
    }

    /**
     * Edit the coupon
     *
     * @param integer $id
     * @param array $couponInfo
     *      integer discount
     *      integer date_start
     *      integer date_end
     * @return boolean|string
     */
    public function editCoupon($id, array $couponInfo)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $update = $this->update()
                ->table('payment_discount_cupon')
                ->set($couponInfo)
                ->where(array(
                    'id' => $id
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
     * Add a new coupon
     *
     * @param array $couponInfo
     *      integer discount
     *      integer date_start
     *      integer date_end
     * @return integer|string
     */
    public function addCoupon(array $couponInfo)
    {
        $insertId = 0;

        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $insert = $this->insert()
                ->into('payment_discount_cupon')
                ->values($couponInfo);

            $statement = $this->prepareStatementForSqlObject($insert);
            $statement->execute();
            $insertId = $this->adapter->getDriver()->getLastGeneratedValue();

            // generate a random slug
            $update = $this->update()
                ->table('payment_discount_cupon')
                ->set(array(
                    'slug' => strtoupper($this->generateSlug($insertId, $this->
                            generateRandString(self::COUPON_MIN_SLUG_LENGTH, self::ALLOWED_SLUG_CHARS), 'payment_discount_cupon', 'id'))
                ))
                ->where(array(
                    'id' => $insertId
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

        return $insertId;
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
                $this->clearExchangeRates();
                $this->cleanShoppingCart();
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
     * Delete a coupon
     *
     * @param integer $couponId
     * @return boolean|string
     */
    public function deleteCoupon($couponId)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $delete = $this->delete()
                ->from('payment_discount_cupon')
                ->where(array(
                    'id' => $couponId
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

    /**
     * Get coupons
     *
     * @param integer $page
     * @param integer $perPage
     * @param string $orderBy
     * @param string $orderType
     * @param array $filters
     *      string slug
     *      integer discount
     *      integer used
     *      integer start
     *      integer end
     * @return object
     */
    public function getCoupons($page = 1, $perPage = 0, $orderBy = null, $orderType = null, array $filters = array())
    {
        $orderFields = array(
            'id',
            'slug',
            'discount',
            'used',
            'start',
            'end'
        );

        $orderType = !$orderType || $orderType == 'desc'
            ? 'desc'
            : 'asc';

        $orderBy = $orderBy && in_array($orderBy, $orderFields)
            ? $orderBy
            : 'id';

        $select = $this->select();
        $select->from('payment_discount_cupon')
            ->columns(array(
                'id',
                'slug',
                'discount',
                'used',
                'start' => 'date_start',
                'end' => 'date_end'
            ))
            ->order($orderBy . ' ' . $orderType);

        // filter by a slug
        if (!empty($filters['slug'])) {
            $select->where(array(
                'slug' => $filters['slug']
            ));
        }

        // filter by a discount
        if (!empty($filters['discount'])) {
            $select->where(array(
                'discount' => $filters['discount']
            ));
        }

        // filter by a status
        if (isset($filters['used']) && $filters['used'] != null) {
            $select->where(array(
                'used' => ((int) $filters['used'] == self::COUPON_USED ? $filters['used'] : self::COUPON_NOT_USED)
            ));
        }

        // filter by an activation date
        if (!empty($filters['start'])) {
            $select->where(array(
                'date_start' => $filters['start']
            ));
        }

        // filter by a deactivation date
        if (!empty($filters['end'])) {
            $select->where(array(
                'date_end' => $filters['end']
            ));
        }

        $paginator = new Paginator(new DbSelectPaginator($select, $this->adapter));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(PaginationUtility::processPerPage($perPage));
        $paginator->setPageRange(ApplicationService::getSetting('application_page_range'));

        return $paginator;
    }

    /**
     * Get transactions
     *
     * @param integer $page
     * @param integer $perPage
     * @param string $orderBy
     * @param string $orderType
     * @param array $filters
     *      string slug
     *      integer paid
     *      string email
     *      string date
     * @return object
     */
    public function getTransactions($page = 1, $perPage = 0, $orderBy = null, $orderType = null, array $filters = array())
    {
        $orderFields = array(
            'id',
            'slug',
            'paid',
            'cost',
            'email',
            'date',
            'currency'
        );

        $orderType = !$orderType || $orderType == 'desc'
            ? 'desc'
            : 'asc';

        $orderBy = $orderBy && in_array($orderBy, $orderFields)
            ? $orderBy
            : 'id';

        $select = $this->select();
        $select->from(array('a' => 'payment_transaction'))
            ->columns(array(
                'id',
                'slug',
                'paid',
                'email',
                'cost' => 'amount',
                'date'
            ))
            ->join(
                array('b' => 'payment_currency'),
                'a.currency = b.id',
                array(
                    'currency' => 'code'
                )
            )
            ->order($orderBy . ' ' . $orderType);

        // filter by a slug
        if (!empty($filters['slug'])) {
            $select->where(array(
                'a.slug' => $filters['slug']
            ));
        }

        // filter by a paid status
        if (isset($filters['paid']) && $filters['paid'] != null) {
            $select->where(array(
                'a.paid' => ((int) $filters['paid'] == self::TRANSACTION_PAID ? $filters['paid'] : self::TRANSACTION_NOT_PAID)
            ));
        }

        // filter by a email
        if (!empty($filters['email'])) {
            $select->where(array(
                'a.email' => $filters['email']
            ));
        }

        // filter by a date
        if (!empty($filters['date'])) {
            $select->where(array(
                'a.date' => $filters['date']
            ));
        }

        $paginator = new Paginator(new DbSelectPaginator($select, $this->adapter));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(PaginationUtility::processPerPage($perPage));
        $paginator->setPageRange(ApplicationService::getSetting('application_page_range'));

        return $paginator;
    }
}