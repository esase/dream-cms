<?php
namespace Page\PagePrivacy;

interface IPagePrivacy
{
    /**
     * Is allowed to view
     *
     * @param array $privacyOptions
     * @param boolean $trustedData
     * @return boolean
     */
    public function isAllowedViewPage(array $privacyOptions = [], $trustedData = false);
}