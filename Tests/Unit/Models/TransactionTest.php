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

namespace WirecardShopwareElasticEngine\Tests\Unit\Models;

use WirecardShopwareElasticEngine\Components\Payments\Payment;
use WirecardShopwareElasticEngine\Models\Transaction;
use WirecardShopwareElasticEngine\Tests\Unit\ModelTestCase;

class TransactionTest extends ModelTestCase
{
    /**
     * @var Transaction
     */
    protected $model;

    public function getModel()
    {
        return new Transaction();
    }

    public function testGetId()
    {
        $this->assertNull($this->model->getId());
    }

    public function testSettersAndGetters()
    {
        $this->assertGetterAndSetter('orderNumber', 1337);
        $this->assertGetterAndSetter('parentTransactionId', '6832b2f0-792b-4161-9f9a-f2916f7aae8e');
        $this->assertGetterAndSetter('transactionType', Payment::TRANSACTION_TYPE_PURCHASE);
        $this->assertGetterAndSetter('transactionId', '6832b2f0-abcd-4161-9f9a-f2916f7aae8e');
        $this->assertGetterAndSetter('providerTransactionId', '6832b2f0-efgh-4161-9f9a-f2916f7aae8e');
        $this->assertGetterAndSetter('providerTransactionReference', 'foo');
        $this->assertGetterAndSetter('requestId', 'bar');
        $this->assertGetterAndSetter('amount', 42.42);
        $this->assertGetterAndSetter('currency', 'USD');
        $this->assertGetterAndSetter('type', Transaction::TYPE_NOTIFY);
        $this->assertGetterAndSetter('state', Transaction::STATE_CLOSED, Transaction::STATE_OPEN);
        $this->assertGetterAndSetter('response', [
            'transaction-id' => '6832b2f0-abcd-4161-9f9a-f2916f7aae8e',
            'request-id'     => 'bar',
        ]);

        $this->assertEquals([
            'id'                           => null,
            'orderNumber'                  => 1337,
            'transactionType'              => Payment::TRANSACTION_TYPE_PURCHASE,
            'transactionId'                => '6832b2f0-abcd-4161-9f9a-f2916f7aae8e',
            'parentTransactionId'          => '6832b2f0-792b-4161-9f9a-f2916f7aae8e',
            'providerTransactionId'        => '6832b2f0-efgh-4161-9f9a-f2916f7aae8e',
            'providerTransactionReference' => 'foo',
            'requestId'                    => 'bar',
            'type'                         => 'notify',
            'amount'                       => 42.42,
            'currency'                     => 'USD',
            'createdAt'                    => null,
            'response'                     => [
                'transaction-id' => '6832b2f0-abcd-4161-9f9a-f2916f7aae8e',
                'request-id'     => 'bar',
            ],
            'state'                        => 'closed',
        ], $this->model->toArray());
    }

    public function testCreatedAt()
    {
        $this->assertGetterAndSetter('createdAt', new \DateTime());
    }
}
