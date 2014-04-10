<?php

namespace Payment\Model;

use Application\Utility\ErrorLogger;
use Exception;
use Zend\Db\ResultSet\ResultSet;
use Application\Service\Service as ApplicationService;

class Payment extends Base
{
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
}