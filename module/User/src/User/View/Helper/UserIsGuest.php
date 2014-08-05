<?php
namespace User\View\Helper;
 
use Zend\View\Helper\AbstractHelper;

class UserIsGuest extends AbstractHelper
{
    /**
     * Is guest
     * @var boolean
     */
    protected $isGuest;

    /**
     * Class constructor
     *
     * @param boolean $isGuest
     */
    public function __construct($isGuest)
    {
        $this->isGuest = $userIdentity;
    }

    /**
     * Is guest
     *
     * @return boolean
     */
    public function __invoke()
    {
        return $this->isGuest;
    }
}