<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.dream-cms.kg/en/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Dream CMS software.
 * The Initial Developer of the Original Code is Dream CMS (http://www.dream-cms.kg).
 * All portions of the code written by Dream CMS are Copyright (c) 2014. All Rights Reserved.
 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2014 Dream CMS. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Dream CMS software
 * Attribution URL: http://www.dream-cms.kg/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */
namespace Application\Controller;

use Application\Utility\ApplicationEmailNotification;

class ApplicationEmailQueueConsoleController extends ApplicationAbstractBaseConsoleController
{
    /**
     * Model instance
     *
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