<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Exception;

/**
 * Thrown by the `PaymentFactory` if the payment could not be found.
 *
 * @see     \WirecardElasticEngine\Components\Services\PaymentFactory::create()
 *
 * @package WirecardElasticEngine\Exception
 *
 * @since   1.0.0
 */
class UnknownPaymentException extends \Exception
{
    /**
     * @param string $paymentName
     *
     * @since 1.0.0
     */
    public function __construct($paymentName)
    {
        parent::__construct("Unknown payment '$paymentName'");
    }
}
