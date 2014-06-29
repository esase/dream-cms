<?php
namespace Payment\Test\Model;

use Payment\Test\PaymentBootstrap;
use PHPUnit_Framework_TestCase;
use Application\Service\Service as ApplicationService;

class PaymentConsoleTest extends PHPUnit_Framework_TestCase
{
    /**
     * Service manager
     * @var object
     */
    protected $serviceManager;

    /**
     * Model
     * @var object
     */
    protected $model;

    /**
     * Transaction id
     * @var integer
     */
    protected $transactionId;

    /**
     * Test payment module id
     */
    const TEST_PAYMENT_MODULE_ID = 1; // application module

    /**
     * Test payment item's ID
     */
    const TEST_PAYMENT_ITEM_ID = 999;

    /**
     * Test payment shopping cart's ID
     */
    const TEST_PAYMENT_SHOPPING_CART_ID = 'asdsadasd122132_sada';

    /**
     * Test payment currency
     */
    const TEST_PAYMENT_CURRENCY = 2; // USD

    /**
     * Setup
     */
    protected function setUp()
    {
        // get service manager
        $this->serviceManager = PaymentBootstrap::getServiceManager();

        // get base model instance
        $this->model = $this->serviceManager
            ->get('Application\Model\ModelManager')
            ->getInstance('Payment\Model\PaymentConsole');

        // add a new payment module
        $query = $this->model->insert()
            ->into('payment_module')
            ->values(array(
                'module' => self::TEST_PAYMENT_MODULE_ID
            ));

        $statement = $this->model->prepareStatementForSqlObject($query);
        $statement->execute();
    }

    /**
     * Tear down
     */
    protected function tearDown()
    {
        // delete the test payment module
        $query = $this->model->delete()
            ->from('payment_module')
            ->where(array('module' => self::TEST_PAYMENT_MODULE_ID));

        $statement = $this->model->prepareStatementForSqlObject($query);
        $statement->execute();

        // delete the test payment transaction
        $query = $this->model->delete()
            ->from('payment_transaction')
            ->where(array('id' => $this->transactionId));

        $statement = $this->model->prepareStatementForSqlObject($query);
        $statement->execute();
    }

    /**
     * Test expired shopping cart items
     */
    public function testExpiredShoppingCartItems()
    {
        $itemsLifeTime = ApplicationService::getSetting('payment_clearing_time');

        // add a new shopping cart item
        $query = $this->model->insert()
            ->into('payment_shopping_cart')
            ->values(array(
                'module' => self::TEST_PAYMENT_MODULE_ID,
                'object_id' => self::TEST_PAYMENT_ITEM_ID,
                'shopping_cart_id' => self::TEST_PAYMENT_SHOPPING_CART_ID,
                'date' => time() - $itemsLifeTime
            ));

        $statement = $this->model->prepareStatementForSqlObject($query);
        $statement->execute();
        $itemId = $this->model->getAdapter()->getDriver()->getLastGeneratedValue();

        $this->assertEquals($itemId, current($this->model->getExpiredShoppingCartItems())['id']);
    }

    /**
     * Test expired transactions
     */
    public function testExpiredTransactions()
    {
        $itemsLifeTime = ApplicationService::getSetting('payment_clearing_time');

        // add a new shopping cart item
        $query = $this->model->insert()
            ->into('payment_transaction')
            ->values(array(
                'date' => time() - $itemsLifeTime,
                'currency' => self::TEST_PAYMENT_CURRENCY
            ));

        $statement = $this->model->prepareStatementForSqlObject($query);
        $statement->execute();
        $this->transactionId = $this->model->getAdapter()->getDriver()->getLastGeneratedValue();

        $this->assertEquals($this->transactionId, current($this->model->getExpiredTransactions())['id']);
    }
}