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
namespace Application\Utility;

use Zend\Log\Logger as Logger;
use Zend\Log\Writer\Stream as LogWriterStream;
use Application\Service\ApplicationServiceLocator as ServiceLocatorService;
use Application\Service\ApplicationSetting as SettingService;
use Localization\Service\Localization as LocalizationService;

class ApplicationErrorLogger
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
            $writer = new LogWriterStream(ServiceLocatorService::getServiceLocator()->get('Config')['paths']['error_log']);
            $logger = new Logger();
            $logger->addWriter($writer);
            $logger->err($errorMessage);

            // do we need send this error via email?
            if (null != ($errorEmail = SettingService::getSetting('application_errors_notification_email'))) {
                ApplicationEmailNotification::sendNotification($errorEmail,
                    SettingService::getSetting('application_error_notification_title', LocalizationService::getDefaultLocalization()['language']),
                    SettingService::getSetting('application_error_notification_message', LocalizationService::getDefaultLocalization()['language']), [
                        'find' => [
                            'ErrorDescription'
                        ],
                        'replace' => [
                            $errorMessage
                        ]
                    ]);
            }
        }
        catch (Exception $e) {
            return false;
        }

        return true;
    }
}