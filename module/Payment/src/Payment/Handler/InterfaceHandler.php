<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Payment\Handler;

interface InterfaceHandler
{
    /**
     * Get the item info
     *
     * @param integer $id
     * @return array
     *      string|integer slug - optional
     *      string title - required
     *      float|array cost - required
     *      float discount - optional
     *      integer count - required (only for countable modules)
     */
    public function getItemInfo($id);

    /**
     * Get discount
     *
     * @param integer $id
     * @return float
     */
    public function getDiscount($id);

    /**
     * Clear the discount
     *
     * @param integer $id
     * @param float $discount
     * @return void
     */
    public function clearDiscount($id, $discount);

    /**
     * Return back the discount
     *
     * @param integer $id
     * @param float $discount
     * @return void
     */
    public function returnBackDiscount($id, $discount);

    /**
     * Decrease the item's count 
     *
     * @param integer $id
     * @param integer $count
     * @return void
     */
    public function decreaseCount($id, $count);

    /**
     * Set the item as paid
     *
     * @param integer $id
     * @param array $transactionInfo
     *      integer id
     *      string slug
     *      integer user_id
     *      string first_name
     *      string last_name
     *      string phone
     *      string address
     *      string email
     *      integer currency
     *      integer payment_type
     *      integer discount_cupon
     *      string currency_code
     *      string payment_name 
     * @return void
     */
    public function setPaid($id, array $transactionInfo);
}