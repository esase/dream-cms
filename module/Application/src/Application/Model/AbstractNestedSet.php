<?php

namespace Application\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Predicate\Predicate as Predicate;
use Application\Utility\ErrorLogger;
use Exception;
use Zend\Db\Sql\Expression as Expression;
use Closure;

abstract class AbstractNestedSet 
{
    /**
     * Left key
     * @var string
     */
    protected $left = 'left_key';

    /**
     * Right key
     * @var string
     */
    protected $right = 'right_key';

    /**
     * Level
     * @var string
     */
    protected $level = 'level';

    /**
     * Table gateway
     * @var object
     */
    protected $tableGateway;

    public function __construct(TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    /**
     * Insert node
     *
     * @param integer $parentLevel
     * @param integer $parentRightKey
     * @apram array $data
     * @return integer|string
     */
    public function insertNode($parentLevel = 0, $parentRightKey = 1, array $data = array())
    {
        $insertId = 0;

        try {
            $this->tableGateway->getAdapter()->getDriver()->getConnection()->beginTransaction();

            // update child
            if ($parentLevel) {
                $this->tableGateway->update(array(
                    $this->left => new Expression($this->left   . ' + 2'),
                    $this->right => new Expression($this->right . ' + 2'),
                ), array(
                    (new Predicate)->greaterThan($this->left, $parentRightKey),
                ));
            }

            // update parent
            $this->tableGateway->update(array(
                $this->right => new Expression($this->right . ' + 2'),
            ), array(
                (new Predicate)->greaterThanOrEqualTo($this->right, $parentRightKey),
                (new Predicate)->lessThan($this->left, $parentRightKey)
            )); 

            // insert a new node
             $this->tableGateway->insert(array(
                $this->left => $parentRightKey,
                $this->right => $parentRightKey + 1,
                $this->level => $parentLevel + 1
            ) + $data);

            $insertId = $this->tableGateway->getLastInsertValue();
            $this->tableGateway->getAdapter()->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->tableGateway->getAdapter()->getDriver()->getConnection()->rollback();
            ErrorLogger::log($e);

            return $e->getMessage();
        }

        return $insertId;
    }

    /**
     * Get parent nodes
     *
     * @param integer $leftKey
     * @param integer $rightKey
     * @param object $closure
     * @return boolean|array
     */
    public function getParentNodes($leftKey, $rightKey, Closure $closure = null)
    {
        $resultSet = $this->tableGateway->select(function (Select $select) use ($leftKey, $rightKey, $closure) {
            $select->where->lessThanOrEqualTo($this->left, $leftKey);
            $select->where->greaterThanOrEqualTo($this->right, $rightKey);
            $select->order($this->left);

            if ($closure) {
                $closure($select);
            }
        });

        return $resultSet->count() ? $resultSet->toArray() : false;
    }

    /**
     * Get node info
     *
     * @param integer $id
     * @param string $field
     * @param object $closure
     * @return array|boolean
     */
    public function getNodeInfo($id, $field = 'id', Closure $closure = null)
    {
        $resultSet = $this->tableGateway->select(function (Select $select) use ($id, $field, $closure) {
            $select->where(array(
                $field => $id
            ))->limit(1);

            if ($closure) {
                $closure($select);
            }
        });

        return $resultSet->count() ? (array) $resultSet->current() : false;
    }
}