<?php
namespace Acl\Form;

use Application\Form\ApplicationCustomFormBuilder;
use Application\Form\ApplicationAbstractCustomForm;

class AclResourceSetting extends ApplicationAbstractCustomForm 
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
    protected $ignoredElements = ['clean_counter'];

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
    protected $formElements = [
        'actions_limit' => [
            'name' => 'actions_limit',
            'type' => ApplicationCustomFormBuilder::FIELD_INTEGER,
            'label' => 'Number of allowed actions',
            'required' => false,
            'max_length' => self::ACTIONS_LIMIT_MAX_LENGTH
        ],
        'actions_reset' => [
            'name' => 'actions_reset',
            'type' => ApplicationCustomFormBuilder::FIELD_INTEGER,
            'label' => 'Number of actions is reset every N seconds',
            'required' => false,
            'max_length' => self::ACTIONS_RESET_MAX_LENGTH
        ],
        'date_start' => [
            'name' => 'date_start',
            'type' => ApplicationCustomFormBuilder::FIELD_DATE_UNIXTIME,
            'label' => 'This action is available since',
            'required' => false
        ],
        'date_end' => [
            'name' => 'date_end',
            'type' => ApplicationCustomFormBuilder::FIELD_DATE_UNIXTIME,
            'label' => 'This action is available until',
            'required' => false
        ],
        'clean_counter' => [
            'name' => 'clean_counter',
            'type' => ApplicationCustomFormBuilder::FIELD_CHECKBOX,
            'label' => 'Clean the action counter',
            'required' => false
        ],
        'submit' => [
            'name' => 'submit',
            'type' => ApplicationCustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Submit'
        ]
    ];

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

            $this->form = new ApplicationCustomFormBuilder($this->formName,
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