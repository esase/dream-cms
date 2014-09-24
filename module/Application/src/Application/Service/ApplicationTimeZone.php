<?php
namespace Application\Service;

class ApplicationTimeZone
{
    /**
     * Get time zones
     *
     * @return array
     */
    public static function getTimeZones()
    {
        $timeZoneModel = ApplicationServiceManager::getServiceManager()
            ->get('Application\Model\ModelManager')
            ->getInstance('Application\Model\ApplicationTimeZone');

        return $timeZoneModel->getTimeZones();
    }
}