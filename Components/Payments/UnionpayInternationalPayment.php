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
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\UpiTransaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardElasticEngine\Components\Actions\ViewAction;
use WirecardElasticEngine\Components\Data\OrderSummary;
use WirecardElasticEngine\Components\Data\PaymentConfig;
use WirecardElasticEngine\Components\Payments\Contracts\ProcessPaymentInterface;
use WirecardElasticEngine\Components\Payments\Contracts\ProcessReturnInterface;
use WirecardElasticEngine\Components\Services\SessionManager;
use WirecardElasticEngine\Models\Transaction;

/**
 * @package WirecardElasticEngine\Components\Payments
 *
 * @since   1.1.0
 */
class UnionpayInternationalPayment extends Payment implements ProcessReturnInterface, ProcessPaymentInterface
{
    const PAYMETHOD_IDENTIFIER = 'wirecard_elastic_engine_unionpay_international';

    /**
     * @var UpiTransaction
     */
    private $transactionInstance;

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'WirecardUnionPayInternational';
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
        return 10;
    }

    /**
     * @return UpiTransaction
     *
     * @since 1.1.0
     */
    public function getTransaction()
    {
        if (! $this->transactionInstance) {
            $this->transactionInstance = new UpiTransaction();
        }
        return $this->transactionInstance;
    }

    /**
     * {@inheritdoc}
     */
    public function getTransactionConfig(ParameterBagInterface $parameterBag, $selectedCurrency)
    {
        $config = parent::getTransactionConfig($parameterBag, $selectedCurrency);
        $config->add(new PaymentMethodConfig(
            UpiTransaction::NAME,
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
            $this->getPluginConfig('UnionpayInternationalServer'),
            $this->getPluginConfig('UnionpayInternationalHttpUser'),
            $this->getPluginConfig('UnionpayInternationalHttpPassword')
        );

        $paymentConfig->setTransactionMAID($this->getPluginConfig('UnionpayInternationalMerchantId'));
        $paymentConfig->setTransactionSecret($this->getPluginConfig('UnionpayInternationalSecret'));
        $paymentConfig->setTransactionOperation($this->getPluginConfig('UnionpayInternationalTransactionType'));
        $paymentConfig->setFraudPrevention($this->getPluginConfig('UnionpayInternationalFraudPrevention'));

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
        $transaction->setTermUrl($redirect->getSuccessUrl());

        $requestData = $transactionService->getCreditCardUiWithData(
            $transaction,
            $orderSummary->getPayment()->getTransactionType(),
            $shop->getLocale()->getLocale()
        );

        $transactionModel = new Transaction(Transaction::TYPE_INITIAL_REQUEST);
        $transactionModel->setPaymentUniqueId($orderSummary->getPaymentUniqueId());
        $transactionModel->setBasketSignature($orderSummary->getBasketMapper()->getSignature());
        $transactionModel->setRequest(json_decode($requestData, true));
        $this->em->persist($transactionModel);
        $this->em->flush();

        return new ViewAction('credit_card.tpl', [
            'wirecardUrl'         => $orderSummary->getPayment()->getPaymentConfig()->getBaseUrl(),
            'wirecardRequestData' => $requestData,
            'url'                 => $this->router->assemble([
                'action' => 'return',
                'method' => $this->getName(),
            ]),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function processReturn(
        TransactionService $transactionService,
        \Enlight_Controller_Request_Request $request,
        SessionManager $sessionManager
    ) {
        $params = $request->getParams();
        if (! empty($params['jsresponse'])) {
            return $transactionService->processJsResponse($request->getParams(), $this->router->assemble([
                'action' => 'return',
                'method' => $this->getName(),
            ]));
        }

        return null;
    }
}
