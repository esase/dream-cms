<?php

namespace Application\Utility;
use Application\Service\Service as ApplicationService;
use Zend\Mail\Message;
use Zend\Mail\Transport\Sendmail as SendmailTransport;
use Zend\Mail\Transport\Smtp as SmtpTransport;
use Zend\Mail\Transport\SmtpOptions;
use Zend\Mime\Part as MimePart;
use Zend\Mime\Message as MimeMessage;
use Application\Event\Event as ApplicationEvent;
use Application\Model\Acl as AclModelBase;

class EmailNotification
{
    /**
     * Send a notification
     *
     * @param string $email
     * @param string $subject
     * @param string $message
     * @param array $replacements
     *      array find
     *      array replace
     * @param string $replaceLeftBacket
     * @param string $replaceRightBacket
     * @return boolean
     */
    public static function sendNotification($email, $subject, $message,
            array $replacements = array(), $replaceLeftBacket = '{', $replaceRightBacket = '}')
    {
        // fire the event
        $result = ApplicationEvent::fireEvent(ApplicationEvent::SEND_EMAIL_NOTIFICATION,
                $email, AclModelBase::DEFAULT_SYSTEM_ID, 'Event - Email notification will be send', array($email, $subject));

        if ($result->stopped()) {
            return false;
        }

        // replace special markers
        if (isset($replacements['find'], $replacements['replace'])) {
            // process replacements
            $replacements['find'] = array_map(function($value) use($replaceLeftBacket, $replaceRightBacket) {
                return $replaceLeftBacket . $value . $replaceRightBacket;
            }, $replacements['find']);
     
            $message = str_replace($replacements['find'], $replacements['replace'], $message);
        }

        // add the mime type
        $message = new MimePart($message);
        $message->type = 'text/html';

        $body = new MimeMessage();
        $body->setParts(array($message));

        $messageInstance = new Message();
        $messageInstance->addFrom(ApplicationService::getSetting('notification_from'))
            ->addTo($email)
            ->setSubject($subject)
            ->setBody($body)
            ->setEncoding('UTF-8');

        // should we use SMTP?
        if ((int) ApplicationService::getSetting('use_smtp')) {
            $transport = new SmtpTransport();
            $options = new SmtpOptions(array(
                'host' => ApplicationService::getSetting('smtp_host'),
                'connection_class' => 'login',
                'connection_config' => array(
                    'ssl' => 'tls',
                    'username' => ApplicationService::getSetting('smtp_user'),
                    'password' => ApplicationService::getSetting('smtp_password')
                ),
                'port' => ApplicationService::getSetting('smtp_port'),
            ));

            $transport->setOptions($options);
        }
        else {
            $transport = new SendmailTransport();
        }

        return $transport->send($messageInstance);
    }
}