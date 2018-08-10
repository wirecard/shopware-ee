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
