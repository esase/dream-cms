<?php
namespace User\View\Widget;

use User\Model\UserWidget as UserWidgetModel;

class UserActivateWidget extends UserAbstractWidget
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

            // get an activate form
            $activateForm = $this->getServiceLocator()
                ->get('Application\Form\FormManager')
                ->getInstance('User\Form\UserActivationCode')
                ->setModel($this->getModel())
                ->setUserId($userInfo['user_id']);

            $request = $this->getRequest();

            // validate the form
            if ($request->isPost() &&
                    $this->getRequest()->getPost('form_name') == $activateForm->getFormName()) {

                // fill the form with received values
                $activateForm->getForm()->setData($request->getPost(), false);

                // activate the users's status
                if ($activateForm->getForm()->isValid()) {
                    // approve the user
                    if (true !== ($approveResult = $this->getModel()->
                            setUserStatus($userInfo['user_id'], true, $userInfo, $userInfo['nick_name']))) {

                        $this->getFlashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->translate('Error occurred'));

                        return $this->reloadPage();
                    }

                    // login and redirect the user
                    return $this->loginUser($userInfo['user_id'], $userInfo['nick_name']);
                }
            }

            return $this->getView()->partial('user/widget/activate', [
                'activateForm' => $activateForm->getForm()
            ]);
        }

        return false;
    }
}