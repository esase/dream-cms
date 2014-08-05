<?php
namespace User\View\Helper;
 
use Zend\View\Helper\AbstractHelper;

class UserIdentity extends AbstractHelper
{
    /**
     * User identity
     * @var object
     */
    protected $userIdentity;

    /**
     * Class constructor
     *
     * @param object|array $userIdentity
     */
    public function __construct($userIdentity)
    {
        $this->userIdentity = $userIdentity;
    }

    /**
     * User identity
     *
     * @return boolean
     */
    public function __invoke()
    {
        return $this->userIdentity;
    }
}