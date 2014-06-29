<?php

namespace Application\Form;

use Application\Form\CustomFormBuilder;

class AclResourceSetting extends AbstractCustomForm 
{
    /**
     * Actions limit max string length
     */
    const ACTIONS_LIMIT_MAX_LENGTH = 7;

    /**
     * Actions reset max string length
     */
    const ACTIONS_RESET_MAX_LENGTH = 7;

    /**
     * Form name
     * @var string
     */
    protected $formName = 'acl-resource-settings';

    /**
     * List of ignored elements
     * @var array
     */
    protected $ignoredElements = array('clean_counter');

    /**
     * Actions limit
     * @var integer
     */
    protected $actionsLimit;

    /**
     * Actions reset
     * @var integer
     */
    protected $actionsReset;

    /**
     * Date start
     * @var integer
     */
    protected $dateStart;

    /**
     * Date end
     * @var integer
     */
    protected $dateEnd;

    /**
     * Show clear action counter
     * @var boolean
     */
    protected $showCleanActionCounter = false;

    /**
     * Form elements
     * @var array
     */
    protected $formElements = array(
        'actions_limit' => array(
            'name' => 'actions_limit',
            'type' => CustomFormBuilder::FIELD_INTEGER,
            'label' => 'Number of allowed actions',
            'required' => false,
            'category' => 'General info',
            'max_length' => self::ACTIONS_LIMIT_MAX_LENGTH
        ),
        'actions_reset' => array(
            'name' => 'actions_reset',
            'type' => CustomFormBuilder::FIELD_INTEGER,
            'label' => 'Number of actions is reset every N seconds',
            'required' => false,
            'category' => 'General info',
            'max_length' => self::ACTIONS_RESET_MAX_LENGTH
        ),
        'date_start' => array(
            'name' => 'date_start',
            'type' => CustomFormBuilder::FIELD_DATE_UNIXTIME,
            'label' => 'This action is available since',
            'required' => false,
            'category' => 'General info',
        ),
        'date_end' => array(
            'name' => 'date_end',
            'type' => CustomFormBuilder::FIELD_DATE_UNIXTIME,
            'label' => 'This action is available until',
            'required' => false,
            'category' => 'General info',
        ),
        'clean_counter' => array(
            'name' => 'clean_counter',
            'type' => CustomFormBuilder::FIELD_CHECKBOX,
            'label' => 'Clean the action counter',
            'required' => false,
            'category' => 'General info',
        ),
        'submit' => array(
            'name' => 'submit',
            'type' => CustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Submit',
        )
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
            // fill the form with default values
            $this->formElements['actions_limit']['value'] = $this->actionsLimit;
            $this->formElements['actions_reset']['value'] = $this->actionsReset;
            $this->formElements['date_start']['value'] = $this->dateStart;
            $this->formElements['date_end']['value'] = $this->dateEnd;

            // remove the clean action counter field
            if (!$this->showCleanActionCounter) {
                unset($this->formElements['clean_counter']);
            }

            $this->form = new CustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }

    /**
     * Set actions limit
     *
     * @param integer $actionsLimit
     * @return object fluent interface
     */
    public function setActionsLimit($actionsLimit)
    {
        if ((int) $actionsLimit) {
            $this->actionsLimit = $actionsLimit;
        }

        return $this;
    }

    /**
     * Set actions reset
     *
     * @param integer $actionsReset
     * @return object fluent interface
     */
    public function setActionsReset($actionsReset)
    {
        if ((int) $actionsReset) {
            $this->actionsReset = $actionsReset;
        }

        return $this;
    }

    /**
     * Set date start
     *
     * @param integer $dateStart
     * @return object fluent interface
     */
    public function setDateStart($dateStart)
    {
        if ((int) $dateStart) {
            $this->dateStart = $dateStart;
        }

        return $this;
    }

    /**
     * Set date end
     *
     * @param integer $dateEnd
     * @return object fluent interface
     */
    public function setDateEnd($dateEnd)
    {
        if ((int) $dateEnd) {
            $this->dateEnd = $dateEnd;
        }

        return $this;
    }

    /**
     * Show the action clean counter
     *
     * @return object fluent interface
     */
    public function showActionCleanCounter()
    {
        $this->showCleanActionCounter = true;
        return $this;
    }
}