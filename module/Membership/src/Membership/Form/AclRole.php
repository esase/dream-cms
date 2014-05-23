<?php

namespace Membership\Form;

use Application\Form\CustomFormBuilder;
use Application\Form\AbstractCustomForm;
use Application\Model\AclAdministration as AclModelAdministration;
use Application\Service\Service as ApplicationService;
use Membership\Model\MembershipAdministration as MembershipAdministrationModel;

class AclRole extends AbstractCustomForm 
{
    /**
     * Form name
     * @var string
     */
    protected $formName = 'acl-role';

    /**
     * List of ignored elements
     * @var array
     */
    protected $ignoredElements = array('image');

    /**
     * Acl model
     * @var object
     */
    protected $aclModel;

    /**
     * Image
     * @var string
     */
    protected $image;

    /**
     * Form elements
     * @var array
     */
    protected $formElements = array(
        'role_id' => array(
            'name' => 'role_id',
            'type' => CustomFormBuilder::FIELD_SELECT,
            'label' => 'Role',
            'required' => true,
            'category' => 'General info',
        ),
        'cost' => array(
            'name' => 'cost',
            'type' => CustomFormBuilder::FIELD_FLOAT,
            'label' => 'Cost',
            'required' => true,
            'category' => 'General info',
        ),
        'lifetime' => array(
            'name' => 'lifetime',
            'type' => CustomFormBuilder::FIELD_INTEGER,
            'label' => 'Lifetime in days',
            'required' => true,
            'category' => 'General info',
        ),
        'description' => array(
            'name' => 'description',
            'type' => CustomFormBuilder::FIELD_TEXT_AREA,
            'label' => 'Description',
            'required' => true,
            'category' => 'General info',
        ),
        'image' => array(
            'name' => 'image',
            'type' => CustomFormBuilder::FIELD_IMAGE,
            'label' => 'Image',
            'required' => true,
            'extra_options' => array(
                'file_url' => null,
                'preview' => false,
                'delete_option' => false
            ),
            'category' => 'General info'
        ),
        'language' => array(
            'name' => 'language',
            'type' => CustomFormBuilder::FIELD_SELECT,
            'label' => 'Language',
            'required' => false,
            'category' => 'Localization',
        ),
        'submit' => array(
            'name' => 'submit',
            'type' => CustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Submit',
        ),
    );

    /**
     * Get form instance
     *
     * @return object
     */
    public function getForm()
    {
        // get form builder
        if (!$this->form) {
            // file the form with default values
            if ($this->aclModel) {
                // get list of acl roles
                $this->formElements['role_id']['values'] = $this->aclModel->getRolesList();
            }

            // init localizations
            $localizations = ApplicationService::getLocalizations();
            if (count($localizations) > 1) {
                $languages = array();
                foreach ($localizations as $localization) {
                    $languages[$localization['language']] = $localization['description'];
                }

                $this->formElements['language']['values'] = $languages;
                $this->formElements['language']['value']  = ApplicationService::getCurrentLocalization()['language'];
            }
            else {
                unset($this->formElements['language']);
            }

            // add preview for the image
            if ($this->image) {
                $this->formElements['image']['required'] = false;
                $this->formElements['image']['extra_options']['preview'] = true;
                $this->formElements['image']['extra_options']['file_url'] =
                        ApplicationService::getResourcesUrl() . MembershipAdministrationModel::getImagesDir() . $this->image;
            }

            $this->form = new CustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }

    /**
     * Set acl model
     *
     * @param object $aclModel
     * @return object fluent interface
     */
    public function setAclModel(AclModelAdministration $aclModel)
    {
        $this->aclModel = $aclModel;
        return $this;   
    }

    /**
     * Set an image
     *
     * @param string $image
     * @return object fluent interface
     */
    public function setImage($image)
    {
        $this->image = $image;
        return $this;
    }
}