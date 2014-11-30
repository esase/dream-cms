<?php
namespace User\PageProvider;

use Application\Service\ApplicationServiceLocator as ServiceLocatorService;
use Page\PageProvider\PageAbstractPageProvider;

class UserPageProvider extends PageAbstractPageProvider
{
    /**
     * Model instance
     * @var object  
     */
    protected $model;

    /**
     * Pages
     * @var array
     */
    protected static $pages = null;

    /**
     * Get model
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = ServiceLocatorService::getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('User\Model\UserBase');
        }

        return $this->model;
    }

    /**
     * Get pages
     *
     * @param string $language
     * @return array
     *      string url_title
     *      array url_params
     *      array xml_map
     *          string lastmod
     *          string changefreq
     *          string priority
     *     array children
     */
    public function getPages($language)
    {
        if (null === self::$pages) {
            self::$pages = [];
            $users = $this->getModel()->getAllActiveUsers();

            if (count($users)) {
                foreach ($users as $user) {
                    self::$pages[] = [
                        'url_title' => $user['nick_name'],
                        'url_params' => [
                            'slug' => $user['slug']
                        ],
                        'xml_map' => [
                            'lastmod' => $user['date_edited'],
                            'changefreq' => null,
                            'priority' => null
                        ],
                        'children' => [
                        ]
                    ];
                }
            }
        }

        return self::$pages;
    }
}