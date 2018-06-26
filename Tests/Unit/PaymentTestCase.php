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

namespace WirecardShopwareElasticEngine\Tests\Unit;

abstract class PaymentTestCase extends \PHPUnit_Framework_TestCase
{
    public function assertPaymentOptions(array $paymentMethod, $name, $description)
    {
        $this->assertEquals([
            'name'                  => $name,
            'description'           => $description,
            'action'                => 'WirecardElasticEnginePayment',
            'active'                => 0,
            'position'              => 0,
            'additionalDescription' => '',
        ], $paymentMethod);
    }

    public function assertConfigData(array $expected, array $actual)
    {
        $this->assertTrue($this->assertArraysAreSimilar($expected, $actual));
    }

    /**
     * Test helper to compare arrays (regardless of order)
     *
     * @param array $expected
     * @param array $actual
     * @return bool
     */
    protected function assertArraysAreSimilar(array $expected, array $actual)
    {
        if(count(array_diff_assoc($expected, $actual))) {
            return false;
        }

        foreach($expected as $key => $value) {
            if($value !== $actual[$key]) {
                return false;
            }
        }

        return true;
    }
}