<?php
namespace Layout\Model;

use Layout\Event\LayoutEvent;
use Layout\Exception\LayoutException;
use Layout\Utility\LayoutCache as LayoutCacheUtility;
use Application\Utility\ApplicationErrorLogger;
use Application\Utility\ApplicationCache as ApplicationCacheUtility;
use Application\Utility\ApplicationPagination as PaginationUtility;
use Application\Service\Application as ApplicationService;
use Application\Service\ApplicationSetting as SettingService;
use Zend\Db\ResultSet\ResultSet;
use Zend\Paginator\Adapter\ArrayAdapter as ArrayAdapterPaginator;
use Zend\Paginator\Paginator;
use DirectoryIterator;
use CallbackFilterIterator;
use Exception;

class LayoutAdministration extends LayoutBase
{
    /**
     * Layout install config
     * @var string
     */
    protected $layoutInstallConfig = '/layout.config.install.php';

    /**
     * Get all installed layouts
     *
     * @return array
     */
    protected function getAllInstalledLayouts()
    {
        $select = $this->select();
        $select->from('layout_list')
            ->columns([
                'id',
                'name'
            ])
        ->order('id');

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        $layoutsList = [];
        foreach ($resultSet as $layout) {
            $layoutsList[$layout->id] = $layout->name;
        }

        return $layoutsList;
    }

    /**
     * Is custom layout
     *
     * @param string $layout
     * @return boolean
     */
    public function isCustomLayout($layout)
    {
        $layoutDirectory = ApplicationService::getLayoutPath() . '/' . basename($layout);
        return file_exists($layoutDirectory . $this->layoutInstallConfig);
    }

    /**
     * Get custom layout install config
     *
     * @param string $layout
     * @param boolean $checkExisting
     * @return boolean|array
     */
    public function getCustomLayoutInstallConfig($layout, $checkExisting = true)
    {
        // is a custom layout
        if ($checkExisting && false === ($result = $this->isCustomLayout($layout))) {
            return $result;
        }

        return include ApplicationService::getLayoutPath() . '/' . basename($layout) . $this->layoutInstallConfig;
    }

    /**
     * Get not installed layouts
     *
     * @param integer $page
     * @param integer $perPage
     * @param string $orderBy
     * @param string $orderType
     * @return array
     */
    public function getNotInstalledLayouts($page = 1, $perPage = 0, $orderBy = null, $orderType = null)
    {
        $orderFields = [
            'name',
            'vendor',
            'vendor_email',
            'version',
            'date'
        ];

        $orderType = !$orderType || $orderType == 'asc'
            ? SORT_ASC
            : SORT_DESC;

        $orderBy = $orderBy && in_array($orderBy, $orderFields)
            ? $orderBy
            : 'name';

        // get installed active layouts
        $installedLayouts = array_map('strtolower', $this->getAllInstalledLayouts());

        // get all directories and files
        $directoryIterator = new DirectoryIterator(ApplicationService::getLayoutPath());
        $layouts = new CallbackFilterIterator($directoryIterator, function($current, $key, $iterator) use ($installedLayouts) {
            // skip already installed layouts
            if ($current->isDot() || !$current->isDir()
                    || in_array(strtolower($current->getFileName()), $installedLayouts)) {

                return false;
            }

            // check the layout
            return $this->isCustomLayout($current->getFileName());
        });

        $processedLayouts = [];
        $orderValues    = [];

        // process not installed layouts
        foreach($layouts as $layout) {
            $layoutInstallConfig = $this->getCustomLayoutInstallConfig($layout->getFileName(), false);

            $layoutInfo = [
                'name' => $layout->getFileName(),
                'vendor' => !empty($layoutInstallConfig['vendor']) ? $layoutInstallConfig['vendor'] : null,
                'email' => !empty($layoutInstallConfig['vendor_email']) ? $layoutInstallConfig['vendor_email'] : null,
                'version' => !empty($layoutInstallConfig['version']) ? $layoutInstallConfig['version'] : null,
                'date' => $layout->getMTime()
            ];

            $processedLayouts[] = $layoutInfo; 
            $orderValues[]      = $layoutInfo[$orderBy];
        }

        array_multisort($orderValues, $orderType, $processedLayouts);

        $paginator = new Paginator(new ArrayAdapterPaginator($processedLayouts));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(PaginationUtility::processPerPage($perPage));
        $paginator->setPageRange(SettingService::getSetting('application_page_range'));

        return $paginator;
    }

    /**
     * Clear layout install caches
     *
     * @return void
     */
    protected function clearLayoutInstallCaches()
    {
        ApplicationCacheUtility::clearDynamicCache();
        ApplicationCacheUtility::clearJsCache();
        ApplicationCacheUtility::clearCssCache();

        LayoutCacheUtility::clearLayoutCache();
    }

    /**
     * Install custom layout
     *
     * @param string $layoutName
     * @param array $layoutInstallConfig
     *      string compatable
     *      string version 
     *      string vendor 
     *      string vendor_email 
     * @return boolean|string
     */
    public function installCustomLayout($layoutName, array $layoutInstallConfig)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $compatable = !empty($layoutInstallConfig['compatable'])
                ? trim($layoutInstallConfig['compatable'])
                : null;

            $version = !empty($layoutInstallConfig['version'])
                ? trim($layoutInstallConfig['version'])
                : null;

            $vendor = !empty($layoutInstallConfig['vendor'])
                ? trim($layoutInstallConfig['vendor'])
                : null;

            $vendorEmail = !empty($layoutInstallConfig['vendor_email'])
                ? trim($layoutInstallConfig['vendor_email'])
                : null;

            if (!$compatable || true !== ($result =
                    version_compare(SettingService::getSetting('application_generator_version'), $compatable, '>='))) {

                throw new LayoutException('This layout is not compatible with current CMS version');
            }

            if (!$version || !$vendor || !$vendorEmail) {
                throw new LayoutException('It is impossible to determine the layout version, vendor or vendor email');
            }

            // clear caches
            $this->clearLayoutInstallCaches();

            $insert = $this->insert()
                ->into('layout_list')
                ->values([
                    'name' => $layoutName,
                    'type' => self::LAYOUT_TYPE_CUSTOM,
                    'status' => self::LAYOUT_STATUS_NOT_ACTIVE,
                    'version' => $version,
                    'vendor' => $vendor,
                    'vendor_email' => $vendorEmail
                ]);

            $statement = $this->prepareStatementForSqlObject($insert);
            $statement->execute();

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();

            ApplicationErrorLogger::log($e);
            return $e->getMessage();
        }

        // fire the install custom layout event
        LayoutEvent::fireInstallCustomLayoutEvent($layoutName);
        return true;
    }
}