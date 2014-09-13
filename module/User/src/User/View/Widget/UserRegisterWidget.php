<?php
namespace User\View\Widget;

use User\Service\UserIdentity as UserIdentityService;
use Application\Utility\EmailNotification;
use Localization\Service\Localization as LocalizationService;

class UserRegisterWidget extends UserAbstractWidget
{
    /**
     * Get widget content
     *
     * @return string|boolean
     */
    public function getContent() 
    {
        if (UserIdentityService::isGuest() 
                && (int) $this->getSetting('user_allow_register')) {

            // get an user form
            $userForm = $this->getServiceLocator()
                ->get('Application\Form\FormManager')
                ->getInstance('User\Form\User')
                ->setModel($this->getModel())
                ->setTimeZones($this->getTimeZoneModel()->getTimeZones())
                ->showCaptcha(true);

            // validate the form
            if ($this->getRequest()->isPost()) {
                // make certain to merge the files info!
                $post = array_merge_recursive(
                    $this->getRequest()->getPost()->toArray(),
                    $this->getRequest()->getFiles()->toArray()
                );

                // fill the form with received values
                $userForm->getForm()->setData($post, false);

                // save data
                if ($userForm->getForm()->isValid()) {
                    // add a new user with a particular status
                    $status = (int) $this->getSetting('user_auto_confirm') ? true : false;

                    $userInfo = $this->getModel()->addUser($userForm->getForm()->getData(), 
                        LocalizationService::getCurrentLocalization()['language'], $status, $this->getRequest()->getFiles()->avatar, true);

                    // the user has been added
                    if (is_array($userInfo)) {
                        // check the user status
                        if (!$status) {
                            // get user activate url
                            if (false !== ($activateUrl = $this->
                                    getView()->pageUrl('user-activate', ['user_id' => $userInfo['user_id']]))) {

                                // send an email activate notification
                                EmailNotification::sendNotification($userInfo['email'],
                                    $this->getSetting('user_email_confirmation_title'),
                                    $this->getSetting('user_email_confirmation_message'), [
                                        'find' => [
                                            'RealName',
                                            'SiteName',
                                            'ConfirmationLink',
                                            'ConfCode'
                                        ],
                                        'replace' => [
                                            $userInfo['nick_name'],
                                            $this->getSetting('application_site_name'),
                                            $this->getView()->url('page', ['page_name' => $activateUrl, 'slug' => $userInfo['slug']], ['force_canonical' => true]),
                                            $userInfo['activation_code']
                                        ]
                                    ]);

                                $this->getFlashMessenger()
                                    ->setNamespace('success')
                                    ->addMessage($this->translate('We sent a message with a confirmation code to your registration e-mail'));
                            }
                            else {
                                $this->getFlashMessenger()
                                    ->setNamespace('success')
                                    ->addMessage($this->translate('Your profile will be activated after checking'));
                            }

                            $this->reloadPage();
                        }
                        else {
                            // login and redirect the registered user
                            return $this->loginUser($userInfo['user_id'], $userInfo['nick_name'], false);
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

            return $this->getView()->partial('user/widget/register', [
                'userForm' => $userForm->getForm()
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
        return $this->translate('Register');
    }
}