<?php
namespace Application\View\Helper;
 
use Zend\View\Helper\AbstractHelper;
use Zend\Mvc\Controller\Plugin\FlashMessenger as FlashMessenger;
 
class ApplicationFlashMessage extends AbstractHelper
{
    protected $flashMessenger;
 
    public function setFlashMessenger(FlashMessenger $flashMessenger)
    {
        $this->flashMessenger = $flashMessenger;
    }
 
    public function __invoke($includeCurrentMessages = false)
    {
        $messages = array(
            FlashMessenger::NAMESPACE_ERROR => [],
            FlashMessenger::NAMESPACE_SUCCESS => [],
            FlashMessenger::NAMESPACE_INFO => [],
            FlashMessenger::NAMESPACE_DEFAULT => []
        );
 
        foreach ($messages as $ns => &$m) {
            $m = $this->flashMessenger->getMessagesFromNamespace($ns);
            if ($includeCurrentMessages) {
                $m = array_merge($m, $this->flashMessenger->getCurrentMessagesFromNamespace($ns));
            }
        }
 
        return $messages;
    }
}