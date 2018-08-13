<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Tests\Unit\Subscriber;

use PHPUnit\Framework\TestCase;
use WirecardElasticEngine\Components\Payments\Payment;
use WirecardElasticEngine\Subscriber\OrderSubscriber;

class OrderSubscriberTest extends TestCase
{
    public function testSubscribedEvents()
    {
        $this->assertEquals([
            'Shopware_Modules_Order_SendMail_Send' => 'onOrderShouldSendMail',
        ], OrderSubscriber::getSubscribedEvents());
    }

    public function testOnOrderShouldSendMail()
    {
        $subscriber = new OrderSubscriber();

        $env = getenv('SHOPWARE_ENV');
        if ($env === 'testing') {
            $this->assertFalse($subscriber->onOrderShouldSendMail(new \Enlight_Controller_ActionEventArgs()));
        }

        // switch environment to skip "testing" env case
        putenv("SHOPWARE_ENV=dev");

        $this->assertNull($subscriber->onOrderShouldSendMail(new \Enlight_Controller_ActionEventArgs()));

        $args = new \Enlight_Controller_ActionEventArgs();
        $args->set('variables', ['additional' => ['payment' => ['action' => Payment::ACTION]]]);
        $this->assertNull($subscriber->onOrderShouldSendMail($args));

        // restore previous environment
        putenv("SHOPWARE_ENV=" . $env);
    }
}
