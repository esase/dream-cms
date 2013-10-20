<?php

namespace Users\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression as Expression;
use Application\Utility\Cache as CacheUtilities;

class Base extends Sql
{
    /**
     * Static cache instance
     * @var object
     */
    protected $staticCacheInstance;

    protected $privateFields = array(
        'email',
        'api_key',
        'api_secret'
    );

    /**
     * Cache user by id
     */
    const CACHE_USER_BY_ID = 'User_By_Id_';

    /**
     * Cache users tag
     */
    const CACHE_TAG_USERS = 'Tag_Users';

    /**
     * Class constructor
     *
     * @param object $adapter
     * @param object $staticCacheInstance
     */
    public function __construct(Adapter $adapter, $staticCacheInstance)
    {
        parent::__construct($adapter);
        $this->staticCacheInstance = $staticCacheInstance;
    }

    /**
     * Get user info by Id
     *
     * @param integer $userId
     * @param boolean $isApiKey
     * @return array
     */
    public function getUserInfoById($userId, $isApiKey = false)
    {
        // generate cache name
        $cacheName =
                CacheUtilities::getCacheName(self::CACHE_USER_BY_ID . $userId, array($isApiKey));

        // check data in cache
        if (null === ($userInfo = $this->staticCacheInstance->getItem($cacheName))) {
            $select = $this->select();
            $select->from('users')
                ->columns(array(
                    'user_id',
                    'nick_name',
                    'email',
                    'role',
                    'language',
                    'time_zone',
                    'layout',
                    'api_key',
                    'api_secret'
                ));

                // get user info by apiKey
                if ($isApiKey) {
                    $select->where(array(
                        'api_key' => $userId
                    ));
                }
                else {
                    $select->where(array(
                        'user_id' => $userId
                    ));
                }

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());
            $userInfo = $resultSet->current();

            // save data in cache
            $this->staticCacheInstance->setItem($cacheName, $userInfo);
            $this->staticCacheInstance->setTags($cacheName, array(
                self::CACHE_TAG_USERS
            ));
        }

        return $userInfo;
    }

    /**
     * Remove the user cache
     *
     * @param integer $userId
     * @return void
     */
    protected function removeUserCache($userId)
    {
        // clear cache by id
        $cacheName = CacheUtilities::getCacheName(self::CACHE_USER_BY_ID . $userId, array(false));
        $this->staticCacheInstance->removeItem($cacheName);

        // clear cache by api key
        $cacheName = CacheUtilities::getCacheName(self::CACHE_USER_BY_ID . $userId, array(true));
        $this->staticCacheInstance->removeItem($cacheName);
    }
}