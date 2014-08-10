<?php
namespace User\Model;

use Acl\Model\Base as AclModelBase;
use Application\Utility\Cache as CacheUtility;
use Application\Model\AbstractBase;
use Application\Service\Setting as SettingService;
use Localization\Service\Localization as LocalizationService;
use Application\Utility\FileSystem as FileSystemUtility;
use Application\Utility\Image as ImageUtility;
use Application\Utility\ErrorLogger;
use User\Exception\UserException;
use User\Event\Event as UserEvent;
use User\Service\Service as UserService;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Predicate\NotIn as NotInPredicate;
use Zend\Db\Sql\Predicate\isNull as IsNullPredicate;
use Exception;

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
     * User cache data tag
     */
    const CACHE_USER_DATA_TAG = 'User_Data_Tag';

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
     * @param array $userInfo
     * @param string $selfUserName
     * @return boolean|string
     */
    public function setUserStatus($userId, $approved = true, array $userInfo, $selfUserName = null)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $update = $this->update()
                ->table('user_list')
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

        true === $approved
            ? UserEvent::fireUserApproveEvent($userId, $userInfo, $selfUserName)
            : UserEvent::fireUserDisapproveEvent($userId, $userInfo);

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
     * @param boolean $selfEdit
     * @return boolean|string
     */
    public function editUser($userInfo, array $formData, $statusApproved = true, array $avatar = array(), $deleteAvatar = false, $selfEdit = false)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            // generate a new slug
            $extraValues = array(
                'slug' => $this->generateSlug($userInfo['user_id'], 
                        $formData['nick_name'], 'user_list', 'user_id', self::USER_SLUG_LENGTH),

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

            if (!$formData['time_zone']) {
                $formData['time_zone'] = null;
            }

            $update = $this->update()
                ->table('user_list')
                ->set(array_merge($formData, $extraValues))
                ->where(array(
                    'user_id' => $userInfo['user_id']
                ));

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();

            // upload the user's avatar
            $this->uploadAvatar($userInfo['user_id'], $avatar, $userInfo['avatar'], $deleteAvatar);

            // clear a cache
            $this->removeUserCache($userInfo['user_id']);

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ErrorLogger::log($e);

            return $e->getMessage();
        }

        // fire the edit user event
        UserEvent::fireUserEditEvent($userInfo['user_id'], $selfEdit);
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
     * @throws User\Exception\UserException
     * @return void
     */
    protected function uploadAvatar($userId, array $avatar, $oldAvatar = null, $deleteAvatar = false)
    {
        // upload the user's avatar
        if (!empty($avatar['name'])) {
            // delete old avatar
            if ($oldAvatar) {
                if (true !== ($result = $this->deleteUserAvatar($oldAvatar))) {
                    throw new UserException('Avatar deleting failed');
                }
            }

            // upload the new
            if (false === ($avatarName =
                    FileSystemUtility::uploadResourceFile($userId, $avatar, self::$avatarsDir))) {

                throw new UserException('Avatar uploading failed');
            }

            // resize the avatar
            ImageUtility::resizeResourceImage($avatarName, self::$avatarsDir,
                    (int) SettingService::getSetting('user_thumbnail_width'),
                    (int) SettingService::getSetting('user_thumbnail_height'), self::$thumbnailsDir);

            ImageUtility::resizeResourceImage($avatarName, self::$avatarsDir,
                    (int) SettingService::getSetting('user_avatar_width'),
                    (int) SettingService::getSetting('user_avatar_height'));

            $update = $this->update()
                ->table('user_list')
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
                throw new UserException('Avatar deleting failed');
            }

            $update = $this->update()
                ->table('user_list')
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
     * @param boolean $sendMessage
     * @throws User/Exception/UserException
     * @return boolean|string
     */
    public function deleteUser($userInfo, $sendMessage = true)
    {
        // fire the delete user event
        UserEvent::fireUserDeleteEvent($userInfo['user_id'], ($sendMessage ? $userInfo : array()));

        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $delete = $this->delete()
                ->from('user_list')
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
                    throw new UserException('Avatar deleting failed');
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
     * @param boolean $retArray
     * @return array|integer|string
     */
    public function addUser(array $formData, $statusApproved = true, array $avatar = array(), $retArray = false)
    {
        $insertId = 0;

        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            // generate a password salt
            $passwordSalt = $this->generateRandString();

            if (!$formData['time_zone']) {
                $formData['time_zone'] = null;
            }

            $insert = $this->insert()
                ->into('user_list')
                ->values(array_merge($formData, array(
                    'role' => AclModelBase::DEFAULT_ROLE_MEMBER,
                    'status' => $statusApproved ? self::STATUS_APPROVED : self::STATUS_DISAPPROVED,
                    'language' => LocalizationService::getCurrentLocalization()['language'],
                    'registered' => time(),
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
                ->table('user_list')
                ->set(array(
                    'api_key' => $insertId . '_' . $this->generateRandString(),
                    'slug' => $this->generateSlug($insertId, $formData['nick_name'], 'user_list', 'user_id', self::USER_SLUG_LENGTH)
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

        if ($retArray) {
            $userInfo = $this->getUserInfo($insertId);

            // fire the add user event
            UserEvent::fireUserAddEvent($insertId, $userInfo);
            return (array) $userInfo;
        }

        // fire the add user event
        UserEvent::fireUserAddEvent($insertId);
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
        $select->from('user_list')
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
        $select->from('user_list')
            ->columns(array(
                'user_id',
                'nick_name',
                'email',
                'language'
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
     * @param string $roleName
     * @param array $userInfo
     *      string language
     *      string email
     *      string nick_name
     *      integer user_id
     * @param boolean $isSystem
     * @return boolean|string
     */
    public function editUserRole($userId, $roleId, $roleName, array $userInfo, $isSystem = false)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $update = $this->update()
                ->table('user_list')
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

        // fire the edit user role event
        UserEvent::fireEditRoleEvent($userInfo, $roleName, $isSystem);
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
        $select->from('user_list')
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
                ->table('user_list')
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
            $select->from(array('a' =>'user_list'))
                ->columns(array(
                    'user_id',
                    'nick_name',
                    'slug',
                    'status',
                    'email',
                    'phone',
                    'first_name',
                    'last_name',
                    'address',
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
                ->join(
                    array('b' => 'acl_role'),
                    'a.role = b.id',
                    array(
                        'role_name' => 'name'
                    ),
                    'left'
                )
                ->join(
                    array('c' => 'application_time_zone'),
                    'a.time_zone = c.id',
                    array(
                        'time_zone_name' => 'name'
                    ),
                    'left'
                )
                ->where(array(
                    $field => $userId
                ));

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());
            $userInfo = $resultSet->current();

            // save data in cache
            $this->staticCacheInstance->setItem($cacheName, $userInfo);
            $this->staticCacheInstance->setTags($cacheName, array(self::CACHE_USER_DATA_TAG));
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