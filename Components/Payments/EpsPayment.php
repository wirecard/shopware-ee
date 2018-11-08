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
use Wirecard\PaymentSdk\Config\SepaConfig;
use Wirecard\PaymentSdk\Entity\BankAccount;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\SepaCreditTransferTransaction;
use Wirecard\PaymentSdk\Transaction\EpsTransaction;
use Wirecard\PaymentSdk\Transaction\Transaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardElasticEngine\Components\Data\OrderSummary;
use WirecardElasticEngine\Components\Data\EpsPaymentConfig;
use WirecardElasticEngine\Components\Data\PaymentConfig;
use WirecardElasticEngine\Components\Payments\Contracts\AdditionalViewAssignmentsInterface;
use WirecardElasticEngine\Components\Payments\Contracts\ProcessPaymentInterface;
use WirecardElasticEngine\Components\Services\SessionManager;
use WirecardElasticEngine\Models\Transaction as TransactionModel;

/**
 * @package WirecardElasticEngine\Components\Payments
 *
 * @since   1.0.0
 */
class EpsPayment extends Payment implements ProcessPaymentInterface, AdditionalViewAssignmentsInterface {
    const PAYMETHOD_IDENTIFIER = 'wirecard_elastic_engine_eps';

    /**
     * @var EpsTransaction
     */
    private $transactionInstance;

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'WirecardEPS';
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
            $this->transactionInstance = new EpsTransaction();
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
            EpsTransaction::NAME,
            $this->getPaymentConfig()->getTransactionMAID(),
            $this->getPaymentConfig()->getTransactionSecret()
        ));

        return $config;
    }

    /**
     * @return PaymentConfig
     *
     * @since 1.0.0
     */
    public function getPaymentConfig()
    {
        $paymentConfig = new PaymentConfig(
            $this->getPluginConfig('EpsServer'),
            $this->getPluginConfig('EpsHttpUser'),
            $this->getPluginConfig('EpsHttpPassword')
        );

        $paymentConfig->setTransactionMAID($this->getPluginConfig('EpsMerchantId'));
        $paymentConfig->setTransactionSecret($this->getPluginConfig('EpsSecret'));
        $paymentConfig->setTransactionOperation(parent::TRANSACTION_OPERATION_PAY);
        $paymentConfig->setSendDescriptor(true);

        $paymentConfig->setFraudPrevention($this->getPluginConfig('EpsFraudPrevention'));

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
        $additionalPaymentData = $orderSummary->getAdditionalPaymentData();
        /** @var EpsTransaction $transaction */
        $transaction = $this->getTransaction();

        if (array_key_exists('epsBic', $additionalPaymentData)) {
            $bankAccount = new BankAccount();
            $bankAccount->setBic($additionalPaymentData['epsBic']);
            $transaction->setBankAccount($bankAccount);
        }
    }

    /**
     * Some payments (e.g. SEPA) require additional view assignments (e.g. for displaying additional input fields).
     *
     * @param SessionManager $sessionManager
     *
     * @return array
     *
     * @since 1.1.0 Added $sessionManager
     * @since 1.0.0
     */
    public function getAdditionalViewAssignments(SessionManager $sessionManager)
    {
        return [
            'method' => $this->getName()
        ];
    }
}
