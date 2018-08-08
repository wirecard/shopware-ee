<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
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
