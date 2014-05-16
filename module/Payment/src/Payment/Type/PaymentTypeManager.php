<?php

namespace Payment\Type;

use Zend\Mvc\Exception\InvalidArgumentException;
use Payment\Model\Base as BasePaymentModel;
use Zend\Http\PhpEnvironment\Request as HttpRequest;
use Payment\Type\PaymentTypeInterface;
use Zend\View\Helper\Url as UrlViewHelper;

class PaymentTypeManager
{
    /**
     * List of instances
     * @var array
     */
    private $instances = array();

    /**
     * Model
     * @var object
     */
    private $model;

    /**
     * Request
     * @var object
     */
    private $request;

    /**
     * Url view helper
     * @var object
     */
    private $urlViewHelper;

    /**
     * Class constructor
     * 
     * @param object $translator
     */
    public function __construct(HttpRequest $request, BasePaymentModel $model, UrlViewHelper $urlViewHelper)
    {
        $this->request = $request;
        $this->model = $model;
        $this->urlViewHelper = $urlViewHelper;
    }

    /**
     * Get an object instance
     *
     * @papam string $name
     * @return object|boolean
     * @throws Exception\InvalidArgumentException
     */
    public function getInstance($name)
    {
        if (!class_exists($name)) {
            return false;
        }

        if (array_key_exists($name, $this->instances)) {
            return $this->instances[$name];
        }

        $paymentType = new $name($this->request, $this->model, $this->urlViewHelper);

        if (!$paymentType instanceof PaymentTypeInterface) {
            throw new InvalidArgumentException(sprintf('The file "%s" must be an object implementing Payment\Type\PaymentTypeInterface', $name));
        }

        $this->instances[$name] = $paymentType;
        return $this->instances[$name];
    }
}
