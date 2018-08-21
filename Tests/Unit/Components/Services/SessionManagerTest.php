<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Tests\Unit\Components\Services;

use PHPUnit\Framework\TestCase;
use WirecardElasticEngine\Components\Services\SessionManager;

class SessionManagerTest extends TestCase
{
    /** @var \Enlight_Components_Session_Namespace|\PHPUnit_Framework_MockObject_MockObject */
    private $session;

    /** @var SessionManager */
    private $manager;

    public function setUp()
    {
        $this->session = $this->createMock(\Enlight_Components_Session_Namespace::class);
        $this->manager = new SessionManager($this->session);
    }

    public function testPaymentData()
    {
        $this->assertNull($this->manager->getPaymentData());

        $this->session->expects($this->atLeastOnce())->method('offsetSet')->with(
            SessionManager::PAYMENT_DATA,
            ['paymentData']
        );

        $this->manager->storePaymentData(['paymentData']);

        $this->session->expects($this->atLeastOnce())->method('offsetExists')->willReturn(true);
        $this->session->expects($this->atLeastOnce())->method('offsetGet')->willReturn(['paymentDataTest']);
        $this->assertEquals(['paymentDataTest'], $this->manager->getPaymentData());
    }

    public function testGetUserId()
    {
        $this->assertNull($this->manager->getUserId());
        $this->session->expects($this->atLeastOnce())->method('offsetGet')->with('sUserId')->willReturn('1');
        $this->assertEquals('1', $this->manager->getUserId());
    }

    public function testGetUserInfo()
    {
        $this->assertNull($this->manager->getUserInfo());
        $this->session->expects($this->atLeastOnce())->method('offsetGet')->with('userInfo')->willReturn(['info']);
        $this->assertEquals(['info'], $this->manager->getUserInfo());
    }

    public function testGetOrderVariables()
    {
        $this->assertNull($this->manager->getOrderVariables());
        $this->session->expects($this->atLeastOnce())->method('offsetGet')->with('sOrderVariables')
                      ->willReturn(['vars']);
        $this->assertEquals(['vars'], $this->manager->getOrderVariables());
    }

    public function testGetOrderBillingAddress()
    {
        $this->assertEquals([], $this->manager->getOrderBillingAddress());
        $this->session->expects($this->atLeastOnce())->method('offsetGet')->with('sOrderVariables')
                      ->willReturn(['sUserData' => ['billingaddress' => ['addr']]]);
        $this->assertEquals(['addr'], $this->manager->getOrderBillingAddress());
    }

    public function testGetOrderShippingAddress()
    {
        $this->assertEquals([], $this->manager->getOrderShippingAddress());
        $this->session->expects($this->atLeastOnce())->method('offsetGet')->with('sOrderVariables')
                      ->willReturn(['sUserData' => ['shippingaddress' => ['addr']]]);
        $this->assertEquals(['addr'], $this->manager->getOrderShippingAddress());
    }

    public function testGetBasketTotalAmount()
    {
        $this->assertEquals(0.0, $this->manager->getBasketTotalAmount());
        $this->session->expects($this->atLeastOnce())->method('offsetGet')->with('sOrderVariables')
                      ->willReturn([
                          'sUserData' => ['additional' => ['charge_vat' => true]],
                          'sBasket'   => ['AmountWithTaxNumeric' => 89.15],
                      ], [
                          'sUserData' => ['additional' => ['charge_vat' => false]],
                          'sBasket'   => ['AmountNetNumeric' => 79.15],
                      ], [
                          'sUserData' => ['additional' => ['charge_vat' => true]],
                          'sBasket'   => ['AmountNumeric' => 69.15],
                      ]);
        $this->assertEquals(89.15, $this->manager->getBasketTotalAmount());
        $this->assertEquals(79.15, $this->manager->getBasketTotalAmount());
        $this->assertEquals(69.15, $this->manager->getBasketTotalAmount());
    }
}
