<?php
namespace Layout\Utility;

use Application\Service\ApplicationSetting as SettingService;
use Application\Service\ApplicationServiceLocator as ServiceLocatorService;
use Layout\Module as LayoutModule;
use Zend\Http\Header\SetCookie;

class LayoutCookie
{
    /**
     * Save layout
     *
     * @param integer $layoutId
     * @return void
     */
    public static function saveLayout($layoutId)
    {
        $header = new SetCookie();
        $header->setName(LayoutModule::LAYOUT_COOKIE)
            ->setValue($layoutId)
            ->setPath('/')
            ->setExpires(time() + (int) SettingService::getSetting('layout_select_cookie_time'));

        ServiceLocatorService::getServiceLocator()->get('Response')->getHeaders()->addHeader($header);
    }
}