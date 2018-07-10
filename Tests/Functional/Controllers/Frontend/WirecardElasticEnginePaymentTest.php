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

namespace WirecardShopwareElasticEngine\Tests\Functional\Controllers\Frontend;

use WirecardShopwareElasticEngine\Components\Payments\PaypalPayment;

class WirecardElasticEnginePaymentTest extends \Enlight_Components_Test_Plugin_TestCase
{
    const USER_AGENT = 'Mozilla/5.0 (Android; Tablet; rv:14.0) Gecko/14.0 Firefox/14.0';

    public function testIndexAction()
    {
        $this->markTestIncomplete();
        
        $this->reset();
        $this->Request()->setMethod('POST');
        $this->Request()->setHeader('User-Agent', self::USER_AGENT);
        //$this->Request()->setParam('sQuantity', 5);

        $orderVariables              = new \ArrayObject();
        $orderVariables['sBasket']   = [
            'content'          => [],
            'AmountNetNumeric' => 100.0,
            'sAmount'          => 100.0,
            'sAmountTax'       => 20.0,
            'sCurrencyId'      => 1,
        ];
        $orderVariables['sUserData'] = [
            'additional'     => [
                'user'    => [
                    'paymentID' => 1,
                    'firstname' => 'First Name',
                    'lastname'  => 'Last Name',
                    'email'     => 'test@example.com',
                ],
                'payment' => [
                    'name' => PaypalPayment::PAYMETHOD_IDENTIFIER,
                ],
            ],
            'billingaddress' => [
                'userID' => 1,
            ],
        ];

        Shopware()->Session()['sOrderVariables'] = $orderVariables;

        $response = $this->dispatch('/WirecardElasticEnginePayment');
        $this->assertContains('<div class="modal--checkout-add-article">', $response->getBody());
    }
}
