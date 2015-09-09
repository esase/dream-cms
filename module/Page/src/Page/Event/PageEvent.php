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
namespace Page\Event;

use Application\Event\ApplicationAbstractEvent;
use User\Service\UserIdentity as UserIdentityService;

class PageEvent extends ApplicationAbstractEvent
{
    /**
     * Delete page event
     */
    const PAGE_DELETE = 'page_delete';

    /**
     * Show page event
     */
    const PAGE_SHOW = 'page_show';

    /**
     * Add page event
     */
    const PAGE_ADD = 'page_add';

    /**
     * Edit page event
     */
    const PAGE_EDIT = 'page_edit';

    /**
     * Add widget event
     */
    const WIDGET_ADD = 'page_widget_add';

    /**
     * Delete widget event
     */
    const WIDGET_DELETE = 'page_widget_delete';

    /**
     * Change widget position event
     */
    const WIDGET_CHANGE_POSITION = 'page_widget_change_position';

    /**
     * Edit widget settings event
     */
    const WIDGET_EDIT_SETTINGS = 'page_widget_edit_settings';

    /**
     * Fire edit widget settings event
     *
     * @param integer $widgetId
     * @param integer $pageId
     * @return void
     */
    public static function fireEditWidgetSettingsEvent($widgetId, $pageId)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - Widget settings edited by guest'
            : 'Event - Widget settings edited by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$widgetId, $pageId]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $widgetId, $pageId];

        self::fireEvent(self::WIDGET_EDIT_SETTINGS, 
                $widgetId, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }

    /**
     * Fire delete widget event
     *
     * @param integer $widgetId
     * @param integer $pageId
     * @return void
     */
    public static function fireDeleteWidgetEvent($widgetId, $pageId)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - Widget deleted by guest'
            : 'Event - Widget deleted by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$widgetId, $pageId]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $widgetId, $pageId];

        self::fireEvent(self::WIDGET_DELETE, 
                $widgetId, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }

    /**
     * Fire change widget position event
     *
     * @param integer $widgetId
     * @param integer $pageId
     * @return void
     */
    public static function fireChangeWidgetPositionEvent($widgetId, $pageId)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - Widget position changed by guest'
            : 'Event - Widget position changed by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$widgetId, $pageId]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $widgetId, $pageId];

        self::fireEvent(self::WIDGET_CHANGE_POSITION, 
                $widgetId, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }

    /**
     * Fire add widget event
     *
     * @param integer $widgetId
     * @param integer $pageId
     * @return void
     */
    public static function fireAddWidgetEvent($widgetId, $pageId)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - Widget added by guest'
            : 'Event - Widget added by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$widgetId, $pageId]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $widgetId, $pageId];

        self::fireEvent(self::WIDGET_ADD, 
                $widgetId, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }

    /**
     * Fire edit page event
     *
     * @param integer $pageId
     * @return void
     */
    public static function fireEditPageEvent($pageId)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - Page edited by guest'
            : 'Event - Page edited by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$pageId]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $pageId];

        self::fireEvent(self::PAGE_EDIT, 
                $pageId, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }

    /**
     * Fire add page event
     *
     * @param integer $pageId
     * @return void
     */
    public static function fireAddPageEvent($pageId)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - Page added by guest'
            : 'Event - Page added by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$pageId]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $pageId];

        self::fireEvent(self::PAGE_ADD, 
                $pageId, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }

    /**
     * Fire delete page event
     *
     * @param integer $pageId
     * @return void
     */
    public static function fireDeletePageEvent($pageId)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - Page deleted by guest'
            : 'Event - Page deleted by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$pageId]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $pageId];

        self::fireEvent(self::PAGE_DELETE, 
                $pageId, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }

    /**
     * Fire the page show event
     *
     * @param string $pageName
     * @param string $language
     * @return void
     */
    public static function firePageShowEvent($pageName, $language)
    {
        self::fireEvent(self::PAGE_SHOW, $pageName,
                self::getUserId(true), 'Event - Page was shown by the system', [$pageName, $language]);
    }
}