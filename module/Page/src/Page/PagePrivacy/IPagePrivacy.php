<?php
namespace Page\PagePrivacy;

interface IPagePrivacy
{
    /**
     * Set object id
     * 
     * @param string|integer $objectId
     * @return object fluent interface
     */
    public function setObjectId($objectId);

    /**
     * Is allowed to view
     *
     * @param array $privacyOptions
     * @param boolean $trustedData
     * @return boolean
     */
    public function isAllowedViewPage(array $privacyOptions = [], $trustedData = false);
}