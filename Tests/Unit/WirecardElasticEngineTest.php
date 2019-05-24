<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Tests\Unit\Subscriber;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Shopware\Bundle\PluginInstallerBundle\Service\InstallerService;
use Shopware\Components\Plugin;
use Shopware\Components\Routing\RouterInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use WirecardElasticEngine\Components\Payments\PaymentInterface;
use WirecardElasticEngine\WirecardElasticEngine;

class WirecardElasticEngineTest extends TestCase
{
    public function testPlugin()
    {
        $plugin = new WirecardElasticEngine(true, 'ShopwarePlugins');
        $this->assertInstanceOf(Plugin::class, $plugin);
        $this->assertTrue($plugin->isActive());
    }

    public function testSupportedPayments()
    {
        $services = [
            'models'                                  => $this->createMock(EntityManagerInterface::class),
            'config'                                  => $this->createMock(\Shopware_Components_Config::class),
            'shopware_plugininstaller.plugin_manager' => $this->createMock(InstallerService::class),
            'router'                                  => $this->createMock(RouterInterface::class),
            'events'                                  => $this->createMock(\Enlight_Event_EventManager::class),
        ];
        $map      = [];
        foreach ($services as $name => $service) {
            $map[] = [$name, Container::EXCEPTION_ON_INVALID_REFERENCE, $service];
        }

        /** @var ContainerInterface|\PHPUnit_Framework_MockObject_MockObject $container */
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnMap($map);

        $plugin = new WirecardElasticEngine(true, 'ShopwarePlugins');
        $plugin->setContainer($container);
        $this->assertInstanceOf(Plugin::class, $plugin);
        $payments = $plugin->getSupportedPayments();
        $this->assertNotEmpty($payments);
        foreach ($payments as $payment) {
            $this->assertInstanceOf(PaymentInterface::class, $payment);
        }
    }
}
