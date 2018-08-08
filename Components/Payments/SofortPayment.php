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
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\SepaCreditTransferTransaction;
use Wirecard\PaymentSdk\Transaction\SofortTransaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardElasticEngine\Components\Data\OrderSummary;
use WirecardElasticEngine\Components\Data\SofortPaymentConfig;
use WirecardElasticEngine\Components\Payments\Contracts\ProcessPaymentInterface;

/**
 * @package WirecardElasticEngine\Components\Payments
 *
 * @since   1.0.0
 */
class SofortPayment extends Payment implements ProcessPaymentInterface
{
    const PAYMETHOD_IDENTIFIER = 'wirecard_elastic_engine_sofort';

    /**
     * @var SofortTransaction
     */
    private $transactionInstance;

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'WirecardSofort';
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
        return 9;
    }

    /**
     * {@inheritdoc}
     */
    public function getTransaction()
    {
        if (! $this->transactionInstance) {
            $this->transactionInstance = new SofortTransaction();
        }
        return $this->transactionInstance;
    }

    /**
     * If the paymentMethod is 'sepacredit' or a 'credit'/'cancel' operation is requested, we need a
     * SepaCreditTransferTransaction instead of SofortTransaction for this payment method.
     *
     * @param string|null $operation
     * @param string|null $paymentMethod
     *
     * @return SofortTransaction|SepaCreditTransferTransaction
     *
     * @since 1.0.0
     */
    public function getBackendTransaction($operation, $paymentMethod)
    {
        if ($paymentMethod === SepaCreditTransferTransaction::NAME
            || $operation === Operation::CREDIT
            || $operation === Operation::CANCEL
        ) {
            return new SepaCreditTransferTransaction();
        }
        return new SofortTransaction();
    }

    /**
     * {@inheritdoc}
     */
    public function getTransactionConfig(Shop $shop, ParameterBagInterface $parameterBag, $selectedCurrency)
    {
        $config = parent::getTransactionConfig($shop, $parameterBag, $selectedCurrency);
        $config->add(new  PaymentMethodConfig(
            SofortTransaction::NAME,
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
     * {@inheritdoc}
     */
    public function getPaymentConfig()
    {
        $paymentConfig = new SofortPaymentConfig(
            $this->getPluginConfig('SofortServer'),
            $this->getPluginConfig('SofortHttpUser'),
            $this->getPluginConfig('SofortHttpPassword')
        );

        $paymentConfig->setTransactionMAID($this->getPluginConfig('SofortMerchantId'));
        $paymentConfig->setTransactionSecret($this->getPluginConfig('SofortSecret'));
        $paymentConfig->setTransactionOperation(parent::TRANSACTION_OPERATION_PAY);
        $paymentConfig->setSendDescriptor(true);
        $paymentConfig->setBackendTransactionMAID($this->getPluginConfig('SepaBackendMerchantId'));
        $paymentConfig->setBackendTransactionSecret($this->getPluginConfig('SepaBackendSecret'));
        $paymentConfig->setBackendCreditorId($this->getPluginConfig('SepaBackendCreditorId'));

        $paymentConfig->setFraudPrevention($this->getPluginConfig('SofortFraudPrevention'));

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
        $transaction->setOrderNumber($orderSummary->getPaymentUniqueId());

        return null;
    }
}
