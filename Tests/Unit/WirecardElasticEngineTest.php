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
        $plugin = new WirecardElasticEngine(true);
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

        $plugin = new WirecardElasticEngine(true);
        $plugin->setContainer($container);
        $this->assertInstanceOf(Plugin::class, $plugin);
        $payments = $plugin->getSupportedPayments();
        $this->assertNotEmpty($payments);
        foreach ($payments as $payment) {
            $this->assertInstanceOf(PaymentInterface::class, $payment);
        }
    }
}
