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
namespace User\Model;

use Acl\Model\AclBase as AclBaseModel;
use Application\Utility\ApplicationCache as CacheUtility;
use Application\Model\ApplicationAbstractBase;
use Application\Service\ApplicationSetting as SettingService;
use Application\Utility\ApplicationFileSystem as FileSystemUtility;
use Application\Utility\ApplicationImage as ImageUtility;
use Application\Utility\ApplicationErrorLogger;
use User\Exception\UserException;
use User\Event\UserEvent;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Predicate\NotIn as NotInPredicate;
use Zend\Db\Sql\Predicate\IsNull as IsNullPredicate;
use Exception;

class UserBase extends ApplicationAbstractBase
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
     * Cache active users
     */
    const CACHE_ACTIVE_USERS = 'User_Active';

    /**
     * Cache user info
     */
    const CACHE_USER_INFO = 'User_Info_';

    /**
     * User cache data tag
     */
    const CACHE_USER_DATA_TAG = 'User_Data_Tag';

    /**
     * User specific cache data tag
     */
    const CACHE_USER_SPECIFIC_DATA_TAG = 'User_Specific_Data_Tag_';

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
     * User slug length
     */
    const USER_SLUG_LENGTH = 40;

    /**
     * User info fields
     *
     * @var array
     */
    protected $userInfoFields = [
        self::USER_INFO_BY_ID,
        self::USER_INFO_BY_EMAIL,
        self::USER_INFO_BY_SLUG,
        self::USER_INFO_BY_API_KEY
    ];

    /**
     * List of private fields
     *
     * @var array
     */
    protected $privateFields = [
        'email',
        'api_key',
        'api_secret',
        'activation_code'
    ];

    /**
     * Avatars directory
     *
     * @var string
     */
    protected static $avatarsDir = 'user/';

    /**
     * Thumbnails directory
     *
     * @var string
     */
    protected static $thumbnailsDir = 'user/thumbnail/';

    /**
     * User info
     *
     * @var array
     */
    protected static $userInfo = [];

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
                ->set([
                    'status' => ($approved ? self::STATUS_APPROVED : self::STATUS_DISAPPROVED),
                    'activation_code' => null,
                    'date_edited' => date('Y-m-d')
                ])
                ->where([
                    'user_id' => $userId
                ])
                ->where([
                    new NotInPredicate('user_id', [self::DEFAULT_USER_ID])
                ]);

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();

            // clear caches
            $this->removeUserCache($userId);
            $this->clearActiveUsersCache();

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

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
     *      string slug optional
     *      string email required
     *      string password optional
     *      string time_zone optional
     * @param boolean $statusApproved
     * @param array $avatar
     * @param boolean $deleteAvatar
     * @param boolean $selfEdit
     * @return boolean|string
     */
    public function editUser($userInfo, array $formData, $statusApproved = true, array $avatar = [], $deleteAvatar = false, $selfEdit = false)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $extraValues = [
               'status' => $statusApproved ? self::STATUS_APPROVED : self::STATUS_DISAPPROVED,
               'date_edited' => date('Y-m-d')
            ];

            // generate a new slug
            if (empty($formData['slug'])) {
                $extraValues['slug'] = $this->
                        generateSlug($userInfo['user_id'], $formData['nick_name'], 'user_list', 'user_id', self::USER_SLUG_LENGTH);
            }

            // regenerate the user's password
            if (!empty($formData['password'])) {
                $extraValues = array_merge($extraValues, [
                    'password' => $this->generatePassword($formData['password'])
                ]);
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
                ->where([
                    'user_id' => $userInfo['user_id']
                ]);

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();

            // upload the user's avatar
            $this->uploadAvatar($userInfo['user_id'], $avatar, $userInfo['avatar'], $deleteAvatar);

            // clear caches
            $this->removeUserCache($userInfo['user_id']);
            $this->clearActiveUsersCache();

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

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
     * @throws \User\Exception\UserException
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

            // upload a new one
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
                ->set([
                    'avatar' => $avatarName
                ])
                ->where(['user_id' => $userId]);

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
                ->set([
                    'avatar' => ''
                ])
                ->where(['user_id' => $userId]);

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
        $avatarTypes = [
            self::$thumbnailsDir,
            self::$avatarsDir
        ];

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
     * @return boolean|string
     */
    public function deleteUser(array $userInfo, $sendMessage = true)
    {
        // fire the delete user event
        UserEvent::fireUserDeleteEvent($userInfo['user_id'], ($sendMessage ? $userInfo : []));

        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $delete = $this->delete()
                ->from('user_list')
                ->where([
                    'user_id' => $userInfo['user_id']
                ])
                ->where([
                    new NotInPredicate('user_id', [self::DEFAULT_USER_ID])
                ]);

            $statement = $this->prepareStatementForSqlObject($delete);
            $result = $statement->execute();

            // delete an avatar
            if ($userInfo['avatar']) {
                if (true !== ($avatarDeleteResult = $this->deleteUserAvatar($userInfo['avatar']))) {
                    throw new UserException('Avatar deleting failed');
                }
            }

            // clear caches
            $this->removeUserCache($userInfo['user_id']);
            $this->clearActiveUsersCache();

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        return $result->count() ? true : false;
    }

    /**
     * Add a new user
     *
     * @param array $formData
     *      string nick_name required
     *      string slug optional
     *      string email required
     *      string password required
     *      string time_zone optional
     * @param string $language
     * @param boolean $statusApproved
     * @param array $avatar
     * @param boolean $retArray
     * @return array|integer|string
     */
    public function addUser(array $formData, $language, $statusApproved = true, array $avatar = [], $retArray = false)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            if (!$formData['time_zone']) {
                $formData['time_zone'] = null;
            }

            $insert = $this->insert()
                ->into('user_list')
                ->values(array_merge($formData, [
                    'role' => AclBaseModel::DEFAULT_ROLE_MEMBER,
                    'status' => $statusApproved ? self::STATUS_APPROVED : self::STATUS_DISAPPROVED,
                    'language' => $language,
                    'registered' => time(),
                    'password' => $this->generatePassword($formData['password']),
                    'api_secret' => $this->generateRandString(),
                    'activation_code' => !$statusApproved // generate an activation code
                        ? $this->generateRandString()
                        : null,
                    'date_edited' => date('Y-m-d')
                ]));

            $statement = $this->prepareStatementForSqlObject($insert);
            $statement->execute();
            $insertId = $this->adapter->getDriver()->getLastGeneratedValue();

            // generate a new api key
            $updateFields = [
                'api_key' => $insertId . '_' . $this->generateRandString()
            ];

            // generate slug automatically
            if (empty($formData['slug'])) {
                $updateFields['slug'] = $this->generateSlug($insertId,
                        $formData['nick_name'], 'user_list', 'user_id', self::USER_SLUG_LENGTH);
            }

            // update some fields
            $update = $this->update()
                ->table('user_list')
                ->set($updateFields)
                ->where([
                    'user_id' => $insertId
                ]);

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();

            // upload the user's avatar
            $this->uploadAvatar($insertId, $avatar);
            $this->clearActiveUsersCache();

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        if ($retArray) {
            $userInfo = (array) $this->getUserInfo($insertId);

            // fire the add user event
            UserEvent::fireUserAddEvent($insertId, $userInfo);
            return $userInfo;
        }

        // fire the add user event
        UserEvent::fireUserAddEvent($insertId);

        return $insertId;
    }

    /**
     * Generate a password
     *
     * @param string $password
     * @return string
     */
    protected function generatePassword($password)
    {
        return sha1(md5($password) . $this->serviceLocator->get('Config')['site_salt']);
    }

    /**
     * Is slug free
     *
     * @param string $slug
     * @param integer $userId
     * @return boolean
     */
    public function isSlugFree($slug, $userId = 0)
    {
        $select = $this->select();
        $select->from('user_list')
            ->columns([
                'user_id'
            ])
            ->where(['slug' => $slug]);

        if ($userId) {
            $select->where([
                new NotInPredicate('user_id', [$userId])
            ]);
        }

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        return $resultSet->current() ? false : true;
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
            ->columns([
                'user_id'
            ])
            ->where(['email' => $email]);

        if ($userId) {
            $select->where([
                new NotInPredicate('user_id', [$userId])
            ]);
        }

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        $resultSet->initialize($statement->execute());

        return $resultSet->current() ? false : true;
    }

    /**
     * Update users with empty roles
     *
     * @param integer $roleId
     * @return boolean|string
     */
    public function updateUsersWithEmptyRoles($roleId)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $update = $this->update()
                ->table('user_list')
                ->set([
                    'role' => $roleId,
                    'date_edited' => date('Y-m-d')
                ])
                ->where([
                    new IsNullPredicate('role')
                ]);

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();

            // clear all users cache
            $this->staticCacheInstance->clearByTags([
                self::CACHE_USER_DATA_TAG
            ]);

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        return true;
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
                ->set([
                    'role' => $roleId,
                    'date_edited' => date('Y-m-d')
                ])
                ->where([
                    'user_id' => $userId
                ])
                ->where([
                    new NotInPredicate('user_id', [self::DEFAULT_USER_ID])
                ]);

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();

            // clear a cache
            $this->removeUserCache($userId);

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

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
            ->columns([
                'user_id'
            ])
            ->where(['nick_name' => $nickName]);

        if ($userId) {
            $select->where([
                new NotInPredicate('user_id', [$userId])
            ]);
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
                ->set([
                    'language' => $language,
                    'date_edited' => date('Y-m-d')
                ])
                ->where([
                    'user_id' => $userId
                ]);

            $statement = $this->prepareStatementForSqlObject($update);
            $statement->execute();

            // clear a cache
            $this->removeUserCache($userId);

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ApplicationErrorLogger::log($e);

            return $e->getMessage();
        }

        return true;
    }

    // TODO: is it safe ?

    /**
     * Get all active users
     *
     * @return array
     */
    public function getAllActiveUsers()
    {
        // generate a cache name
        $cacheName = CacheUtility::getCacheName(self::CACHE_ACTIVE_USERS);

        // check data in cache
        if (null === ($users = $this->staticCacheInstance->getItem($cacheName))) {
            $select = $this->select();
            $select->from('user_list')
                ->columns([
                    'user_id',
                    'nick_name',
                    'slug',
                    'date_edited'
                ])
                ->where([
                    'status' => self::STATUS_APPROVED
                ]);

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());
            $users = $resultSet->toArray();

            // save data in cache
            $this->staticCacheInstance->setItem($cacheName, $users);
            $this->staticCacheInstance->setTags($cacheName, [self::CACHE_USER_DATA_TAG]);    
        }

        return $users;
    }

    /**
     * Clear active users cache
     *
     * @return boolean
     */
    public function clearActiveUsersCache()
    {
        $cacheName = CacheUtility::getCacheName(self::CACHE_ACTIVE_USERS);

        if ($this->staticCacheInstance->hasItem($cacheName)) {
            return $this->staticCacheInstance->removeItem($cacheName);
        }

        return false;
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
        // check data in a memory
        if (isset(self::$userInfo[$userId][$field])) {
            return self::$userInfo[$userId][$field];
        }

        // process a field name
        $field = in_array($field, $this->userInfoFields)
            ? $field
            : self::USER_INFO_BY_ID;

        // generate a cache name
        $cacheName = CacheUtility::getCacheName(self::CACHE_USER_INFO . $userId, [$field]);

        // check data in cache
        if (null === ($userInfo = $this->staticCacheInstance->getItem($cacheName))) {
            $select = $this->select();
            $select->from(['a' =>'user_list'])
                ->columns([
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
                ])
                ->join(
                    ['b' => 'acl_role'],
                    'a.role = b.id',
                    [
                        'role_name' => 'name'
                    ],
                    'left'
                )
                ->join(
                    ['c' => 'application_time_zone'],
                    'a.time_zone = c.id',
                    [
                        'time_zone_name' => 'name'
                    ],
                    'left'
                )
                ->where([
                    $field => $userId
                ]);

            $statement = $this->prepareStatementForSqlObject($select);
            $resultSet = new ResultSet;
            $resultSet->initialize($statement->execute());
            $userInfo = $resultSet->current() ? (array) $resultSet->current() : [];

            // save data in cache
            if ($userInfo) {
                $this->staticCacheInstance->setItem($cacheName, $userInfo);
                $this->staticCacheInstance->setTags($cacheName, [
                    self::CACHE_USER_DATA_TAG, 
                    self::CACHE_USER_SPECIFIC_DATA_TAG . $userInfo['user_id']
                ]);
            }
        }

        self::$userInfo[$userId][$field] = $userInfo;

        return $userInfo;
    }

    /**
     * Remove the user cache
     *
     * @param integer $userId
     * @return boolean
     */
    protected function removeUserCache($userId)
    {
        return $this->staticCacheInstance->clearByTags([
            self::CACHE_USER_SPECIFIC_DATA_TAG . $userId
        ]);
    }
}