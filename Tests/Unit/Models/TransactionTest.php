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

use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;
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

    public function testGettersAndSetters()
    {
        $this->assertGetterAndSetter('orderNumber', 42);
        $this->assertGetterAndSetter('transactionId', '6832b2f0-792b-4161-9f9a-f2916f7aae8e');
        $this->assertGetterAndSetter('providerTransactionId', '14B82779CX007053E');
        $this->assertGetterAndSetter('paymentStatus', Status::PAYMENT_STATE_RESERVED);
        $this->assertGetterAndSetter('basketSignature', '0fc30f3d8823f331f59b08f7d9942700451f6d5d2a360e67a4023ac740f9e421');
        $this->assertGetterAndSetter('requestId', '693715a8071485c44fe4a5d8c1114e697555f84ca7daded7e3d314e0815aec01');
    }
}
