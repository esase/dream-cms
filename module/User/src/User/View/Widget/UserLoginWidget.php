<?php
namespace User\View\Widget;

use  Page\View\Widget\AbstractWidget;

class UserLoginWidget extends AbstractWidget
{
    /**
     * Get widget content
     *
     * @return string
     */
    public function getContent() 
    {
        return 'Some content';
    }

    /**
     * Get widget title
     *
     * @return string
     */
    public function getTitle() 
    {
        return $this->getView()->translate('Login');
    }
}