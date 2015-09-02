<?php

namespace Application\Utility;

use Application\Event\ApplicationEvent;
use Application\Service\ApplicationSetting as ApplicationSettingService;
use Application\Service\ApplicationServiceLocator as ApplicationServiceLocatorService;
use Zend\Mime\Part as MimePart;
use Zend\Mime\Message as MimeMessage;
use Zend\Mail\Message;
use Zend\Mail\Transport\Sendmail as SendmailTransport;
use Zend\Mail\Transport\Smtp as SmtpTransport;
use Zend\Mail\Transport\SmtpOptions;
use Exception;

class ApplicationEmailNotification
{
    /**
     * Message transport
     * @var \Zend\Mail\Transport\TransportInterface
     */
    protected static $messageTransport;

    /**
     * Send a notification
     *
     * @param string $email
     * @param string $title
     * @param string $message
     * @param array $replacements
     *      array find
     *      array replace
     * @param boolean $highPriority
     * @param string $replaceLeftDivider
     * @param string $replaceRightDivider
     * @return boolean
     */
    public static function sendNotification($email, $title, $message, array $replacements = [], $highPriority = false, $replaceLeftDivider = '__', $replaceRightDivider = '__')
    {
        // replace special markers
        if (isset($replacements['find'], $replacements['replace'])) {
            // process replacements
            $replacements['find'] = array_map(function($value) use($replaceLeftDivider, $replaceRightDivider) {
                return $replaceLeftDivider . $value . $replaceRightDivider;
            }, $replacements['find']);

            $message = str_replace($replacements['find'], $replacements['replace'], $message);
        }

        // immediately send notification
        if ($highPriority) {
            if (true === ($result = self::immediatelySendNotification($email, $title, $message))) {
                return true;
            }

            return false;
        }

        // add message in a queue
        $model = ApplicationServiceLocatorService::getServiceLocator()
            ->get('Application\Model\ModelManager')
            ->getInstance('Application\Model\ApplicationEmailQueue');

        if (true !== ($result = $model->createMessage($email, $title, $message))) {
            return false;
        }

        return true;
    }

    /**
     * Immediately send notification
     *
     * @param string $email
     * @param string $title
     * @param string $messageDesc
     * @return boolean|string
     */
    public static function immediatelySendNotification($email, $title, $messageDesc)
    {
        try {
            // fire the send email notification event
            ApplicationEvent::fireSendEmailNotificationEvent($email, $title);

            // add the mime type
            $message = new MimePart($messageDesc);
            $message->type = 'text/html';

            $body = new MimeMessage();
            $body->setParts([$message]);

            $messageInstance = new Message();
            $messageInstance->addFrom(ApplicationSettingService::getSetting('application_notification_from'))
                ->addTo($email)
                ->setSubject($title)
                ->setBody($body)
                ->setEncoding('UTF-8');

            self::getMessageTransport()->send($messageInstance);
        }
        catch (Exception $e) {
            ApplicationErrorLogger::log($e);
            return 'Error: ' . $e->getMessage() . "\n";
        }

        return true;
    }

    /**
     * Get message transport
     *
     * @return \Zend\Mail\Transport\TransportInterface
     */
    protected static function getMessageTransport()
    {
        if (!self::$messageTransport) {
            // should we use SMTP?
            if ((int) ApplicationSettingService::getSetting('application_use_smtp')) {
                self::$messageTransport = new SmtpTransport;

                // get connection config
                $connectionConfig = [];

                // get smtp ssl
                if (null != ($smtpSsl = ApplicationSettingService::getSetting('application_smtp_ssl'))) {
                    $connectionConfig['ssl'] = $smtpSsl;
                }

                // get smtp user
                if (null != ($smtpUsername = ApplicationSettingService::getSetting('application_smtp_user'))) {
                    $connectionConfig['username'] = $smtpUsername;
                }

                // get smtp password
                if (null != ($smtpPassword = ApplicationSettingService::getSetting('application_smtp_password'))) {
                    $connectionConfig['password'] = $smtpPassword;
                }

                // set global options
                $globalOptions = [];

                // get smtp host
                if (null != ($smtpHost = ApplicationSettingService::getSetting('application_smtp_host'))) {
                    $globalOptions['host'] = $smtpHost;
                }

                // get connection class
                if (null != ($smtpConnection = ApplicationSettingService::getSetting('application_smtp_login'))) {
                    $globalOptions['connection_class'] = $smtpConnection;
                }

                // get connection config
                if ($connectionConfig) {
                    $globalOptions['connection_config'] = $connectionConfig;
                }

                // get smtp port
                if (null != ($smtpPort = ApplicationSettingService::getSetting('application_smtp_port'))) {
                    $globalOptions['port'] = $smtpPort;
                }

                $options = new SmtpOptions($globalOptions);
                self::$messageTransport->setOptions($options);

                return self::$messageTransport;
            }

            self::$messageTransport = new SendmailTransport;
        }

        return self::$messageTransport;
    }
}