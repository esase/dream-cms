<?php

namespace Page\Form;

use Application\Form\ApplicationAbstractCustomForm;
use Application\Form\ApplicationCustomFormBuilder;
use Page\Model\PageBase as PageBaseModel;

class PageWidgetFilter extends ApplicationAbstractCustomForm 
{
    /**
     * Embed mode
     * @var boolean
     */
    protected $embedMode = false;

    /**
     * Form name
     * @var string
     */
    protected $formName = 'filter';

    /**
     * Form method
     * @var string
     */
    protected $method = 'get';

    /**
     * List of not validated elements
     * @var array
     */
    protected $notValidatedElements = ['submit'];

    /**
     * Model
     * @var \Page\Model\PageBase
     */
    protected $model;

    /**
     * Hide status filter
     * @var boolean
     */
    protected $hideStatusFilter;

    /**
     * Form elements
     * @var array
     */
    protected $formElements = [
        'modules' => [
            'name' => 'modules',
            'type' => ApplicationCustomFormBuilder::FIELD_MULTI_SELECT,
            'label' => 'Module',
            'values' => []
        ],
        'embed' => [
            'name' => 'embed_mode',
            'type' => ApplicationCustomFormBuilder::FIELD_HIDDEN,
            'value' => true
        ],
        'submit' => [
            'name' => 'submit',
            'type' => ApplicationCustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Search'
        ]
    ];

    /**
     * Get form instance
     *
     * @return \Application\Form\ApplicationCustomFormBuilder
     */
    public function getForm()
    {
        // get form builder
        if (!$this->form) {
            if ($this->model) {
                // fill the form with default values
                $this->formElements['modules']['values'] = $this->model->getActiveModulesList();
            }

            if (!$this->embedMode) {
                unset($this->formElements['embed']);
            }

            $this->form = new ApplicationCustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }

    /**
     * Set model
     *
     * @param \Page\Model\PageBase $model
     * @return \Page\Form\PageWidgetFilter
     */
    public function setModel(PageBaseModel $model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Set embed mode
     *
     * @return \Page\Form\PageWidgetFilter
     */
    public function setEmbedMode()
    {
        $this->embedMode = true;

        return true;
    }
}