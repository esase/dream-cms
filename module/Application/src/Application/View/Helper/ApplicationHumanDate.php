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
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;

class ApplicationHumanDate extends AbstractHelper
{
    /**
     * Minute in seconds
     *
     * @var integer
     */
    protected $minuteInSeconds = 60;

    /**
     * Hour in seconds
     *
     * @var integer
     */
    protected $hourInSeconds;

    /**
     * Day in seconds
     *
     * @var integer
     */
    protected $dayInSeconds;

    /**
     * Week in seconds
     *
     * @var integer
     */
    protected $weekInSeconds;

    /**
     * Year in seconds
     *
     * @var integer
     */
    protected $yearInSeconds;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->hourInSeconds = 60 * $this->minuteInSeconds;
        $this->dayInSeconds  = 24 * $this->hourInSeconds;
        $this->weekInSeconds = 7 * $this->dayInSeconds;
        $this->yearInSeconds = 365 * $this->dayInSeconds;
    }

    /**
     * Get human date
     *
     * @param integer $date
     * @param array $options
     *      string format (full, long, medium, short)
     * @param string $locale
     * @param integer $diffDate
     * @return string
     */
    public function __invoke($date, array $options = [], $locale = null, $diffDate = null)
    {
        if (!$diffDate) {
            $diffDate = time();
        }

        $diff = (int) abs($date - $diffDate);

        if ($diff < $this->hourInSeconds) {
            if (($mins = round($diff / $this->minuteInSeconds)) <= 0) {
                $mins = 1;
            }

            $since = sprintf($this->getView()->translatePlural('minute ago', 'minutes ago', $mins), $mins);
        }
        else if ($diff < $this->dayInSeconds && $diff >= $this->hourInSeconds) {
            if (($hours = round($diff / $this->hourInSeconds)) <= 0) {
                $hours = 1;
            }

            $since = sprintf($this->getView()->translatePlural('hour ago', 'hours ago', $hours), $hours);
        }
        else if ($diff < $this->weekInSeconds && $diff >= $this->dayInSeconds) {
            if (($days = round($diff / $this->dayInSeconds)) <= 0) {
                $days = 1;
            }

            $since = sprintf($this->getView()->translatePlural('day ago', 'days ago', $days), $days);
        }
        else if ($diff < 30 * $this->dayInSeconds && $diff >= $this->weekInSeconds ) {
            if (($weeks = round($diff / $this->weekInSeconds)) <= 0) {
                $weeks = 1;
            }

            $since = sprintf($this->getView()->translatePlural('week ago', 'weeks ago', $weeks), $weeks);
        }
        else {
            $since = $this->getView()->applicationDate($date, $options, $locale);
        }

        return $since;
    }
}
