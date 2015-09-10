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
namespace Page\View\Helper;

use Zend\View\Helper\AbstractHelper;

class PageFooterMenu extends AbstractHelper
{
    /**
     * Footer menu
     *
     * @var array
     */
    protected $footerMenu = [];

    /**
     * Class constructor
     *
     * @param array $footerMenu
     */
    public function __construct(array $footerMenu = [])
    {
        $this->footerMenu = $footerMenu;
    }

    /**
     * Page footer menu
     *
     * @param integer $maxFooterRows
     * @param integer $maxFooterColumns
     * @return string
     */
    public function __invoke($maxFooterRows, $maxFooterColumns)
    {
        $footerMenuProcessed = $footerMenu = null;
        $headerLink = true;

        $totalIndex = $index  = 0;
        $maxAllowedRows = $maxFooterColumns * $maxFooterRows;

        // process the footer menu
        foreach ($this->footerMenu as $menu) {
            // get a page url
            if (false !== ($pageUrl = $this->getView()->pageUrl($menu['slug']))) {
                // get the page's title
                $pageTitle = $this->getView()->pageTitle($menu);

                if ($headerLink) {
                    $footerMenu .= $this->getView()->partial('page/partial/footer-menu-header', [
                        'url' => $pageUrl,
                        'title' => $pageTitle
                    ]);

                    $headerLink = false;
                }
                else {
                    $footerMenu .= $this->getView()->partial('page/partial/footer-menu-item', [
                        'url' => $pageUrl,
                        'title' => $pageTitle
                    ]);
                }

                $index++;
                $totalIndex++;

                // check end of a column
                if ($index == $maxFooterRows) {
                    $footerMenu .= $this->getView()->partial('page/partial/footer-menu-end');

                    $headerLink = true;
                    $index = 0;
                }

                // wrap content
                if ($totalIndex == $maxAllowedRows) {
                    $footerMenuProcessed .= $this->getView()->partial('page/partial/footer-menu', [
                        'footer_menu' => $footerMenu
                    ]);

                    $footerMenu = null;
                    $headerLink = true;
                    $index = $totalIndex = 0;
                }
            }
        }

        // generate the end of the footer menu
        if ($index) {
            $footerMenu .= $this->getView()->partial('page/partial/footer-menu-end');
        }

        // wrap content
        if ($footerMenu) {
            $footerMenu =  $this->getView()->partial('page/partial/footer-menu', [
                'footer_menu' => $footerMenu
            ]);
        }

        return $footerMenuProcessed . $footerMenu;
    }
}