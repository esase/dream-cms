<?php
namespace Application\Utility;

use Application\Service\ApplicationServiceLocator as ApplicationServiceLocatorService;

class ApplicationEmailNotification
{
    /**
     * Send a notification
     *
     * @param string $email
     * @param string $title
     * @param string $message
     * @param array $replacements
     *      array find
     *      array replace
     * @param string $replaceLeftDevider
     * @param string $replaceRightDevider
     * @return boolean
     */
    public static function sendNotification($email, $title, $message, array $replacements = [], $replaceLeftDevider = '__', $replaceRightDevider = '__')
    {
        // replace special markers
        if (isset($replacements['find'], $replacements['replace'])) {
            // process replacements
            $replacements['find'] = array_map(function($value) use($replaceLeftDevider, $replaceRightDevider) {
                return $replaceLeftDevider . $value . $replaceRightDevider;
            }, $replacements['find']);

            $message = str_replace($replacements['find'], $replacements['replace'], $message);
        }

        $model = ApplicationServiceLocatorService::getServiceLocator()
            ->get('Application\Model\ModelManager')
            ->getInstance('Application\Model\ApplicationEmailQueue');

        if (true !== ($result = $model->createMessage($email, $title, $message))) {
            return false;
        }

        return true;
    }
}