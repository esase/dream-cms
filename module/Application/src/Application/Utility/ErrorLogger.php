<?php

namespace Application\Utility;

use Zend\Log\Logger as Logger;
use Zend\Log\Writer\Stream as LogWriterStream;
use Application\Service\Service as ApplicationService;
use User\Service\Service as UserService;
use Application\Utility\EmailNotification;

class ErrorLogger
{
    /**
     * Log an error
     *
     * @param string $errorMessage
     * @return boolean
     */
    public static function log($errorMessage)
    {
        try {
            $writer = new LogWriterStream(ApplicationService::getServiceManager()->get('Config')['paths']['error_log']);
            $logger = new Logger();
            $logger->addWriter($writer);
            $logger->err($errorMessage);

            // do we need send this error via email?
            if (null != ($errorEmail = ApplicationService::getSetting('application_errors_notification_email'))) {
                EmailNotification::sendNotification($errorEmail,
                    ApplicationService::getSetting('application_error_notification_title', UserService::getDefaultLocalization()['language']),
                    ApplicationService::getSetting('application_error_notification_message', UserService::getDefaultLocalization()['language']), array(
                        'find' => array(
                            'ErrorDescription'
                        ),
                        'replace' => array(
                            $errorMessage
                        )
                    ));
            }
        }
        catch (Exception $e) {
            return false;
        }

        return true;
    }
}