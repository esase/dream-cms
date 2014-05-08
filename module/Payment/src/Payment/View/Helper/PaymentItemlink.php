<?php

namespace Payment\View\Helper;
 
use Zend\View\Helper\AbstractHelper;
use Payment\Model\Base as BaseModel;

class PaymentItemLink extends AbstractHelper
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
     *      integer module_state
     * @return string
     */
   public function __invoke($info)
   {
        // check the item's status
        if ($info['deleted'] == BaseModel::ITEM_DELETED || $info['active'] ==
                BaseModel::ITEM_NOT_ACTIVE || $info['module_state'] != BaseModel::MODULE_ACTIVE) {

            return $info['title'];
        }

        return '<a target="_blank" href="' . $this->getView()->url('application', array('controller' =>
                $info['view_controller'], 'action' => $info['view_action'], 'slug' => $info['slug'])) . '">' . $info['title'] . '</a>';
   }
}
