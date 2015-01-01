<?php
namespace Application\Controller;

use Application\Model\ApplicationAbstractBase as ApplicationAbstractBaseModel;
use Application\Utility\ApplicationCache as ApplicationCacheUtility;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;

class ApplicationModuleAdministrationController extends ApplicationAbstractAdministrationController
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
                ->getInstance('Application\Model\ApplicationModuleAdministration');
        }

        return $this->model;
    }

    /**
     * Default action
     */
    public function indexAction()
    {
        // redirect to list action
        return $this->redirectTo('modules-administration', 'list-installed');
    }

    /**
     * View module description
     */
    public function ajaxViewModuleDescriptionAction()
    {
        // check the permission and increase permission's actions track
        if (true !== ($result = $this->aclCheckPermission())) {
            return $result;
        }

        $module = $this->params()->fromQuery('id', -1);

        // get a module description
        if (false === ($descripion = $this->getModel()->getModuleDescription($module))) {
            return $this->createHttpNotFoundModel($this->getResponse());
        }

        return new ViewModel([
            'descripion' => $descripion
        ]);
    }

    /**
     * View dependent modules
     */
    public function ajaxViewDependentModulesAction()
    {
        // check the permission and increase permission's actions track
        if (true !== ($result = $this->aclCheckPermission())) {
            return $result;
        }

        $module = $this->params()->fromQuery('id', -1);

        // get a module dependent modules
        if (null == ($modules = $this->getModel()->getDependentModules($module))) {
            return $this->createHttpNotFoundModel($this->getResponse());
        }

        return new ViewModel([
            'modules' => $modules
        ]);
    }

    /**
     * View module system requirements
     */
    public function ajaxViewModuleSystemRequirementsAction()
    {
        // check the permission and increase permission's actions track
        if (true !== ($result = $this->aclCheckPermission())) {
            return $result;
        }

        $module = $this->params()->fromQuery('id', -1);
        $moduleConfig = $this->getModel()->getCustomModuleInstallConfig($module);

        if (false === $moduleConfig || null == ($requirements =
                $this->getModel()->getNotValidatedModuleSystemRequirements($moduleConfig))) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }

        return new ViewModel([
            'requirements' => $requirements
        ]);
    }

    /**
     * List of not installed custom modules
     */
    public function listNotInstalledAction()
    {
        // check the permission and increase permission's actions track
        if (true !== ($result = $this->aclCheckPermission())) {
            return $result;
        }

        // get data
        $paginator = $this->getModel()->getNotInstalledModules($this->
                getPage(), $this->getPerPage(), $this->getOrderBy(), $this->getOrderType());

        return [
            'paginator' => $paginator,
            'order_by' => $this->getOrderBy(),
            'order_type' => $this->getOrderType(),
            'per_page' => $this->getPerPage()
        ];
    }

    /**
     * Uninstall selected modules
     */
    public function uninstallAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            $uninstallIntroText = [];

            if (null !== ($modulesIds = $request->getPost('modules', null))) {
                // uninstall selected modules
                $uninstallResult = false;
                foreach ($modulesIds as $module) {
                    // get a module info
                    if (null != ($moduleInfo = $this->getModel()->getModuleInfo($module))) {
                        // check dependent modules and type of the module
                        if (count($this->getModel()->getDependentModules($module)) ||
                                $moduleInfo['type'] == ApplicationAbstractBaseModel::MODULE_TYPE_SYSTEM) {

                            continue;
                        }

                        // check module's structure pages
                        if (true === ($result = $this->getModel()->checkModuleStructurePages($module))) {
                            $this->flashMessenger()
                                ->setNamespace('error')
                                ->addMessage(sprintf($this->getTranslator()->
                                        translate('Module uninstallation system pages warning'), $this->getTranslator()->translate($module)));

                            break;
                        }

                        // check the permission and increase permission's actions track
                        if (true !== ($result = $this->aclCheckPermission(null, true, false))) {
                            $this->flashMessenger()
                                ->setNamespace('error')
                                ->addMessage($this->getTranslator()->translate('Access Denied'));

                            break;
                        }

                        $moduleInstallConfig = $this->getModel()->getCustomModuleInstallConfig($module);

                        // uninstall the module
                        if (true !== ($uninstallResult =
                                $this->getModel()->uninstallCustomModule($module, $moduleInstallConfig))) {

                            $this->flashMessenger()
                                ->setNamespace('error')
                                ->addMessage(($uninstallResult ? $this->getTranslator()->translate($uninstallResult)
                                    : $this->getTranslator()->translate('Error occurred')));

                            break;
                        }

                        // checking for uninstall intro text
                        if (!empty($moduleInstallConfig['uninstall_intro'])) {
                            $uninstallIntroText[] = $this->getTranslator()->translate($moduleInstallConfig['uninstall_intro']);
                        }
                    }
                }

                if (true === $uninstallResult) {
                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Selected modules have been uninstalled'));

                    if ($uninstallIntroText) {
                        foreach ($uninstallIntroText as $intro) {
                            $this->flashMessenger()
                                ->setNamespace('info')
                                ->addMessage($intro);
                        }
                    }
                }
            }
        }

        // redirect back
        return $this->redirectTo('modules-administration', 'list-installed', [], true);
    }
   
    /**
     * Install selected modules
     */
    public function installAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            $installIntroText = [];

            if (null !== ($modulesIds = $request->getPost('modules', null))) {
                // install selected modules
                $installResult = false;
                foreach ($modulesIds as $module) {
                    // get the module's config
                    $moduleInstallConfig = $this->getModel()->getCustomModuleInstallConfig($module);

                    if (false === $moduleInstallConfig) {
                        continue;
                    }

                    // check the module depends and system requirements
                    $moduleDepends = $this->getModel()->checkModuleDepends($moduleInstallConfig);
                    $moduleRequirements = $this->getModel()->
                            getNotValidatedModuleSystemRequirements($moduleInstallConfig);

                    // skip all not validated modules
                    if (true !== $moduleDepends || count($moduleRequirements)) {
                        continue;
                    }

                    // check the permission and increase permission's actions track
                    if (true !== ($result = $this->aclCheckPermission(null, true, false))) {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()->translate('Access Denied'));

                        break;
                    }

                    // install the module
                    if (true !== ($installResult = $this->
                            getModel()->installCustomModule($module, $moduleInstallConfig))) {

                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage(($installResult ? $this->getTranslator()->translate($installResult)
                                : $this->getTranslator()->translate('Error occurred')));

                        break;
                    }

                    // checking for install intro text
                    if (!empty($moduleInstallConfig['install_intro'])) {
                        $installIntroText[] = $this->getTranslator()->translate($moduleInstallConfig['install_intro']);
                    }
                }

                if (true === $installResult) {
                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Selected modules have been installed'));

                    if ($installIntroText) {
                        foreach ($installIntroText as $intro) {
                            $this->flashMessenger()
                                ->setNamespace('info')
                                ->addMessage($intro);
                        }
                    }
                }
            }
        }

        // redirect back
        return $this->redirectTo('modules-administration', 'list-not-installed', [], true);
    }

    /**
     * Activate selected modules
     */
    public function activateAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            if (null !== ($modulesIds = $request->getPost('modules', null))) {
                // activate selected modules
                $activateResult = false;
                foreach ($modulesIds as $module) {
                    // get a module info
                    if (null != ($moduleInfo = $this->getModel()->getModuleInfo($module))) {
                        // check dependent modules and type of the module
                        if (count($this->getModel()->getDependentModules($module)) ||
                                $moduleInfo['type'] == ApplicationAbstractBaseModel::MODULE_TYPE_SYSTEM) {

                            continue;
                        }

                        // check the permission and increase permission's actions track
                        if (true !== ($result = $this->aclCheckPermission(null, true, false))) {
                            $this->flashMessenger()
                                ->setNamespace('error')
                                ->addMessage($this->getTranslator()->translate('Access Denied'));

                            break;
                        }

                        // activate the module
                        if (true !== ($activateResult = $this->getModel()->setCustomModuleStatus($module))) {
                            $this->flashMessenger()
                                ->setNamespace('error')
                                ->addMessage(($uninstallResult ? $this->getTranslator()->translate($activateResult)
                                    : $this->getTranslator()->translate('Error occurred')));

                            break;
                        }
                    }
                }

                if (true === $activateResult) {
                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Selected modules have been activated'));
                }
            }
        }

        ApplicationCacheUtility::clearDynamicCache();

        // redirect back
        return $this->redirectTo('modules-administration', 'list-installed', [], true);
    }

    /**
     * Deactivate selected modules
     */
    public function deactivateAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            if (null !== ($modulesIds = $request->getPost('modules', null))) {
                // deactivate selected modules
                $deactivateResult = false;
                foreach ($modulesIds as $module) {
                    // get a module info
                    if (null != ($moduleInfo = $this->getModel()->getModuleInfo($module))) {
                        // check dependent modules and type of the module
                        if (count($this->getModel()->getDependentModules($module)) ||
                                $moduleInfo['type'] == ApplicationAbstractBaseModel::MODULE_TYPE_SYSTEM) {

                            continue;
                        }

                        // check the permission and increase permission's actions track
                        if (true !== ($result = $this->aclCheckPermission(null, true, false))) {
                            $this->flashMessenger()
                                ->setNamespace('error')
                                ->addMessage($this->getTranslator()->translate('Access Denied'));

                            break;
                        }

                        // deactivate the module
                        if (true !== ($deactivateResult = $this->getModel()->setCustomModuleStatus($module, false))) {
                            $this->flashMessenger()
                                ->setNamespace('error')
                                ->addMessage(($uninstallResult ? $this->getTranslator()->translate($deactivateResult)
                                    : $this->getTranslator()->translate('Error occurred')));

                            break;
                        }
                    }
                }

                if (true === $deactivateResult) {
                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Selected modules have been deactivated'));
                }
            }
        }

        ApplicationCacheUtility::clearDynamicCache();

        // redirect back
        return $this->redirectTo('modules-administration', 'list-installed', [], true);
    }

    /**
     * List of installed modules
     */
    public function listInstalledAction()
    {
        // check the permission and increase permission's actions track
        if (true !== ($result = $this->aclCheckPermission())) {
            return $result;
        }

        $filters = [];

        // get a filter form
        $filterForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Application\Form\ApplicationModuleFilter');

        $request = $this->getRequest();
        $filterForm->getForm()->setData($request->getQuery(), false);

        // check the filter form validation
        if ($filterForm->getForm()->isValid()) {
            $filters = $filterForm->getForm()->getData();
        }

        // get data
        $paginator = $this->getModel()->getInstalledModules($this->getPage(),
                $this->getPerPage(), $this->getOrderBy(), $this->getOrderType(), $filters);

        return new ViewModel([
            'filter_form' => $filterForm->getForm(),
            'paginator' => $paginator,
            'order_by' => $this->getOrderBy(),
            'order_type' => $this->getOrderType(),
            'per_page' => $this->getPerPage()
        ]);
    }

    /**
     * Upload updates
     */
    public function uploadUpdatesAction()
    {
        $sessionContainer = new Container('application\module\update');

        // show updates intro text
        if (!empty($sessionContainer->module) && !empty($sessionContainer->intro)) {
            // load translations
            $this->getModel()->addModuleTranslations($this->
                    getModel()->getSystemModuleConfig($sessionContainer->module, false));

            $this->flashMessenger()
                ->setNamespace('success')
                ->addMessage($this->getTranslator()->translate('Updates of module have been uploaded'));

            $this->flashMessenger()
                ->setNamespace('info')
                ->addMessage($this->getTranslator()->translate($sessionContainer->intro));

            unset($sessionContainer->module);
            unset($sessionContainer->intro);

            return $this->redirectTo('modules-administration', 'upload-updates');
        }

        $request = $this->getRequest();
        $host = $request->getUri()->getHost();

        // get an module form
        $moduleForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Application\Form\ApplicationModule')
            ->setHost($host);

        // validate the form
        if ($request->isPost()) {
            // make certain to merge the files info!
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray()
            );

            // fill the form with received values
            $moduleForm->getForm()->setData($post, false);

            // upload updates
            if ($moduleForm->getForm()->isValid()) {
                // check the permission and increase permission's actions track
                if (true !== ($result = $this->aclCheckPermission())) {
                    return $result;
                }

                $result = $this->getModel()->uploadModuleUpdates($moduleForm->getForm()->getData(), $host);

                if (is_array($result)) {
                    if (!empty($result['update_intro'])) {
                        $sessionContainer->module = $result['module'];
                        $sessionContainer->intro = $result['update_intro'];
                    }
                    else {
                        $this->flashMessenger()
                            ->setNamespace('success')
                            ->addMessage($this->getTranslator()->translate('Updates of module have been uploaded'));
                    }
                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate($result));
                }

                return $this->redirectTo('modules-administration', 'upload-updates');
            }
        }

        return new ViewModel([
            'moduleForm' => $moduleForm->getForm()
        ]);
    }

    /**
     * Upload a new module
     */
    public function uploadAction()
    {
        $request = $this->getRequest();
        $host = $request->getUri()->getHost();

        // get an module form
        $moduleForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Application\Form\ApplicationModule')
            ->setHost($host);

        // validate the form
        if ($request->isPost()) {
            // make certain to merge the files info!
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray()
            );

            // fill the form with received values
            $moduleForm->getForm()->setData($post, false);

            // upload a module
            if ($moduleForm->getForm()->isValid()) {
                // check the permission and increase permission's actions track
                if (true !== ($result = $this->aclCheckPermission())) {
                    return $result;
                }

                // upload the module
                if (true === ($result =
                        $this->getModel()->uploadCustomModule($moduleForm->getForm()->getData(), $host))) {

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Module has been uploaded'));
                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate($result));
                }

                return $this->redirectTo('modules-administration', 'upload');
            }
        }

        return new ViewModel([
            'moduleForm' => $moduleForm->getForm()
        ]);
    }

    /**
     * Delete a module
     */
    public function deleteAction()
    {
        $moduleName = $this->getSlug();

        // module should be not installed
        if (null != ($moduleInfo = $this->getModel()->getModuleInfo($moduleName)) 
                || null == ($installConfig = $this->getModel()->getCustomModuleInstallConfig($moduleName))
                || false === $this->getModel()->isCustomModule($moduleName)) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // add translations
        $this->getModel()->addModuleTranslations($this->getModel()->getSystemModuleConfig($moduleName, false));
        $request = $this->getRequest();
        $host = $request->getUri()->getHost();

        // get an module form
        $moduleForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Application\Form\ApplicationModule')
            ->setHost($host)
            ->setDeleteMode();

        // validate the form
        if ($request->isPost()) {
            // fill the form with received values
            $moduleForm->getForm()->setData($request->getPost(), false);

            // delete a module
            if ($moduleForm->getForm()->isValid()) {
                // check the permission and increase permission's actions track
                if (true !== ($result = $this->aclCheckPermission())) {
                    return $result;
                }

                // delete the module
                if (true === ($result = $this->getModel()->
                        deleteCustomModule($moduleName, $moduleForm->getForm()->getData(), $host, $installConfig))) {

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Module has been deleted'));

                    return $this->redirectTo('modules-administration', 'list-not-installed');
                }

                $this->flashMessenger()
                    ->setNamespace('error')
                    ->addMessage($this->getTranslator()->translate($result));

                return $this->redirectTo('modules-administration', 'delete', [
                    'slug' => $moduleName
                ]);
            }
        }

        return new ViewModel([
            'module_name' => $moduleName,
            'moduleForm' => $moduleForm->getForm()
        ]);
    }
}