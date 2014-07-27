<?php
namespace Payment\Type;

use Zend\Stdlib\RequestInterface;
use Application\Service\Service as ApplicationService;
use Payment\Model\Base as BasePaymentModel;
use Zend\View\Helper\Url as UrlViewHelper;

abstract class AbstractType implements PaymentTypeInterface
{
    /**
     * Request
     * @var object
     */
    protected $request;

    /**
     * Model
     * @var object
     */
    protected $model;

    /**
     * Url view helper
     * @var object
     */
    private $urlViewHelper;

    /**
     * Class constructor
     *
     * @param object $serviceManager
     */
    public function __construct(RequestInterface $request, BasePaymentModel $model, UrlViewHelper $urlViewHelper)
    {
        $this->request = $request;
        $this->model = $model;
        $this->urlViewHelper = $urlViewHelper;
    }

    /**
     * Get success url
     *
     * @return string
     */
    public function getSuccessUrl()
    {
        return $this->urlViewHelper->__invoke('administration', array(
            'controller' => 'payments',
            'action' => 'success'
        ), array('force_canonical' => true));
    }

    /**
     * Get error url
     *
     * @return string
     */
    public function getErrorUrl()
    {
        return $this->urlViewHelper->__invoke('administration', array(
            'controller' => 'payments',
            'action' => 'error'
        ), array('force_canonical' => true));
    }
}