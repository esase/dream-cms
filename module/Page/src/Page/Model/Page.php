<?php
namespace Page\Model;

use Acl\Model\Base as AclBaseModel;
use Application\Model\AbstractNestedSet;
use Application\Model\AbstractBase;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression as Expression;

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

    /**
     * Default page layout flag
     */
    const DEFAULT_PAGE_LAYOUT = 1;

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
     * @param boolean $excludeHome
     * @return array|false
     */
    public function getPageParents($leftKey, $rightKey, $userRole, $language, $excludeHome = true)
    {
        // TODO: do we need cache here?
        return $this->getParentNodes($leftKey, 
                $rightKey, function (Select $select) use ($userRole, $language, $excludeHome) {

            $select->columns(['slug', 'title', 'type', 'check', 'level']);
            $select = $this->getPageFilter($select, $userRole, $language, false);

            if ($excludeHome) {
                $select->where->notEqualTo('level', 1);
            }
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
            ['b' => 'application_module'],
            new Expression('b.id = module and b.status = ?', [AbstractBase::MODULE_STATUS_ACTIVE]), 
            []
        );

        // add a layout information
        if ($addLayout) {
            $select->join(
                ['c' => 'page_layout'],
                new Expression('c.id = ' . $this->tableGateway->table . '.layout'), 
                [
                    'layout' => 'path'
                ],
                'left'
            );

            $select->join(
                ['d' => 'page_layout'],
                new Expression('d.default = ?', [self::DEFAULT_PAGE_LAYOUT]), 
                [
                    'default_layout' => 'path'
                ]
            );
        }

        // administrators can see any pages
        if ($userRole != AclBaseModel::DEFAULT_ROLE_ADMIN) {
            $select->join(
                ['e' => 'page_visibility'],
                new Expression('e.page_id = ' . 
                        $this->tableGateway->table . '.id and e.hidden = ?', [$userRole]), 
                [],
                'left'
            );
        }

        $select->where([
            'language' => $language,
            'active' => self::PAGE_STATUS_ACTIVE
        ]);

        if ($userRole != AclBaseModel::DEFAULT_ROLE_ADMIN) {
            $select->where->IsNull('e.id');
        }

        return $select;
    }
}