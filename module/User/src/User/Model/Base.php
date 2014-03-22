<?php

namespace User\Model;

use Application\Model\AbstractBase;
use Zend\Db\Sql\Sql;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression as Expression;
use Application\Utility\Cache as CacheUtility;
use Zend\Db\Sql\Predicate\NotIn as NotInPredicate;
use Zend\Db\Sql\Predicate\isNull as IsNullPredicate;
use Application\Service\Service as ApplicationService;
use Application\Model\Acl as AclModelBase;
use Application\Utility\FileSystem as FileSystemUtility;
use Exception;
use Application\Utility\Image as ImageUtility;
use Application\Utility\ErrorLogger;

class Base extends AbstractBase
{
    /**
     * Default system's id
     */
    const DEFAULT_SYSTEM_ID  = 0;

    /**
     * Default guest's id
     */
    const DEFAULT_GUEST_ID  = -1;

    /**
     * Default user's id
     */
    const DEFAULT_USER_ID  = 1;

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
     * Avatars directory
     * @var string
     */
    protected static $avatarsDir = 'user/';

    /**
     * Thumbnails directory
     * @var string
     */
    protected static $thumbnailsDir = 'user/thumbnail/';

    /**
     * Get avatars directory name
     *
     * @return string
     */
    public static function getAvatarsDir()
    {
        return self::$avatarsDir;
    }

    /**
     * Get thumbnails directory name
     *
     * @return string
     */
    public static function getThumbnailsDir()
    {
        return self::$thumbnailsDir;
    }

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
                ->table('user')
                ->set(array(
                    'status' => ($approved ? self::STATUS_APPROVED : self::STATUS_DISAPPROVED),
                    'activation_code' => ''
                ))
                ->where(array(
                    'user_id' => $userId
                ))
                ->where(array(
                    new NotInPredicate('user_id', array(self::DEFAULT_USER_ID))
                ));

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();

            // clear a cache
            $this->removeUserCache($userId);

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ErrorLogger::log($e);

            return $e->getMessage();
        }

        return true;
    }

    /**
     * Edit user
     *
     * @param array $userInfo
     * @param array $formData
     *      string nick_name required
     *      string email required
     *      string password optional
     *      string time_zone optional
     * @param boolean $statusApproved
     * @param array $avatar
     * @param boolean $deleteAvatar
     * @return boolean|string
     */
    public function editUser($userInfo, array $formData, $statusApproved = true, array $avatar = array(), $deleteAvatar = false)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            // generate a new slug
            $extraValues = array(
                'slug' => $this->generateSlug($userInfo['user_id'], $formData['nick_name'], 'user', 'user_id', self::USER_SLUG_LENGTH),
                'status' => $statusApproved ? self::STATUS_APPROVED : self::STATUS_DISAPPROVED
            );

            // regenerate the user's password
            if (!empty($formData['password'])) {
                // generate a password salt
                $passwordSalt = $this->generateRandString();

                $extraValues = array_merge($extraValues, array(
                    'password' => $this->generatePassword($formData['password'], $passwordSalt),
                    'salt' => $passwordSalt
                ));
            }
            else {
                // remove the empty password
                unset($formData['password']);
            }

            $update = $this->update()
                ->table('user')
                ->set(array_merge($formData, $extraValues))
                ->where(array(
                    'user_id' => $userInfo['user_id']
                ));

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();

            // upload the user's avatar
            $this->uploadAvatar($userInfo['user_id'], $avatar, $userInfo['avatar'], $deleteAvatar);

            // clear a cache
            $this->removeUserCache($userInfoId);

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ErrorLogger::log($e);

            return $e->getMessage();
        }

        return true;
    }

    /**
     * Upload an avatar
     *
     * @param integer $userId
     * @param array $avatar
     *      string name
     *      string type
     *      string tmp_name
     *      integer error
     *      integer size
     * @param string $oldAvatar
     * @param boolean $deleteAvatar
     * @return void
     */
    protected function uploadAvatar($userId, array $avatar, $oldAvatar = null, $deleteAvatar = false)
    {
        // upload the user's avatar
        if (!empty($avatar['name'])) {
            // delete old avatar
            if ($oldAvatar) {
                if (true !== ($result = $this->deleteUserAvatar($oldAvatar))) {
                    throw new Exception('Avatar deleting failed');
                }
            }

            // upload the new
            if (false === ($avatarName =
                    FileSystemUtility::uploadResourceFile($userId, $avatar, self::$avatarsDir))) {

                throw new Exception('Avatar uploading failed');
            }

            // resize the avatar
            ImageUtility::resizeResourceImage($avatarName, self::$avatarsDir,
                    (int) ApplicationService::getSetting('user_thumbnail_width'),
                    (int) ApplicationService::getSetting('user_thumbnail_height'), self::$thumbnailsDir);

            ImageUtility::resizeResourceImage($avatarName, self::$avatarsDir,
                    (int) ApplicationService::getSetting('user_avatar_width'),
                    (int) ApplicationService::getSetting('user_avatar_height'));

            $update = $this->update()
                ->table('user')
                ->set(array(
                    'avatar' => $avatarName
                ))
                ->where(array('user_id' => $userId));

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();
        }
        elseif ($deleteAvatar && $oldAvatar) {
            // just delete the user's avatar
            if (true !== ($result = $this->deleteUserAvatar($oldAvatar))) {
                throw new Exception('Avatar deleting failed');
            }

            $update = $this->update()
                ->table('user')
                ->set(array(
                    'avatar' => ''
                ))
                ->where(array('user_id' => $userId));

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();
        }
    }

    /**
     * Delete an user's avatar
     *
     * @param string $avatarName
     * @return boolean
     */
    protected function deleteUserAvatar($avatarName)
    {
        $avatarTypes = array(
            self::$thumbnailsDir,
            self::$avatarsDir
        );

        // delete avatar
        foreach ($avatarTypes as $path) {
            if (true !== ($result = FileSystemUtility::deleteResourceFile($avatarName, $path))) {
                return $result;
            }
        }

        return true; 
    }

    /**
     * Delete an user
     *
     * @param array $userInfo
     * @return boolean|string
     */
    public function deleteUser($userInfo)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $delete = $this->delete()
                ->from('user')
                ->where(array(
                    'user_id' => $userInfo['user_id']
                ))
                ->where(array(
                    new NotInPredicate('user_id', array(self::DEFAULT_USER_ID))
                ));

            $statement = $this->prepareStatementForSqlObject($delete);
            $result = $statement->execute();

            // delete an avatar
            if ($userInfo['avatar']) {
                if (true !== ($avatarDeleteResult = $this->deleteUserAvatar($userInfo['avatar']))) {
                    throw new Exception('Avatar deleting failed');
                }
            }

            // clear a cache
            $this->removeUserCache($userId);

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ErrorLogger::log($e);

            return $e->getMessage();
        }

        return $result->count() ? true : false;
    }

    /**
     * Add a new user
     *
     * @param array $formData
     *      string nick_name required
     *      string email required
     *      string password required
     *      string time_zone optional
     * @param boolean $statusApproved
     * @param array $avatar
     * @return integer|string
     */
    public function addUser(array $formData, $statusApproved = true, array $avatar = array())
    {
        $insertId = 0;

        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            // generate a password salt
            $passwordSalt = $this->generateRandString();

            $insert = $this->insert()
                ->into('user')
                ->values(array_merge($formData, array(
                    'role' => AclModelBase::DEFAULT_ROLE_MEMBER,
                    'status' => $statusApproved ? self::STATUS_APPROVED : self::STATUS_DISAPPROVED,
                    'language' => ApplicationService::getCurrentLocalization()['language'],
                    'registered' => new Expression('now()'),
                    'salt' => $passwordSalt,
                    'password' => $this->generatePassword($formData['password'], $passwordSalt),
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
                ->table('user')
                ->set(array(
                    'api_key' => $insertId . '_' . $this->generateRandString(),
                    'slug' => $this->generateSlug($insertId, $formData['nick_name'], 'user', 'user_id', self::USER_SLUG_LENGTH)
                ))
                ->where(array(
                    'user_id' => $insertId
                ));

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();

            // upload the user's avatar
            $this->uploadAvatar($insertId, $avatar);

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ErrorLogger::log($e);

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
        $select->from('user')
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
     * Get users with empty role
     *
     * @return array
     */
    public function getUsersWithEmptyRole()
    {
        $select = $this->select();
        $select->from('user')
            ->columns(array(
                'user_id'
            ))
            ->where(array(
                new IsNullPredicate('role')
            ));

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        return $resultSet->toArray();
    }

    /**
     * Edit the user's role
     *
     * @param integer $userId
     * @param integer $roleId
     * @return boolean|string
     */
    public function editUserRole($userId, $roleId)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $update = $this->update()
                ->table('user')
                ->set(array(
                    'role' => $roleId
                ))
                ->where(array(
                    'user_id' => $userId
                ))
                ->where(array(
                    new NotInPredicate('user_id', array(self::DEFAULT_USER_ID))
                ));

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();

            // clear a cache
            $this->removeUserCache($userId);

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ErrorLogger::log($e);

            return $e->getMessage();
        }

        return true;
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
        $select->from('user')
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
                ->table('user')
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
            ErrorLogger::log($e);

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
        $cacheName = CacheUtility::getCacheName(self::CACHE_USER_INFO . $userId, array($field));

        // check data in cache
        if (null === ($userInfo = $this->staticCacheInstance->getItem($cacheName))) {
            $select = $this->select();
            $select->from('user')
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
                    'activation_code',
                    'avatar'
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
            $cacheName = CacheUtility::getCacheName(self::CACHE_USER_INFO . $userId, array($field));
            $this->staticCacheInstance->removeItem($cacheName);
        }
    }
}