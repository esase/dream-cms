<?php
namespace Page\PagePrivacy;

abstract class PageAbstractPagePrivacy implements IPagePrivacy
{
    /**
     * Object id
     * @var string|integer 
     */
    protected $objectId;

    /**
     * Set object id
     * 
     * @param string|integer $objectId
     * @return object fluent interface
     */
    public function setObjectId($objectId)
    {
        $this->objectId = $objectId;
        return $this;
    }
}