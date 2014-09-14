<?php
namespace Page\Model;

use Acl\Model\AclBase as AclBaseModel;
use Application\Model\ApplicationAbstractNestedSet;
use Application\Model\ApplicationAbstractBase;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression as Expression;

class Page extends ApplicationAbstractNestedSet 
{
    /**
     * Page status active 
     */
    const PAGE_STATUS_ACTIVE = 1;

    /**
     * Page in menu 
     */
    const PAGE_IN_MENU = 1;

    /**
     * Page in sitemap 
     */
    const PAGE_IN_SITEMAP = 1;

    /**
     * Page in footer menu 
     */
    const PAGE_IN_FOOTER_MENU = 1;

    /**
     * Page in user menu 
     */
    const PAGE_IN_USER_MENU = 1;

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
     * @param boolean $excludeHome
     * @return array|false
     */
    public function getPageParents($leftKey, $rightKey, $userRole, $language, $excludeHome = true)
    {
        // TODO: do we need cache here?
        return $this->getParentNodes($leftKey, 
                $rightKey, function (Select $select) use ($userRole, $language, $excludeHome) {

            $select->columns(['slug', 'title', 'type', 'level', 'redirect_url']);
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
            new Expression('b.id = module and b.status = ?', [ApplicationAbstractBase::MODULE_STATUS_ACTIVE]), 
            []
        );

        // add a layout information
        if ($addLayout) {
            $select->join(
                ['c' => 'page_layout'],
                'c.id = ' . $this->tableGateway->table . '.layout', 
                [
                    'layout' => 'name'
                ]
            );
        }

        // administrators can see any pages
        if ($userRole != AclBaseModel::DEFAULT_ROLE_ADMIN) {
            $select->join(
                ['d' => 'page_visibility'],
                new Expression('d.page_id = ' . 
                        $this->tableGateway->table . '.id and d.hidden = ?', [$userRole]), 
                [],
                'left'
            );
        }

        $select->join(
            ['i' => 'page_system'],
            'i.id = ' . $this->tableGateway->table . '.system_page', 
            [
                'privacy',
                'system_title' => 'title'
            ],
            'left'
        );

        $select->where([
            'language' => $language,
            'active' => self::PAGE_STATUS_ACTIVE
        ]);

        if ($userRole != AclBaseModel::DEFAULT_ROLE_ADMIN) {
            $select->where->IsNull('d.id');
        }
        
        return $select;
    }
}