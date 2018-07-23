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
    const PAYMENT_DATA = 'WirecardElasticEnginePaymentData';
    const DEVICE_FINGERPRINT_ID = 'fingerprint_id';

    /**
     * @var \Enlight_Components_Session_Namespace
     */
    private $session;

    public function __construct(\Enlight_Components_Session_Namespace $session)
    {
        $this->session = $session;
    }

    /**
     * @param array $paymentData
     */
    public function storePaymentData(array $paymentData)
    {
        $this->session->offsetSet(self::PAYMENT_DATA, $paymentData);
    }

    /**
     * @return array|null
     */
    public function getPaymentData()
    {
        if (! $this->session->offsetExists(self::PAYMENT_DATA)) {
            return null;
        }
        return $this->session->offsetGet(self::PAYMENT_DATA);
    }

    /**
     * @param string $maid
     * @return string
     */
    public function getDeviceFingerprintId($maid)
    {
        if (! $this->session->get(self::DEVICE_FINGERPRINT_ID)) {
            $this->session->offsetSet(self::DEVICE_FINGERPRINT_ID, md5($maid . '_' . microtime()));
        }

        return $this->session->get(self::DEVICE_FINGERPRINT_ID);
    }

    public function destroyDeviceFingerprintId()
    {
        if ($this->session->offsetExists(self::DEVICE_FINGERPRINT_ID)) {
            $this->session->offsetUnset(self::DEVICE_FINGERPRINT_ID);
        }
    }
}
