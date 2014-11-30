<?php
namespace User\View\Widget;

use Application\Service\ApplicationTimeZone as TimeZoneService;
use User\Service\UserIdentity as UserIdentityService;
use Acl\Model\AclBase as AclBaseModel;

class UserEditWidget extends UserAbstractWidget
{
    /**
     * Get widget content
     *
     * @return string|boolean
     */
    public function getContent() 
    {
        if (!UserIdentityService::isGuest()) {
            // get an user form
            $userForm = $this->getServiceLocator()
                ->get('Application\Form\FormManager')
                ->getInstance('User\Form\User')
                ->setModel($this->getModel())
                ->setTimeZones(TimeZoneService::getTimeZones())
                ->setUserId(UserIdentityService::getCurrentUserIdentity()['user_id'])
                ->setUserAvatar(UserIdentityService::getCurrentUserIdentity()['avatar']);

            // fill the form with default values
            $userForm->getForm()->setData(UserIdentityService::getCurrentUserIdentity());

            // validate the form
            if ($this->getRequest()->isPost() &&
                    $this->getRequest()->getPost('form_name') == $userForm->getFormName()) {

                // make certain to merge the files info!
                $post = array_merge_recursive(
                    $this->getRequest()->getPost()->toArray(),
                    $this->getRequest()->getFiles()->toArray()
                );

                // fill the form with received values
                $userForm->getForm()->setData($post, false);

                // save data
                if ($userForm->getForm()->isValid()) {
                    // set status
                    $status = (int) $this->getSetting('user_auto_confirm') ||
                            UserIdentityService::getCurrentUserIdentity()['role'] ==  AclBaseModel::DEFAULT_ROLE_ADMIN ? true : false;

                    $deleteAvatar = (int) $this->getRequest()->getPost('avatar_delete') ? true : false;

                    // edit current user's info
                    $result = $this->getModel()->editUser(UserIdentityService::getCurrentUserIdentity(), 
                            $userForm->getForm()->getData(), $status, $this->getRequest()->getFiles()->avatar, $deleteAvatar, true);

                    if (true === $result) {
                        if ($status) {
                            $this->getFlashMessenger()
                                ->setNamespace('success')
                                ->addMessage($this->translate('Your account has been edited'));
                        }
                        else {
                            $this->getFlashMessenger()
                                ->setNamespace('success')
                                ->addMessage($this->translate('Your account will be active after checking'));

                            // redirect to login page
                            $loginUrl = $this->getView()->pageUrl('login');
                            return $this->redirectTo(['page_name' => (false !== $loginUrl ? $loginUrl : '')]);
                        }
                    }
                    else {
                        $this->getFlashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->translate('Error occurred'));
                    }

                    return $this->reloadPage();
                }
            }

            return $this->getView()->partial('user/widget/edit', [
                'user_form' => $userForm->getForm()
            ]);
        }

        return false;
    }
}