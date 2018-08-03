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
use WirecardElasticEngine\Components\Data\SepaPaymentConfig;
use WirecardElasticEngine\Components\Payments\Payment;

class SepaPaymentConfigTest extends TestCase
{
    public function testRequiredValues()
    {
        $config = new SepaPaymentConfig('https://api-test.wirecard.com', '70000-APITEST-AP', 'foobar5!$');
        $this->assertInstanceOf(PaymentConfig::class, $config);
    }

    public function testOptionalValues()
    {
        $config = new SepaPaymentConfig('https://api-test.wirecard.com', 'foo', 'bar');
        $config->setTransactionMAID('transaction-maid');
        $config->setTransactionSecret('transaction-secret');
        $config->setTransactionOperation(Payment::TRANSACTION_OPERATION_PAY);

        $this->assertFalse($config->showBic());
        $this->assertNull($config->getCreditorId());
        $this->assertNull($config->getCreditorName());
        $this->assertNull($config->getCreditorAddress());
        $this->assertNull($config->getBackendCreditorId());
        $this->assertNull($config->getBackendTransactionMAID());
        $this->assertNull($config->getBackendTransactionSecret());

        $config->setShowBic(true);
        $config->setCreditorId('creditor-id');
        $config->setCreditorName('creditor-name');
        $config->setCreditorAddress('creditor-address');
        $config->setBackendCreditorId('backend-id');
        $config->setBackendTransactionMAID('backend-maid');
        $config->setBackendTransactionSecret('backend-secret');

        $this->assertTrue($config->showBic());
        $this->assertEquals('creditor-id', $config->getCreditorId());
        $this->assertEquals('creditor-name', $config->getCreditorName());
        $this->assertEquals('creditor-address', $config->getCreditorAddress());
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
            'showBic'                => true,
            'creditorId'             => 'creditor-id',
            'creditorName'           => 'creditor-name',
            'creditorAddress'        => 'creditor-address',
            'backendTransactionMaid' => 'backend-maid',
            'backendCreditorId'      => 'backend-id',
        ], $config->toArray());
    }
}
