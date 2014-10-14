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