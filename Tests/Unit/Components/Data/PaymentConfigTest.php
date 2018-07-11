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
        $this->assertNull($config->getTransactionType());
        $this->assertNull($config->getThreeDMAID());
        $this->assertNull($config->getThreeDSecret());
        $this->assertNull($config->getThreeDMinLimit());
        $this->assertNull($config->getThreeDMinLimitCurrency());
        $this->assertNull($config->getThreeDSslMaxLimit());
        $this->assertNull($config->getThreeDSslMaxLimitCurrency());
        $this->assertFalse($config->hasFraudPrevention());
        $this->assertFalse($config->sendBasket());
        $this->assertFalse($config->sendDescriptor());

        $config->setTransactionMAID('transaction-maid');
        $config->setTransactionSecret('transaction-secret');
        $config->setTransactionType(Payment::TRANSACTION_TYPE_AUTHORIZATION);
        $config->setThreeDMAID('three3d-maid');
        $config->setThreeDSecret('three3d-secret');
        $config->setThreeDMinLimit(50.0);
        $config->setThreeDMinLimitCurrency('EUR');
        $config->setThreeDSslMaxLimit(200.0);
        $config->setThreeDSslMaxLimitCurrency('USD');
        $config->setFraudPrevention(true);
        $config->setSendBasket(true);
        $config->setSendDescriptor(true);

        $this->assertEquals('transaction-maid', $config->getTransactionMAID());
        $this->assertEquals('transaction-secret', $config->getTransactionSecret());
        $this->assertEquals(Payment::TRANSACTION_TYPE_AUTHORIZATION, $config->getTransactionType());
        $this->assertEquals('three3d-maid', $config->getThreeDMAID());
        $this->assertEquals('three3d-secret', $config->getThreeDSecret());
        $this->assertEquals(50.0, $config->getThreeDMinLimit());
        $this->assertEquals('EUR', $config->getThreeDMinLimitCurrency());
        $this->assertEquals(200.0, $config->getThreeDSslMaxLimit());
        $this->assertEquals('USD', $config->getThreeDSslMaxLimitCurrency());
        $this->assertTrue($config->hasFraudPrevention());
        $this->assertTrue($config->sendBasket());
        $this->assertTrue($config->sendDescriptor());

        $config->setTransactionType(Payment::TRANSACTION_TYPE_PURCHASE);
        $config->setFraudPrevention(false);
        $config->setSendBasket(false);
        $config->setSendDescriptor(false);
        $this->assertEquals(Payment::TRANSACTION_TYPE_PURCHASE, $config->getTransactionType());
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

        $config->setThreeDMinLimit('500,0');
        $config->setThreeDSslMaxLimit('2000,0');
        $this->assertEquals('500,0', $config->getThreeDMinLimit());
        $this->assertEquals('2000,0', $config->getThreeDSslMaxLimit());

        $this->assertEquals([
            'baseUrl'                   => 'https://api-test.wirecard.com',
            'httpUser'                  => 'foo',
            'transactionMAID'           => 'transaction-maid',
            'transactionType'           => Payment::TRANSACTION_TYPE_PURCHASE,
            'sendBasket'                => false,
            'fraudPrevention'           => false,
            'sendDescriptor'            => false,
            'threeDMAID'                => 'three3d-maid',
            'threeDMinLimit'            => '500,0',
            'threeDMinLimitCurrency'    => 'EUR',
            'threeDSslMaxLimit'         => '2000,0',
            'threeDSslMaxLimitCurrency' => 'USD',
        ], $config->toArray());
    }
}
