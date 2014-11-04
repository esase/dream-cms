<?php
namespace Page\PagePrivacy;

class Page404Privacy extends PageAbstractPagePrivacy
{
    /**
     * Page 404 privacy
     *
     * @param array $privacyOptions
     * @return boolean
     */
    public function isAllowedViewPage(array $privacyOptions = [])
    {
        if (empty($privacyOptions['skip_checking'])) {
            return false;
        }

        return true;
    }
}