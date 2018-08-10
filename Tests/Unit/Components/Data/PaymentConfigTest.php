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
use WirecardElasticEngine\Components\Payments\Payment;

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
