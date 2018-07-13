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
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\SepaDirectDebitTransaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardShopwareElasticEngine\Components\Data\OrderSummary;
use WirecardShopwareElasticEngine\Components\Data\SepaPaymentConfig;

class SepaPayment extends Payment
{
    const PAYMETHOD_IDENTIFIER = 'wirecard_elastic_engine_sepa';

    /**
     * @var SepaDirectDebitTransaction
     */
    private $transactionInstance;

    /**
     * @inheritdoc
     */
    public function getLabel()
    {
        return 'Wirecard SEPA Direct Debit';
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return self::PAYMETHOD_IDENTIFIER;
    }

    public function getPosition()
    {
        return 8;
    }

    /**
     * @return SepaDirectDebitTransaction
     */
    public function getTransaction()
    {
        if (! $this->transactionInstance) {
            $this->transactionInstance = new SepaDirectDebitTransaction();
        }
        return $this->transactionInstance;
    }

    /**
     * @inheritdoc
     */
    public function getTransactionConfig(Shop $shop, ParameterBagInterface $parameterBag)
    {
        $config = parent::getTransactionConfig($shop, $parameterBag);

        return $config;
    }

    /**
     * @inheritdoc
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
        $paymentConfig->setMandateText($this->getPluginConfig('SepaMandateText'));
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
        // TODO
    }
}
