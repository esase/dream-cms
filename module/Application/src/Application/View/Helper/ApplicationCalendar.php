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

use Application\Service\ApplicationSetting as ApplicationSettingService;
use Zend\View\Helper\AbstractHelper;
use IntlDateFormatter;

class ApplicationCalendar extends AbstractHelper
{
    /** 
     * Wrapper id
     *
     * @var string
     */
    protected $wrapperId;

    /**
     * Calendar init
     *
     * @var boolean
     */
    protected $calendarInit = false;

    /**
     * Url
     *
     * @var string
     */
    protected $url;

    /**
     * Month
     *
     * @var integer
     */
    protected $month;

    /**
     * Year
     *
     * @var integer
     */
    protected $year;

    /**
     * Start date
     *
     * @var integer
     */
    protected $startDate;

    /**
     * Days in month
     *
     * @var integer
     */
    protected $daysInMonth;

    /**
     * End date
     *
     * @var integer
     */
    protected $endDate;

    /**
     * Week offset
     *
     * @var integer
     */
    protected $weekOffset;

    /**
     * Prev month
     *
     * @var integer
     */
    protected $prevMonth;

    /**
     * Prev year
     *
     * @var integer
     */
    protected $prevYear;

    /**
     * Next month
     *
     * @var integer
     */
    protected $nextMonth;

    /**
     * Next year
     *
     * @var integer
     */
    protected $nextYear;

    /**
     * Current date
     *
     * @var string
     */
    protected $currentDate;

    /**
     * Links
     *
     * @var array
     */
    protected $links;

    /**
     * Get calendar
     *
     * @return \Application\View\Helper\ApplicationCalendar
     */
    public function __invoke()
    {
        return $this;
    }

    /**
     * Set url
     *
     * @param string $url
     * @return \Application\View\Helper\ApplicationCalendar
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Set links
     *
     * @param array $links
     *      string title
     *      string url
     * @return \Application\View\Helper\ApplicationCalendar
     */
    public function setLinks(array $links)
    {
        $this->links = $links;

        return $this;
    }

    /**
     * Set wrapper
     *
     * @param string $wrapperId
     * @return \Application\View\Helper\ApplicationCalendar
     */
    public function setWrapperId($wrapperId)
    {
        $this->wrapperId = $wrapperId;

        return $this;
    }

    /**
     * Set month
     *
     * @param integer $month
     * @return \Application\View\Helper\ApplicationCalendar
     */
    public function setMonth($month)
    {
        $month = (int) $month;
        $this->month = $month > 12 ? 12 : ($month < 1 ? 1 : $month);

        return $this;
    }

    /**
     * Set year
     *
     * @param integer $year
     * @return \Application\View\Helper\ApplicationCalendar
     */
    public function setYear($year)
    {
        $year    = (int) $year;
        $minYear = (int) ApplicationSettingService::getSetting('application_calendar_min_year');
        $maxYear = (int) ApplicationSettingService::getSetting('application_calendar_max_year');
        $this->year = $year > $maxYear ? $maxYear : ($year < $minYear ? $minYear : $year);

        return $this;
    }

    /**
     * Get start date
     *
     * @return integer
     */
    public function getStartDate()
    {
        $this->initCalendar();

        return $this->startDate;
    }

    /**
     * Get end date
     *
     * @return integer
     */
    public function getEndDate()
    {
        $this->initCalendar();

        return $this->endDate;
    }

    /**
     * Init calendar
     *
     * @return void
     */
    protected function initCalendar()
    {
        if ($this->calendarInit) {
            return;
        }

        // get current month
        if(!$this->month) {
            $this->month = date('n');
        }

        // get current year
        if (!$this->year) {
            $this->year  = date('Y');
        }

        // get date options
        $this->startDate = mktime(0, 0, 0, $this->month, 1, $this->year);
        $this->daysInMonth = date('t', $this->startDate);
        $this->endDate = mktime(0, 0, 0, $this->month, $this->daysInMonth, $this->year);
        $this->weekOffset = date('w', $this->startDate) - 1;

        if ($this->weekOffset < 0) {
            $this->weekOffset = 6;
        }

        $this->prevMonth = $this->month - 1;
        $this->prevYear = $this->year;

        if ($this->month == 1) {
            $this->prevMonth = 12;
            $this->prevYear = $this->year - 1;
        }

        $this->nextMonth = $this->month + 1;
        $this->nextYear = $this->year;

        if ($this->month == 12) {
            $this->nextMonth = 1;
            $this->nextYear = $this->year + 1;
        }

        $this->currentDate = date('Y-m-d');
        $this->calendarInit = true;
    }

    /**
     * Get calendar
     *
     * @return string
     */
    public function getCalendar()
    {
        $this->initCalendar();

        return $this->getView()->partial('partial/calendar', [
            'links' => $this->links,
            'wrapper' => $this->wrapperId,
            'prev_month' => $this->prevMonth,
            'prev_year' => $this->prevYear,
            'next_month' => $this->nextMonth,
            'next_year' => $this->nextYear,
            'url' => $this->url,
            'month' => $this->month,
            'year' => $this->year,
            'week_offset' => $this->weekOffset,
            'days_in_month' => $this->daysInMonth,
            'current_date' => $this->currentDate,
            'month_name' => $this->getView()->
                    dateFormat($this->startDate, IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, 'LLLL, yyyy')
        ]);
    }
}