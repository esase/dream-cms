<?php
namespace Application\Controller;

use Zend\EventManager\EventManagerInterface;
use User\Service\Service as UserService;

abstract class ApplicationAbstractAdministrationController extends ApplicationAbstractBaseController
{
    /**
     * Layout name
     */
    protected $layout = 'layout/administration';

    /**
     * Set event manager
     */
    public function setEventManager(EventManagerInterface $events)
    {
        parent::setEventManager($events);
        $controller = $this;

        // execute before executing action logic
        $events->attach('dispatch', function ($e) use ($controller) {
            // check permission
            if (!UserService::checkPermission($controller->
                    params('controller') . ' ' . $controller->params('action'), false)) {

                return $controller->showErrorPage();
            }

            // set an admin layout
            if (!$e->getRequest()->isXmlHttpRequest()) {
                $controller->layout($this->layout);
            }
        }, 100);
    }

    /**
     * Generate settings form
     *
     * @param string $module
     * @param string $controller
     * @param string $action
     * @return object
     */
    protected function settingsForm($module, $controller, $action)
    {
        $currentlanguage = UserService::getCurrentLocalization()['language'];
        
        // get settings form
        $settingsForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Application\Form\ApplicationSetting');

        // get settings list
        $settings = $this->getServiceLocator()
            ->get('Application\Model\ModelManager')
            ->getInstance('Application\Model\ApplicationSettingAdministration');

        if (false !== ($settingsList = $settings->getSettingsList($module, $currentlanguage))) {
            $settingsForm->addFormElements($settingsList);
            $request  = $this->getRequest();

            // validate the form
            if ($request->isPost()) {
                // fill the form with received values
                $settingsForm->getForm()->setData($request->getPost(), false);

                // save data
                if ($settingsForm->getForm()->isValid()) {
                    // check the permission and increase permission's actions track
                    if (true !== ($result = $this->checkPermission())) {
                        return $settingsForm->getForm();
                    }

                    if (true === ($result = $settings->
                            saveSettings($settingsList, $settingsForm->getForm()->getData(), $currentlanguage, $module))) {

                        $this->flashMessenger()
                            ->setNamespace('success')
                            ->addMessage($this->getTranslator()->translate('Settings have been saved'));
                    }
                    else {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()->translate($result));
                    }

                    $this->redirectTo($controller, $action);
                }
            }
        }

        return $settingsForm->getForm();
    }
}