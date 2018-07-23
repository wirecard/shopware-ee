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

namespace WirecardShopwareElasticEngine\Tests\Unit\Controllers\Frontend;

use PHPUnit\Framework\TestCase;
use Shopware\Components\BasketSignature\BasketPersister;
use Shopware\Components\BasketSignature\BasketSignatureGeneratorInterface;
use Shopware\Components\DependencyInjection\Container;
use Shopware\Models\Shop\Locale;
use WirecardShopwareElasticEngine\Components\Services\PaymentFactory;
use WirecardShopwareElasticEngine\Exception\UnknownPaymentException;

require_once __DIR__ . '/../../../../Controllers/Frontend/WirecardElasticEnginePayment.php';

class WirecardElasticEnginePaymentTest extends TestCase
{
    private $originalShopware;

    /** @var \Enlight_Controller_Request_Request|\PHPUnit_Framework_MockObject_MockObject */
    private $request;

    /** @var \Enlight_Controller_Response_Response|\PHPUnit_Framework_MockObject_MockObject */
    private $response;

    /** @var Container|\PHPUnit_Framework_MockObject_MockObject */
    private $container;

    public function setUp()
    {
        $this->request  = $this->createMock(\Enlight_Controller_Request_Request::class);
        $this->response = $this->createMock(\Enlight_Controller_Response_Response::class);

        $this->container        = $this->createMock(Container::class);
        $this->originalShopware = \Shopware(new \Shopware($this->container));

        //        $this->controller = \Enlight_Class::Instance(
        //            \Shopware_Controllers_Frontend_WirecardElasticEnginePayment::class,
        //            [$this->request, $this->response]
        //        );
    }

    public function tearDown()
    {
        \Shopware($this->originalShopware);
    }

    private function createController()
    {
        $controller = new \Shopware_Controllers_Frontend_WirecardElasticEnginePayment(
            $this->request,
            $this->response
        );
        $controller->setContainer($this->container);
        return $controller;
    }

    private function setServices($services)
    {
        $services = array_merge([
            'Front'                                   => $this->createMock(\Enlight_Controller_Front::class),
            'hooks'                                   => $this->createMock(\Enlight_Hook_HookManager::class),
            'basket_signature_generator'              => $this->createMock(BasketSignatureGeneratorInterface::class),
            'basket_persister'                        => $this->createMock(BasketPersister::class),
            'config'                                  => $this->createMock(\Shopware_Components_Config::class),
            'wirecard_elastic_engine.payment_factory' => $this->createMock(PaymentFactory::class),
        ], $services);

        $behavior = Container::EXCEPTION_ON_INVALID_REFERENCE;
        $map      = [];
        foreach ($services as $name => $service) {
            $map[] = [$name, $behavior, $service];
        }
        $this->container->method('get')->willReturnMap($map);
    }

    public function testIndexAction()
    {
        $this->markTestIncomplete();

        $orderVariables = $this->createMock(\ArrayObject::class);
        $orderVariables->method('getArrayCopy')->willReturn([
            'sBasket' => [],
        ]);
        $session = $this->createMock(\Enlight_Components_Session_Namespace::class);
        $session->method('offsetGet')->willReturnMap([
            ['sOrderVariables', $orderVariables],
        ]);

        $shop   = $this->createMock(\Shopware::class);
        $locale = $this->createMock(Locale::class);
        $shop->method('__call')->willReturnMap([
            ['getLocale', null, $locale],
        ]);

        $this->setServices([
            'session' => $session,
            'shop'    => $shop,
        ]);

        $this->createController()->indexAction();
    }

    public function testIndexActionUnknownPaymentException()
    {
        $this->markTestIncomplete();

        $basket = $this->createMock(\ArrayObject::class);
        $basket->method('getArrayCopy')->willReturn([
            'sBasket' => [],
        ]);
        $session = $this->createMock(\Enlight_Components_Session_Namespace::class);
        $session->method('offsetGet')->willReturn($basket);

        $this->setServices([
            'session' => $session,
        ]);

        $this->expectException(UnknownPaymentException::class);
        $this->createController()->indexAction();
    }
}
