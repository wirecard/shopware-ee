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

namespace WirecardElasticEngine\Components\Mail;

use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\Transaction\Transaction;
use WirecardElasticEngine\Models\Transaction as TransactionModel;
use WirecardElasticEngine\WirecardElasticEngine;

/**
 * @package WirecardElasticEngine\Components\Mail
 *
 * @since   1.0.0
 */
class MerchantNotificationMail
{
    /**
     * @var \Enlight_Components_Mail
     */
    private $mail;

    /**
     * @var \Shopware_Components_Config
     */
    private $shopwareConfig;

    /**
     * @var \Shopware_Components_Snippet_Manager
     */
    private $snippetManager;

    /**
     * @param \Enlight_Components_Mail             $mail
     * @param \Shopware_Components_Config          $config
     * @param \Shopware_Components_Snippet_Manager $snippetManager
     *
     * @since 1.0.0
     */
    public function __construct(
        \Enlight_Components_Mail $mail,
        \Shopware_Components_Config $config,
        \Shopware_Components_Snippet_Manager $snippetManager
    ) {
        $this->mail           = $mail;
        $this->shopwareConfig = $config;
        $this->snippetManager = $snippetManager;
    }

    /**
     * Send payment notification e-mail to merchant, if transaction is "authorization" or "purchase" and
     * wirecardElasticEngineNotifyMail has been entered in plugin configuration.
     *
     * @param SuccessResponse  $notification
     * @param TransactionModel $notifyTransaction
     *
     * @return \Zend_Mail|null
     *
     * @since 1.0.0
     */
    public function send(SuccessResponse $notification, TransactionModel $notifyTransaction)
    {
        if ($notification->getTransactionType() !== Transaction::TYPE_AUTHORIZATION
            && $notification->getTransactionType() !== Transaction::TYPE_PURCHASE
        ) {
            return null;
        }

        $notifyMailAddress = $this->shopwareConfig->getByNamespace(
            WirecardElasticEngine::NAME,
            'wirecardElasticEngineNotifyMail'
        );
        if (! $notifyMailAddress) {
            return null;
        }

        $this->mail->addTo($notifyMailAddress);
        $this->mail->setSubject($this->getSubject());
        $this->mail->setBodyText($this->getMessage($notification, $notifyTransaction));
        return $this->mail->send();
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    private function getSubject()
    {
        $snippets = $this->snippetManager->getNamespace('backend/wirecard_elastic_engine/common');
        return $snippets->get('PaymentNotificationMailSubject', 'Payment notification received');
    }

    /**
     * @param SuccessResponse  $notification
     * @param TransactionModel $notifyTransaction
     *
     * @return string
     *
     * @since 1.0.0
     */
    private function getMessage(SuccessResponse $notification, TransactionModel $notifyTransaction)
    {
        $orderNumber     = $notifyTransaction->getOrderNumber() ?: '-';
        $paymentId       = $notifyTransaction->getPaymentUniqueId();
        $transactionId   = $notification->getTransactionId();
        $transactionType = $notification->getTransactionType();
        $amount          = $notification->getRequestedAmount()->getValue();
        $currency        = $notification->getRequestedAmount()->getCurrency();

        $snippets = $this->snippetManager->getNamespace('backend/wirecard_elastic_engine/transactions_window');

        $orderNumberLabel     = $snippets->get('OrderNumber', 'Order Number');
        $paymentNumberLabel   = $snippets->get('PaymentUniqueId', 'Payment Number');
        $transactionIdLabel   = $snippets->get('TransactionId', 'Transaction ID');
        $transactionTypeLabel = $snippets->get('TransactionType', 'Action');
        $amountLabel          = $snippets->get('Amount', 'Amount');

        $message = $orderNumberLabel . ': ' . $orderNumber . PHP_EOL;
        $message .= $paymentNumberLabel . ': ' . $paymentId . PHP_EOL;
        $message .= $transactionIdLabel . ': ' . $transactionId . PHP_EOL;
        $message .= $transactionTypeLabel . ': ' . $transactionType . PHP_EOL;
        $message .= $amountLabel . ': ' . $amount . ' ' . $currency . PHP_EOL;

        $message .= PHP_EOL . PHP_EOL;
        $message .= $snippets->get('Response', 'Response data ID') . ': ' . PHP_EOL;
        $message .= print_r($notification->getData(), true);

        return $message;
    }
}
