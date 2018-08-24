<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Subscriber;

use Enlight\Event\SubscriberInterface;

/**
 * @package WirecardElasticEngine\Subscriber
 *
 * @since   1.0.0
 */
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

    /**
     * @param \Enlight_Event_EventArgs $args
     *
     * @return bool|null
     *
     * @since 1.0.0
     */
    public function onOrderShouldSendMail(\Enlight_Event_EventArgs $args)
    {
        if (getenv('SHOPWARE_ENV') === 'testing') {
            // Disable confirmation mail in testing environment
            return false;
        }

        // Return null to allow other subscribers to handle this event (see EventManager::notifyUntil)
        return null;
    }
}
