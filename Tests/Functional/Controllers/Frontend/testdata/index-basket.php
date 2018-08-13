<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

use WirecardElasticEngine\Components\Payments\Payment;
use WirecardElasticEngine\Components\Payments\PaypalPayment;

return [
    'sBasket'   => [
        'content'          => [
            [
                'articlename' => 'Foo',
                'id'          => 1,
                'articleID'   => 1,
                'ordernumber' => 1,
                'tax'         => 10,
                'tax_rate'    => 20,
                'quantity'    => 1,
                'price'       => 50,
            ],
            [
                'articlename' => 'Bar',
                'id'          => 2,
                'articleID'   => 2,
                'ordernumber' => 2,
                'tax'         => 10,
                'tax_rate'    => 20,
                'quantity'    => 1,
                'price'       => 50,
            ],
        ],
        'AmountNetNumeric' => 100.0,
        'sAmount'          => 100.0,
        'sAmountTax'       => 20.0,
        'sCurrencyId'      => 1,
    ],
    'sUserData' => [
        'additional'      => [
            'user'    => [
                'paymentID' => 7,
                'firstname' => 'First Name',
                'lastname'  => 'Last Name',
                'email'     => 'test@example.com',
            ],
            'payment' => [
                'action' => Payment::ACTION,
                'name'   => PaypalPayment::PAYMETHOD_IDENTIFIER,
            ],
        ],
        'billingaddress'  => ['userID' => 1, 'countryID' => 1],
        'shippingaddress' => ['userID' => 1, 'countryID' => 1],
    ],
];
