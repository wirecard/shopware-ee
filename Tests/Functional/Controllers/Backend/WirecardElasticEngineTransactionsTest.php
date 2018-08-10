<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Tests\Functional\Controllers\Backend;

use Wirecard\PaymentSdk\Transaction\Operation;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class WirecardElasticEngineTransactionsTest extends \Enlight_Components_Test_Plugin_TestCase
{
    const USER_AGENT = 'Mozilla/5.0 (Android; Tablet; rv:14.0) Gecko/14.0 Firefox/14.0';

    public function setUp()
    {
        parent::setUp();

        /** @var \Shopware_Plugins_Backend_PluginManager_Bootstrap $auth */
        $backend = Shopware()->Plugins()->get('Backend');
        /** @var \Shopware_Plugins_Backend_Auth_Bootstrap $auth */
        $auth = $backend->get('Auth');

        // disable auth and acl
        $auth->setNoAuth();
        $auth->setNoAcl();
    }

    public function testListAction()
    {
        $this->Request()->setMethod('GET');
        $this->Request()->setHeader('User-Agent', self::USER_AGENT);

        $response = $this->dispatch('/backend/WirecardElasticEngineTransactions/list');

        $this->assertEquals(200, $response->getHttpResponseCode());
        $body = $response->getBody('default');
        $data = json_decode($body, true);
        $this->assertArrayHasKey('total', $data);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
    }

    public function testCredentialsAction()
    {
        $this->Request()->setMethod('GET');
        $this->Request()->setHeader('User-Agent', self::USER_AGENT);

        $this->Request()->setParam('method', 'CreditCard');
        $this->Request()->setParam('wirecardElasticEngineCreditCardServer', 'https://api-test.wirecard.com');
        $this->Request()->setParam('wirecardElasticEngineCreditCardHttpUser', '70000-APITEST-AP');
        $this->Request()->setParam('wirecardElasticEngineCreditCardHttpPassword', 'qD2wzQ_hrc!8');
        $response = $this->dispatch('/backend/WirecardElasticEngineTransactions/testCredentials');

        $this->assertEquals(200, $response->getHttpResponseCode());
        $body = $response->getBody('default');
        $data = json_decode($body, true);
        $this->assertEquals([
            'status' => 'success',
            'method' => 'CreditCard',
        ], $data);
    }

    public function testCredentialsActionFails()
    {
        $this->Request()->setMethod('GET');
        $this->Request()->setHeader('User-Agent', self::USER_AGENT);

        $response = $this->dispatch('/backend/WirecardElasticEngineTransactions/testCredentials');

        $this->assertEquals(200, $response->getHttpResponseCode());
        $body = $response->getBody('default');
        $data = json_decode($body, true);
        $this->assertEquals([
            'status' => 'failed',
            'msg'    => 'Missing credentials. Please check Server, HttpUser and HttpPassword.',
        ], $data);
    }

    public function testDetailsActionNoTransactions()
    {
        $this->Request()->setMethod('GET');
        $this->Request()->setHeader('User-Agent', self::USER_AGENT);

        $this->Request()->setParam('payment', 'wirecard_elastic_engine_credit_card');
        $this->Request()->setParam('orderNumber', '20001');
        $response = $this->dispatch('/backend/WirecardElasticEngineTransactions/details');

        $this->assertEquals(200, $response->getHttpResponseCode());
        $body = $response->getBody('default');
        $data = json_decode($body, true);
        $this->assertEquals([
            'success' => false,
            'message' => 'No transactions found',
        ], $data);
    }

    public function testProcessBackendOperationsActionFails()
    {
        $this->Request()->setMethod('GET');
        $this->Request()->setHeader('User-Agent', self::USER_AGENT);

        $this->Request()->setParam('payment', 'wirecard_elastic_engine_credit_card');
        $this->Request()->setParam('operation', Operation::CANCEL);
        $this->Request()->setParam('transactionId', '0');
        $response = $this->dispatch('/backend/WirecardElasticEngineTransactions/processBackendOperations');

        $this->assertEquals(200, $response->getHttpResponseCode());
        $body = $response->getBody('default');
        $data = json_decode($body, true);
        $this->assertEquals([
            'success' => false,
            'message' => 'Transaction processing failed',
        ], $data);
    }
}
