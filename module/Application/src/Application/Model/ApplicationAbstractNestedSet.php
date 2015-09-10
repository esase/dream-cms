<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.dream-cms.kg/en/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Dream CMS software.
 * The Initial Developer of the Original Code is Dream CMS (http://www.dream-cms.kg).
 * All portions of the code written by Dream CMS are Copyright (c) 2014. All Rights Reserved.
 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2014 Dream CMS. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Dream CMS software
 * Attribution URL: http://www.dream-cms.kg/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */
namespace Application\Model;

use Application\Exception\ApplicationException;
use Application\Utility\ApplicationErrorLogger;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Predicate\Predicate as Predicate;
use Zend\Db\Sql\Expression as Expression;
use Exception;
use Closure;

abstract class ApplicationAbstractNestedSet 
{
    /**
     * Root right key
     */
    const ROOT_RIGHT_KEY = 1;

    /**
     * Root left key
     */
    const ROOT_LEFT_KEY = 0;

    /**
     * Root level
     */
    const ROOT_LEVEl = 0;

    /**
     * Node id
     *
     * @var string
     */
    protected $nodeId = 'id';

    /**
     * Parent
     *
     * @var string
     */
    protected $parent = 'parent_id';

    /**
     * Left key
     *
     * @var string
     */
    protected $left = 'left_key';

    /**
     * Right key
     *
     * @var string
     */
    protected $right = 'right_key';

    /**
     * Level
     *
     * @var string
     */
    protected $level = 'level';

    /**
     * Table gateway
     *
     * @var \Zend\Db\TableGateway\TableGateway
     */
    public $tableGateway;

    public function __construct(TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    /**
     * Get left key
     *
     * @return string
     */
    public function getLeftKey()
    {
        return $this->left;
    }
    
    /**
     * Get right key
     *
     * @return string
     */
    public function getRightKey()
    {
        return $this->right;
    }

    /**
     * Update node
     *
     * @param integer $id
     * @param array $data
     * @param boolean $useTransaction
     * @return boolean|string
     */
    public function updateNode($id, array $data, $useTransaction = true)
    {
        try {
            if ($useTransaction) {
                $this->tableGateway->getAdapter()->getDriver()->getConnection()->beginTransaction();
            }

            $this->tableGateway->update($data, [$this->nodeId => $id]);

            if ($useTransaction) {
                $this->tableGateway->getAdapter()->getDriver()->getConnection()->commit();
            }
        }
        catch (Exception $e) {
            if ($useTransaction) {
                $this->tableGateway->getAdapter()->getDriver()->getConnection()->rollback();
            }

            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        return true;
    }

    /**
     * Get all nodes
     *
     * @param array $filter
     * @param Closure $closure
     * @return \Zend\Db\ResultSet\ResultSet
     */
    public function getAllNodes(array $filter = [], Closure $closure = null)
    {
        $resultSet = $this->tableGateway->select(function (Select $select) use ($filter, $closure) {
            $select->order($this->left);

            if ($filter) {
                $select->where($filter);
            }

            if ($closure) {
                $closure($select);
            }
        });

        return $resultSet;
    }

    /**
     * Delete node
     *
     * @param integer $leftKey
     * @param integer $rightKey
     * @param array $filter
     * @param boolean $useTransaction
     * @return boolean|string
     */
    public function deleteNode($leftKey, $rightKey, array $filter = [], $useTransaction = true)
    {
        try {
            if ($useTransaction) {
                $this->tableGateway->getAdapter()->getDriver()->getConnection()->beginTransaction();
            }

            $predicate = new Predicate();
            $this->tableGateway->delete([
                $predicate->greaterThanOrEqualTo($this->left, $leftKey),
                $predicate->lessThanOrEqualTo($this->right, $rightKey)
            ] + $filter);

            $predicate = new Predicate();
            $this->tableGateway->update([
                $this->left => new Expression('IF(' . $this->left . ' > ?, ' . $this->left . ' - (? - ? + 1), ' . $this->left . ')', [
                    $leftKey,
                    $rightKey,
                    $leftKey
                ]),
                $this->right => new Expression($this->right . ' - (? - ? + 1)', [
                   $rightKey,
                   $leftKey
                ]),
            ],
            [
                $predicate->greaterThan($this->right, $rightKey),
            ] + $filter);

            if ($useTransaction) {
                $this->tableGateway->getAdapter()->getDriver()->getConnection()->commit();
            }
        }
        catch (Exception $e) {
            if ($useTransaction) {
                $this->tableGateway->getAdapter()->getDriver()->getConnection()->rollback();
            }

            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        return true;
    }

    /**
     * Is node movable
     *
     * @param integer $leftKey
     * @param inetger $rightKey
     * @param integer $level
     * @param integer $parentLeft
     * @return boolean
     */
    public function isNodeMovable($leftKey, $rightKey, $level, $parentLeft)
    {
        return $parentLeft >= $leftKey
                && $parentLeft <= $rightKey || ($level - 1 == self::ROOT_LEVEl) ? false : true; 
    }

    /**
     * Move node to the end
     *
     * @param array $options
     *      integer id required
     *      integer left_key required
     *      integer right_key required
     *      integer level required
     *      integer parent_id required
     *      integer parent_left_key required
     *      integer parent_right_key required
     *      integer parent_level required
     * @param array $filter
     * @param boolean $useTransaction
     * @return boolean|string
     */
    public function moveNodeToEnd($options, array $filter = [], $useTransaction = true)
    {
        return $this->moveNode($options, $filter, $useTransaction);
    }

    /**
     * Move node to start
     *
     * @param array $options
     *      integer id required
     *      integer left_key required
     *      integer right_key required
     *      integer level required
     *      integer parent_id required
     *      integer parent_left_key required
     *      integer parent_right_key required
     *      integer parent_level required
     * @param array $filter
     * @param boolean $useTransaction
     * @return boolean|string
     */
    public function moveNodeToStart($options, array $filter = [], $useTransaction = true)
    {
        $options['near_key'] = $options['parent_left_key'];

        return $this->moveNode($options, $filter, $useTransaction);
    }

    /**
     * Move node after
     *
     * @param array $options
     *      integer id required
     *      integer left_key required
     *      integer right_key required
     *      integer level required
     *      integer parent_id required
     *      integer parent_left_key required
     *      integer parent_right_key required
     *      integer parent_level required
     *      integer after_right_key required
     * @param array $filter
     * @param boolean $useTransaction
     * @return boolean|string
     */
    public function moveNodeAfter($options, array $filter = [], $useTransaction = true)
    {
        $options['near_key'] = $options['after_right_key'];

        return $this->moveNode($options, $filter, $useTransaction);
    }

    /**
     * Move node before
     *
     * @param array $options
     *      integer id required
     *      integer left_key required
     *      integer right_key required
     *      integer level required
     *      integer parent_id required
     *      integer parent_left_key required
     *      integer parent_right_key required
     *      integer parent_level required
     *      integer before_left_key required
     * @param array $filter
     * @param boolean $useTransaction
     * @return boolean|string
     */
    public function moveNodeBefore(array $options, array $filter = [], $useTransaction = true)
    {
        $prevNode = $this->getPrevNode($options['parent_level'], $options['before_left_key'], $filter);
        $options['near_key'] = false !== $prevNode
            ? $prevNode[$this->right]
            : $options['parent_left_key'];

        return $this->moveNode($options, $filter, $useTransaction);
    }

    /**
     * Move node
     *
     * @param array $options
     *      integer id required
     *      integer left_key required
     *      integer right_key required
     *      integer level required
     *      integer parent_id required
     *      integer parent_left_key required
     *      integer parent_right_key required
     *      integer parent_level required
     *      integer near_key optional
     * @param array $filter
     * @param boolean $useTransaction
     * @return boolean|string
     * @throws \Application\Exception\ApplicationException
     */
    protected function moveNode(array $options, array $filter = [], $useTransaction = true)
    {
        try {
            if ($useTransaction) {
                $this->tableGateway->getAdapter()->getDriver()->getConnection()->beginTransaction();
            }

            if (false === $this->isNodeMovable($options['left_key'],
                    $options['right_key'], $options['level'], $options['parent_left_key'])) {

                throw new ApplicationException('Node is not movable');            
            }

            $nearKey   = empty($options['near_key'])
                ? $options['parent_right_key'] - 1
                : $options['near_key'];
    
            $skewTree  = $options['right_key'] - $options['left_key'] + 1;
            $skewLevel = $options['parent_level'] - $options['level'] + 1;
    
            if ($nearKey < $options['right_key']) {
                $skewEdit  = $nearKey - $options['left_key'] + 1;
    
                $this->tableGateway->update([
                    $this->right => new Expression('IF(' . $this->left . ' >= ?, ' . $this->right . ' + ?, IF(' . $this->right . ' < ?, ' . $this->right . ' + ?, ' . $this->right . '))', [
                       $options['left_key'],
                       $skewEdit,
                       $options['left_key'],
                       $skewTree
                    ]),
                    $this->level => new Expression('IF(' . $this->left . ' >= ?, ' . $this->level . ' + ?, ' . $this->level . ')', [
                        $options['left_key'],
                        $skewLevel,
                    ]),
                    $this->left => new Expression('IF(' . $this->left . ' >= ?, ' . $this->left . ' + ?, IF(' . $this->left . ' > ?, ' . $this->left . ' + ?, ' . $this->left . '))', [
                        $options['left_key'],
                        $skewEdit,
                        $nearKey,
                        $skewTree
                    ])
                ], [
                    (new Predicate)->greaterThan($this->right, $nearKey),
                    (new Predicate)->lessThan($this->left, $options['right_key'])
                ] + $filter);
            }
            else {
                $skewEdit = $nearKey - $options['left_key'] + 1 - $skewTree;
    
                $this->tableGateway->update([
                    $this->left => new Expression('IF(' . $this->right . ' <= ?, ' . $this->left . ' + ?, IF(' . $this->left . ' > ?, ' . $this->left . ' - ?, ' . $this->left . '))', [
                       $options['right_key'],
                       $skewEdit,
                       $options['right_key'],
                       $skewTree
                    ]),
                    $this->level => new Expression('IF(' . $this->right . ' <= ?, ' . $this->level . ' + ?, ' . $this->level . ')', [
                        $options['right_key'],
                        $skewLevel,
                    ]),
                    $this->right => new Expression('IF(' . $this->right . ' <= ?, ' . $this->right . ' + ?, IF(' . $this->right . ' <= ?, ' . $this->right . ' - ?, ' . $this->right . '))', [
                        $options['right_key'],
                        $skewEdit,
                        $nearKey,
                        $skewTree
                    ])
                ], [
                    (new Predicate)->greaterThan($this->right, $options['left_key']),
                    (new Predicate)->lessThanOrEqualTo($this->left, $nearKey)
                ] + $filter);
            }

            // update parent info
            $this->tableGateway->update([
                $this->parent => $options['parent_id']
            ], [
                $this->nodeId => $options['id']
            ]);

            if ($useTransaction) {
                $this->tableGateway->getAdapter()->getDriver()->getConnection()->commit();
            }
        }
        catch (Exception $e) {
            if ($useTransaction) {
                $this->tableGateway->getAdapter()->getDriver()->getConnection()->rollback();
            }

            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        return true;
    }

    /**
     * Insert node
     *
     * @param integer $level
     * @param integer $nearKey
     * @param array $data
     * @param array $filter
     * @param boolean $useTransaction
     * @return integer|string
     */
    protected function insertNode($level, $nearKey, array $data, array $filter = [], $useTransaction = true)
    {
        try {
            if ($useTransaction) {
                $this->tableGateway->getAdapter()->getDriver()->getConnection()->beginTransaction();
            }

            $this->tableGateway->update([
                $this->left => new Expression('IF (' . $this->left . ' > ?, ' . $this->left . ' + 2, ' . $this->left . ')', [$nearKey]),
                $this->right => new Expression('IF (' . $this->right . '  > ?, ' . $this->right . ' + 2, ' . $this->right . ')', [$nearKey]),
            ], [
                (new Predicate)->greaterThanOrEqualTo($this->right, $nearKey),
            ] + $filter);

            $leftKey  = $nearKey + 1;
            $rightKey = $nearKey + 2;
            $level    = $level + 1;

            // insert a new node
            $this->tableGateway->insert([
                $this->left => $leftKey,
                $this->right => $rightKey,
                $this->level => $level
            ] + $data);

            $insertId = $this->tableGateway->getLastInsertValue();

            // update parent info
            if (false !== ($parent = $this->getParentNode($leftKey, $rightKey, $level, $filter))) {
                $this->tableGateway->update([
                    $this->parent => $parent[$this->nodeId]
                ], [
                    $this->nodeId => $insertId
                ]);
            }

            if ($useTransaction) {
                $this->tableGateway->getAdapter()->getDriver()->getConnection()->commit();
            }
        }
        catch (Exception $e) {
            if ($useTransaction) {
                $this->tableGateway->getAdapter()->getDriver()->getConnection()->rollback();
            }

            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        return $insertId;
    }

    /**
     * Insert node to end
     *
     * @param integer $parentLevel
     * @param integer $parentRightKey
     * @param array $data
     * @param array $filter
     * @param boolean $useTransaction
     * @return integer|string
     */
    public function insertNodeToEnd($parentLevel, $parentRightKey, array $data, array $filter = [], $useTransaction = true)
    {
        $parentRightKey = $parentRightKey - 1;

        return $this->insertNode($parentLevel, $parentRightKey, $data, $filter, $useTransaction);
    }

    /**
     * Insert node to start
     *
     * @param integer $parentLevel
     * @param integer $parentLeftKey
     * @param array $data
     * @param array $filter
     * @param boolean $useTransaction
     * @return integer|string
     */
    public function insertNodeToStart($parentLevel, $parentLeftKey, array $data, array $filter = [], $useTransaction = true)
    {
        return $this->insertNode($parentLevel, $parentLeftKey, $data, $filter, $useTransaction);
    }

    /**
     * Insert node after
     *
     * @param integer $parentLevel
     * @param integer $afterRightKey
     * @param array $data
     * @param array $filter
     * @param boolean $useTransaction
     * @return integer|string
     */
    public function insertNodeAfter($parentLevel, $afterRightKey, array $data, array $filter = [], $useTransaction = true)
    {
        return $this->insertNode($parentLevel, $afterRightKey, $data, $filter, $useTransaction);
    }

    /**
     * Insert node before
     *
     * @param integer $parentLevel
     * @param integer $parentLeftKey
     * @param integer $beforeLeftKey
     * @param array $data
     * @param array $filter
     * @param boolean $useTransaction
     * @return integer|string
     */
    public function insertNodeBefore($parentLevel, $parentLeftKey, $beforeLeftKey, array $data, array $filter = [], $useTransaction = true)
    {
        $prevNode = $this->getPrevNode($parentLevel, $beforeLeftKey, $filter);
        $nearKey = false !== $prevNode
            ? $prevNode[$this->right]
            : $parentLeftKey;

        return $this->insertNode($parentLevel, $nearKey, $data, $filter, $useTransaction);
    }

    /**
     * Get parent node
     *
     * @param integer $leftKey
     * @param integer $rightKey
     * @param integer $level
     * @param array $filter
     * @param Closure $closure
     * @return array|boolean
     */
    public function getParentNode($leftKey, $rightKey, $level, array $filter = [], Closure $closure = null)
    {        
        $resultSet = $this->tableGateway->select(function (Select $select) use ($leftKey, $rightKey, $level, $filter, $closure) {
            $select->where->lessThanOrEqualTo($this->left, $leftKey);
            $select->where->greaterThanOrEqualTo($this->right, $rightKey);
            $select->where->equalTo($this->level, $level - 1);
            $select->order($this->left);            
            $select->limit(1);

            if ($filter) {
                $select->where($filter);
            }

            if ($closure) {
                $closure($select);
            }
        });

        return $resultSet->count() ? $resultSet->current() : false;
    }

    /**
     * Get prev node
     *
     * @param integer $level
     * @param integer $leftKey
     * @param array $filter
     * @param Closure $closure
     * @return array|boolean
     */
    public function getPrevNode($level, $leftKey, array $filter = [], Closure $closure = null)
    {
        $resultSet = $this->tableGateway->select(function (Select $select) use ($level, $leftKey, $filter, $closure) {
            $select->where->lessThan($this->left, $leftKey);
            $select->where->equalTo($this->level, $level + 1);
            $select->order($this->left . ' desc');
            $select->limit(1);

            if ($filter) {
                $select->where($filter);
            }

            if ($closure) {
                $closure($select);
            }
        });

        return $resultSet->count() ? $resultSet->current() : false;
    }

    /**
     * Get children nodes
     *
     * @param integer $parentId
     * @param array $filter
     * @param Closure $closure
     * @return boolean|array
     */
    public function getChildrenNodes($parentId, array $filter = [], Closure $closure = null)
    {
        $resultSet = $this->tableGateway->select(function (Select $select) use ($parentId, $filter, $closure) {
            $select->where([
                $this->parent => $parentId
            ]);

            $select->order($this->left);

            if ($filter) {
                $select->where($filter);
            }

            if ($closure) {
                $closure($select);
            }
        });

        return $resultSet->count() ? $resultSet->toArray() : false;
    }

    /**
     * Update siblings nodes
     *
     * @param array $data
     * @param integer $leftKey
     * @param integer $rightKey
     * @param integer $level
     * @param array $filter
     * @param boolean $useTransaction
     * @return boolean|string
     */
    public function updateSiblingsNodes(array $data, $leftKey, $rightKey, $level = null, array $filter = [], $useTransaction = true)
    {
        try {
            if ($useTransaction) {
                $this->tableGateway->getAdapter()->getDriver()->getConnection()->beginTransaction();
            }

            $baseFilter = array_merge([
                (new Predicate)->greaterThan($this->left, $leftKey),
                (new Predicate)->lessThan($this->right, $rightKey)
            ], $filter);

            if ($level) {
                $baseFilter[$this->level] = $level;
            }

            $this->tableGateway->update($data, $baseFilter);

            if ($useTransaction) {
                $this->tableGateway->getAdapter()->getDriver()->getConnection()->commit();
            }
        }
        catch (Exception $e) {
            if ($useTransaction) {
                $this->tableGateway->getAdapter()->getDriver()->getConnection()->rollback();
            }

            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        return true;
    }

    /**
     * Get siblings nodes
     *
     * @param integer $leftKey
     * @param integer $rightKey
     * @param integer $level
     * @param array $filter
     * @param Closure $closure
     * @param integer $limit
     * @return boolean|array
     */
    public function getSiblingsNodes($leftKey, $rightKey, $level = null, array $filter = [], Closure $closure = null, $limit = null)
    {
        $resultSet = $this->tableGateway->select(function (Select $select) use ($leftKey, $rightKey, $level, $filter, $closure, $limit) {
            $select->where->greaterThan($this->left, $leftKey);
            $select->where->lessThan($this->right, $rightKey);
            $select->order($this->left);

            if ($level) {
                $select->where($this->level, $level);
            }

            if ($filter) {
                $select->where($filter);
            }

            if ($closure) {
                $closure($select);
            }

            if ($limit) {
                $select->limit($limit);
            }
        });

        return $resultSet->count() ? $resultSet->toArray() : false;
    }

    /**
     * Get parent nodes
     *
     * @param integer $leftKey
     * @param integer $rightKey
     * @param array $filter
     * @param Closure $closure
     * @return boolean|array
     */
    public function getParentNodes($leftKey, $rightKey, array $filter = [], Closure $closure = null)
    {
        $resultSet = $this->tableGateway->select(function (Select $select) use ($leftKey, $rightKey, $filter, $closure) {
            $select->where->lessThanOrEqualTo($this->tableGateway->table . '.' . $this->left, $leftKey);
            $select->where->greaterThanOrEqualTo($this->tableGateway->table . '.' . $this->right, $rightKey);
            $select->order($this->tableGateway->table . '.' . $this->left);

            if ($filter) {
                $select->where($filter);
            }

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
     * @param Closure $closure
     * @return array|boolean
     */
    public function getNodeInfo($id, $field = null, Closure $closure = null)
    {
        $resultSet = $this->tableGateway->select(function (Select $select) use ($id, $field, $closure) {
            $select->where([
                $this->tableGateway->table . '.' . ($field ? $field : $this->nodeId) => $id
            ])->limit(1);

            if ($closure) {
                $closure($select);
            }
        });

        return $resultSet->count() ? (array) $resultSet->current() : false;
    }

    /**
     * Get last node
     *
     * @param array $filter
     * @param integer $parentLeftKey
     * @param integer $parentRightKey     
     * @param Closure $closure
     * @return integer|boolean
     */
    public function getLastNode(array $filter = [], $parentLeftKey = null, $parentRightKey = null, Closure $closure = null)
    {
        $resultSet = $this->tableGateway->select(function (Select $select) use ($filter, $parentLeftKey, $parentRightKey, $closure) {
            $select->columns([
                'max' => new Expression('MAX(' . $this->right . ')')
            ]);

            if ($parentLeftKey) {
                $select->where->greaterThan($this->left, $parentLeftKey);
            }

            if ($parentRightKey) {
                $select->where->lessThan($this->right, $parentRightKey);
            }

            if ($filter) {
                $select->where($filter);
            }

            if ($closure) {
                $closure($select);
            }
        });

        return !empty($resultSet->current()['max']) ? $resultSet->current()['max'] : false;
    }    
}