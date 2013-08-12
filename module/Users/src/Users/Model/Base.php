<?php

namespace Users\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression as Expression;

class Base extends Sql
{
    /**
     * Static cache utils
     * @var object
     */
    protected $staticCacheUtils;

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
     */
    public function __construct(Adapter $adapter, \Custom\Cache\Utils $staticCacheUtils)
    {
        parent::__construct($adapter);
        $this->staticCacheUtils = $staticCacheUtils;
    }

    /**
     * Get user info by Id
     *
     * @param integer $userId
     * @return array
     */
    public function getUserInfoById($userId)
    {
        // generate cache name
        $cacheName = $this->staticCacheUtils->
                getCacheName(self::CACHE_USER_BY_ID . $userId);

        // check data in cache
        if (null === ($userInfo = $this->
                staticCacheUtils->getCacheInstance()->getItem($cacheName))) {

            $select = $this->select();
            $select->from('users')
                ->columns(array(
                    'nick_name',
                    'email',
                    'role',
                    'language',
                    'time_zone',
                    'layout'
                ))
                ->where(array(
                    'user_id' => $userId
                ));

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());
            $userInfo = $resultSet->current();

            // save data in cache
            $this->staticCacheUtils->getCacheInstance()->setItem($cacheName, $userInfo);
            $this->staticCacheUtils->getCacheInstance()->setTags($cacheName, array(
                self::CACHE_TAG_USERS
            ));
        }

        return $userInfo;
    }
}