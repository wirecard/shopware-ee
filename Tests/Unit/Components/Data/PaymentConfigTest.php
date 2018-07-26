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

namespace WirecardShopwareElasticEngine\Tests\Unit\Components\Data;

use PHPUnit\Framework\TestCase;
use WirecardShopwareElasticEngine\Components\Data\PaymentConfig;
use WirecardShopwareElasticEngine\Components\Payments\Payment;

class PaymentConfigTest extends TestCase
{
    public function testRequiredValues()
    {
        $config = new PaymentConfig('https://api-test.wirecard.com', '70000-APITEST-AP', 'foobar5!$');

        $this->assertEquals('https://api-test.wirecard.com', $config->getBaseUrl());
        $this->assertEquals('70000-APITEST-AP', $config->getHttpUser());
        $this->assertEquals('foobar5!$', $config->getHttpPassword());
    }

    public function testOptionalValues()
    {
        $config = new PaymentConfig('https://api-test.wirecard.com', 'foo', 'bar');

        $this->assertNull($config->getTransactionMAID());
        $this->assertNull($config->getTransactionSecret());
        $this->assertNull($config->getTransactionOperation());
        $this->assertFalse($config->hasFraudPrevention());
        $this->assertFalse($config->sendBasket());
        $this->assertFalse($config->sendDescriptor());

        $config->setTransactionMAID('transaction-maid');
        $config->setTransactionSecret('transaction-secret');
        $config->setTransactionOperation(Payment::TRANSACTION_OPERATION_PAY);
        $config->setFraudPrevention(true);
        $config->setSendBasket(true);
        $config->setSendDescriptor(true);

        $this->assertEquals('transaction-maid', $config->getTransactionMAID());
        $this->assertEquals('transaction-secret', $config->getTransactionSecret());
        $this->assertEquals(Payment::TRANSACTION_OPERATION_PAY, $config->getTransactionOperation());
        $this->assertTrue($config->hasFraudPrevention());
        $this->assertTrue($config->sendBasket());
        $this->assertTrue($config->sendDescriptor());

        $config->setTransactionOperation(Payment::TRANSACTION_OPERATION_RESERVE);
        $config->setFraudPrevention(false);
        $config->setSendBasket(false);
        $config->setSendDescriptor(false);
        $this->assertEquals(Payment::TRANSACTION_OPERATION_RESERVE, $config->getTransactionOperation());
        $this->assertFalse($config->hasFraudPrevention());
        $this->assertFalse($config->sendBasket());
        $this->assertFalse($config->sendDescriptor());

        $config->setFraudPrevention('1');
        $config->setSendBasket('1');
        $config->setSendDescriptor('1');
        $this->assertTrue($config->hasFraudPrevention());
        $this->assertTrue($config->sendBasket());
        $this->assertTrue($config->sendDescriptor());

        $config->setFraudPrevention('0');
        $config->setSendBasket('0');
        $config->setSendDescriptor('0');
        $this->assertFalse($config->hasFraudPrevention());
        $this->assertFalse($config->sendBasket());
        $this->assertFalse($config->sendDescriptor());

        $this->assertEquals([
            'baseUrl'              => 'https://api-test.wirecard.com',
            'transactionMAID'      => 'transaction-maid',
            'transactionOperation' => Payment::TRANSACTION_OPERATION_RESERVE,
            'sendBasket'           => false,
            'fraudPrevention'      => false,
            'sendDescriptor'       => false,
        ], $config->toArray());
    }
}
