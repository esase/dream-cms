<?php
namespace Page\View\Widget;

use Application\Utility\ApplicationEmailNotification as EmailNotificationUtility;

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
            ->showCaptcha((int) $this->getWidgetSetting('page_contact_form_captcha'));

        if ($this->getRequest()->isPost() &&
                $this->getRequest()->getPost('form_name') == $contactForm->getFormName()) {

            // fill form with received values
            $contactForm->getForm()->setData($this->getRequest()->getPost());

            if ($contactForm->getForm()->isValid()) {
                // wrap a message
                $message = $this->getView()->
                        partial('page/widget/contact-message-wrapper', $contactForm->getForm()->getData());

                // send the message
                if (true === ($result = EmailNotificationUtility::sendNotification($this->
                        getWidgetSetting('page_contact_form_email'), $this->translate('A message from the contact form'), $message))) {

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