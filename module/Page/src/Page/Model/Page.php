<?php

namespace Page\Model;

use Application\Model\AbstractNestedSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression as Expression;
use Application\Model\AbstractBase;
use Application\Model\Acl as AclBaseModel;

class Page extends AbstractNestedSet 
{
    /**
     * Page status active 
     */
    const PAGE_STATUS_ACTIVE = 1;

    /**
     * Page type system
     */
    const PAGE_TYPE_SYSTEM = 'system';

    /**
     * Page type custom
     */
    const PAGE_TYPE_CUSTOM = 'custom';

    public function __construct(TableGateway $tableGateway)
    {
        parent::__construct($tableGateway);
    }

    /**
     * Get page parents
     *
     * @param integer $leftKey
     * @param integer $rightKey
     * @param integer $userRole
     * @param string $language
     * @return array|false
     */
    public function getPageParents($leftKey, $rightKey, $userRole, $language)
    {
        // TODO: do we need cache here?
        return $this->getParentNodes($leftKey, $rightKey, function (Select $select) use ($userRole, $language) {
            $select = $this->getPageFilter($select, $userRole, $language, false);
        });
    }

    /**
     * Get page info
     *
     * @param string $slug
     * @param integer $userRole
     * @param string $language
     * @return array|false
     */
    public function getPageInfo($slug, $userRole, $language)
    {
        return $this->getNodeInfo($slug, 'slug', function (Select $select) use ($userRole, $language) {
            $select = $this->getPageFilter($select, $userRole, $language);
        });
    }

    /**
     * Get page filter
     *
     * @param object $select
     * @param integer $userRole
     * @param string $language
     * @param boolean $addLayout
     * @return object
     */
    protected function getPageFilter(Select $select, $userRole, $language, $addLayout = true)
    {
        $select->join(
            array('b' => 'application_module'),
            new Expression('b.id = module and b.status = ?', array(AbstractBase::MODULE_STATUS_ACTIVE)), 
            array()
        );

        // add a layout information
        if ($addLayout) {
            $select->join(
                array('c' => 'page_layout'),
                new Expression('c.id = ' . $this->tableGateway->table . '.layout'), 
                array(
                    'layout' => 'name'
                ),
                'left'
            );
        }

        // administrators can see any pages
        if ($userRole != AclBaseModel::DEFAULT_ROLE_ADMIN) {
            $select->join(
                array('d' => 'page_permission'),
                new Expression('d.page_id = ' . 
                        $this->tableGateway->table . '.id and d.disallowed_role = ?', array($userRole)), 
                array(),
                'left'
            );
        }

        $select->where(array(
            'language' => $language,
            'active' => self::PAGE_STATUS_ACTIVE
        ));

        if ($userRole != AclBaseModel::DEFAULT_ROLE_ADMIN) {
            $select->where->IsNull('d.id');
        }

        return $select;
    }
}