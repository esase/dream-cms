<?php
namespace User\View\Widget;

use Page\View\Widget\PageAbstractWidget;
use User\Utility\UserAuthenticate as UserAuthenticateUtility;

abstract class UserAbstractWidget extends PageAbstractWidget
{
    /**
     * Model instance
     * @var object  
     */
    protected $model;

    /**
     * Get model
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = $this->getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('User\Model\UserWidget');
        }

        return $this->model;
    }

    /**
     * Login user
     *
     * @param integer $userId
     * @param string $nickName
     * @param boolean $rememberMe
     * @return string
     */
    protected function loginUser($userId, $nickName, $rememberMe = false)
    {
        UserAuthenticateUtility::loginUser($userId, $nickName, $rememberMe);

        if (null !== ($backUrl = $this->getRequest()->getQuery('back_url', null))) {
            return $this->redirectToUrl($backUrl);
        }

        // check the user's dashboard url
        $userDashboard = $this->getView()->pageUrl('dashboard');

        return false !== $userDashboard
            ? $this->redirectTo(['page_name' => $userDashboard])
            : $this->redirectTo(); // redirect to home page        
    }
}