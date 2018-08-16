<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Components\Services;

/**
 * @package WirecardElasticEngine\Components\Services
 *
 * @since   1.0.0
 */
class SessionManager
{
    const PAYMENT_DATA = 'WirecardElasticEnginePaymentData';
    const DEVICE_FINGERPRINT_ID = 'fingerprint_id';

    /**
     * @var \Enlight_Components_Session_Namespace
     */
    private $session;

    /**
     * @param \Enlight_Components_Session_Namespace $session
     *
     * @since 1.0.0
     */
    public function __construct(\Enlight_Components_Session_Namespace $session)
    {
        $this->session = $session;
    }

    /**
     * @param array $paymentData
     *
     * @since 1.0.0
     */
    public function storePaymentData(array $paymentData)
    {
        $this->session->offsetSet(self::PAYMENT_DATA, $paymentData);
    }

    /**
     * @return array|null
     *
     * @since 1.0.0
     */
    public function getPaymentData()
    {
        if (! $this->session->offsetExists(self::PAYMENT_DATA)) {
            return null;
        }
        return $this->session->offsetGet(self::PAYMENT_DATA);
    }

    /**
     * Returns the device fingerprint id from the session. In case no device fingerprint id was generated so far a new
     * one will get generated and returned instead.
     * Device fingerprint id format: md5 of [maid]_[microtime]
     *
     * @param string $maid
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getDeviceFingerprintId($maid)
    {
        if (! $this->session->get(self::DEVICE_FINGERPRINT_ID)) {
            $this->session->offsetSet(self::DEVICE_FINGERPRINT_ID, md5($maid . '_' . microtime()));
        }
        return $this->session->get(self::DEVICE_FINGERPRINT_ID);
    }

    /**
     * Removes the device fingerprint id from the session. This should only be called in the return action, since the
     * id is valid until the payment is successfully done.
     *
     * @see \Shopware_Controllers_Frontend_WirecardElasticEnginePayment::returnAction()
     *
     * @since 1.0.0
     */
    public function destroyDeviceFingerprintId()
    {
        if ($this->session->offsetExists(self::DEVICE_FINGERPRINT_ID)) {
            $this->session->offsetUnset(self::DEVICE_FINGERPRINT_ID);
        }
    }

    /**
     * @return int
     *
     * @since 1.1.0
     */
    public function getUserId()
    {
        return $this->session->offsetGet('sUserId');
    }

    /**
     * @return array
     *
     * @since 1.1.0
     */
    public function getUserInfo()
    {
        return $this->session->offsetGet('userInfo');
    }

    /**
     * @return array
     *
     * @since 1.1.0
     */
    public function getOrderVariables()
    {
        return $this->session->offsetGet('sOrderVariables');
    }

    /**
     * @return array
     *
     * @since 1.1.0
     */
    public function getOrderBilldingAddress()
    {
        $orderVariables = $this->getOrderVariables();
        return isset($orderVariables['sUserData']['billingaddress'])
            ? $orderVariables['sUserData']['billingaddress']
            : [];
    }

    /**
     * @return array
     *
     * @since 1.1.0
     */
    public function getOrderShippingAddress()
    {
        $orderVariables = $this->getOrderVariables();
        return isset($orderVariables['sUserData']['shippingaddress'])
            ? $orderVariables['sUserData']['shippingaddress']
            : [];
    }
}
