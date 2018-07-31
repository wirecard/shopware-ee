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

namespace WirecardShopwareElasticEngine\Tests\Functional\Controllers\Backend;

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
