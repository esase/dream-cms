<?php
namespace Application\Service;

class Captcha
{
    /**
     * Get captcha path
     *
     * @return string
     */
    public static function getCaptchaPath()
    {
        return APPLICATION_PUBLIC .
                '/' . ServiceManager::getServiceManager()->get('Config')['paths']['captcha'] . '/';
    }

    /**
     * Get captcha font path
     *
     * @return string
     */
    public static function getCaptchaFontPath()
    {
        return APPLICATION_PUBLIC . '/' .
                ServiceManager::getServiceManager()->get('Config')['paths']['captcha'] . '/' .
                ServiceManager::getServiceManager()->get('Config')['paths']['captcha_font'];
    }

    /**
     * Get captcha url
     *
     * @return string
     */
    public static function getCaptchaUrl()
    {
        return Application::getApplicationUrl() . '/' .
                ServiceManager::getServiceManager()->get('Config')['paths']['captcha'] . '/';
    }
}