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

namespace WirecardElasticEngine\Tests\Unit\Components\Data;

use PHPUnit\Framework\TestCase;
use WirecardElasticEngine\Components\Data\IdealPaymentConfig;
use WirecardElasticEngine\Components\Data\PaymentConfig;
use WirecardElasticEngine\Components\Payments\Payment;

class IdealPaymentConfigTest extends TestCase
{
    public function testRequiredValues()
    {
        $config = new IdealPaymentConfig('https://api-test.wirecard.com', '70000-APITEST-AP', 'foobar5!$');
        $this->assertInstanceOf(PaymentConfig::class, $config);
    }

    public function testOptionalValues()
    {
        $config = new IdealPaymentConfig('https://api-test.wirecard.com', 'foo', 'bar');
        $config->setTransactionMAID('transaction-maid');
        $config->setTransactionSecret('transaction-secret');
        $config->setTransactionOperation(Payment::TRANSACTION_OPERATION_PAY);

        $this->assertNull($config->getBackendCreditorId());
        $this->assertNull($config->getBackendTransactionMAID());
        $this->assertNull($config->getBackendTransactionSecret());

        $config->setBackendCreditorId('backend-id');
        $config->setBackendTransactionMAID('backend-maid');
        $config->setBackendTransactionSecret('backend-secret');

        $this->assertEquals('backend-id', $config->getBackendCreditorId());
        $this->assertEquals('backend-maid', $config->getBackendTransactionMAID());
        $this->assertEquals('backend-secret', $config->getBackendTransactionSecret());

        $this->assertEquals([
            'baseUrl'                => 'https://api-test.wirecard.com',
            'transactionMAID'        => 'transaction-maid',
            'transactionOperation'   => Payment::TRANSACTION_OPERATION_PAY,
            'sendBasket'             => false,
            'fraudPrevention'        => false,
            'sendDescriptor'         => false,
            'backendTransactionMaid' => 'backend-maid',
            'backendCreditorId'      => 'backend-id',
        ], $config->toArray());
    }
}