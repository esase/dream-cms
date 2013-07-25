<?php

namespace Application\Model;

interface ModelBuilderInterface
{
    /**
     * Get instance of specified model
     *
     * @papam string $modelName
     * @return object|boolean
     */
    public function getInstance($modelName);
}
