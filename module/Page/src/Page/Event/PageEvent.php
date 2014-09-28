<?php
namespace Page\Event;

use Application\Event\ApplicationAbstractEvent;
use User\Service\UserIdentity as UserIdentityService;

class PageEvent extends ApplicationAbstractEvent
{
    /**
     * Show page event
     */
    const PAGE_SHOW = 'page_show';

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