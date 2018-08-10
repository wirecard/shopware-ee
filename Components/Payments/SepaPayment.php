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
use Wirecard\PaymentSdk\Config\SepaConfig;
use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\Mandate;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\SepaCreditTransferTransaction;
use Wirecard\PaymentSdk\Transaction\SepaDirectDebitTransaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardElasticEngine\Components\Data\OrderSummary;
use WirecardElasticEngine\Components\Data\SepaPaymentConfig;
use WirecardElasticEngine\Components\Payments\Contracts\AdditionalViewAssignmentsInterface;
use WirecardElasticEngine\Components\Payments\Contracts\ProcessPaymentInterface;
use WirecardElasticEngine\Exception\InsufficientDataException;

/**
 * @package WirecardElasticEngine\Components\Payments
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
