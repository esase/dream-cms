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
     * Get all nodes
     *
     * @param object $closure
     * @return object
     */
    public function getAllNodes(Closure $closure = null)
    {
        $resultSet = $this->tableGateway->select(function (Select $select) use ($closure) {
            $select->order($this->left);

            if ($closure) {
                $closure($select);
            }
        });

        return $resultSet;
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
     * Delete node
     *
     * @param integer $leftKey
     * @param integer $rightKey
     * @return boolean|string
     */
    public function deleteNode($leftKey, $rightKey)
    {
        try {
            $this->tableGateway->getAdapter()->getDriver()->getConnection()->beginTransaction();

            $predicate = new Predicate();
            $this->tableGateway->delete(array(
                $predicate->greaterThanOrEqualTo($this->left, $leftKey),
                $predicate->lessThanOrEqualTo($this->right, $rightKey)
            ));

            $predicate = new Predicate();
            $this->tableGateway->update(array(
                $this->left => new Expression('IF(' . $this->left . ' > ?, ' . $this->left . ' - (? - ? + 1), ' . $this->left . ')', array(
                    $leftKey,
                    $rightKey,
                    $leftKey
                )),
                $this->right => new Expression($this->right . ' - (? - ? + 1)', array(
                   $rightKey,
                   $leftKey
                )),
            ), array(
                $predicate->greaterThan($this->right, $rightKey),
            ));

            $this->tableGateway->getAdapter()->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->tableGateway->getAdapter()->getDriver()->getConnection()->rollback();
            ErrorLogger::log($e);

            return $e->getMessage();
        }

        return true;
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
                $predicate = new Predicate();
                $this->tableGateway->update(array(
                    $this->left => new Expression($this->left   . ' + 2'),
                    $this->right => new Expression($this->right . ' + 2'),
                ), array(
                    $predicate->greaterThan($this->left, $parentRightKey),
                ));
            }

            // update parent
            $predicate = new Predicate();
            $this->tableGateway->update(array(
                $this->right => new Expression($this->right . ' + 2'),
            ), array(
                $predicate->greaterThanOrEqualTo($this->right, $parentRightKey),
                $predicate->lessThan($this->left, $parentRightKey)
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
     * Get parent node
     *
     * @param integer $leftKey
     * @param integer $rightKey
     * @param integer $level
     * @param object $closure
     * @return object
     */
    public function getParentNode($leftKey, $rightKey, $level, Closure $closure = null)
    {
        $resultSet = $this->tableGateway->select(function (Select $select) use ($leftKey, $rightKey, $level, $closure) {
            $predicate = new Predicate();
            $select->where(array(
                $predicate->lessThanOrEqualTo($this->left, $leftKey),
                $predicate->greaterThanOrEqualTo($this->right, $rightKey),
                $this->level => $level - 1
            ))->order($this->left);

            if ($closure) {
                $closure($select);
            }
        });

        return $resultSet->current();
    }

    /*
                $select->where->lessThanOrEqualTo($this->left, $leftKey);
            $select->where->greaterThanOrEqualTo($this->right, $rightKey);
            $select->order($this->left);
    */
    
    /**
     * Get children nodes
     *
     * @param integer $leftKey
     * @param integer $rightKey
     * @param object $closure
     * @return object
     */
    public function getChildrenNodes($leftKey, $rightKey, Closure $closure = null)
    {
        $resultSet = $this->tableGateway->select(function (Select $select) use ($leftKey, $rightKey, $closure) {
            $select->where->greaterThanOrEqualTo($this->left, $leftKey);
            $select->where->lessThanOrEqualTo($this->right, $rightKey);
            $select->order($this->left);

            if ($closure) {
                $closure($select);
            }
        });

        return $resultSet;
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