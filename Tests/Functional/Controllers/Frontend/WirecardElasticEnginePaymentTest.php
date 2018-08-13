<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Tests\Functional\Controllers\Frontend;

use Doctrine\ORM\EntityRepository;
use WirecardElasticEngine\Components\Payments\CreditCardPayment;
use WirecardElasticEngine\Models\Transaction;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class WirecardElasticEnginePaymentTest extends \Enlight_Components_Test_Plugin_TestCase
{
    const USER_AGENT = 'Mozilla/5.0 (Android; Tablet; rv:14.0) Gecko/14.0 Firefox/14.0';

    public function setUp()
    {
        parent::setUp();
        $this->reset();
    }

    public function testIndexAction()
    {
        $basketData = require __DIR__ . '/testdata/index-basket.php';

        $orderVariables              = new \ArrayObject();
        $orderVariables['sBasket']   = $basketData['sBasket'];
        $orderVariables['sUserData'] = $basketData['sUserData'];
        Shopware()->Session()->offsetSet('sOrderVariables', $orderVariables);

        $this->Request()->setMethod('GET');
        $this->Request()->setHeader('User-Agent', self::USER_AGENT);

        $response = $this->dispatch('/WirecardElasticEnginePayment');

        $this->assertTrue($response->isRedirect());
        $this->assertEquals(302, $response->getHttpResponseCode());
        $locationHeader = $response->getHeaders()[0];
        $this->assertEquals('Location', $locationHeader['name']);
        $this->assertContains('sandbox.paypal.com', $locationHeader['value']);
    }

    public function testIndexActionBasketException()
    {
        $basketData = require __DIR__ . '/testdata/index-basket.php';

        // Quantity causes BasketException thrown in basket validation
        $basketData['sBasket']['content'][0]['quantity'] = 10000;

        $orderVariables              = new \ArrayObject();
        $orderVariables['sBasket']   = $basketData['sBasket'];
        $orderVariables['sUserData'] = $basketData['sUserData'];
        Shopware()->Session()->offsetSet('sOrderVariables', $orderVariables);

        $this->Request()->setMethod('GET');
        $this->Request()->setHeader('User-Agent', self::USER_AGENT);

        $response = $this->dispatch('/WirecardElasticEnginePayment');

        $this->assertTrue($response->isRedirect());
        $this->assertEquals(302, $response->getHttpResponseCode());
        $locationHeader = $response->getHeaders()[0];
        $this->assertEquals('Location', $locationHeader['name']);
        $this->assertContains('checkout/shippingPayment/wirecard_elastic_engine_error_code/2',
            $locationHeader['value']);
    }

    public function testReturnActionSuccess()
    {
        $initialRequest  = json_decode(file_get_contents(__DIR__ . '/testdata/initial-request.json'), true);
        $returnPayload   = json_decode(file_get_contents(__DIR__ . '/testdata/return-payload.json'), true);
        $basketData      = require __DIR__ . '/testdata/return-basket.php';
        $basketSignature = '3691c683586000115ef63cb81ada7e7ade4d5b9d0242b020d01e88ff559ffc9a';
        $paymentUniqueId = '1532501234exxxf';
        $requestId       = '249XXXXXXXXXxxxXXXXXXXXXxxxXXXXXXXXXxxxXXXXXXXXXXXXXXXXxxxXXXc87';

        $this->prepareInitialTransaction($paymentUniqueId, $requestId, $basketSignature, $initialRequest);

        // delete order from a previous test
        Shopware()->Db()->delete('s_order', "temporaryID='$paymentUniqueId' AND status!=-1");

        // prepare shopware basket
        $persister = Shopware()->Container()->get('basket_persister');
        $persister->persist($basketSignature, $basketData);

        $this->Request()->setMethod('GET');
        $this->Request()->setHeader('User-Agent', self::USER_AGENT);
        $this->Request()->setParams($returnPayload);

        $response = $this->dispatch('/WirecardElasticEnginePayment/return/method/'
                                    . CreditCardPayment::PAYMETHOD_IDENTIFIER);

        $this->assertTrue($response->isRedirect());
        $this->assertEquals(302, $response->getHttpResponseCode());
        $locationHeader = $response->getHeaders()[0];
        $this->assertEquals('Location', $locationHeader['name']);
        $this->assertContains('checkout/finish/sUniqueID/' . $paymentUniqueId,
            $locationHeader['value']);
    }

    public function testReturnActionMissingPayload()
    {
        $this->Request()->setMethod('GET');
        $this->Request()->setHeader('User-Agent', self::USER_AGENT);

        $response = $this->dispatch('/WirecardElasticEnginePayment/return/method/'
                                    . CreditCardPayment::PAYMETHOD_IDENTIFIER);

        $this->assertTrue($response->isRedirect());
        $this->assertEquals(302, $response->getHttpResponseCode());
        $locationHeader = $response->getHeaders()[0];
        $this->assertEquals('Location', $locationHeader['name']);
        $this->assertContains('checkout/shippingPayment/wirecard_elastic_engine_error_code/1',
            $locationHeader['value']);
    }

    public function testNotifyAction()
    {
        $initialRequest  = json_decode(file_get_contents(__DIR__ . '/testdata/initial-request.json'), true);
        $notifyPayload   = file_get_contents(__DIR__ . '/testdata/notify-payload.xml');
        $basketSignature = '3691c683586000115ef63cb81ada7e7ade4d5b9d0242b020d01e88ff559ffc9a';
        $paymentUniqueId = '1532501234exxxf';
        $requestId       = '249XXXXXXXXXxxxXXXXXXXXXxxxXXXXXXXXXxxxXXXXXXXXXXXXXXXXxxxXXXc87';

        $this->prepareInitialTransaction($paymentUniqueId, $requestId, $basketSignature, $initialRequest);

        $this->Request()->setMethod('GET');
        $this->Request()->setHeader('User-Agent', self::USER_AGENT);
        $this->Request()->setRawBody($notifyPayload);

        $response = $this->dispatch('/WirecardElasticEnginePayment/notify/method/'
                                    . CreditCardPayment::PAYMETHOD_IDENTIFIER);

        $this->assertFalse($response->isRedirect());
        $this->assertEquals(200, $response->getHttpResponseCode());
        $this->assertEmpty($response->getBody());
    }

    private function prepareInitialTransaction($paymentUniqueId, $requestId, $basketSignature, $initialRequest)
    {
        // prepare initial transaction
        $em = Shopware()->Container()->get('models');
        /** @var EntityRepository $repo */
        $repo        = $em->getRepository(Transaction::class);
        $transaction = $repo->findOneBy(['requestId' => $requestId]);
        if (! $transaction) {
            $transaction = new Transaction(Transaction::TYPE_INITIAL_REQUEST);
            $transaction->setPaymentUniqueId($paymentUniqueId);
            $transaction->setBasketSignature($basketSignature);
            $transaction->setRequest($initialRequest);
            $em->persist($transaction);
            $em->flush();
        }
        return $transaction;
    }
}
