<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Components\Payments;

use Shopware\Models\Order\Order;
use Shopware\Models\Shop\Shop;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\RatepayInvoiceTransaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardElasticEngine\Components\Actions\ErrorAction;
use WirecardElasticEngine\Components\Data\OrderSummary;
use WirecardElasticEngine\Components\Data\RatepayInvoicePaymentConfig;
use WirecardElasticEngine\Components\Mapper\OrderBasketMapper;
use WirecardElasticEngine\Components\Mapper\UserMapper;
use WirecardElasticEngine\Components\Payments\Contracts\AdditionalViewAssignmentsInterface;
use WirecardElasticEngine\Components\Payments\Contracts\DisplayRestrictionInterface;
use WirecardElasticEngine\Components\Payments\Contracts\ProcessPaymentInterface;
use WirecardElasticEngine\Components\Services\SessionManager;
use WirecardElasticEngine\Exception\ArrayKeyNotFoundException;

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
     * Set amount and basket for RatepayInvoicePayment
     *
     * @param Order       $order
     * @param null|string $operation
     * @param null|string $paymentMethod
     *
     * @return RatepayInvoiceTransaction
     *
     * @since 1.0.0
     */
    public function getBackendTransaction(Order $order, $operation, $paymentMethod)
    {
        $transaction = new RatepayInvoiceTransaction();
        $transaction->setOrderNumber($order->getTemporaryId());
        $transaction->setAmount(new Amount($order->getInvoiceAmount(), $order->getCurrency()));

        $mapper = new OrderBasketMapper();
        $transaction->setBasket($mapper->createBasket($order));
        return $transaction;
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
        $billingShippingMustBeIdentical = $this->getPluginConfig('RatepayInvoiceBillingShippingMustBeIdentical');
        $paymentConfig->setAllowDifferentBillingShipping(! $billingShippingMustBeIdentical);
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
            $transaction->setAccountHolder($orderSummary->getUserMapper()->getWirecardBillingAccountHolder());
        }

        return $this->validateConsumerDateOfBirth($orderSummary, $transaction->getAccountHolder());
    }

    /**
     * @param OrderSummary  $orderSummary
     * @param AccountHolder $accountHolder
     *
     * @return ErrorAction|null
     *
     * @since 1.0.0
     */
    private function validateConsumerDateOfBirth(OrderSummary $orderSummary, AccountHolder $accountHolder)
    {
        $accountHolderProperties = $accountHolder->mappedProperties();

        // Date of bith is part of consumer data: check has already been done via checkDisplayRestrictions method
        if (! empty($accountHolderProperties['date-of-birth'])) {
            return null;
        }

        // Otherwise validate against birthday in payment data from checkout page
        $birthDay = $this->getDateOfBirthFromPaymentData($orderSummary->getAdditionalPaymentData());
        if (! $birthDay || $this->isBelowAgeRestriction($birthDay)) {
            return new ErrorAction(
                ErrorAction::PROCESSING_FAILED_WRONG_AGE,
                $birthDay ? 'Consumer must be at least 18 years old' : 'Consumer birthday missing'
            );
        }

        $accountHolder->setDateOfBirth($birthDay);
        return null;
    }

    /**
     * @return array
     *
     * @since 1.0.0
     */
    private function getComparableAddressKeys()
    {
        return [
            "firstname",
            "lastname",
            "street",
            "zipcode",
            "city",
            "countryId",
            "stateId",
        ];
    }

    /**
     * @param array $srcAddress
     * @param array $destAddress
     *
     * @return bool
     *
     * @since 1.0.0
     */
    private function addressEquals(array $srcAddress, array $destAddress)
    {
        foreach ($this->getComparableAddressKeys() as $key) {
            $srcValue  = isset($srcAddress[$key]) ? $srcAddress[$key] : '';
            $destValue = isset($destAddress[$key]) ? $destAddress[$key] : '';
            if ($srcValue !== $destValue) {
                return false;
            }
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function checkDisplayRestrictions(UserMapper $userMapper)
    {
        $paymentConfig = $this->getPaymentConfig();
        try {
            $billingAddress  = $userMapper->getBillingAddress();
            $shippingAddress = $userMapper->getShippingAddress();
        } catch (ArrayKeyNotFoundException $e) {
            return false;
        }

        // Check if merchant disallows different billing/shipping address and compare both
        if (! $paymentConfig->isAllowedDifferentBillingShipping()
            && ! $this->addressEquals($billingAddress, $shippingAddress)
        ) {
            return false;
        }

        // Check if consumer age is above 18, either via user data or payment data from checkout page
        $birthDay = $userMapper->getBirthday();
        if (! $birthDay && Shopware()->Session()->offsetExists(SessionManager::PAYMENT_DATA)) {
            $paymentData = Shopware()->Session()->offsetGet(SessionManager::PAYMENT_DATA);
            $birthDay    = $this->getDateOfBirthFromPaymentData($paymentData);
        }
        if ($birthDay && $this->isBelowAgeRestriction($birthDay)) {
            return false;
        }

        // Check if currency is accepted
        $currency = Shopware()->Shop()->getCurrency();
        if (! in_array($currency->getId(), $paymentConfig->getAcceptedCurrencies())) {
            return false;
        }

        $basket = Shopware()->Modules()->Basket();

        // Check if shopping basket amount is within the set range (allow if amount is 0.0)
        $amount = $basket->sGetAmount();
        $amount = empty($amount['totalAmount']) ? 0.0 : floatval($amount['totalAmount']);
        if (! $currency->getDefault()) {
            $amount /= $currency->getFactor();
        }
        $minAmount = floatval($paymentConfig->getMinAmount());
        $maxAmount = floatval($paymentConfig->getMaxAmount());
        if ($amount !== 0.0 && ($amount < $minAmount || $amount > $maxAmount)) {
            return false;
        }

        // Check if no digital goods are part of the shopping basket
        if ($basket->sCheckForESD()) {
            return false;
        }

        // Check shipping/billing country
        if (! in_array($shippingAddress['countryId'], $paymentConfig->getShippingCountries())
            || ! in_array($billingAddress['countryId'], $paymentConfig->getBillingCountries())
        ) {
            return false;
        }

        return true;
    }

    /**
     * @param \DateTime $birthDay
     *
     * @return bool
     *
     * @since 1.0.0
     */
    private function isBelowAgeRestriction(\DateTime $birthDay)
    {
        $age = $birthDay->diff(new \DateTime());
        return $age->y < 18;
    }

    /**
     * @param array $paymentData
     *
     * @return \DateTime|null
     *
     * @since 1.0.0
     */
    private function getDateOfBirthFromPaymentData($paymentData)
    {
        if (! isset($paymentData['birthday']['year'])
            || ! isset($paymentData['birthday']['month'])
            || ! isset($paymentData['birthday']['day'])
        ) {
            return null;
        }
        $birthDay = new \DateTime();
        $birthDay->setDate(
            intval($paymentData['birthday']['year']),
            intval($paymentData['birthday']['month']),
            intval($paymentData['birthday']['day'])
        );
        return $birthDay;
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
            'showForm' => ! $userMapper->getBirthday(),
        ];
    }
}
