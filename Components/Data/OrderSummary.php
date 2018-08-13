<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Components\Data;

use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Device;
use WirecardElasticEngine\Components\Mapper\BasketMapper;
use WirecardElasticEngine\Components\Mapper\UserMapper;
use WirecardElasticEngine\Components\Payments\Payment;
use WirecardElasticEngine\Components\Payments\PaymentInterface;

/**
 * The `OrderSummary` is passed to the `PaymentHandler` which processes the actual payment, based on the information
 * stored in the summary (e.g. used payment method, consumer information, etc..).
 *
 * @package WirecardElasticEngine\Components\Data
 *
 * @since   1.0.0
 */
class OrderSummary
{
    /**
     * @var string
     */
    protected $paymentUniqueId;

    /**
     * @var Payment
     */
    protected $payment;

    /**
     * @var BasketMapper
     */
    protected $basketMapper;

    /**
     * @var Amount
     */
    protected $amount;

    /**
     * @var UserMapper
     */
    protected $userMapper;

    /**
     * @var string
     */
    protected $deviceFingerprintId;

    /**
     * @var array
     */
    protected $additionalPaymentData;

    /**
     * @param string           $paymentUniqueId
     * @param PaymentInterface $payment
     * @param UserMapper       $userMapper
     * @param BasketMapper     $basketMapper
     * @param Amount           $amount
     * @param string           $deviceFingerprintId
     * @param array            $additionalPaymentData
     *
     * @since 1.0.0
     */
    public function __construct(
        $paymentUniqueId,
        PaymentInterface $payment,
        UserMapper $userMapper,
        BasketMapper $basketMapper,
        Amount $amount,
        $deviceFingerprintId,
        $additionalPaymentData = []
    ) {
        $this->paymentUniqueId       = $paymentUniqueId;
        $this->payment               = $payment;
        $this->userMapper            = $userMapper;
        $this->amount                = $amount;
        $this->basketMapper          = $basketMapper;
        $this->deviceFingerprintId   = $deviceFingerprintId;
        $this->additionalPaymentData = $additionalPaymentData;
    }

    /**
     * @return PaymentInterface
     *
     * @since 1.0.0
     */
    public function getPayment()
    {
        return $this->payment;
    }

    /**
     * @return UserMapper
     *
     * @since 1.0.0
     */
    public function getUserMapper()
    {
        return $this->userMapper;
    }

    /**
     * @return BasketMapper
     *
     * @since 1.0.0
     */
    public function getBasketMapper()
    {
        return $this->basketMapper;
    }

    /**
     * @return Amount
     *
     * @since 1.0.0
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getPaymentUniqueId()
    {
        return $this->paymentUniqueId;
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getDeviceFingerprintId()
    {
        return $this->deviceFingerprintId;
    }

    /**
     * @return Device
     *
     * @since 1.0.0
     */
    public function getWirecardDevice()
    {
        $device = new Device();
        $device->setFingerprint($this->getDeviceFingerprintId());
        return $device;
    }

    /**
     * @return array
     */
    public function getAdditionalPaymentData()
    {
        return $this->additionalPaymentData;
    }

    /**
     * @return array
     * @throws \WirecardElasticEngine\Exception\ArrayKeyNotFoundException
     *
     * @since 1.0.0
     */
    public function toArray()
    {
        return [
            'paymentUniqueId' => $this->getPaymentUniqueId(),
            'payment'         => [
                'name'          => $this->getPayment()->getName(),
                'paymentConfig' => $this->getPayment()->getPaymentConfig()->toArray(),
                'transaction'   => $this->getPayment()->getTransaction()->mappedProperties(),
            ],
            'user'            => $this->getUserMapper()->toArray(),
            'basket'          => $this->getBasketMapper()->toArray(),
            'amount'          => $this->getAmount()->mappedProperties(),
        ];
    }
}
