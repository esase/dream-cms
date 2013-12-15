<?php

namespace Application\Form;

interface CustomFormInterface
{
    /**
     * Get form instance
     *
     * @return object
     */
    public function getForm();

    /**
     * Add form elements
     *
     * @return void
     */
    public function addFormElements(array $elements);

    /**
     * Set form elements
     *
     * @return void
     */
    public function setFormElements(array $elements);
}