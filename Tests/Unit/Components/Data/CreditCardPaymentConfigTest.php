<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Tests\Unit\Components\Data;

use PHPUnit\Framework\TestCase;
use WirecardElasticEngine\Components\Data\CreditCardPaymentConfig;
use WirecardElasticEngine\Components\Data\PaymentConfig;
use WirecardElasticEngine\Components\Payments\Payment;

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
        $this->assertNull($config->getSslMaxLimit());
        $this->assertNull($config->getSslMaxLimitCurrency());
        $this->assertNull($config->isVaultEnabled());
        $this->assertNull($config->allowAddressChanges());
        $this->assertNull($config->useThreeDOnTokens());

        $config->setThreeDMAID('three3d-maid');
        $config->setThreeDSecret('three3d-secret');
        $config->setThreeDMinLimit(50.0);
        $config->setThreeDMinLimitCurrency('EUR');
        $config->setSslMaxLimit(200.0);
        $config->setSslMaxLimitCurrency('USD');
        $config->setVaultEnabled(true);
        $config->setAllowAddressChanges(true);
        $config->setThreeDUsageOnTokens(true);

        $this->assertEquals('three3d-maid', $config->getThreeDMAID());
        $this->assertEquals('three3d-secret', $config->getThreeDSecret());
        $this->assertEquals(50.0, $config->getThreeDMinLimit());
        $this->assertEquals('EUR', $config->getThreeDMinLimitCurrency());
        $this->assertEquals(200.0, $config->getSslMaxLimit());
        $this->assertEquals('USD', $config->getSslMaxLimitCurrency());
        $this->assertTrue($config->isVaultEnabled());
        $this->assertTrue($config->allowAddressChanges());
        $this->assertTrue($config->useThreeDOnTokens());

        $config->setThreeDMinLimit('500,0');
        $config->setSslMaxLimit('2000,0');
        $this->assertEquals('500,0', $config->getThreeDMinLimit());
        $this->assertEquals('2000,0', $config->getSslMaxLimit());

        $this->assertEquals([
            'baseUrl'                => 'https://api-test.wirecard.com',
            'transactionMAID'        => 'transaction-maid',
            'transactionOperation'   => Payment::TRANSACTION_OPERATION_PAY,
            'sendBasket'             => false,
            'fraudPrevention'        => false,
            'sendDescriptor'         => false,
            'threeDMAID'             => 'three3d-maid',
            'threeDMinLimit'         => '500,0',
            'threeDMinLimitCurrency' => 'EUR',
            'sslMaxLimit'            => '2000,0',
            'sslMaxLimitCurrency'    => 'USD',
            'vaultEnabled'           => true,
            'allowAddressChanges'    => true,
            'threeDUsageOnTokens'    => true,
        ], $config->toArray());
    }
}
