<?php

namespace Payment\Test\Model;

use Payment\Test\PaymentBootstrap;
use PHPUnit_Framework_TestCase;

class BaseTest extends PHPUnit_Framework_TestCase
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
     * Setup
     */
    protected function setUp()
    {
        // get service manager
        $this->serviceManager = PaymentBootstrap::getServiceManager();

        // get base model instance
        $this->model = $this->serviceManager
            ->get('Application\Model\ModelManager')
            ->getInstance('Payment\Model\Base');
    }

    /**
     * Tear down
     */
    protected function tearDown()
    {}

    /**
     * Test items amount calculation
     */
    public function testItemsAmount()
    {
        $this->assertEquals($this->model->getItemsAmount(array(
            0 => array(
                'cost' => 20,
                'count' => 3,
                'discount' => 0
            )
        )), 60);

        $this->assertEquals($this->model->getItemsAmount(array(
            0 => array(
                'cost' => 20,
                'count' => 3,
                'discount' => 0
            ),
            1 => array(
                'cost' => 10,
                'count' => 1,
                'discount' => 0
            ),
            2 => array(
                'cost' => 100,
                'count' => 1,
                'discount' => 20
            )
        )), 150);

        $this->assertEquals($this->model->getItemsAmount(array(
            0 => array(
                'cost' => 20,
                'count' => 3,
                'discount' => 0
            ),
            1 => array(
                'cost' => 10,
                'count' => 1,
                'discount' => 0
            ),
            2 => array(
                'cost' => 100,
                'count' => 1,
                'discount' => 20
            )
        ), 50), 75);

        $this->assertEquals($this->model->getItemsAmount(array()), 0);
    }

    /**
     * Test discounted items amount calculation
     */
    public function testDiscountedItemsAmount()
    {
        $this->assertEquals($this->model->getDiscountedItemsAmount(100, 50), 50);
        $this->assertEquals($this->model->getDiscountedItemsAmount(100, 100), 0);
        $this->assertEquals($this->model->getDiscountedItemsAmount(50, 25), 37.5);
    }
}
