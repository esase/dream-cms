<?php
namespace Page\View\Widget;

use Application\Utility\ApplicationEmailNotification as EmailNotificationUtility;
use User\Service\UserIdentity as UserIdentityService;

class PageContactFormWidget extends PageAbstractWidget
{
    /**
     * Get widget content
     *
     * @return string|boolean
     */
    public function getContent() 
    {
        // get a contact form
        $contactForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Page\Form\PageContact')
            ->showCaptcha((int) $this->
                    getWidgetSetting('page_contact_form_captcha') && UserIdentityService::isGuest());

        if ($this->getRequest()->isPost() &&
                $this->getRequest()->getPost('form_name') == $contactForm->getFormName()) {

            // fill form with received values
            $contactForm->getForm()->setData($this->getRequest()->getPost());

            if ($contactForm->getForm()->isValid()) {
                $formData = $contactForm->getForm()->getData();

                $sendResult = EmailNotificationUtility::sendNotification($this->getWidgetSetting('page_contact_form_email'),
                    $this->getWidgetSetting('page_contact_form_title'),
                    $this->getWidgetSetting('page_contact_form_message'), [
                        'find' => [
                            'RealName',
                            'Email',
                            'Phone',
                            'Message'
                        ],
                        'replace' => [
                            $formData['name'],
                            $formData['email'],
                            $formData['phone'],
                            $formData['message']
                        ]
                    ], true);

                // send the message
                if (true === $sendResult) {
                    $this->getFlashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->translate('Your message has been sent'));
                }
                else {
                    $this->getFlashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->translate('Message cannot be sent. Please try again later'));
                }

                $this->reloadPage();
            }
        }

        return $this->getView()->partial('page/widget/contact', [
            'contact_form' => $contactForm->getForm()
        ]);
    }
}