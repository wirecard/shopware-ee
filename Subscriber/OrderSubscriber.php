<?php

namespace WirecardShopwareElasticEngine\Subscriber;

use Enlight\Event\SubscriberInterface;
use WirecardShopwareElasticEngine\Components\Payments\Payment;

class OrderSubscriber implements SubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Modules_Order_SendMail_Send' => 'onOrderShouldSendMail',
        ];
    }

    public function onOrderShouldSendMail(\Enlight_Event_EventArgs $args)
    {
        if (isset($args['variables']['additional']['payment']['action'])
            && $args['variables']['additional']['payment']['action'] === Payment::ACTION
        ) {
            // We just disable the confirmation mail on WirecardElasticEnginePayment for now
            return false;
        }

        return true;
    }
}
