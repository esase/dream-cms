<?php

namespace Application\Controller;

use Application\Event\ApplicationEvent;
use Application\Utility\ApplicationErrorLogger;
use Zend\Mail\Message;
use Zend\Mail\Transport\Sendmail as SendmailTransport;
use Zend\Mail\Transport\Smtp as SmtpTransport;
use Zend\Mail\Transport\SmtpOptions;
use Zend\Mime\Part as MimePart;
use Zend\Mime\Message as MimeMessage;
use Exception;

class ApplicationEmailQueueConsoleController extends ApplicationAbstractBaseConsoleController
{
    /**
     * Model instance
     * @var object  
     */
    protected $model;

    /**
     * Message transport
     * @var object
     */
    protected $messageTransport;

    /**
     * Get model
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = $this->getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('Application\Model\ApplicationEmailQueue');
        }

        return $this->model;
    }

    /**
     * Get message transport
     *
     * @return object
     */
    protected function getMessageTransport()
    {
        if (!$this->messageTransport) {
            // should we use SMTP?
            if ((int) $this->applicationSetting('application_use_smtp')) {
                $this->messageTransport = new SmtpTransport;

                // get connection config
                $connectionConfig = [];

                // get smtp ssl
                if (null != ($smtpSsl = $this->applicationSetting('application_smtp_ssl'))) {
                    $connectionConfig['ssl'] = $smtpSsl;
                }

                // get smtp user
                if (null != ($smtpUsername = $this->applicationSetting('application_smtp_user'))) {
                    $connectionConfig['username'] = $smtpUsername;
                }

                // get smtp password
                if (null != ($smtpPassword = $this->applicationSetting('application_smtp_password'))) {
                    $connectionConfig['password'] = $smtpPassword;
                }

                // set global options
                $globalOptions = [];

                // get smtp host
                if (null != ($smtpHost = $this->applicationSetting('application_smtp_host'))) {
                    $globalOptions['host'] = $smtpHost;
                }

                // get connection class
                if (null != ($smtpConnection = $this->applicationSetting('application_smtp_login'))) {
                    $globalOptions['connection_class'] = $smtpConnection;
                }

                // get connection config
                if ($connectionConfig) {
                    $globalOptions['connection_config'] = $connectionConfig;
                }

                // get smtp port
                if (null != ($smtpPort = $this->applicationSetting('application_smtp_port'))) {
                    $globalOptions['port'] = $smtpPort;
                }

                $options = new SmtpOptions($globalOptions);
                $this->messageTransport->setOptions($options);
                return $this->messageTransport;
            }

            $this->messageTransport = new SendmailTransport;
        }

        return $this->messageTransport;
    }

    /**
     * Send messages
     */
    public function sendMessagesAction()
    {
        $request = $this->getRequest();
        $sentMessages  = 0;

        // get a list of messages from a messages queue
        try {
            if (null != ($messagesList = $this->getModel()->
                    getMessages((int) $this->applicationSetting('application_notifications_count')))) {

                foreach ($messagesList as $messageInfo) {
                    // add the mime type
                    $message = new MimePart($messageInfo->message);
                    $message->type = 'text/html';

                    $body = new MimeMessage();
                    $body->setParts([$message]);

                    $messageInstance = new Message();
                    $messageInstance->addFrom($this->applicationSetting('application_notification_from'))
                        ->addTo($messageInfo->email)
                        ->setSubject($messageInfo->title)
                        ->setBody($body)
                        ->setEncoding('UTF-8');

                    $this->getMessageTransport()->send($messageInstance);
                    $sentMessages++;

                    // fire the send email notification event
                    ApplicationEvent::fireSendEmailNotificationEvent($messageInfo->email, $messageInfo->title);

                    // delete the message from the messages queue
                    $this->getModel()->deleteMessage($messageInfo->id);
                }
            }
        }
        catch (Exception $e) {
            ApplicationErrorLogger::log($e);
            return 'Error: ' . $e->getMessage() . "\n";
        }

        $verbose = $request->getParam('verbose');

        if (!$verbose) {
            return 'Messages have been sent.' . "\n";
        }

        return $sentMessages  . ' messages have been sent.' . "\n";
    }
}