<?php

namespace Application\Controller;

use Application\Utility\ApplicationEmailNotification;

class ApplicationEmailQueueConsoleController extends ApplicationAbstractBaseConsoleController
{
    /**
     * Model instance
     * @var \Application\Model\ApplicationEmailQueue
     */
    protected $model;

    /**
     * Get model
     *
     * @return \Application\Model\ApplicationEmailQueue
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
     * Send messages
     */
    public function sendMessagesAction()
    {
        $request = $this->getRequest();
        $sentMessages  = 0;

        // get a list of messages from a messages queue
        if (null != ($messagesList = $this->getModel()->
                getMessages((int) $this->applicationSetting('application_notifications_count')))) {

            foreach ($messagesList as $messageInfo) {
                $result = ApplicationEmailNotification::
                        immediatelySendNotification($messageInfo->email, $messageInfo->title, $messageInfo->message);

                if (true !== $result) {
                    return $result;
                }

                // delete the message from the messages queue
                $sentMessages++;
                $this->getModel()->deleteMessage($messageInfo->id);
            }
        }

        $verbose = $request->getParam('verbose');

        if (!$verbose) {
            return 'All messages have been sent.' . "\n";
        }

        return $sentMessages  . ' messages have been sent.' . "\n";
    }
}