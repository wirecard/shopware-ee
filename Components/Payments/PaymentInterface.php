<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Components\Payments;

use Shopware\Models\Shop\Shop;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Wirecard\PaymentSdk\Config\Config;
use WirecardElasticEngine\Components\Data\PaymentConfig;

/**
 * Defines the shape of payment implementations.
 *
 * @package WirecardElasticEngine\Components\Payments
 *
 * @since   1.0.0
 */
interface PaymentInterface
{
    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getLabel();

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function getName();

    /**
     * @return int
     *
     * @since 1.0.0
     */
    public function getPosition();

    /**
     * Returns the config (in form of an array) for registering payments in Shopware.
     *
     * @return array
     *
     * @since 1.0.0
     */
    public function getPaymentOptions();

    /**
     * Returns payment specific transaction object (always returns the same instance!).
     *
     * @return \Wirecard\PaymentSdk\Transaction\Transaction
     *
     * @since 1.0.0
     */
    public function getTransaction();

    /**
     * Returns payment specific transaction object for backend operations (always returns a new instance!).
     * Returns null, if no backend operations are allowed on this payment
     *
     * @param string|null $operation
     * @param string|null $paymentMethod
     * @param string|null $transactionType
     *
     * @return \Wirecard\PaymentSdk\Transaction\Transaction|null
     *
     * @since 1.0.0
     */
    public function getBackendTransaction($operation, $paymentMethod, $transactionType);

    /**
     * Returns the transaction type from `getPaymentOptions`.
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function getTransactionType();

    /**
     * Returns transaction config.
     *
     * @param Shop                  $shop
     * @param ParameterBagInterface $parameterBag
     * @param string                $selectedCurrency
     *
     * @return Config
     *
     * @since 1.0.0
     */
    public function getTransactionConfig(Shop $shop, ParameterBagInterface $parameterBag, $selectedCurrency);

    /**
     * Returns payment specific configuration.
     *
     * @return PaymentConfig
     *
     * @since 1.0.0
     */
    public function getPaymentConfig();
}
