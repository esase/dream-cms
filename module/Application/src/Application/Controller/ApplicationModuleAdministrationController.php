<?php
namespace Application\Controller;

use Application\Model\ApplicationAbstractBase as ApplicationAbstractBaseModel;
use Zend\View\Model\ViewModel;

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
        $moduleConfig = $this->getModel()->getCustomModuleConfig($module);

        if (false === $moduleConfig || null == ($requirements =
                $this->getModel()->getNotValidatedCustomModuleSystemRequirements($moduleConfig))) {

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
                                ->addMessage(sprintf($this->getTranslator()->translate('Module uninstallation system pages warning'), $module));

                            break;
                        }

                        // check the permission and increase permission's actions track
                        if (true !== ($result = $this->aclCheckPermission(null, true, false))) {
                            $this->flashMessenger()
                                ->setNamespace('error')
                                ->addMessage($this->getTranslator()->translate('Access Denied'));

                            break;
                        }

                        $moduleInstallConfig = $this->getModel()->getCustomModuleConfig($module);

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
                foreach ($modulesIds as $module) {
                    // get the module's config
                    $moduleInstallConfig = $this->getModel()->getCustomModuleConfig($module);

                    if (false === $moduleInstallConfig) {
                        continue;
                    }

                    // check the module depends and system requirements
                    $moduleDepends = $this->getModel()->checkCustomModuleDepends($moduleInstallConfig);
                    $moduleRequirements = $this->getModel()->
                            getNotValidatedCustomModuleSystemRequirements($moduleInstallConfig);

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
     * Add new module
     */
    public function addNewAction()
    {
    }
}