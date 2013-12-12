<?php

namespace Application\Utility;

use Application\Service\Service as ApplicationService;

class Pagination
{
    /**
     * Process per page
     *
     * @param integer $perPage
     * @return integer
     */
    public static function processPerPage($perPage)
    {
        if ((int) $perPage <= 0 ||
                    (int) $perPage > (int) ApplicationService::getSetting('application_max_per_page_range')) {

            // set default value
            $perPage = ApplicationService::getSetting('application_per_page');
        }

        return $perPage;
    }

    /**
     * Get per page ranges
     *
     * @return array
     */
    public static function getPerPageRanges()
    {
        $ranges   =  array();
        $minRange =  (int) ApplicationService::getSetting('application_min_per_page_range');
        $maxRange =  (int) ApplicationService::getSetting('application_max_per_page_range');
        $step     =  (int) ApplicationService::getSetting('application_per_page_step');

        for ($i = $minRange; $i <= $maxRange; $i += $step) {
            $ranges[$i] = $i;
        }

        return $ranges;
    }
}