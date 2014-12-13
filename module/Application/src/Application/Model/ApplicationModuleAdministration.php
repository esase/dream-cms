<?php
namespace Application\Model;

use Application\Service\ApplicationServiceLocator as ServiceLocatorService;
use Application\Service\ApplicationSetting as SettingService;
use Application\Utility\ApplicationPagination as PaginationUtility;
use Zend\Paginator\Adapter\ArrayAdapter as ArrayAdapterPaginator;
use Zend\Paginator\Paginator;
use DirectoryIterator;
use CallbackFilterIterator;

class ApplicationModuleAdministration extends ApplicationBase
{
    /**
     * Module config
     * @var string
     */
    protected $moduleConfig = '/config/module.config.php';

    /**
     * Module install config
     * @var string
     */
    protected $moduleInstallConfig = '/config/module.config.install.php';

    /**
     * Get modules dir
     *
     * @return string
     */
    public function getModulesDir()
    {
        return APPLICATION_ROOT . '/module/';
    }

    /**
     * Get module description
     *
     * @param string|integer $module
     * @return string|boolean
     */
    public function getModuleDescription($module)
    {
        // try to get description from installed modules
        if (null != ($moduleInfo = $this->getModuleInfo($module))) {
            return !empty($moduleInfo['description'])  ? $moduleInfo['description'] : false;
        }

        // try to get description from not installed modules
        $moduleDirectory = $this->getModulesDir() . basename($module);
        if (file_exists($moduleDirectory) && is_dir($moduleDirectory)) {
            if (file_exists($moduleDirectory . $this
                    ->moduleInstallConfig) && file_exists($moduleDirectory . $this->moduleConfig)) {

                $moduleInstallConfig = include $moduleDirectory . $this->moduleInstallConfig;

                // load the module's translations
                $this->addModuleTranslations(include $moduleDirectory . $this->moduleConfig);
                return !empty($moduleInstallConfig['description'])  ? $moduleInstallConfig['description'] : false;
            }
        }

        return false;
    }

    /**
     * Get not installed modules
     *
     * @param integer $page
     * @param integer $perPage
     * @param string $orderBy
     * @param string $orderType
     * @param array $filters
     * @return array
     */
    public function getNotInstalledModules($page = 1, $perPage = 0, $orderBy = null, $orderType = null, array $filters = [])
    {
        $orderFields = [
            'vendor',
            'vendor_email',
            'version',
            'date'
        ];

        $orderType = !$orderType || $orderType == 'desc'
            ? SORT_DESC
            : SORT_ASC;

        $orderBy = $orderBy && in_array($orderBy, $orderFields)
            ? $orderBy
            : 'date';

        // get installed modules
        $installedModules = array_map('strtolower', $this->getInstalledModulesList());

        // get all directories and files
        $directoryIterator = new DirectoryIterator($this->getModulesDir());
        $modules = new CallbackFilterIterator($directoryIterator, function($current, $key, $iterator) use ($filters, $installedModules) {
            // skip already installed modules and files
            if ($current->isDot() || !$current->isDir()
                    || in_array(strtolower($current->getFileName()), $installedModules)) {

                return false;
            }

            // check module configs
            return file_exists($current->getPathName() . $this->moduleConfig)
                    && file_exists($current->getPathName() . $this->moduleInstallConfig);
        });

        $processedModules = [];
        $orderValues    = [];

        // process not installed modules
        foreach($modules as $data) {
            $moduleInstallConfig = include $data->getPathName() . $this->moduleInstallConfig;

            $moduleInfo = [
                'name' => $data->getFileName(),
                'vendor' => !empty($moduleInstallConfig['version']) ? $moduleInstallConfig['vendor'] : null,
                'email' => !empty($moduleInstallConfig['version']) ? $moduleInstallConfig['vendor_email'] : null,
                'version' => !empty($moduleInstallConfig['version']) ? $moduleInstallConfig['version'] : null,
                'description' => !empty($moduleInstallConfig['description']) ? $moduleInstallConfig['description'] : null,
                'date' => $data->getMTime()
            ];

            $processedModules[] = $moduleInfo; 
            $orderValues[]    = $moduleInfo[$orderBy];

            // load the module's translations
            $this->addModuleTranslations(include $data->getPathName() . $this->moduleConfig);
        }

        array_multisort($orderValues, $orderType, $processedModules);

        $paginator = new Paginator(new ArrayAdapterPaginator($processedModules));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(PaginationUtility::processPerPage($perPage));
        $paginator->setPageRange(SettingService::getSetting('application_page_range'));

        return $paginator;
    }

    /**
     * Add module translations
     *
     * @var array $moduleConfig
     * @return void
     */
    protected function addModuleTranslations($moduleConfig)
    {
        $translator = ServiceLocatorService::getServiceLocator()->get('translator');

        if (!empty($moduleConfig['translator']['translation_file_patterns'])) {
            foreach ($moduleConfig['translator']['translation_file_patterns'] as $pattern) {
                $translator->addTranslationFilePattern($pattern['type'],
                        $pattern['base_dir'], $pattern['pattern'], $pattern['text_domain']);
            }
        }
    }
}