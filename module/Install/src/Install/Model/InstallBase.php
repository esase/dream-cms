<?php

namespace Install\Model;

use Zend\Math\Rand;
use Zend\Db\Adapter\Adapter as DbAdapter;
use Transliterator;
use Exception;

class InstallBase
{
    /**
     * Writable resources
     * @var array
     */
    protected $writableResources = [
        'data/cache/application',
        'data/cache/config',
        'data/session',
        'config/autoload',
        'config/module/custom.php',
        'config/module/system.php',
        'public/resource',
        'public/resource/filemanager',
        'public/resource/user',
        'public/resource/user/thumbnail',
        'public/layout_cache/css',
        'public/layout_cache/js',
        'public/captcha',
        'data/log'
    ];

    /**
     * Php extensions
     * @var array
     */
    protected $phpExtensions = [
        'intl',
        'zlib',
        'gd',
        'fileinfo',
        'zip',
        'pdo_mysql',
        'curl',
        'mbstring'
    ];

    /**
     * Php settings
     * @var array
     */
    protected $phpSettings = [
        'allow_url_fopen' => 1,
        'file_uploads' => 1
    ];

    /**
     * Enabled php functions
     * @var array
     */
    protected $phpEnabledFunctions = [
        'eval'
    ];

    /**
     * Php version 
     * @var string
     */
    protected $phpVersion = '5.4.0';

    /**
     * Install sql file
     * @var string
     */
    protected $installSqlFile = 'install.sql';

    /**
     * Get cron jobs
     * 
     * @return array
     */
    public function getCronJobs()
    {
        return [
            0 => [
                'time'   => '*/5 * * * *',
                'action' => APPLICATION_PUBLIC . '/index.php application send messages &> /dev/null'
            ]
        ];
    }

    /**
     * Get install module dir path
     *
     * @return string
     */
    public function getInstallModuleDirPath()
    {
        return APPLICATION_ROOT . '/module/Install';
    }

    /**
     * Get cron command line
     *
     * @return string
     */
    public function getCronCommandLine()
    {
        return '/replace/it/with/path/to/php/binary -q';
    }

    /**
     * Install script
     *
     * @param array $formData
     * @param string $cmsName
     * @param string $cmsVersion
     * @return string|boolean
     */
    public function install(array $formData, $cmsName, $cmsVersion)
    {
        try {
            // generate autoload config
            $this->generateAutoloadConfig($formData);

            // install the sql file
            $this->installSqlFile($formData, $cmsName, $cmsVersion);

            // clear cache files
            $this->clearConfigCacheFiles();

            // generate system modules config
            $this->generateSystemModulesConfig();
        }
        catch (Exception $e) {
            return $e->getMessage();
        }

        return true;
    }

    /**
     * Clear config cache files
     *
     * @return void
     */
    protected function clearConfigCacheFiles()
    {
        foreach (glob(APPLICATION_ROOT . '/data/cache/config/*.php') as $config) {
            unlink($config);
        }
    }

    /**
     * Generate system modules config
     *
     * @return void
     */
    protected function generateSystemModulesConfig()
    {
        $systemModules = "
        <?php
            return [
                'Application',
                'Acl',
                'User',
                'Layout',
                'Localization',
                'Page',
                'XmlRpc',
                'FileManager'
            ];
        ";

        file_put_contents(APPLICATION_ROOT . '/config/module/system.php', $systemModules);
    }

    /**
     * Generate autoload config
     *
     * @param array $formData
     * @return void
     */
    protected function generateAutoloadConfig(array $formData)
    {
        // remove the already created config
        if (file_exists(APPLICATION_ROOT . '/config/autoload/local.php')) {
            unlink(APPLICATION_ROOT . '/config/autoload/local.php');
        }

        // generate a new one
        $configTemplate = file_get_contents(APPLICATION_ROOT . '/config/autoload/local.dist.php');

        $configFindKeys = [
            '__DB_NAME__',
            '__DB_HOST__',
            '__DB_USER_NAME__',
            '__DB_PASSWORD__',
            '__DB_PORT__'
        ];

        $configReplaceKeys = [
            $formData['db_name'],
            $formData['db_host'],
            $formData['db_user'],
            $formData['db_password'],
            $formData['db_port'],
        ];

        $configTemplate = str_replace($configFindKeys, $configReplaceKeys, $configTemplate);
        file_put_contents(APPLICATION_ROOT . '/config/autoload/local.php', $configTemplate);
    }

    /**
     * Install sql file
     *
     * @param array $formData
     * @param string $cmsName
     * @param string $cmsVersion
     * @return void
     */
    protected function installSqlFile(array $formData, $cmsName, $cmsVersion)
    {
        $passwordSalt = $this->generateRandomString();

        $sqlFindKeys = [
            '__admin_nick_name_value__',
            '__admin_nick_name_slug_value__',
            '__admin_password_value__',
            '__admin_password_salt_value__',
            '__admin_api_key_value__',
            '__admin_api_secret_value__',
            '__admin_email_value__',
            '__admin_registered_value__',
            '__site_email_value__',
            '__dynamic_cache_value__',
            '__memcache_host_value__',
            '__memcache_port_value__',
            '__cms_name_value__',
            '__cms_version_value__',
        ];

        $sqlReplaysKeys = [
            $formData['admin_username'],
            $this->slugify($formData['admin_username']),
            sha1(md5($formData['admin_password']) . $passwordSalt),
            $passwordSalt,
            $this->generateRandomString(),
            $this->generateRandomString(),
            $formData['admin_email'],
            time(),
            $formData['site_email'],
            $formData['dynamic_cache'],
            $formData['memcache_host'],
            $formData['memcache_port'],
            $cmsName,
            $cmsVersion
        ];

        $adapter = new DbAdapter([
            'driver' => 'Pdo_Mysql',
            'database' => $formData['db_name'],
            'username' => $formData['db_user'],
            'password' => $formData['db_password'],
            'port'     => $formData['db_port'],
            'host'     => $formData['db_host'],
            'charset'  => 'utf8'
        ]);

        $adapter->getDriver()->getConnection()->connect();

        $this->executeSqlFile($adapter, [
            'from' => $sqlFindKeys,
            'to' => $sqlReplaysKeys
        ]);
    }

    /**
     * Execute sql file
     *
     * @param object $adapter
     * @param array $replace
     *      string from
     *      string to
     * @throws Exception
     * @return void
     */
    protected function executeSqlFile(DbAdapter $adapter, array $replace = [])
    {
        $path = realpath(dirname(__FILE__)) . '/' . $this->installSqlFile;

        if(!file_exists($path) || !($handler = fopen($path, 'r'))) {
            throw new Exception('Install sql file not found or permission denied');
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

            $adapter->query(trim($query), DbAdapter::QUERY_MODE_EXECUTE);
            $query = null;
        }

        fclose($handler);
    }

    /**
     * Get not configured php settings
     *
     * @return array
     */
    public function getNotConfiguredPhpSettings()
    {
        asort($this->phpSettings);
        $settings = [];

        foreach ($this->phpSettings as $setting => $value) {
            if ($value != ($currentSettingValue = ini_get($setting))) {
                $settings[] = [
                    'name' => $setting,
                    'current' => $currentSettingValue,
                    'desired' => $value
                ];
            }
        }

        // check the PHP version
        if (version_compare(PHP_VERSION, $this->phpVersion) == -1) {
            $settings[] = [
                'name' => 'PHP',
                'current' => PHP_VERSION,
                'desired' => $this->phpVersion
            ];
        }

        return $settings ? $settings  : [];
    }

    /**
     * Get disabled php funcitons
     *
     * @return array
     */
    public function getPhpDisabledFunctions()
    {
        asort($this->phpEnabledFunctions);
        $disabledFunctions = [];
        $disabledList = explode(',', ini_get('disable_functions'));

        foreach ($this->phpEnabledFunctions as $function) {
            if (in_array($function, $disabledList)) {
                $disabledFunctions[] = [
                    'function' => $function,
                    'current' => 'Disabled',
                    'desired' => 'Enabled'
                ];
            }
        }

        return $disabledFunctions ? $disabledFunctions  : [];
    }

    /**
     * Get not installed php extensions
     *
     * @return array
     */
    public function getNotInstalledPhpExtensions()
    {
        asort($this->phpExtensions);
        $extensions = [];

        foreach ($this->phpExtensions as $extension) {
            if (false === ($result = extension_loaded($extension))) {
                $extensions[] = [
                    'extension' => $extension,
                    'current' => 'Not installed',
                    'desired' => 'Installed'
                ];
            }
        }

        return $extensions ? $extensions  : [];
    }

    /**
     * Get not writable resources
     *
     * @return array
     */
    public function getNotWritableResources()
    {
        asort($this->writableResources);
        $resources = [];

        foreach ($this->writableResources as $path) {
            if (false === ($result = is_writable(APPLICATION_ROOT . '/' . $path))) {
                $resources[] = [
                    'path' => $path,
                    'current' => 'Not writable',
                    'desired' => 'Writable'
                ];
            }
        }

        return $resources ? $resources  : [];
    }

    /**
     * Generate a random string
     *
     * @param integer $stringLength
     * @param string $chars
     * @return string
     */
    protected static function generateRandomString($stringLength = 10, $chars = 'abcdefghijklmnopqrstuvwxyz0123456789')
    {
        return Rand::getString($stringLength, $chars, true);
    }

    /**
     * Slugify a string
     * 
     * @param string $title
     * @param integer $maxChars
     * @param string $spaceDevider
     * @param integer $objectId
     * @param string $pattern
     * @return string
     */
    protected function slugify($title, $maxChars = 100, $spaceDevider = '-', $objectId = 0, $pattern = '0-9a-z\s')
    {
        $transliterator = Transliterator::create('Any-Latin; Latin-ASCII; Lower();');
        $title = preg_replace('/[^' . $pattern. ']/i', '', $transliterator->transliterate($title));
        $title = str_replace(' ', $spaceDevider, $title);

        $slug = $objectId ? $objectId . $spaceDevider . $title : $title;

        return strlen($slug) > $maxChars ? substr($slug, 0, $maxChars) : $slug;
    }
}