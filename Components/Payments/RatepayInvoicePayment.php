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

namespace WirecardElasticEngine\Components\Payments;

use Shopware\Models\Shop\Currency;
use Shopware\Models\Shop\Shop;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\RatepayInvoiceTransaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardElasticEngine\Components\Actions\ErrorAction;
use WirecardElasticEngine\Components\Data\OrderSummary;
use WirecardElasticEngine\Components\Data\RatepayInvoicePaymentConfig;
use WirecardElasticEngine\Components\Mapper\UserMapper;
use WirecardElasticEngine\Components\Payments\Contracts\AdditionalViewAssignmentsInterface;
use WirecardElasticEngine\Components\Payments\Contracts\DisplayRestrictionInterface;
use WirecardElasticEngine\Components\Payments\Contracts\ProcessPaymentInterface;

/**
 * @package WirecardElasticEngine\Components\Payments
 *
 * @since   1.0.0
 */
class RatepayInvoicePayment extends Payment implements
    DisplayRestrictionInterface,
    ProcessPaymentInterface,
    AdditionalViewAssignmentsInterface
{
    const PAYMETHOD_IDENTIFIER = 'wirecard_elastic_engine_ratepay_invoice';

    /**
     * @var RatepayInvoiceTransaction
     */
    private $transactionInstance;

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'WirecardRatepayInvoice';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::PAYMETHOD_IDENTIFIER;
    }

    /**
     * {@inheritdoc}
     */
    public function getPosition()
    {
        return 2;
    }

    /**
     * @return RatepayInvoiceTransaction
     *
     * @since 1.0.0
     */
    public function getTransaction()
    {
        if (! $this->transactionInstance) {
            $this->transactionInstance = new RatepayInvoiceTransaction();
        }
        return $this->transactionInstance;
    }

    /**
     * {@inheritdoc}
     */
    public function getTransactionConfig(Shop $shop, ParameterBagInterface $parameterBag, $selectedCurrency)
    {
        $config = parent::getTransactionConfig($shop, $parameterBag, $selectedCurrency);
        $config->add(new PaymentMethodConfig(
            RatepayInvoiceTransaction::NAME,
            $this->getPaymentConfig()->getTransactionMAID(),
            $this->getPaymentConfig()->getTransactionSecret()
        ));

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentConfig()
    {
        $paymentConfig = new RatepayInvoicePaymentConfig(
            $this->getPluginConfig('RatepayInvoiceServer'),
            $this->getPluginConfig('RatepayInvoiceHttpUser'),
            $this->getPluginConfig('RatepayInvoiceHttpPassword')
        );

        $paymentConfig->setTransactionMAID($this->getPluginConfig('RatepayInvoiceMerchantId'));
        $paymentConfig->setTransactionSecret($this->getPluginConfig('RatepayInvoiceSecret'));
        $paymentConfig->setTransactionOperation(parent::TRANSACTION_OPERATION_RESERVE);
        $paymentConfig->setSendBasket(true);
        $paymentConfig->setMinAmount($this->getPluginConfig('RatepayInvoiceMinAmount'));
        $paymentConfig->setMaxAmount($this->getPluginConfig('RatepayInvoiceMaxAmount'));
        $paymentConfig->setAcceptedCurrencies($this->getPluginConfig('RatepayInvoiceAcceptedCurrencies'));
        $paymentConfig->setShippingCountries($this->getPluginConfig('RatepayInvoiceShippingCountries'));
        $paymentConfig->setBillingCountries($this->getPluginConfig('RatepayInvoiceBillingCountries'));
        $paymentConfig->setDifferentBillingShipping($this->getPluginConfig('RatepayInvoiceDifferentBillingShipping'));

        $paymentConfig->setFraudPrevention($this->getPluginConfig('RatepayInvoiceFraudPrevention'));

        return $paymentConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function processPayment(
        OrderSummary $orderSummary,
        TransactionService $transactionService,
        Shop $shop,
        Redirect $redirect,
        \Enlight_Controller_Request_Request $request,
        \sOrder $shopwareOrder
    ) {
        $transaction = $this->getTransaction();
        if (! $this->getPaymentConfig()->hasFraudPrevention()) {
            $transaction->setOrderNumber($orderSummary->getPaymentUniqueId());
            $accountHolder = $orderSummary->getUserMapper()->getWirecardBillingAccountHolder();
            $transaction->setAccountHolder($accountHolder);
        }

        $accountHolder = $transaction->getAccountHolder();
        $accountHolderProperties = $accountHolder->mappedProperties();
        if (empty($accountHolderProperties['date_of_birth'])) {
            $additionalPaymentData = $orderSummary->getAdditionalPaymentData();

            $birthDay = new \DateTime();
            $birthDay->setDate(
                $additionalPaymentData['birthday']['year'],
                $additionalPaymentData['birthday']['month'],
                $additionalPaymentData['birthday']['day']
            );

            $age = $birthDay->diff(new \DateTime);

            if ($age->y < 18) {
                return new ErrorAction(
                    ErrorAction::PROCESSING_FAILED,
                    'customer is too young'
                );
            }
            $accountHolder->setDateOfBirth($birthDay);
            $transaction->setAccountHolder($accountHolder);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function checkDisplayRestrictions(UserMapper $userMapper)
    {
        $acceptedBillingCountries = $this->getPaymentConfig()->getBillingCountries();
        $acceptedShippingCountries = $this->getPaymentConfig()->getShippingCountries();
        $acceptedCurrencies = $this->getPaymentConfig()->getAcceptedCurrencies();
        $billingAddress = $userMapper->getBillingAddress();
        $shippingAddress = $userMapper->getShippingAddress();
        $minAmount = $this->getPaymentConfig()->getMinAmount();
        $maxAmount = $this->getPaymentConfig()->getMaxAmount();

        // age above 18
        $birthDay = $userMapper->getBirthday();
        if ($birthDay) {
            $now = new \DateTime();
            $age = $birthDay->diff($now);

            if ($age->y < 18) {
                return false;
            }
        }

        // currency accepted
        $currency = Shopware()->Shop()->getCurrency();
        if (! in_array($currency->getId(), $acceptedCurrencies)) {
            return false;
        }

        // shopping basket amount within the range
        $basket = Shopware()->Modules()->Basket();
        $amount = $basket->sGetAmount();
        $amount = empty($amount['totalAmount']) ? 0 : $amount['totalAmount'];
        $amount = floatval($amount);

        if (! $currency->getDefault()) {
            $amount /= $currency->getFactor();
        }

        if ($amount !== 0.0 && ($amount < $minAmount || $amount > $maxAmount)) {
            return false;
        }

        // no digital goods
        if ($basket->sCheckForESD()) {
            return false;
        }

        // shipping country
        if (! in_array($shippingAddress['countryId'], $acceptedShippingCountries)) {
            return false;
        }

        // billing country
        if (! in_array($billingAddress['countryId'], $acceptedBillingCountries)) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getAdditionalViewAssignments()
    {
        $userData = Shopware()->Session()->sOrderVariables['sUserData'];

        $userMapper = new UserMapper($userData, '', '');

        return [
            'method'   => $this->getName(),
            'showForm' => (! $userMapper->getBirthday()),
        ];
    }
}
