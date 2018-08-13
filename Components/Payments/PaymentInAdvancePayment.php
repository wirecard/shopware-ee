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
use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\PoiPiaTransaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardElasticEngine\Components\Data\OrderSummary;
use WirecardElasticEngine\Components\Data\PaymentConfig;
use WirecardElasticEngine\Components\Payments\Contracts\AdditionalPaymentInformationInterface;
use WirecardElasticEngine\Components\Payments\Contracts\ProcessPaymentInterface;
use WirecardElasticEngine\Models\Transaction;

/**
 * @package WirecardElasticEngine\Components\Payments
 *
 * @since   1.1.0
 */
class PaymentInAdvancePayment extends Payment implements ProcessPaymentInterface, AdditionalPaymentInformationInterface
{
    const PAYMETHOD_IDENTIFIER = 'wirecard_elastic_engine_pia';

    /**
     * @var PoiPiaTransaction
     */
    private $transactionInstance;

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'WirecardPaymentInAdvance';
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
        return 6;
    }

    /**
     * @return PoiPiaTransaction
     *
     * @since 1.1.0
     */
    public function getTransaction()
    {
        if (! $this->transactionInstance) {
            $this->transactionInstance = new PoiPiaTransaction();
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
            PoiPiaTransaction::NAME,
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
        $paymentConfig = new PaymentConfig(
            $this->getPluginConfig('PoiPiaServer'),
            $this->getPluginConfig('PoiPiaHttpUser'),
            $this->getPluginConfig('PoiPiaHttpPassword')
        );

        $paymentConfig->setTransactionMAID($this->getPluginConfig('PoiPiaMerchantId'));
        $paymentConfig->setTransactionSecret($this->getPluginConfig('PoiPiaSecret'));
        $paymentConfig->setTransactionOperation(parent::TRANSACTION_OPERATION_RESERVE);
        $paymentConfig->setFraudPrevention($this->getPluginConfig('PoiPiaFraudPrevention'));

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
        if (! $this->getPaymentConfig()->hasFraudPrevention()) {
            $accountHolder = new AccountHolder();
            $accountHolder->setLastName($orderSummary->getUserMapper()->getLastName());
            $accountHolder->setFirstName($orderSummary->getUserMapper()->getFirstName());
            $this->getTransaction()->setAccountHolder($accountHolder);
        }
    }

    /**
     * Some payments (e.g. PIA) require additional payment information on the checkout finish page (e.g. bank data).
     *
     * @param \Enlight_View_Default $view
     *
     * @since 1.1.0
     */
    public function assignAdditionalPaymentInformation(\Enlight_View_Default $view)
    {
        $payment     = $view->getAssign('sPayment');
        $orderNumber = $view->getAssign('sOrderNumber');
        if (! isset($payment['name']) || $payment['name'] !== self::PAYMETHOD_IDENTIFIER || ! $orderNumber) {
            return;
        }

        $transaction = $this->em->getRepository(Transaction::class)->findOneBy([
            'orderNumber' => $orderNumber,
            'type'        => Transaction::TYPE_INITIAL_RESPONSE,
        ]);
        if (! $transaction) {
            return;
        }

        $response = $transaction->getResponse();
        $view->assign('wirecardElasticEngineBankData', [
            'bankName'  => $response['merchant-bank-account.0.bank-name'],
            'bic'       => $response['merchant-bank-account.0.bic'],
            'iban'      => $response['merchant-bank-account.0.iban'],
            'address'   => $response['merchant-bank-account.0.branch-address'],
            'city'      => $response['merchant-bank-account.0.branch-city'],
            'state'     => $response['merchant-bank-account.0.branch-state'],
            'reference' => $transaction->getProviderTransactionReference(),
        ]);
    }
}
