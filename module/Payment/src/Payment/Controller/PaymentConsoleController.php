<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Payment\Controller;

use Zend\Console\Request as ConsoleRequest;
use RuntimeException;
use Payment\Event\Event as PaymentEvent;
use User\Model\Base as UserBaseModel;

class PaymentConsoleController extends PaymentBaseController
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
                ->getInstance('Payment\Model\PaymentConsole');
        }

        return $this->model;
    }

    /**
     * Clean shopping cart and transactions items
     */
    public function cleanExpiredItemsAction()
    {
        $request = $this->getRequest();

        // Make sure that we are running in a console and the user has not tricked our
        // application into running this action from a public web server.
        if (!$request instanceof ConsoleRequest) {
            throw new RuntimeException('You can only use this action from a console!');
        }
        
        // get list of expired shopping cart items
        $deletedShoppingCartItems = 0;
        if (null != ($items = $this->getModel()->getExpiredShoppingCartItems())) {
            foreach ($items as $item) {
                // delete the item
                if (true === ($deleteResult =
                        $this->getModel()->deleteFromShoppingCart($item['id'], false))) {

                    $deletedShoppingCartItems++;

                    // fire the delete item from shopping cart event
                    PaymentEvent::fireDeleteItemFromShoppingCartEvent($item['id'], true);
                }
            }
        }

        // get list of expired not paid transactions
        $deletedTransactions = 0;
        if (null != ($transactions = $this->getModel()->getExpiredTransactions())) {
            // process list of transactions
            foreach ($transactions as $transaction) {
                // delete the transaction
                if (true === ($deleteResult =
                        $this->getModel()->deleteTransaction($transaction['id'], false))) {

                    $deletedTransactions++;

                    // fire the delete payment transaction event
                    PaymentEvent::fireDeletePaymentTransactionEvent($transaction['id'], 'system');
                }
            }
        }

        $verbose = $request->getParam('verbose');

        if (!$verbose) {
            return 'All expired shopping cart items and expired not paid transactions have been deleted.' . "\n";
        }

        $longDescription  = $deletedShoppingCartItems . ' items have been deleted from the shopping cart.'. "\n";
        $longDescription .= $deletedTransactions . ' not paid transactions have been deleted.'. "\n";

        return $longDescription;
    }
}