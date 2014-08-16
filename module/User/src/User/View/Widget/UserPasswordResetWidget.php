<?php
namespace User\View\Widget;

use User\Model\UserWidget as UserWidgetModel;

class UserPasswordResetWidget extends UserAbstractWidget
{
    /**
     * Get widget content
     *
     * @return string|boolean
     */
    public function getContent() 
    {
        if (null != ($userInfo = 
                $this->getModel()->getUserInfo($this->getSlug(), UserWidgetModel::USER_INFO_BY_SLUG))) {

            // get a reset form
            $resetForm = $this->getServiceLocator()
                ->get('Application\Form\FormManager')
                ->getInstance('User\Form\ActivationCode')
                ->setModel($this->getModel())
                ->setUserId($userInfo['user_id']);

            $request  = $this->getRequest();

            // validate the form
            if ($request->isPost()) {
                // fill the form with received values
                $resetForm->getForm()->setData($request->getPost(), false);

                // reset the users's password
                if ($resetForm->getForm()->isValid()) {
                    if (true === ($result = $this->getModel()->resetUserPassword($userInfo))) {
                        $this->getFlashMessenger()
                            ->setNamespace('success')
                            ->addMessage($this->translate('We have sent a new password to your email'));
                    }
                    else {
                        $this->getFlashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->translate('Error occurred'));
                    }

                    return $this->reloadPage();
                }
            }

            return $this->getView()->partial('user/widget/password-reset', [
                'resetForm' => $resetForm->getForm(),
            ]);
        }

        return false;
    }

    /**
     * Get widget title
     *
     * @return string
     */
    public function getTitle() 
    {
        return $this->translate('Password reset');
    }
}