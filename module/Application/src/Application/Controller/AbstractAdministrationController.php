<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\EventManager\EventManagerInterface;
use Users\Service\Service as UsersService;
use Application\Form\Settings as SettingsForm;

class AbstractAdministrationController extends AbstractActionController
{
    /**
     * Translator
     * @var object  
     */
    protected $translator;

    /**
     * Get translation
     */
    protected function getTranslator()
    {
        if (!$this->translator) {
            $this->translator = $this->getServiceLocator()->get('Translator');
        }

        return $this->translator;
    }

    /**
     * Generate settings form
     *
     * @param string $module
     * @param string $controller
     * @return object
     */
    protected function settingsForm($module, $controller)
    {
        $currentlanguage = UsersService::getCurrentLocalization()['language'];

        // get settings form
        $settingsForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Application\Form\Settings');

        // get settngs list
        $settings = $this->getServiceLocator()
            ->get('Application\Model\ModelManager')
            ->getInstance('Application\Model\SettingAdministration');

        // get settings list
        if (false !== ($settingsList = $settings->getSettingsList($module, $currentlanguage))) {
            $settingsForm->addFormElements($settingsList);
            $request  = $this->getRequest();

            // validate form
            if ($request->isPost()) {
                // fill form with received values
                $settingsForm->getForm()->setData($request->getPost(), false);

                // save data
                if ($settingsForm->getForm()->isValid()) {
                    if (true === ($result = $settings->
                            saveSettings($settingsList, $settingsForm->getForm()->getData(), $currentlanguage))) {

                        $this->flashMessenger()
                            ->setNamespace('success')
                            ->addMessage($this->getTranslator()->translate('Settings have been saved'));
                    }
                    else {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($result);
                    }

                    $this->redirect()->toRoute('application', array('controller' => $controller));
                }
            }
        }

        return $settingsForm->getForm();
    }
}
