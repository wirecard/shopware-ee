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

}
