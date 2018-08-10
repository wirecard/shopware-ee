<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Tests\Unit;

use Doctrine\ORM\EntityManagerInterface;
use Shopware\Bundle\PluginInstallerBundle\Service\InstallerService;
use Shopware\Components\Routing\RouterInterface;
use Shopware\Models\Plugin\Plugin;
use WirecardElasticEngine\WirecardElasticEngine;

abstract class PaymentTestCase extends \PHPUnit_Framework_TestCase
{
    /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var \Shopware_Components_Config|\PHPUnit_Framework_MockObject_MockObject */
    protected $config;

    /** @var InstallerService|\PHPUnit_Framework_MockObject_MockObject */
    protected $installer;

    /** @var RouterInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $router;

    /** @var \Enlight_Event_EventManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $eventManager;

    public function setUp()
    {
        parent::setUp();
        $this->em           = $this->createMock(EntityManagerInterface::class);
        $this->config       = $this->createMock(\Shopware_Components_Config::class);
        $this->installer    = $this->createMock(InstallerService::class);
        $this->router       = $this->createMock(RouterInterface::class);
        $this->eventManager = $this->createMock(\Enlight_Event_EventManager::class);

        $plugin = $this->createMock(Plugin::class);
        $plugin->method('getName')->willReturn(WirecardElasticEngine::NAME);
        $plugin->method('getVersion')->willReturn('__PLUGIN_VERSION__');
        $this->installer->method('getPluginByName')->willReturn($plugin);
    }

    public function assertPaymentOptions(array $paymentMethod, $name, $description, $position)
    {
        $this->assertEquals([
            'name'                  => $name,
            'description'           => $description,
            'action'                => 'WirecardElasticEnginePayment',
            'active'                => 0,
            'position'              => $position,
            'additionalDescription' => '',
        ], $paymentMethod);
    }

    public function assertConfigData(array $expected, array $actual)
    {
        $this->assertTrue($this->getArraysAreSimilar($expected, $actual));
    }

    /**
     * Test helper to compare arrays (regardless of order)
     *
     * @param array $expected
     * @param array $actual
     *
     * @return bool
     */
    protected function getArraysAreSimilar(array $expected, array $actual)
    {
        if (count(array_diff_assoc($expected, $actual))) {
            return false;
        }

        foreach ($expected as $key => $value) {
            if ($value !== $actual[$key]) {
                return false;
            }
        }

        return true;
    }
}
