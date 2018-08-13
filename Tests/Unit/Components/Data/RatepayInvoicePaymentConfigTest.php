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
use WirecardElasticEngine\Components\Data\PaymentConfig;
use WirecardElasticEngine\Components\Data\RatepayInvoicePaymentConfig;
use WirecardElasticEngine\Components\Payments\Payment;

class RatepayInvoicePaymentConfigTest extends TestCase
{
    public function testRequiredValues()
    {
        $config = new RatepayInvoicePaymentConfig('https://api-test.wirecard.com', '70000-APITEST-AP', 'foobar5!$');
        $this->assertInstanceOf(PaymentConfig::class, $config);
    }

    public function testOptionalValues()
    {
        $config = new RatepayInvoicePaymentConfig('https://api-test.wirecard.com', 'foo', 'bar');
        $config->setTransactionMAID('transaction-maid');
        $config->setTransactionSecret('transaction-secret');
        $config->setTransactionOperation(Payment::TRANSACTION_OPERATION_PAY);

        $this->assertNull($config->getMinAmount());
        $this->assertNull($config->getMaxAmount());
        $this->assertNull($config->getAcceptedCurrencies());
        $this->assertNull($config->getBillingCountries());
        $this->assertNull($config->getShippingCountries());
        $this->assertNull($config->isAllowedDifferentBillingShipping());

        $config->setAcceptedCurrencies(null);
        $config->setBillingCountries(null);
        $config->setShippingCountries(null);
        $this->assertEquals([], $config->getAcceptedCurrencies());
        $this->assertEquals([], $config->getBillingCountries());
        $this->assertEquals([], $config->getShippingCountries());

        $config->setMinAmount('12');
        $config->setMaxAmount('4321');
        $config->setAcceptedCurrencies([1]);
        $config->setBillingCountries([1, 2]);
        $config->setShippingCountries([1, 2, 3]);
        $config->setAllowDifferentBillingShipping(true);

        $this->assertEquals('12', $config->getMinAmount());
        $this->assertEquals('4321', $config->getMaxAmount());
        $this->assertEquals([1], $config->getAcceptedCurrencies());
        $this->assertEquals([1, 2], $config->getBillingCountries());
        $this->assertEquals([1, 2, 3], $config->getShippingCountries());
        $this->assertTrue($config->isAllowedDifferentBillingShipping());

        $config->setMinAmount(5.5);
        $config->setMaxAmount(100.5);
        $this->assertEquals(5.5, $config->getMinAmount());
        $this->assertEquals(100.5, $config->getMaxAmount());

        $this->assertEquals([
            'baseUrl'                       => 'https://api-test.wirecard.com',
            'transactionMAID'               => 'transaction-maid',
            'transactionOperation'          => Payment::TRANSACTION_OPERATION_PAY,
            'sendBasket'                    => false,
            'fraudPrevention'               => false,
            'sendDescriptor'                => false,
            'minAmount'                     => 5.5,
            'maxAmount'                     => 100.5,
            'acceptedCurrencies'            => [1],
            'billingCountries'              => [1, 2],
            'shippingCountries'             => [1, 2, 3],
            'allowDifferentBillingShipping' => true,
        ], $config->toArray());
    }
}
