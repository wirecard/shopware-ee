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

namespace WirecardShopwareElasticEngine\Components\Services;

class SessionHandler
{
    const ORDER = 'WirecardElasticEngineOrder';

    const ORDER_NUMBER = 'orderNumber';
    const BASKET_SIGNATURE = 'basketSignature';

    /**
     * @var \Enlight_Components_Session_Namespace
     */
    private $session;

    public function __construct(\Enlight_Components_Session_Namespace $session)
    {
        $this->session = $session;
    }

    /**
     * @param string $orderNumber
     * @param string $basketSignature
     */
    public function storeOrder($orderNumber, $basketSignature)
    {
        $this->session->offsetSet(self::ORDER, [
            self::ORDER_NUMBER     => $orderNumber,
            self::BASKET_SIGNATURE => $basketSignature,
        ]);
    }

    /**
     * @return string|null
     */
    public function getOrderNumber()
    {
        if (! $this->session->offsetExists(self::ORDER)) {
            return null;
        }
        $store = $this->session->offsetGet(self::ORDER);
        return isset($store[self::ORDER_NUMBER]) ? $store[self::ORDER_NUMBER] : null;
    }

    /**
     * @return string|null
     */
    public function getBasketSignature()
    {
        if (! $this->session->offsetExists(self::ORDER)) {
            return null;
        }
        $store = $this->session->offsetGet(self::ORDER);
        return isset($store[self::BASKET_SIGNATURE]) ? $store[self::BASKET_SIGNATURE] : null;
    }

    public function clearOrder()
    {
        $this->session->offsetSet(self::ORDER, []);
    }
}
