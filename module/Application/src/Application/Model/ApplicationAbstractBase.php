<?php
namespace Application\Model;

use Application\Service\Application as ApplicationService;
use Application\Exception\ApplicationException;
use Application\Service\ApplicationServiceLocator as ServiceLocatorService;
use Application\Utility\ApplicationCache as CacheUtility;
use Localization\Service\Localization as LocalizationService;
use Zend\Db\Sql\Sql;
use Zend\Db\Adapter\AdapterInterface;
use Zend\Cache\Storage\StorageInterface;
use Application\Utility\ApplicationSlug as SlugUtility;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Predicate\NotIn as NotInPredicate;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Db\Adapter\Adapter as DbAdapter;
use ZipArchive;

abstract class ApplicationAbstractBase extends Sql
{
    /**
     * Service locator
     * @var object
     */
    protected $serviceLocator;

    /**
     * Static cache instance
     * @var object
     */
    protected $staticCacheInstance;

    /**
     * Slug salt length
     * @var integer
     */
    protected $slugSaltLength = 10;

    /**
     * Module by name
     */
    const CACHE_MODULE_BY_NAME = 'Application_Module_By_Name_';

    /**
     * Active modules
     */
    const CACHE_MODULES_ACTIVE = 'Application_Modules_Active';

    /**
     * Modules data cache tag
     */
    const CACHE_MODULES_DATA_TAG = 'Application_Modules_Data_Tag';

    /**
     * Module active status flag
     */
    const MODULE_STATUS_ACTIVE = 'active';

    /**
     * Module not active status flag
     */
    const MODULE_STATUS_NOT_ACTIVE = 'not_active';

    /**
     * Module type system
     */
    const MODULE_TYPE_SYSTEM = 'system';

    /**
     * MOdule type custom
     */
    const MODULE_TYPE_CUSTOM = 'custom';

    /**
     * Class constructor
     *
     * @param object $adapter
     * @param object $staticCacheInstance
     */
    public function __construct(AdapterInterface $adapter, StorageInterface $staticCacheInstance)
    {
        parent::__construct($adapter);

        $this->serviceLocator = ServiceLocatorService::getServiceLocator();
        $this->staticCacheInstance = $staticCacheInstance;
    }

    /**
     * Get current language
     *
     * @return string
     */
    public function getCurrentLanguage()
    {
       return LocalizationService::getCurrentLocalization()['language']; 
    }

    /**
     * Get date range
     *
     * @param string $date 
     * @return array
     */
    public function getDateRange($date)
    {
        return [
            strtotime($date),
            strtotime($date . ' 23:59:59')
        ];
    }

    /**
     * Generate slug
     *
     * @param integer $objectId
     * @param string $title
     * @param string $table
     * @param string $idField
     * @param integer $slugLength
     * @param array $filters
     * @param string $slugField
     * @param string $spaceDevider
     * @return string
     */
    public function generateSlug($objectId, $title, $table, $idField, $slugLength = 60, array $filters = [], $slugField = 'slug', $spaceDevider = '-')
    {
        // generate a slug
        $newSlug  = $slug = SlugUtility::slugify($title, $slugLength, $spaceDevider);
        $slagSalt = null;

        while (true) {
            // check the slug existent
            $select = $this->select();
            $select->from($table)
                ->columns([
                    $slugField
                ])
                ->where([
                    $slugField => $newSlug                    
                ] + $filters);

            $select->where([
                new NotInPredicate($idField, [$objectId])
            ]);

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());

            // generated slug not found
            if (!$resultSet->current()) {
                break;
            }
            else {
                $newSlug = $objectId . $spaceDevider . $slug . $slagSalt;
            }

            // add an extra slug
            $slagSalt = $spaceDevider . SlugUtility::generateRandomSlug($this->slugSaltLength); // add a salt
        }

        return $newSlug;
    }

    /**
     * Get module info
     *
     * @param string $moduleName
     * @return array
     */
    public function getModuleInfo($moduleName)
    {
        // generate cache name
        $cacheName = CacheUtility::getCacheName(self::CACHE_MODULE_BY_NAME . $moduleName);

        // check data in cache
        if (null === ($module = $this->staticCacheInstance->getItem($cacheName))) {
            $select = $this->select();
            $select->from('application_module')
                ->columns([
                    'id',
                    'name',
                    'type',
                    'status',
                    'version',
                    'vendor',
                    'vendor_email',
                    'description',
                    'layout_path'
                ])
                ->where(['name' => $moduleName]);

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());

            if (null != ($module = $resultSet->current())) {
                // save data in cache
                $this->staticCacheInstance->setItem($cacheName, $module);
                $this->staticCacheInstance->setTags($cacheName, [self::CACHE_MODULES_DATA_TAG]);
            }
        }

        return $module;
    }

    /**
     * Get active modules list
     *
     * @return array
     */
    public function getActiveModulesList()
    {
        // generate cache name
        $cacheName = CacheUtility::getCacheName(self::CACHE_MODULES_ACTIVE);

        // check data in cache
        if (null === ($modulesList = $this->staticCacheInstance->getItem($cacheName))) {
            $select = $this->select();
            $select->from('application_module')
                ->columns([
                    'id',
                    'name'
                ])
            ->where([
                'status' => self::MODULE_STATUS_ACTIVE
            ])
            ->order('id');
    
            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());
    
            foreach ($resultSet as $module) {
                $modulesList[$module->id] = $module->name;
            }

            // save data in cache
            $this->staticCacheInstance->setItem($cacheName, $modulesList);
            $this->staticCacheInstance->setTags($cacheName, [self::CACHE_MODULES_DATA_TAG]);
        }

        return $modulesList;
    }

    /**
     * Generate a rand string
     *
     * @param integer $length
     * @param  string|null $charlist
     * @return string
     */
    public function generateRandString($length = 10, $charlist = null)
    {
        return SlugUtility::generateRandomSlug($length, $charlist);
    }

    /**
     * Get adapter
     *
     * @return object
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * Generate tmp dir
     *
     * @return string
     */
    protected function generateTmpDir()
    {
        return ApplicationService::getTmpPath()
                . '/' . time() . '_' . $this->generateRandString();
    }

    /**
     * Unzip files
     *
     * @param string $file
     * @param string $path
     * @throws ApplicationException
     * @return void
     */
    public function unzipFiles($file, $path)
    {
        // unzip a custom module into the tmp dir
        $zip = new ZipArchive;

        if (true !== ($result = $zip->open($file))) {
            $zip->close();
            throw new ApplicationException('Cannot open archived files');
        }

        if (true !== ($result = $zip->extractTo($path))) {
            $zip->close();
            throw new ApplicationException('Cannot extract archived files');
        }

        $zip->close();
    }

    /**
     * Execute sql file
     *
     * @param string $filePath
     * @param array $replace
     *      string from
     *      string to
     * @throws ApplicationException
     * @return void
     */
    protected function executeSqlFile($filePath, array $replace = [])
    {
        if(!file_exists($filePath) || !($handler = fopen($filePath, 'r'))) {
            throw new ApplicationException('Sql file not found or permission denied');
        }

        $query = null;
        $delimiter = ';';
        $result = [];

        // collect all queries
        while(!feof($handler)) {
            $str = trim(fgets($handler));

            if(empty($str) || $str[0] == '' || $str[0] == '#' || ($str[0] == '-' && $str[1] == '-'))
                continue;

            // change delimiter
            if(strpos($str, 'DELIMITER //') !== false || strpos($str, 'DELIMITER ;') !== false) {
                $delimiter = trim(str_replace('DELIMITER', '', $str));
                continue;
            }

            $query .= ' ' . $str;

            // check for multiline query
            if(substr($str, -strlen($delimiter)) != $delimiter) {
                continue;
            }

            // execute query
            if (!empty($replace['from']) && !empty($replace['to'])) {
                $query = str_replace($replace['from'], $replace['to'], $query);
            }

            if($delimiter != ';') {
                $query = str_replace($delimiter, '', $query);
            }

            $this->adapter->query(trim($query), DbAdapter::QUERY_MODE_EXECUTE);
            $query = null;
        }

        fclose($handler);
    }
}