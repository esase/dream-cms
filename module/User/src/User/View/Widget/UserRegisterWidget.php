<?php
namespace User\View\Widget;

use Page\View\Widget\AbstractWidget;
use User\Service\UserIdentity as UserIdentityService;
use Application\Utility\EmailNotification;

class UserRegisterWidget extends AbstractWidget
{
    /**
     * Model instance
     * @var object  
     */
    protected $model;

    /**
     * Time zone model instance
     * @var object  
     */
    protected $timeZoneModel;

    /**
     * Get model
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = $this->getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('User\Model\User');
        }

        return $this->model;
    }

    /**
     * Get timezone model
     */
    protected function getTimeZoneModel()
    {
        if (!$this->timeZoneModel) {
            $this->timeZoneModel = $this->getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('Application\Model\TimeZone');
        }

        return $this->timeZoneModel;
    }

    /**
     * Get widget content
     *
     * @return string|boolean
     */
    public function getContent() 
    {
    //TODO: check everethung carefully, events, email, etc
    
        if (UserIdentityService::isGuest() 
                && (int) $this->getSetting('user_allow_register')) {

            // get an user form
            $userForm = $this->getServiceLocator()
                ->get('Application\Form\FormManager')
                ->getInstance('User\Form\User')
                ->setModel($this->getModel())
                ->setTimeZones($this->getTimeZoneModel()->getTimeZones())
                ->showCaptcha(true);

            $request  = $this->getServiceLocator()->get('Request');

            // validate the form
            if ($request->isPost()) {
                // make certain to merge the files info!
                $post = array_merge_recursive(
                    $request->getPost()->toArray(),
                    $request->getFiles()->toArray()
                );

                // fill the form with received values
                $userForm->getForm()->setData($post, false);

                // save data
                if ($userForm->getForm()->isValid()) {
                    // add a new user with a particular status
                    $status = (int) $this->getSetting('user_auto_confirm') ? true : false;

                   $userInfo = $this->getModel()->addUser($userForm->
                        getForm()->getData(), $status, $request->getFiles()->avatar, true);

                    // the user has been added
                    if (is_array($userInfo)) {
                        // check the user status
                        if (!$status) {
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
                                        'link',
                                        /*$this->url()->fromRoute('administration', array('controller' => 'user',
                                                'action' => 'activate', 'slug' => $userInfo['slug']), array('force_canonical' => true)),*/

                                        $userInfo['activation_code']
                                    ]
                                ]);

                            $this->getFlashMessenger()
                                  ->setNamespace('success')
                                  ->addMessage($this->translate('We sent a message with a confirmation code to your registration e-mail'));

                        }
                        else {
                            // login and redirect the registered user
                            //return $this->loginUser($userInfo['user_id'], $userInfo['nick_name'], false);
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