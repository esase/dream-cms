<?php
namespace Page\PagePrivacy;

class Page404Privacy extends PageAbstractPagePrivacy
{
    /**
     * Page 404 privacy
     *
     * @param array $privacyOptions
     * @param boolean $trustedData
     * @return boolean
     */
    public function isAllowedViewPage(array $privacyOptions = [], $trustedData = false)
    {
        if (!$trustedData) {
            return false;
        }

        return true;
    }
}