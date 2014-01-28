<?php

namespace Users\Model;

use Application\Model\AbstractBase;
use Application\Model\Acl;
use Zend\Db\Sql\Sql;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression as Expression;
use Application\Utility\Cache as CacheUtilities;
use Zend\Db\Sql\Predicate\NotIn as NotInPredicate;
use Application\Service\Service as ApplicationService;
use Application\Model\Acl as AclBase;

class Base extends AbstractBase
{
    /**
     * List of private fields
     * @var array
     */
    protected $privateFields = array(
        'email',
        'api_key',
        'api_secret',
        'activation_code'
    );

    /**
     * Cache user info
     */
    const CACHE_USER_INFO = 'User_Info_';

    /**
     * Approved status
     */
    const STATUS_APPROVED = 'approved';
    
    /**
     * Disapproved status
     */
    const STATUS_DISAPPROVED = 'disapproved';

    /**
     * Remember me time
     */
    const REMEMBER_ME_TIME = 7776000; // 90 days

    /**
     * User info field id
     */
    const USER_INFO_BY_ID = 'user_id';

    /**
     * User info field email
     */
    const USER_INFO_BY_EMAIL = 'email';

    /**
     * User info field slug
     */
    const USER_INFO_BY_SLUG = 'slug';

    /**
     * User info field api key
     */
    const USER_INFO_BY_API_KEY = 'api_key';

    /**
     * User slug lengh
     */
    const USER_SLUG_LENGTH = 40;

    /**
     * User info fields
     * @var array
     */
    protected $userInfoFields = array(
        self::USER_INFO_BY_ID,
        self::USER_INFO_BY_EMAIL,
        self::USER_INFO_BY_SLUG,
        self::USER_INFO_BY_API_KEY
    );

    /**
     * Set user's status
     *
     * @param integer $userId
     * @param boolean $approved
     * @return boolean|string
     */
    public function setUserStatus($userId, $approved = true)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $update = $this->update()
                ->table('users')
                ->set(array(
                    'status' => ($approved ? self::STATUS_APPROVED : self::STATUS_DISAPPROVED),
                    'activation_code' => ''
                ))
                ->where(array(
                    'user_id' => $userId
                ))
                ->where(array(
                    new NotInPredicate('user_id', array(AclBase::DEFAULT_USER_ID))
                ));

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();

            // clear a cache
            $this->removeUserCache($userId);

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            return $e->getMessage();
        }

        return true;
    }

    /**
     * Edit user
     *
     * @param integer $userId
     * @param array $userInfo
     *      string nick_name required
     *      string email required
     *      string password optional
     *      string time_zone optional
     * @param boolean $statusApproved
     * @return boolean|string
     */
    public function editUser($userId, array $userInfo, $statusApproved = true)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            // generate a new slug
            $extraValues = array(
                'slug' => $this->generateSlug($userId, $userInfo['nick_name'], 'users', 'user_id', self::USER_SLUG_LENGTH),
                'status' => $statusApproved ? self::STATUS_APPROVED : self::STATUS_DISAPPROVED
            );

            // regenerate user's password
            if (!empty($userInfo['password'])) {
                // generate a password salt
                $passwordSalt = $this->generateRandString();

                $extraValues = array_merge($extraValues, array(
                    'password' => $this->generatePassword($userInfo['password'], $passwordSalt),
                    'salt' => $passwordSalt
                ));
            }
            else {
                // remove the empty password
                unset($userInfo['password']);
            }

            $update = $this->update()
                ->table('users')
                ->set(array_merge($userInfo, $extraValues))
                ->where(array(
                    'user_id' => $userId
                ));

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();

            // clear a cache
            $this->removeUserCache($userId);

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            return $e->getMessage();
        }

        return true;
    }

    /**
     * Add a new user
     *
     * @param array $userInfo
     *      string nick_name required
     *      string email required
     *      string password required
     *      string time_zone optional
     * @param boolean $statusApproved
     * @return integer|string
     */
    public function addUser(array $userInfo, $statusApproved = true)
    {
        $insertId = 0;

        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            // generate a password salt
            $passwordSalt = $this->generateRandString();

            $insert = $this->insert()
                ->into('users')
                ->values(array_merge($userInfo, array(
                    'role' => Acl::DEFAULT_ROLE_MEMBER,
                    'status' => $statusApproved ? self::STATUS_APPROVED : self::STATUS_DISAPPROVED,
                    'language' => ApplicationService::getCurrentLocalization()['language'],
                    'registered' => new Expression('now()'),
                    'salt' => $passwordSalt,
                    'password' => $this->generatePassword($userInfo['password'], $passwordSalt),
                    'api_secret' => $this->generateRandString(),
                    'activation_code' => !$statusApproved // generate an activation code
                        ? $this->generateRandString()
                        : ''
                )));

            $statement = $this->prepareStatementForSqlObject($insert);
            $statement->execute();
            $insertId = $this->adapter->getDriver()->getLastGeneratedValue();

            // update an api key and generate user's slug
            $update = $this->update()
                ->table('users')
                ->set(array(
                    'api_key' => $insertId . $this->generateRandString(),
                    'slug' => $this->generateSlug($insertId, $userInfo['nick_name'], 'users', 'user_id', self::USER_SLUG_LENGTH)
                ))
                ->where(array(
                    'user_id' => $insertId
                ));

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            return $e->getMessage();
        }

        return $insertId;
    }

    /**
     * Generate a password
     *
     * @param string $password
     * @param string $salt
     * @return string
     */
    protected function generatePassword($password, $salt)
    {
        return sha1(md5($password) . $salt);
    }

    /**
     * Is email free
     *
     * @param string $email
     * @param integer $userId
     * @return boolean
     */
    public function isEmailFree($email, $userId = 0)
    {
        $select = $this->select();
        $select->from('users')
            ->columns(array(
                'user_id'
            ))
            ->where(array('email' => $email));

        if ($userId) {
            $select->where(array(
                new NotInPredicate('user_id', array($userId))
            ));
        }

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        return $resultSet->current() ? false : true;
    }

    /**
     * Is nickname free
     *
     * @param string $nickName
     * @param integer $userId
     * @return boolean
     */
    public function isNickNameFree($nickName, $userId = 0)
    {
        $select = $this->select();
        $select->from('users')
            ->columns(array(
                'user_id'
            ))
            ->where(array('nick_name' => $nickName));

        if ($userId) {
            $select->where(array(
                new NotInPredicate('user_id', array($userId))
            ));
        }

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        return $resultSet->current() ? false : true;
    }

    /**
     * Set user's language
     *
     * @param integer $userId
     * @param string $language
     * @return boolean|string
     */
    public function setUserLanguage($userId, $language)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $update = $this->update()
                ->table('users')
                ->set(array(
                    'language' => $language
                ))
                ->where(array(
                    'user_id' => $userId
                ));

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();

            // clear a cache
            $this->removeUserCache($userId);

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            return $e->getMessage();
        }

        return true;
    }

    /**
     * Get user info
     *
     * @param integer|string $userId
     * @param string $field
     * @return array
     */
    public function getUserInfo($userId, $field = self::USER_INFO_BY_ID)
    {
        // process a field name
        $field = in_array($field, $this->userInfoFields)
            ? $field
            : self::USER_INFO_BY_ID;

        // generate a cache name
        $cacheName = CacheUtilities::getCacheName(self::CACHE_USER_INFO . $userId, array($field));

        // check data in cache
        if (null === ($userInfo = $this->staticCacheInstance->getItem($cacheName))) {
            $select = $this->select();
            $select->from('users')
                ->columns(array(
                    'user_id',
                    'nick_name',
                    'slug',
                    'status',
                    'email',
                    'role',
                    'language',
                    'time_zone',
                    'layout',
                    'api_key',
                    'api_secret',
                    'registered',
                    'activation_code'
                ))
                ->where(array(
                    $field => $userId
                ));

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());
            $userInfo = $resultSet->current();

            // save data in cache
            $this->staticCacheInstance->setItem($cacheName, $userInfo);
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
        // clear all cache kinds
        foreach ($this->userInfoFields as $field) {
            $cacheName = CacheUtilities::getCacheName(self::CACHE_USER_INFO . $userId, array($field));
            $this->staticCacheInstance->removeItem($cacheName);
        }
    }
}