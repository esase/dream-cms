<?php
namespace Payment\View\Helper;
 
use Zend\View\Helper\AbstractHelper;
use User\Service\Service as UserService;

class PaymentTransactionInfo extends AbstractHelper
{
    /**
     * Payment transaction info
     *
     * @param array $transactionInfo
     * @param array $options
     *      string  controller required
     *      string  action required
     *      boolean check_acl optional
     * @return string 
     */
    public function __invoke($transactionInfo, array $options = array())
    {
        if (!empty($options['controller']) && !empty($options['action'])) {
            // check the route's acl
            if (!empty($options['check_acl']) && !UserService::checkPermission($options['controller'] . ' ' . $options['action'])) {
               return $transactionInfo['slug'];
            }

            $pageUrl = $this->getView()->url('application', array('controller' =>
                    $options['controller'], 'action' => $options['action'], 'slug' => $transactionInfo['slug']));

            return '<a href="javascript:void(0)" onclick="showPopup(\'' .
                    $this->getView()->escapeJs($pageUrl) . '\', \'popup-transaction-info\')">' . $transactionInfo['slug'] . '</a>';  
        }

        return $transactionInfo['slug'];
    }
}