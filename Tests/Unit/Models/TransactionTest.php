<?php
/**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard AG and are explicitly not part
 * of the Wirecard AG range of products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License version 3 (GPLv3) and can be used, developed and passed on to third parties under
 * the same terms.
 *
 * However, Wirecard AG does not provide any guarantee or accept any liability for any errors
 * occurring when used in an enhanced, customized shop system configuration.
 *
 * Operation in an enhanced, customized configuration is at your own risk and requires a
 * comprehensive test phase by the user of the plugin.
 *
 * Customers use the plugins at their own risk. Wirecard AG does not guarantee their full
 * functionality neither does Wirecard AG assume liability for any disadvantages related to
 * the use of the plugins. Additionally, Wirecard AG does not guarantee the full functionality
 * for customized shop systems or installed plugins of other vendors of plugins within the same
 * shop system.
 *
 * Customers are responsible for testing the plugin's functionality before starting productive
 * operation.
 *
 * By installing the plugin into the shop system the customer agrees to these terms of use.
 * Please do not use the plugin if you do not agree to these terms of use!
 */

namespace WirecardShopwareElasticEngine\Tests\Functional\Components\Payments;

use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;
use WirecardShopwareElasticEngine\Models\Transaction;

class TransactionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Transaction
     */
    protected $model;

    public function setUp()
    {
        $this->model = new Transaction();
    }

    public function testGetId()
    {
        $this->assertNull($this->model->getId());
    }

    public function testOrderNumber()
    {
        $this->assertNull($this->model->getOrderNumber());
        $this->model->setOrderNumber(42);
        $this->assertEquals(42, $this->model->getOrderNumber());
    }

    public function testOrder()
    {
        $this->assertNull($this->model->getOrder());

        /** @var Order $order */
        $order = new Order();
        $this->model->setOrder($order);

        $this->assertSame($order, $this->model->getOrder());
    }

    public function testTransactionId()
    {
        $this->assertNull($this->model->getTransactionId());
        $this->model->setTransactionId('6832b2f0-792b-4161-9f9a-f2916f7aae8e');
        $this->assertEquals('6832b2f0-792b-4161-9f9a-f2916f7aae8e', $this->model->getTransactionId());
    }

    public function testProviderTransactionId()
    {
        $this->assertNull($this->model->getProviderTransactionId());
        $this->model->setProviderTransactionId('14B82779CX007053E');
        $this->assertEquals('14B82779CX007053E', $this->model->getProviderTransactionId());
    }

    public function testReturnResponse()
    {
        $this->assertNull($this->model->getReturnResponse());

        $response = [
            'transaction-id' => '1bd5e7cb-552d-4a31-b72c-dfac4ec30130',
            'request-id'     => 'de4cf94fd467aa5c4a5590d4490ff855',
        ];

        $this->model->setReturnResponse(serialize($response));
        $this->assertEquals(serialize($response), $this->model->getReturnResponse());
    }

    public function testNotificationResponse()
    {
        $this->assertNull($this->model->getNotificationResponse());

        $response = [
            'transaction-id' => '6832b2f0-792b-4161-9f9a-f2916f7aae8e',
            'request-id'     => 'db2616a7bc7d140ec4e20117c8582a54',
        ];

        $this->model->setNotificationResponse(serialize($response));
        $this->assertEquals(serialize($response), $this->model->getNotificationResponse());
    }

    public function testPaymentStatus()
    {
        $this->assertNull($this->model->getPaymentStatus());
        $this->model->setPaymentStatus(Status::PAYMENT_STATE_RESERVED);
        $this->assertEquals(Status::PAYMENT_STATE_RESERVED, $this->model->getPaymentStatus());
    }
}
