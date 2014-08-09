<?php
namespace Localization\Controller\Plugin;

use Localization\Service\Localization as LocalizationService;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class Localization extends AbstractPlugin
{
    /**
     * Get current localization
     *
     * @return array
     */
    public function __invoke()
    {
        return LocalizationService::getCurrentLocalization();
    }
}