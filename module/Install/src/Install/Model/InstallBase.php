<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.dream-cms.kg/en/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Dream CMS software.
 * The Initial Developer of the Original Code is Dream CMS (http://www.dream-cms.kg).
 * All portions of the code written by Dream CMS are Copyright (c) 2014. All Rights Reserved.
 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2014 Dream CMS. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Dream CMS software
 * Attribution URL: http://www.dream-cms.kg/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */
namespace Install\Model;

use Zend\Math\Rand;
use Zend\Db\Adapter\Adapter as DbAdapter;
use Transliterator;
use Exception;
use ReflectionExtension;

class InstallBase
{
    /**
     * Site salt length
     */
    CONST SITE_SALT_LENGTH = 15;

    /**
     * Site salt chars
     */
    CONST SITE_SALT_CHARS = 'abcdefghijklmnopqrstuvwxyz0123456789,._-=+:*';

    /**
     * System modules
     *
     * @var array
     */
    protected $systemModules = [
        'Application',
        'Acl',
        'User',
        'Layout',
        'Localization',
        'Page',
        'XmlRpc',
        'FileManager'
    ];

    /**
     * Writable resources
     *
     * @var array
     */
    protected $writableResources = [
        'data/cache/application',
        'data/cache/config',
        'data/session',
        'data/tmp',
        'config/autoload',
        'config/module/custom.php',
        'config/module/system.php',
        '__public__/resource',
        '__public__/resource/filemanager',
        '__public__/resource/user',
        '__public__/resource/user/thumbnail',
        '__public__/layout_cache/css',
        '__public__/layout_cache/js',
        '__public__/captcha',
        'data/log'
    ];

    /**
     * Php basic extensions
     *
     * @var array
     */
    protected $phpBasicExtensions = [
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
     *
     * @var array
     */
    protected $phpSettings = [
        'allow_url_fopen' => 1,
        'file_uploads' => 1
    ];

    /**
     * Enabled php functions
     *
     * @var array
     */
    protected $phpEnabledFunctions = [
        'eval'
    ];

    /**
     * Php version
     *
     * @var string
     */
    protected $phpVersion = '5.5';

    /**
     * Intl version
     *
     * @var string
     */
    protected $intlVersion = '1.1.0';

    /**
     * Intl ICU version
     *
     * @var string
     */
    protected $intlIcuVersion = '50.1';

    /**
     * Install sql file
     *
     * @var string
     */
    protected $installSqlFile = 'install.sql';

    /**
     * Site salt
     *
     * @var string
     */
    protected $siteSalt;

    /**
     * Get site salt
     * 
     * @return string
     */
    protected function getSiteSalt()
    {
        if (!$this->siteSalt) {
            $this->siteSalt = self::generateRandomString(self::SITE_SALT_LENGTH, self::SITE_SALT_CHARS);
        }

        return $this->siteSalt;
    }

    /**
     * Get cron jobs
     * 
     * @return array
     */
    public function getCronJobs()
    {
        return [
            [
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
        $modules = implode(',', array_map(function($value) {return "'" . $value . "'";}, $this->systemModules));
        $content = <<<CONTENT
<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.dream-cms.kg/en/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Dream CMS software.
 * The Initial Developer of the Original Code is Dream CMS (http://www.dream-cms.kg).
 * All portions of the code written by Dream CMS are Copyright (c) 2014. All Rights Reserved.
 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2014 Dream CMS. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Dream CMS software
 * Attribution URL: http://www.dream-cms.kg/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */
 return [{$modules}];
CONTENT;

        file_put_contents(APPLICATION_ROOT . '/config/module/system.php', $content);
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
            '__SITE_SALT__',
            '__DB_NAME__',
            '__DB_HOST__',
            '__DB_USER_NAME__',
            '__DB_PASSWORD__',
            '__DB_PORT__'
        ];

        $configReplaceKeys = [
            addslashes($this->getSiteSalt()),
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
        $sqlFindKeys = [
            '__admin_nick_name_value__',
            '__admin_nick_name_slug_value__',
            '__admin_password_value__',
            '__admin_api_key_value__',
            '__admin_api_secret_value__',
            '__admin_email_value__',
            '__admin_registered_value__',
            '__site_email_value__',
            '__dynamic_cache_value__',
            '__memcache_host_value__',
            '__memcache_port_value__',
            '__cms_name_value__',
            '__cms_version_value__'
        ];

        $sqlReplaysKeys = [
            $formData['admin_username'],
            $this->slugify($formData['admin_username']),
            sha1(md5($formData['admin_password']) . $this->getSiteSalt()),
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
     * @param \Zend\Db\Adapter\Adapter $adapter
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

            // check for multi line query
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
     * Get list of disabled php functions
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
     * Get list of not installed php extensions
     *
     * @return array
     */
    public function getNotInstalledPhpExtensions()
    {
        asort($this->phpBasicExtensions);
        $extensions = [];

        foreach ($this->phpBasicExtensions as $extension) {
            if (false === ($result = extension_loaded($extension))) {
                $extensions[] = [
                    'extension' => $extension,
                    'current' => 'Not installed',
                    'desired' => 'Installed'
                ];
            }
        }

        // check the intl extension
        if (false === ($result = extension_loaded('intl'))) {
            $extensions[] = [
                'extension' => 'intl',
                'current' => 'Not installed',
                'desired' => 'Installed'
            ];
        }
        else {
            // get current intl version
            $intlVersion = preg_replace('/[^0-9\.]/', '', phpversion('intl'));
            $intlIcuVersion = $this->getIntlIcuVersion();

            if (false === version_compare($intlVersion, $this->intlVersion, '>=') 
                    || false === version_compare($intlIcuVersion, $this->intlIcuVersion, '>=')) {

                $extensions[] = [
                    'extension' => 'intl',
                    'current' => 'Intl ' . $intlVersion . ', ICU ' . $intlIcuVersion,
                    'desired' => 'Intl ' . $this->intlVersion . ', ICU ' . $this->intlIcuVersion
                ];
            }
        }

        return $extensions ? $extensions  : [];
    }

    /**
     * Get intl icu version
     *
     * @return string
     */
    protected function getIntlIcuVersion()
    {
        if (defined('INTL_ICU_VERSION')) {
            return INTL_ICU_VERSION;
        } 

        $reflector = new ReflectionExtension('intl');

        ob_start();
        $reflector->info();
        $output = strip_tags(ob_get_clean());

        preg_match('/^ICU version +(?:=> )?(.*)$/m', $output, $matches);

        return $matches[1];
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

        $publicDir = basename(APPLICATION_PUBLIC);
        foreach ($this->writableResources as $path) {
            $path = str_replace('__public__', $publicDir, $path);

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
     * @param string $spaceDivider
     * @param integer $objectId
     * @param string $pattern
     * @return string
     */
    protected function slugify($title, $maxChars = 100, $spaceDivider = '-', $objectId = 0, $pattern = '0-9a-z\s')
    {
        $transliterator = Transliterator::create('Any-Latin; Latin-ASCII; Lower();');
        $title = preg_replace('/[^' . $pattern. ']/i', '', $transliterator->transliterate($title));
        $title = str_replace(' ', $spaceDivider, $title);

        $slug = $objectId ? $objectId . $spaceDivider . $title : $title;

        return strlen($slug) > $maxChars ? substr($slug, 0, $maxChars) : $slug;
    }
}