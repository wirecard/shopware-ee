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
use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use Wirecard\PaymentSdk\Config\SepaConfig;
use Wirecard\PaymentSdk\Entity\IdealBic;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\SepaCreditTransferTransaction;
use Wirecard\PaymentSdk\Transaction\IdealTransaction;
use Wirecard\PaymentSdk\Transaction\Transaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardElasticEngine\Components\Data\OrderSummary;
use WirecardElasticEngine\Components\Data\IdealPaymentConfig;
use WirecardElasticEngine\Components\Payments\Contracts\AdditionalViewAssignmentsInterface;
use WirecardElasticEngine\Components\Payments\Contracts\ProcessPaymentInterface;

/**
 * @package WirecardElasticEngine\Components\Payments
 *
 * @since   1.0.0
 */
class IdealPayment extends Payment implements ProcessPaymentInterface, AdditionalViewAssignmentsInterface
{
    const PAYMETHOD_IDENTIFIER = 'wirecard_elastic_engine_ideal';

    /**
     * @var IdealTransaction
     */
    private $transactionInstance;

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'WirecardiDEAL';
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
        return 3;
    }

    /**
     * {@inheritdoc}
     */

    public function getTransaction()
    {
        if (! $this->transactionInstance) {
            $this->transactionInstance = new IdealTransaction();
        }
        return $this->transactionInstance;
    }

    /**
     * If the paymentMethod is 'sepacredit' or a 'credit'/'cancel' operation is requested, we need a
     * SepaCreditTransferTransaction instead of IdealTransaction for this payment method.
     *
     * @param string|null $operation
     * @param string|null $paymentMethod
     * @param string|null $transactionType
     *
     * @return IdealTransaction|SepaCreditTransferTransaction
     *
     * @since 1.0.0
     */
    public function getBackendTransaction($operation, $paymentMethod, $transactionType)
    {
        if ($paymentMethod === SepaCreditTransferTransaction::NAME
            || $operation === Operation::CREDIT
            || $operation === Operation::CANCEL
            || $transactionType === Transaction::TYPE_CREDIT
        ) {
            return new SepaCreditTransferTransaction();
        }
        return new IdealTransaction();
    }

    /**
     * {@inheritdoc}
     */
    public function getTransactionConfig(Shop $shop, ParameterBagInterface $parameterBag, $selectedCurrency)
    {
        $config = parent::getTransactionConfig($shop, $parameterBag, $selectedCurrency);
        $config->add(new PaymentMethodConfig(
            IdealTransaction::NAME,
            $this->getPaymentConfig()->getTransactionMAID(),
            $this->getPaymentConfig()->getTransactionSecret()
        ));

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
     * @return IdealPaymentConfig
     *
     * @since 1.0.0
     */
    public function getPaymentConfig()
    {
        $paymentConfig = new IdealPaymentConfig(
            $this->getPluginConfig('IdealServer'),
            $this->getPluginConfig('IdealHttpUser'),
            $this->getPluginConfig('IdealHttpPassword')
        );

        $paymentConfig->setTransactionMAID($this->getPluginConfig('IdealMerchantId'));
        $paymentConfig->setTransactionSecret($this->getPluginConfig('IdealSecret'));
        $paymentConfig->setTransactionOperation(parent::TRANSACTION_OPERATION_PAY);
        $paymentConfig->setSendDescriptor(true);
        $paymentConfig->setBackendTransactionMAID($this->getPluginConfig('SepaBackendMerchantId'));
        $paymentConfig->setBackendTransactionSecret($this->getPluginConfig('SepaBackendSecret'));
        $paymentConfig->setBackendCreditorId($this->getPluginConfig('SepaBackendCreditorId'));

        $paymentConfig->setFraudPrevention($this->getPluginConfig('IdealFraudPrevention'));

        return $paymentConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function getAdditionalViewAssignments()
    {
        $idealBic = new \ReflectionClass(IdealBic::class);

        return [
            'method'     => $this->getName(),
            'idealBanks' => $idealBic->getConstants(),
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

        $idealBic = new \ReflectionClass(IdealBic::class);

        $transaction = $this->getTransaction();
        $transaction->setBic($idealBic->getConstant($additionalPaymentData['idealBank']));
    }
}
