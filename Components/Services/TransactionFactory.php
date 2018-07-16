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

namespace WirecardShopwareElasticEngine\Components\Services;

use Doctrine\ORM\EntityManagerInterface;
use Wirecard\PaymentSdk\Response\FormInteractionResponse;
use Wirecard\PaymentSdk\Response\InteractionResponse;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use WirecardShopwareElasticEngine\Models\Transaction;

class TransactionFactory
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param int      $orderNumber
     * @param Response $response
     * @param string   $type
     *
     * @return Transaction|null
     */
    public function create($orderNumber, Response $response, $type = null)
    {
        $transaction = new Transaction();
        $transaction->setOrderNumber($orderNumber);
        $transaction->setType($type);
        $transaction->setCreatedAt(new \DateTime());

        $transaction->setRequestId($response->getRequestId());
        $transaction->setTransactionType($response->getTransactionType());
        $transaction->setResponse($response->getData());

        if ($response instanceof SuccessResponse) {
            $transaction->setTransactionId($response->getTransactionId());
            $transaction->setParentTransactionId($response->getParentTransactionId());
            $transaction->setProviderTransactionId($response->getProviderTransactionId());
            $transaction->setProviderTransactionReference($response->getProviderTransactionReference());
        } elseif ($response instanceof InteractionResponse || $response instanceof FormInteractionResponse) {
            $transaction->setTransactionId($response->getTransactionId());
        }

        if ($response->getRequestedAmount()) {
            $transaction->setCurrency($response->getRequestedAmount()->getCurrency());
            $transaction->setAmount($response->getRequestedAmount()->getValue());
        }

        if (! $transaction->getTransactionId()) {
            return null;
        }

        $this->em->persist($transaction);
        $this->em->flush();

        return $transaction;
    }
}
