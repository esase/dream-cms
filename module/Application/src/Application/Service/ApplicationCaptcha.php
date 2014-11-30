<?php
namespace Application\Service;

class ApplicationCaptcha
{
    /**
     * Get captcha path
     *
     * @return string
     */
    public static function getCaptchaPath()
    {
        return APPLICATION_PUBLIC .
                '/' . ApplicationServiceLocator::getServiceLocator()->get('Config')['paths']['captcha'] . '/';
    }

    /**
     * Get captcha font path
     *
     * @return string
     */
    public static function getCaptchaFontPath()
    {
        return APPLICATION_PUBLIC . '/' .
                ApplicationServiceLocator::getServiceLocator()->get('Config')['paths']['captcha'] . '/' .
                ApplicationServiceLocator::getServiceLocator()->get('Config')['paths']['captcha_font'];
    }

    /**
     * Get captcha url
     *
     * @return string
     */
    public static function getCaptchaUrl()
    {
        return Application::getApplicationUrl() . '/' .
                ApplicationServiceLocator::getServiceLocator()->get('Config')['paths']['captcha'] . '/';
    }
}