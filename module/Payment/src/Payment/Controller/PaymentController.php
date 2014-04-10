<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Payment\Controller;

use Zend\View\Model\ViewModel;
use Application\Controller\AbstractBaseController;
use Zend\Form\Exception\InvalidArgumentException;
use Payment\Handler\InterfaceHandler as PaymentInterfaceHandler;
use Payment\Event\Event as PaymentEvent;
use User\Service\Service as UserService;

class PaymentController extends AbstractBaseController
{
    /**
     * Model instance
     * @var object  
     */
    protected $model;

    /**
     * Get model
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = $this->getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('Payment\Model\Payment');
        }

        return $this->model;
    }

    /**
     * Add to shopping cart
     *
     * @param array $itemInfo
     *      integer object_id - required
     *      integer module - required
     *      string title - required
     *      string slug - optional
     *      float cost - required
     *      float discount - optional
     *      integer count - required
     * @param object $paymentObject
     * @return boolean
     */
    protected function addToShoppingCart($itemInfo, PaymentInterfaceHandler $paymentObject)
    {
        $result = $this->getModel()->addToShoppingCart($itemInfo);

        if (is_numeric($result)) {
            // clear the item's discount
            if ($itemInfo['discount']) {
                $paymentObject->clearDiscount($itemInfo['object_id'], $itemInfo['discount']);
            }

            // fire the event
            $eventDesc = UserService::isGuest()
                ? 'Event - Item added to shopping cart by guest'
                : 'Event - Item added to shopping cart by user';

            $eventDescParams = UserService::isGuest()
                ? array($result)
                : array(UserService::getCurrentUserIdentity()->nick_name, $result);

            PaymentEvent::fireEvent(PaymentEvent::ADD_ITEM_TO_SHOPPING_CART,
                    $result, UserService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);

            return true;
        }

        return false;
    }

    /**
     * Add to shopping cart
     */
    public function addToShoppingCartAction()
    {
        $objectId = $this->params()->fromPost('object_id', -1);
        $module   = $this->params()->fromPost('module');
        $count    = (int) $this->params()->fromPost('count', 0);

        $shoppingCartForm  = $message = null;
        $updateShopingCart = false;

        // get a module info 
        if (null == ($moduleInfo = $this->getModel()->getModuleInfo($module))) {
            $message = sprintf($this->
                getTranslator()->translate('Received module not found'), $module);
        }
        else {
            // create a payment handler class instance
            $object = new $moduleInfo['handler']($this->getServiceLocator());
            if (!$object instanceof PaymentInterfaceHandler) {
                throw new InvalidArgumentException(sprintf('The file "%s" must be an object implementing Payment\Handler\InterfaceHandler',
                        $moduleInfo['handler']));
            }

            // check an item existing in shopping cart
            if (true === ($result = $this->getModel()->inShoppingCart($objectId, $moduleInfo['id']))) {
                $message = $this->getTranslator()->translate('Item already added into your shopping cart'); 
            }
            else {
                // get the item info
                if (null == $objectInfo = $object->getItemInfo($objectId)) {
                    $message = $this->getTranslator()->translate('Sorry but the item not found or not activated');    
                }
                else {
                    // item's count is not available
                    if ($moduleInfo['countable'] && $objectInfo['count'] <= 0) {
                        $message = $this->getTranslator()->translate('Item not available');
                    }
                    else {
                        // show an additional shopping cart form
                        if ($objectInfo['discount'] || is_array($objectInfo['cost']) ||
                                ($moduleInfo['countable'] && ($count <= 0 || $count > $objectInfo['count']))) {
    
                            // get the form instance
                            $shoppingCartForm = $this->getServiceLocator()
                                ->get('Application\Form\FormManager')
                                ->getInstance('Payment\Form\ShoppingCart')
                                ->hideCountField(!$moduleInfo['countable'])
                                ->setDiscount($objectInfo['discount'])
                                ->setTariffs((is_array($objectInfo['cost']) ? $objectInfo['cost'] : array()))
                                ->setCountLimit(($moduleInfo['countable'] ? $objectInfo['count'] : 0));

                            $request = $this->getRequest();
                            $shoppingCartForm->getForm()->setData($request->getPost(), false);
        
                            // validate the form
                            if ($request->isPost() && null !== $this->params()->fromPost('validate', null)) {
                                if ($shoppingCartForm->getForm()->isValid()) {
                                    $formData = $shoppingCartForm->getForm()->getData();
                                    $itemInfo = array(
                                        'object_id' => $objectId,
                                        'module'    => $moduleInfo['id'],
                                        'title'     => $objectInfo['title'],
                                        'slug'      => $objectInfo['slug'],
                                        'cost'      => !empty($formData['cost']) ? $formData['cost'] : $objectInfo['cost'],
                                        'discount'  => !empty($formData['discount']) ? $objectInfo['discount'] : 0,
                                        'count'     => $moduleInfo['countable'] ? $count : 1
                                    );
    
                                    // add the item into the shopping cart
                                    $shoppingCartForm = null;
                                    if (true === ($result = $this->addToShoppingCart($itemInfo, $object))) {
                                        $updateShopingCart = true;
                                        $message = $this->getTranslator()->translate('Item has been added to your shopping cart');
                                    }
                                    else {
                                        $message = $this->getTranslator()->translate('Error occurred');
                                    }
                                }
                            }
                        }
                        else {
                            $itemInfo = array(
                                'object_id' => $objectId,
                                'module'    => $moduleInfo['id'],
                                'title'     => $objectInfo['title'],
                                'slug'      => $objectInfo['slug'],
                                'cost'      => $objectInfo['cost'],
                                'discount'  => 0,
                                'count'     => $moduleInfo['countable'] ? $count : 1
                            );
    
                            if (true === ($result = $this->addToShoppingCart($itemInfo, $object))) {
                                $updateShopingCart = true;
                                $message = $this->getTranslator()->translate('Item has been added to your shopping cart');
                            }
                            else {
                                $message = $this->getTranslator()->translate('Error occurred');
                            }
                        }
                    }
                }
            }
        }

        $view = new ViewModel(array(
            'updateShopingCart' => $updateShopingCart,
            'shoppingCartForm' => $shoppingCartForm ? $shoppingCartForm->getForm() : null,
            'message' => $message
        ));

        $view->setTerminal(true);
        return $view;
    }
}