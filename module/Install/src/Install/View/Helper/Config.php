<?php
namespace Install\View\Helper;

use Zend\View\Helper\AbstractHelper;

class Config extends AbstractHelper
{
    /**
     * Config
     * @var array
     */
    protected $config;

    /**
     * Class constructor
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Config
     *
     * @return string|array
     */
    public function __invoke($param)
    {
        return !empty($this->config[$param]) ? $this->config[$param] : null;
    }
}
