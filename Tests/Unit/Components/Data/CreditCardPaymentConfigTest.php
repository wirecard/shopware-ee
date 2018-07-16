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
use WirecardShopwareElasticEngine\Components\Data\CreditCardPaymentConfig;
use WirecardShopwareElasticEngine\Components\Data\PaymentConfig;
use WirecardShopwareElasticEngine\Components\Payments\Payment;

class CreditCardPaymentConfigTest extends TestCase
{
    public function testRequiredValues()
    {
        $config = new CreditCardPaymentConfig('https://api-test.wirecard.com', '70000-APITEST-AP', 'foobar5!$');
        $this->assertInstanceOf(PaymentConfig::class, $config);
    }

    public function testOptionalValues()
    {
        $config = new CreditCardPaymentConfig('https://api-test.wirecard.com', 'foo', 'bar');
        $config->setTransactionMAID('transaction-maid');
        $config->setTransactionSecret('transaction-secret');
        $config->setTransactionOperation(Payment::TRANSACTION_OPERATION_PAY);

        $this->assertNull($config->getThreeDMAID());
        $this->assertNull($config->getThreeDSecret());
        $this->assertNull($config->getThreeDMinLimit());
        $this->assertNull($config->getThreeDMinLimitCurrency());
        $this->assertNull($config->getThreeDSslMaxLimit());
        $this->assertNull($config->getThreeDSslMaxLimitCurrency());

        $config->setThreeDMAID('three3d-maid');
        $config->setThreeDSecret('three3d-secret');
        $config->setThreeDMinLimit(50.0);
        $config->setThreeDMinLimitCurrency('EUR');
        $config->setThreeDSslMaxLimit(200.0);
        $config->setThreeDSslMaxLimitCurrency('USD');

        $this->assertEquals('three3d-maid', $config->getThreeDMAID());
        $this->assertEquals('three3d-secret', $config->getThreeDSecret());
        $this->assertEquals(50.0, $config->getThreeDMinLimit());
        $this->assertEquals('EUR', $config->getThreeDMinLimitCurrency());
        $this->assertEquals(200.0, $config->getThreeDSslMaxLimit());
        $this->assertEquals('USD', $config->getThreeDSslMaxLimitCurrency());

        $config->setThreeDMinLimit('500,0');
        $config->setThreeDSslMaxLimit('2000,0');
        $this->assertEquals('500,0', $config->getThreeDMinLimit());
        $this->assertEquals('2000,0', $config->getThreeDSslMaxLimit());

        $this->assertEquals([
            'baseUrl'                   => 'https://api-test.wirecard.com',
            'httpUser'                  => 'foo',
            'transactionMAID'           => 'transaction-maid',
            'transactionOperation'      => Payment::TRANSACTION_OPERATION_PAY,
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
