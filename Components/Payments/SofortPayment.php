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
use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use Wirecard\PaymentSdk\Config\SepaConfig;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\SepaCreditTransferTransaction;
use Wirecard\PaymentSdk\Transaction\SofortTransaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardShopwareElasticEngine\Components\Data\OrderSummary;
use WirecardShopwareElasticEngine\Components\Data\SofortPaymentConfig;

class SofortPayment extends Payment
{
    const PAYMETHOD_IDENTIFIER = 'wirecard_elastic_engine_sofort';

    /**
     * @var SofortTransaction
     */
    private $transactionInstance;

    /**
     * @inheritdoc
     */
    public function getLabel()
    {
        return 'Wirecard Sofort.';
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return self::PAYMETHOD_IDENTIFIER;
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return 9;
    }

    /**
     * @return SofortTransaction
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
     * @inheritdoc
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
     * @inheritdoc
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

        return $paymentConfig;
    }

    /**
     * @inheritdoc
     */
    public function processPayment(
        OrderSummary $orderSummary,
        TransactionService $transactionService,
        Shop $shop,
        Redirect $redirect,
        \Enlight_Controller_Request_Request $request,
        \sOrder $shopwareOrder
    ) {
        return null;
    }
}