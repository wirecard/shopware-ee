<?php

use WirecardShopwareElasticEngine\Components\Payments\CreditCardPayment;
use WirecardShopwareElasticEngine\Components\Payments\Payment;

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
        ],
        'AmountNetNumeric' => 100.0,
        'sAmount'          => 100.0,
        'sAmountTax'       => 20.0,
        'sCurrencyId'      => 1,
    ],
    'sUserData' => [
        'additional'      => [
            'user'    => [
                'paymentID' => 8,
                'firstname' => 'First Name',
                'lastname'  => 'Last Name',
                'email'     => 'test@example.com',
            ],
            'payment' => [
                'action' => Payment::ACTION,
                'name'   => CreditCardPayment::PAYMETHOD_IDENTIFIER,
            ],
        ],
        'billingaddress'  => ['userID' => 1, 'countryID' => 1],
        'shippingaddress' => ['userID' => 1, 'countryID' => 1],
    ],
];