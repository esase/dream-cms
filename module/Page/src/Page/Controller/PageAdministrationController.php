<?php
namespace Page\Controller;

use Page\Model\PageNestedSet;
use Localization\Service\Localization as LocalizationService;
use Application\Controller\ApplicationAbstractAdministrationController;
use Zend\View\Model\ViewModel;

class PageAdministrationController extends ApplicationAbstractAdministrationController
{
    /**
     * Model instance
     * @var object  
     */
    protected $model;

    /**
     * Get model
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = $this->getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('Page\Model\PageAdministration');
        }

        return $this->model;
    }

    /**
     * Settings
     */
    public function settingsAction()
    {
        return new ViewModel([
            'settingsForm' => parent::settingsForm('page', 'pages-administration', 'settings')
        ]);
    }

    /**
     * Default action
     */
    public function indexAction()
    {
        // redirect to list action
        return $this->redirectTo('pages-administration', 'list');
    }

    /**
     * View dependent pages
     */
    public function ajaxViewDependentPagesAction()
    {
        // check the permission and increase permission's actions track
        if (true !== ($result = $this->aclCheckPermission())) {
            return $result;
        }

        // get a selected page id
        $pageId = $this->params()->fromQuery('page_id', -1);
        $checkInStructure = (int) $this->params()->fromQuery('check_structure', 1);

        return new ViewModel([
            'data' => $this->getModel()->getDependentPages($pageId, $checkInStructure > 0)
        ]);
    }

    /**
     * View dependent widgets
     */
    public function ajaxViewDependentWidgetsAction()
    {
        // check the permission and increase permission's actions track
        if (true !== ($result = $this->aclCheckPermission())) {
            return $result;
        }

        $request = $this->getRequest();

        // get a widget info
        if (null == ($connectionInfo =
                $this->getModel()->getWidgetConnectionInfo($this->getSlug()))) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }

        return new ViewModel([
            'data' => $this->getModel()->
                    getDependentWidgets($connectionInfo['widget_id'], $connectionInfo['page_id'])
        ]);
    }

    /**
     * Add selected system pages
     */
    public function addSystemPagesAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            if (null !== ($pagesIds = $request->getPost('pages', null))) {
                // get pages map
                if (null != ($systemPagesMap = $this->getModel()->getSystemPagesMap($pagesIds))) {
                    $addedPagesCount = 0;

                    // get the page info
                    $parentPage = $this->getModel()->
                            getStructurePageInfo($this->params()->fromQuery('page_id', null), true, true);

                    // try to find the home page in system pages map
                    if (!$parentPage) {
                        $homePageName = $this->getServiceLocator()->get('Config')['home_page'];
                        $homePage = [];

                        foreach ($systemPagesMap as $index => $page) {
                            if ($page['slug'] == $homePageName) {
                                $homePage = $page;
                                unset($systemPagesMap[$index]);
                                break;
                            }
                        }

                        if (!$homePage) {
                            $this->flashMessenger()
                                ->setNamespace('error')
                                ->addMessage($this->getTranslator()->translate('Home page is not defined'));

                            return $this->redirectTo('pages-administration', 'system-pages', [], true);
                        }

                        // check the permission and increase permission's actions track
                        if (true !== ($result = $this->aclCheckPermission(null, true, false))) {
                            $this->flashMessenger()
                                ->setNamespace('error')
                                ->addMessage($this->getTranslator()->translate('Access Denied'));

                            return $this->redirectTo('pages-administration', 'system-pages', [], true);
                        }

                        // add the home page
                        $homePageId = $this->getModel()->addPage(PageNestedSet::ROOT_LEVEl,
                                PageNestedSet::ROOT_LEFT_KEY, PageNestedSet::ROOT_RIGHT_KEY, true, $homePage);

                        if (!is_numeric($homePageId)) {
                            $this->flashMessenger()
                                ->setNamespace('error')
                                ->addMessage($this->getTranslator()->translate($homePageId));

                            return $this->redirectTo('pages-administration', 'system-pages', [], true);
                        }

                        $addedPagesCount++;

                        // add sub pages
                        foreach ($systemPagesMap as $page) {
                            // check the permission and increase permission's actions track
                            if (true !== ($result = $this->aclCheckPermission(null, true, false))) {
                                $this->flashMessenger()
                                    ->setNamespace('error')
                                    ->addMessage($this->getTranslator()->translate('Access Denied'));

                                return $this->redirectTo('pages-administration', 'system-pages', [], true);
                            }

                            // get created page info
                            $homePage = $this->getModel()->getStructurePageInfo($homePageId);

                            $result = $this->getModel()->
                                    addPage($homePage['level'], $homePage['left_key'], $homePage['right_key'], true, $page);

                            if (!is_numeric($result)) {
                                $this->flashMessenger()
                                    ->setNamespace('error')
                                    ->addMessage($this->getTranslator()->translate($result));
    
                                return $this->redirectTo('pages-administration', 'system-pages', [], true);
                            }

                            $addedPagesCount++;
                        }
                    }
                    else {
                        // add pages                        
                        foreach ($systemPagesMap as $page) {
                            if (!empty($parentPage['pages_provider'])) {
                                $this->flashMessenger()
                                    ->setNamespace('error')
                                    ->addMessage($this->getTranslator()->translate('You cannot move any pages inside dynamic pages'));

                                return $this->redirectTo('pages-administration', 'system-pages', [], true);
                            }

                            // check the permission and increase permission's actions track
                            if (true !== ($result = $this->aclCheckPermission(null, true, false))) {
                                $this->flashMessenger()
                                    ->setNamespace('error')
                                    ->addMessage($this->getTranslator()->translate('Access Denied'));

                                return $this->redirectTo('pages-administration', 'system-pages', [], true);
                            }

                            $result = $this->getModel()->
                                addPage($parentPage['level'], $parentPage['left_key'], $parentPage['right_key'], true, $page);

                            if (!is_numeric($result)) {
                                $this->flashMessenger()
                                    ->setNamespace('error')
                                    ->addMessage($this->getTranslator()->translate($result));
    
                                return $this->redirectTo('pages-administration', 'system-pages', [], true);
                            }

                            // get updated parent page's info
                            $parentPage = $this->getModel()->getStructurePageInfo($parentPage['id']);
                            $addedPagesCount++;
                        }
                    }

                    $message = $addedPagesCount > 1
                        ? 'Selected system pages have been added'
                        : 'The selected system page has been added';

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate($message));
                }
            }
        }

        // redirect back
        return $this->redirectTo('pages-administration', 'system-pages', [], true);
    }

    /**
     * Delete selected pages
     */
    public function deletePagesAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            if (null !== ($pagesIds = $request->getPost('pages', null))) {
                // delete selected pages
                $deleteResult = false;
                $deletedCount = 0;

                foreach ($pagesIds as $pageId) {
                    // get a page's info
                    $pageInfo = $this->getModel()->getStructurePageInfo($pageId);

                    // page contains subpages or contains dependent pages should not be deleted
                    if (null == $pageInfo || ($pageInfo['dependent_page']
                                || $pageInfo['right_key'] - $pageInfo['left_key'] != 1)) {

                        continue;
                    }

                    // check the permission and increase permission's actions track
                    if (true !== ($result = $this->aclCheckPermission(null, true, false))) {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()->translate('Access Denied'));

                        break;
                    }

                    // delete the page
                    if (true !== ($deleteResult = $this->getModel()->deletePage($pageInfo))) {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage(($deleteResult ? $this->getTranslator()->translate($deleteResult)
                                : $this->getTranslator()->translate('Error occurred')));

                        break;
                    }

                    $deletedCount++;
                }

                if (true === $deleteResult) {
                    $message = $deletedCount > 1
                        ? 'Selected pages have been deleted'
                        : 'The selected page has been deleted';

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate($message));
                }
            }
        }

        // redirect back
        return $request->isXmlHttpRequest()
            ? $this->getResponse()
            : $this->redirectTo('pages-administration', 'list', [], true);
    }

    /**
     * System pages
     */
    public function systemPagesAction()
    {
        // check the permission and increase permission's actions track
        if (true !== ($result = $this->aclCheckPermission())) {
            return $result;
        }

        // get a selected page id
        $pageId = $this->params()->fromQuery('page_id', null);

        // get the page info
        if (null != ($page = $this->
                getModel()->getStructurePageInfo($pageId, true, true))) {

            $pageId = $page['id'];
        }

        $filters = [];

        // get a filter form
        $filterForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Page\Form\PageSystemFilter');

        $filterForm->setModel($this->getModel());

        $request = $this->getRequest();
        $filterForm->getForm()->setData($request->getQuery(), false);

        // check the filter form validation
        if ($filterForm->getForm()->isValid()) {
            $filters = $filterForm->getForm()->getData();
        }

        // get data
        $paginator = $this->getModel()->getSystemPages($this->getPage(),
                $this->getPerPage(), $this->getOrderBy(), $this->getOrderType(), $filters);

        return new ViewModel([
            'pages_map' => $this->getModel()->getPagesMap($this->getModel()->getCurrentLanguage()),
            'filters' => $filters,
            'filter_form' => $filterForm->getForm(),
            'paginator' => $paginator,
            'order_by' => $this->getOrderBy(),
            'order_type' => $this->getOrderType(),
            'per_page' => $this->getPerPage(),
            'page_id' => $pageId
        ]);
    }

    /**
     * Edit page action
     */
    public function editPageAction()
    {
        // get the page info
        if (null == ($page = $this->
                getModel()->getStructurePageInfo($this->getSlug(), true, false, true))) {

            return $this->redirectTo('pages-administration', 'list');
        }

        // get the parent page info
        $parent = $this->getModel()->getStructurePageInfo($page['parent_id']);

        // get a new selected page id
        $newParentId = $this->params()->fromQuery('page_id', null);

        // get a new parent info
        if ($newParentId && $newParentId != $parent['id']) {
            if (null != ($newParentPage =
                    $this->getModel()->getStructurePageInfo($newParentId))) {

                $parent = $newParentPage;
            }
        }

        // get a page form
        $pageForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Page\Form\Page')
            ->setModel($this->getModel())
            ->setPageInfo($page)
            ->setSystemPage($page['system_page'])
            ->showMainMenu(!$page['disable_menu'])
            ->showSiteMap(!$page['disable_site_map'])
            ->showXmlMap(!$page['disable_xml_map'])
            ->showFooterMenu(!$page['disable_footer_menu'])
            ->showUserMenu(!$page['disable_user_menu'])
            ->showVisibilitySettings(!$page['forced_visibility'])
            ->showSeo(!$page['disable_seo']);

        if (!empty($page['system_title'])) {
            $pageForm->setPageSystemTitle($this->getTranslator()->translate($page['system_title']));
        }

        // fill the page parent info
        if ($parent) {
            $pageForm->setPageParent($parent);
        }

        // set default values
        $pageForm->getForm()->setData($page);
        $request  = $this->getRequest();

        // validate the form
        if ($request->isPost()) {
            // fill the form with received values
            $pageForm->getForm()->setData($request->getPost(), false);

            // save data
            if ($pageForm->getForm()->isValid()) {
                if (!empty($newParentPage['pages_provider'])) {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate('You cannot move any pages inside dynamic pages'));

                    return $this->redirectTo('pages-administration', 'edit-page', [
                        'slug' => $page['id']
                    ]);
                }

                // check the permission and increase permission's actions track
                if (true !== ($result = $this->aclCheckPermission())) {
                    return $result;
                }

                // edit a page
                if (true === ($result = $this->getModel()->editPage($page, $pageForm->
                        getForm()->getData(), $page['type'] == PageNestedSet::PAGE_TYPE_SYSTEM, ($parent ? $parent : [])))) {

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Page has been edited'));
                }
                else  {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate($result));
                }

                return $this->redirectTo('pages-administration', 'edit-page', [
                    'slug' => $page['id']
                ]);
            }
        }

        return new ViewModel([
            'page' => $page,
            'pageForm' => $pageForm->getForm(),
            'page_id' => !empty($parent) ? $parent['id'] : null,
            'tree_disabled' => $page['level'] - 1 == PageNestedSet::ROOT_LEVEl
        ]);
    }

    /**
     * Add a new custom page action
     */
    public function addCustomPageAction()
    {
        // get a selected page id
        $pageId = $this->params()->fromQuery('page_id', null);

        // get the page info
        if (null == ($page =
                $this->getModel()->getStructurePageInfo($pageId, true, true))) {

            return $this->redirectTo('pages-administration', 'list');
        }

        // get a page form
        $pageForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Page\Form\Page')
            ->setPageParent($page)
            ->setModel($this->getModel());

        $request  = $this->getRequest();

        // validate the form
        if ($request->isPost()) {
            // fill the form with received values
            $pageForm->getForm()->setData($request->getPost(), false);

            // save data
            if ($pageForm->getForm()->isValid()) {
                if (!empty($page['pages_provider'])) {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate('You cannot add any pages inside dynamic pages'));

                    return $this->redirectTo('pages-administration', 'add-custom-page', [], false, ['page_id' => $pageId]);
                }

                // check the permission and increase permission's actions track
                if (true !== ($result = $this->aclCheckPermission())) {
                    return $result;
                }

                // add a new custom page
                $result = $this->getModel()->addPage($page['level'],
                        $page['left_key'], $page['right_key'], false, $pageForm->getForm()->getData());

                if (is_numeric($result)) {
                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Custom page has been added'));

                    // redirect to the browse widgets page
                    if ($this->aclCheckPermission('pages_administration_browse_widgets', false, false)) {
                        return $this->redirectTo('pages-administration', 'browse-widgets', ['slug' => $result]);
                    }
                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate($result));
                }

                return $this->redirectTo('pages-administration', 'add-custom-page', [], false, ['page_id' => $pageId]);
            }
        }

        return new ViewModel([
            'pageForm' => $pageForm->getForm(),
            'page_id' => $page['id']
        ]);
    }

    /**
     * Change page layout
     */
    public function ajaxChangePageLayoutAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            // get page info
            if (false !== ($pageInfo = $this->getModel()->getStructurePageInfo($this->getSlug()))) {
                $layoutId = $this->params()->fromQuery('layout', -1);

                // get layout info
                if (null != ($layoutInfo = $this->getModel()->getPageLayout($layoutId))) {
                    // check the permission and increase permission's actions track
                    if (true !== ($result = $this->aclCheckPermission())) {
                        return $result;
                    }

                    // change the widget's position
                    $result = $this->getModel()->
                            changePageLayout($layoutId, $pageInfo['id'], $layoutInfo['default_position'], $pageInfo['layout']);

                    if (true !== $result) {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()->translate($result));

                        return $this->getResponse();
                    }

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Layout has been changed'));

                    return $this->getResponse();
                }
            }
        }

        $this->flashMessenger()
            ->setNamespace('error')
            ->addMessage($this->getTranslator()->translate('Error occurred'));

        return $this->getResponse();
    }

    /**
     * Change widget position
     */
    public function ajaxChangeWidgetPositionAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            $widgetConnectionId = $request->getPost('widget_connection', -1);
            $widgetOrder = (int) $request->getPost('widget_order', 0);
            $widgetPosition = $request->getPost('widget_position');

            // get widget connection info
            if (false !== ($connectionInfo = $this->
                    getModel()->getWidgetConnectionInfo($widgetConnectionId))) {

                // check received widget position
                if (false !== ($newWidgetPosition = $this->getModel()->
                        getWidgetPositionInfo($widgetPosition, $connectionInfo['page_layout']))) {

                    // check the permission and increase permission's actions track
                    if (true !== ($result = $this->aclCheckPermission())) {
                        return $result;
                    }

                    // change the widget's position
                    $result = $this->getModel()->
                            changePublicWidgetPosition($connectionInfo, $widgetOrder + 1, $newWidgetPosition['id']);

                    if (true !== $result) {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()->translate($result));                       
                    }

                    return $this->getResponse();
                }
            }
        }

        $this->flashMessenger()
            ->setNamespace('error')
            ->addMessage($this->getTranslator()->translate('Error occurred'));

        return $this->getResponse();
    }

    /**
     * Delete widget
     */
    public function ajaxDeleteWidgetAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            $widgetConnectionId = $this->getSlug();

            // get widget connection info
            if (false !== ($widget = $this->
                    getModel()->getWidgetConnectionInfo($widgetConnectionId))) {

                // check the widget depends
                if (!$widget['widget_depend_connection_id'] && !$widget['widget_page_depend_connection_id']) {
                    // check the permission and increase permission's actions track
                    if (true !== ($result = $this->aclCheckPermission())) {
                        return $result;
                    }

                    // delete the widget connection
                    if (true !== ($deleteResult = $this->getModel()->deleteWidgetConnection($widget))) {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()->translate($deleteResult));

                        return $this->getResponse();
                    }

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Widget has been deleted'));

                    return $this->getResponse();
                }
            }
        }

        $this->flashMessenger()
            ->setNamespace('error')
            ->addMessage($this->getTranslator()->translate('Error occurred'));

        return $this->getResponse();
    }

    /**
     * Add widget
     */
    public function ajaxAddWidgetAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            $pageId = $this->params()->fromQuery('page', -1);
            $widgetId = $this->params()->fromQuery('widget', -1);

            // get a page info
            if (false !== ($page = $this->getModel()->getStructurePageInfo($pageId))) {
                // get a public widget info
                if (false !== ($widget =
                        $this->getModel()->getPublicWidgetInfo($pageId, $widgetId, $page['system_page']))) {

                    // get list of dependent widgets
                    $widgets = $this->getModel()->getDependentPublicWidgets($pageId, $widgetId);

                    // add public widgets
                    foreach ($widgets as $widgetId) {
                        // check the permission and increase permission's actions track
                        if (true !== ($result = $this->aclCheckPermission(null, true, false))) {
                            $this->flashMessenger()
                                ->setNamespace('error')
                                ->addMessage($this->getTranslator()->translate('Access Denied'));

                            return $this->getResponse();
                        }

                        $result = $this->getModel()->addPublicWidget($pageId, $widgetId, $page['layout_default_position']);

                        if (!is_numeric($result)) {
                            $this->flashMessenger()
                                ->setNamespace('error')
                                ->addMessage($this->getTranslator()->translate($result));

                            return $this->getResponse();
                        }
                    }

                    $this->flashMessenger()
                            ->setNamespace('success')
                            ->addMessage($this->getTranslator()->translate('Widget has been added'));

                    return $this->getResponse();
                }
            }
        }

        $this->flashMessenger()
            ->setNamespace('error')
            ->addMessage($this->getTranslator()->translate('Error occurred'));

        return $this->getResponse();
    }

    /**
     * Browse widgets
     */
    public function browseWidgetsAction()
    {
        // check the permission and increase permission's actions track
        if (true !== ($result = $this->aclCheckPermission())) {
            return $result;
        }

        // get the page info
        if (null == ($page = $this->
                getModel()->getStructurePageInfo($this->getSlug(), true, false, true))) {

            return $this->redirectTo('pages-administration', 'list');
        }

        $filters = [];
 
        // get a filter form
        $filterForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Page\Form\PageWidgetFilter');

        $filterForm->setModel($this->getModel());

        $request = $this->getRequest();
        $filterForm->getForm()->setData($request->getQuery(), false);

        // check the filter form validation
        if ($filterForm->getForm()->isValid()) {
            $filters = $filterForm->getForm()->getData();
        }

        // get data
        $paginator = $this->getModel()->
                getPublicWidgets($page['id'], $page['system_page'], $this->getPage(), $this->getPerPage(), $filters);

        $viewModel = new ViewModel([
            'page' => $this->getPage(),
            'page_info' => $page,
            'paginator' => $paginator,
            'per_page' => $this->getPerPage(),
            'filter_form' => $filterForm->getForm(),
            'filters' => $filters,
            'layouts' => $this->getModel()->getPageLayouts(false),
            'manage_layout' => $this->getModel()->getManageLayoutPath(),
            'widgets_connections' => $this->
                    getModel()->getWidgetsConnections($this->getModel()->getCurrentLanguage())
        ]);

        if ($request->isXmlHttpRequest()) {
            $viewModel->setTemplate('page/administration-partial/browse-widgets-wrapper');
        }

        return $viewModel;
    }

    /**
     * Edit widget settings
     */
    public function editWidgetSettingsAction()
    {
        $request = $this->getRequest();

        // get a widget info
        if (null == ($widget =$this->getModel()->
                getWidgetConnectionInfo($this->getSlug(), true))) {

            return $this->redirectTo('pages-administration', 'list');
        }

        $currentlanguage = LocalizationService::getCurrentLocalization()['language'];

        // get settings model
        $settings = $this->getServiceLocator()
            ->get('Application\Model\ModelManager')
            ->getInstance('Page\Model\PageWidgetSetting');

        // get settings form
        $settingsForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Page\Form\PageWidgetSetting')
            ->showVisibilitySettings(!$widget['widget_forced_visibility'])
            ->setModel($settings)
            ->setWidgetDescription($this->getTranslator()->translate($widget['widget_description']));

        // get settings list
        $settingsList = $settings->getSettingsList($widget['id'], $widget['widget_id'], $currentlanguage);
        if (false !== $settingsList) {
            // add extra settings on the form
            $settingsForm->addFormElements($settingsList);
        }

        // set default values
        $settingsForm->getForm()->setData([
            'title' => $widget['widget_title'],
            'layout' => $widget['layout'],
            'visibility_settings' => !empty($widget['visibility_settings'])
                ? $widget['visibility_settings']
                : null
        ]);

        // validate the form
        if ($request->isPost()) {
            // fill the form with received values
            $settingsForm->getForm()->setData($request->getPost(), false);

            // save data
            if ($settingsForm->getForm()->isValid()) {
                // check the permission and increase permission's actions track
                if (true !== ($result = $this->aclCheckPermission())) {
                    return $result;
                }

                if (true === ($result = $this->getModel()->saveWidgetSettings($widget['widget_id'],
                        $widget['page_id'], $widget['id'], $settingsList, $settingsForm->getForm()->getData(), $currentlanguage))) {

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Settings have been saved'));
                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate($result));
                }

                // redirect back
                return $this->redirectTo('pages-administration', 'widget-settings', [], true);
            }
        }

        return new ViewModel([
            'settings_form' => $settingsForm->getForm(),
            'page_info' => $this->getModel()->getStructurePageInfo($widget['page_id']),
            'widget_info' => $widget
        ]);
    }

    /**
     * List of pages
     */
    public function listAction()
    {
        // check the permission and increase permission's actions track
        if (true !== ($result = $this->aclCheckPermission())) {
            return $result;
        }

        // get a selected page id
        $pageId = $this->params()->fromQuery('page_id', null);

        // get the page info
        if ($pageId !== null
                && null == ($page = $this->getModel()->getStructurePageInfo($pageId))) {

            // show the root page
            $pageId = null;
        }

        $filters = [];

        // get a filter form
        $filterForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Page\Form\PageFilter')
            ->setModel($this->getModel());

        $request = $this->getRequest();
        $filterForm->getForm()->setData($request->getQuery(), false);

        // check the filter form validation
        if ($filterForm->getForm()->isValid()) {
            $filters = $filterForm->getForm()->getData();
        }

        // get data
        $paginator = $this->getModel()->getStructurePages($pageId,
                $this->getPage(), $this->getPerPage(), $this->getOrderBy(), $this->getOrderType(), $filters);

        return new ViewModel([
            'pages_map' => $this->getModel()->getPagesMap($this->getModel()->getCurrentLanguage()),
            'filters' => $filters,
            'filter_form' => $filterForm->getForm(),
            'paginator' => $paginator,
            'order_by' => $this->getOrderBy(),
            'order_type' => $this->getOrderType(),
            'per_page' => $this->getPerPage(),
            'page_id' => $pageId
        ]);
    }
}