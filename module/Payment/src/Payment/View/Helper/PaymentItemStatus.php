<?php
namespace Payment\View\Helper;
 
use Zend\View\Helper\AbstractHelper;
use Payment\Model\Base as BaseModel;

class PaymentItemStatus extends AbstractHelper
{
    /**
     * Payment item status
     *
     * @param array $info
     *      integer id
     *      string title
     *      float cost
     *      float discount
     *      integer count
     *      integer active
     *      integer available
     *      integer deleted
     *      string slug
     *      string view_controller
     *      string view_action
     *      integer countable
     *      integer must_login
     *      string extra_options
     *      string handler
     *      integer object_id
     *      integer module_extra_options
     *      srting module_state
     * @return string
     */
    public function __invoke($info)
    {
        // check the item's status
        if ($info['deleted'] == BaseModel::ITEM_DELETED) {
            return $this->getView()->translate('Item deleted');
        }

        if ($info['active'] == BaseModel::ITEM_NOT_ACTIVE) {
            return  $this->getView()->translate('Item is not active');
        }

        if ($info['available'] == BaseModel::ITEM_NOT_AVAILABLE) {
            return  $this->getView()->translate('Item is not available');
        }

        if ($info['module_state'] != BaseModel::MODULE_STATUS_ACTIVE) {
            return  $this->getView()->translate('Module is not active');
        }

        return $this->getView()->translate('Active');
    }
}