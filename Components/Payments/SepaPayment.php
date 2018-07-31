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

namespace WirecardShopwareElasticEngine\Components\Payments;

use Shopware\Models\Shop\Shop;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Wirecard\PaymentSdk\Config\SepaConfig;
use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\Mandate;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\SepaCreditTransferTransaction;
use Wirecard\PaymentSdk\Transaction\SepaDirectDebitTransaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardShopwareElasticEngine\Components\Data\OrderSummary;
use WirecardShopwareElasticEngine\Components\Data\SepaPaymentConfig;
use WirecardShopwareElasticEngine\Components\Payments\Contracts\AdditionalViewAssignmentsInterface;
use WirecardShopwareElasticEngine\Components\Payments\Contracts\ProcessPaymentInterface;
use WirecardShopwareElasticEngine\Exception\InsufficientDataException;

/**
 * @package WirecardShopwareElasticEngine\Components\Payments
 *
 * @since   1.0.0
 */
class SepaPayment extends Payment implements ProcessPaymentInterface, AdditionalViewAssignmentsInterface
{
    const PAYMETHOD_IDENTIFIER = 'wirecard_elastic_engine_sepa';

    /**
     * @var SepaDirectDebitTransaction
     */
    private $transactionInstance;

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'WirecardSEPADirectDebit';
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
        return 8;
    }

    /**
     * @return SepaDirectDebitTransaction
     *
     * @since 1.0.0
     */
    public function getTransaction()
    {
        if (! $this->transactionInstance) {
            $this->transactionInstance = new SepaDirectDebitTransaction();
        }
        return $this->transactionInstance;
    }

    /**
     * If the paymentMethod is 'sepacredit' or a 'credit' operation is requested, we need a
     * SepaCreditTransferTransaction instead of SepaDirectDebitTransaction for this payment method.
     *
     * @param string|null $operation
     * @param string|null $paymentMethod
     *
     * @return SepaDirectDebitTransaction|SepaCreditTransferTransaction
     *
     * @since 1.0.0
     */
    public function getBackendTransaction($operation, $paymentMethod)
    {
        if ($paymentMethod === SepaCreditTransferTransaction::NAME || $operation === Operation::CREDIT) {
            return new SepaCreditTransferTransaction();
        }
        return new SepaDirectDebitTransaction();
    }

    /**
     * {@inheritdoc}
     */
    public function getTransactionConfig(Shop $shop, ParameterBagInterface $parameterBag, $selectedCurrency)
    {
        $config = parent::getTransactionConfig($shop, $parameterBag, $selectedCurrency);

        $sepaDirectDebitConfig = new SepaConfig(
            SepaDirectDebitTransaction::NAME,
            $this->getPaymentConfig()->getTransactionMAID(),
            $this->getPaymentConfig()->getTransactionSecret()
        );
        $sepaDirectDebitConfig->setCreditorId($this->getPaymentConfig()->getCreditorId());
        $config->add($sepaDirectDebitConfig);

        $sepaCreditTransferConfig = new SepaConfig(
            SepaCreditTransferTransaction::NAME,
            $this->getPaymentConfig()->getBackendTransactionMAID(),
            $this->getPaymentConfig()->getBackendTransactionSecret()
        );
        $sepaCreditTransferConfig->setCreditorId($this->getPaymentConfig()->getBackendCreditorId());
        $config->add($sepaCreditTransferConfig);

        return $config;
    }

    /**
     * @return SepaPaymentConfig
     *
     * @since 1.0.0
     */
    public function getPaymentConfig()
    {
        $paymentConfig = new SepaPaymentConfig(
            $this->getPluginConfig('SepaServer'),
            $this->getPluginConfig('SepaHttpUser'),
            $this->getPluginConfig('SepaHttpPassword')
        );

        $paymentConfig->setTransactionMAID($this->getPluginConfig('SepaMerchantId'));
        $paymentConfig->setTransactionSecret($this->getPluginConfig('SepaSecret'));
        $paymentConfig->setTransactionOperation($this->getPluginConfig('SepaTransactionType'));
        $paymentConfig->setShowBic($this->getPluginConfig('SepaShowBic'));
        $paymentConfig->setCreditorId($this->getPluginConfig('SepaCreditorId'));
        $paymentConfig->setCreditorName($this->getPluginConfig('SepaCreditorName'));
        $paymentConfig->setCreditorAddress($this->getPluginConfig('SepaCreditorAddress'));
        $paymentConfig->setBackendTransactionMAID($this->getPluginConfig('SepaBackendMerchantId'));
        $paymentConfig->setBackendTransactionSecret($this->getPluginConfig('SepaBackendSecret'));
        $paymentConfig->setBackendCreditorId($this->getPluginConfig('SepaBackendCreditorId'));

        $paymentConfig->setFraudPrevention($this->getPluginConfig('SepaFraudPrevention'));

        return $paymentConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function getAdditionalViewAssignments()
    {
        $paymentConfig = $this->getPaymentConfig();

        return [
            'method'          => $this->getName(),
            'showBic'         => $paymentConfig->showBic(),
            'creditorId'      => $paymentConfig->getCreditorId(),
            'creditorName'    => $paymentConfig->getCreditorName(),
            'creditorAddress' => $paymentConfig->getCreditorAddress(),
        ];
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
        $additionalPaymentData = $orderSummary->getAdditionalPaymentData();

        if (! isset($additionalPaymentData['sepaConfirmMandate'])
            || $additionalPaymentData['sepaConfirmMandate'] !== 'confirmed'
            || ! isset($additionalPaymentData['sepaIban'])
            || ! isset($additionalPaymentData['sepaFirstName'])
            || ! isset($additionalPaymentData['sepaLastName'])
        ) {
            throw new InsufficientDataException('Insufficient Data for SEPA Direct Debit Transaction');
        }

        $transaction = $this->getTransaction();

        $accountHolder = new AccountHolder();
        $accountHolder->setFirstName($additionalPaymentData['sepaFirstName']);
        $accountHolder->setLastName($additionalPaymentData['sepaLastName']);
        $transaction->setAccountHolder($accountHolder);

        $transaction->setIban($additionalPaymentData['sepaIban']);

        if ($this->getPluginConfig('SepaShowBic') && isset($additionalPaymentData['sepaBic'])) {
            $transaction->setBic($additionalPaymentData['sepaBic']);
        }

        $mandate = new Mandate($this->generateMandateId($orderSummary));
        $transaction->setMandate($mandate);

        return null;
    }

    /**
     * Generate sepa mandate id: Format "[creditorId]-[orderNumber]-[timestamp]"
     * [timestamp] is already part of the paymentUniqueId (first 10 characters). The remaining 5 characters of
     * paymentUniqueId can be used as [orderNumber], which has a max length of 5 anyway.
     *
     * @param OrderSummary $orderSummary
     *
     * @return string
     *
     * @since 1.0.0
     */
    private function generateMandateId(OrderSummary $orderSummary)
    {
        return $this->getPluginConfig('SepaCreditorId') . '-' .
               substr($orderSummary->getPaymentUniqueId(), 10, 5) . '-' .
               substr($orderSummary->getPaymentUniqueId(), 0, 10);
    }
}
