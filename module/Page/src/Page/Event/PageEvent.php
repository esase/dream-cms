<?php
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
            : 'Event - Page deleteted by user';

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