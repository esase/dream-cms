<?php

namespace Membership\View\Helper;
 
use Zend\View\Helper\AbstractHelper;
use Application\Service\Service as ApplicationService;
use Membership\Model\Base as MembershipModelBase;

class MembershipImageUrl extends AbstractHelper
{
    /**
     * Membership image url
     *
     * @param sting $image
     * @return string
     */
    public function __invoke($image)
    {
        return ApplicationService::getResourcesUrl() . MembershipModelBase::getImagesDir() . $image;
    }
}
