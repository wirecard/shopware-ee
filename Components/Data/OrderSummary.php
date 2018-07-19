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

namespace WirecardShopwareElasticEngine\Components\Data;

use Wirecard\PaymentSdk\Entity\Amount;
use WirecardShopwareElasticEngine\Components\Mapper\BasketMapper;
use WirecardShopwareElasticEngine\Components\Mapper\UserMapper;
use WirecardShopwareElasticEngine\Components\Payments\Payment;
use WirecardShopwareElasticEngine\Components\Payments\PaymentInterface;

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
     * OrderDetails constructor.
     *
     * @param string           $paymentUniqueId
     * @param PaymentInterface $payment
     * @param UserMapper       $userMapper
     * @param BasketMapper     $basketMapper
     * @param Amount           $amount
     */
    public function __construct(
        $paymentUniqueId,
        PaymentInterface $payment,
        UserMapper $userMapper,
        BasketMapper $basketMapper,
        Amount $amount
    ) {
        $this->paymentUniqueId = $paymentUniqueId;
        $this->payment         = $payment;
        $this->userMapper      = $userMapper;
        $this->amount          = $amount;
        $this->basketMapper    = $basketMapper;
    }

    /**
     * @return PaymentInterface
     */
    public function getPayment()
    {
        return $this->payment;
    }

    /**
     * @return UserMapper
     */
    public function getUserMapper()
    {
        return $this->userMapper;
    }

    /**
     * @return BasketMapper
     */
    public function getBasketMapper()
    {
        return $this->basketMapper;
    }

    /**
     * @return Amount
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @return string
     */
    public function getPaymentUniqueId()
    {
        return $this->paymentUniqueId;
    }

    /**
     * @return array
     * @throws \WirecardShopwareElasticEngine\Exception\ArrayKeyNotFoundException
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
