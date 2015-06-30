<?php

namespace User\View\Widget;

use User\Model\UserWidget as UserWidgetModel;

class UserForgotWidget extends UserAbstractWidget
{
    /**
     * Get widget content
     *
     * @return string|boolean
     */
    public function getContent() 
    {
        // get a forgot form
        $forgotForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('User\Form\UserForgot')
            ->setModel($this->getModel());

        $request = $this->getRequest();

        // validate the form
        if ($request->isPost() &&
                $this->getRequest()->getPost('form_name') == $forgotForm->getFormName()) {

            // fill the form with received values
            $forgotForm->getForm()->setData($request->getPost(), false);

            if ($forgotForm->getForm()->isValid()) {
                $passwordResetUrl =  $this->getView()->pageUrl('user-password-reset', [], null, true); 

                if (false !== $passwordResetUrl) {
                    // get an user info
                    $userInfo = $this->getModel()->
                            getUserInfo($forgotForm->getForm()->getData()['email'], UserWidgetModel::USER_INFO_BY_EMAIL);

                    // genereate a new activation code
                    if (true === ($result = $this->getModel()->generateActivationCode($userInfo))) {
                        $this->getFlashMessenger()
                            ->setNamespace('success')
                            ->addMessage($this->translate('We sent a message with a confirmation code. You should confirm the password reset'));
                    }
                    else {
                        $this->getFlashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->translate('Error occurred'));
                    }
                }
                else {
                    $this->getFlashMessenger()
                         ->setNamespace('error')
                         ->addMessage($this->translate('Password reset page is not allowed for you!'));
                }

                return $this->reloadPage();
            }
        }

        return $this->getView()->partial('user/widget/forgot', [
            'forgot_form' => $forgotForm->getForm()
        ]);
    }
}