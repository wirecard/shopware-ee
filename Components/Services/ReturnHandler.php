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
use Psr\Log\LoggerInterface;
use Shopware\Components\Routing\RouterInterface;
use Shopware\Models\Order\Order;
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use WirecardShopwareElasticEngine\Components\Actions\Action;
use WirecardShopwareElasticEngine\Components\Actions\ErrorAction;
use WirecardShopwareElasticEngine\Components\Actions\RedirectAction;
use WirecardShopwareElasticEngine\Exception\OrderNotFoundException;
use WirecardShopwareElasticEngine\Exception\ParentTransactionNotFoundException;
use WirecardShopwareElasticEngine\Models\Transaction;

class ReturnHandler extends Handler
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var RouterInterface
     */
    protected $router;

    public function __construct(RouterInterface $router, EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->router = $router;
        $this->em     = $em;
        $this->logger = $logger;
    }

    /**
     * @param Response $response
     * @return Action
     */
    public function execute(Response $response)
    {
        switch (true) {
            case $response instanceof SuccessResponse:
                return $this->handleSuccess($response);

            case $response instanceof FailureResponse:
            default:
                return $this->handleFailure($response);
        }
    }

    /**
     * @param SuccessResponse $response
     * @return Action
     */
    protected function handleSuccess(SuccessResponse $response)
    {
        $transactionId       = $response->getTransactionId();
        $parentTransactionId = $response->getParentTransactionId();
        $orderNumber         = $this->getOrderNumberFromResponse($response);

        $order = $this->em
            ->getRepository(Order::class)
            ->findOneBy([
                'number' => $orderNumber,
            ]);

        if (! $order) {
            throw new OrderNotFoundException($orderNumber, $transactionId);
        }

        // TemporaryID is set to the order number, since the returned `RedirectAction` will contain this ID
        // as `sUniqueID` to get information about what order has been processed and show proper information.
        $order->setTemporaryId($orderNumber);

        $parentTransaction = $this->em
            ->getRepository(Transaction::class)
            ->findOneBy([
                'transactionId' => $response->getParentTransactionId(),
            ]);

        if (! $parentTransaction) {
            throw new ParentTransactionNotFoundException($parentTransactionId, $transactionId);
        }

        $transaction = new Transaction();
        $transaction->setTransactionId($response->getTransactionId());
        $transaction->setProviderTransactionId($response->getProviderTransactionId());
        $transaction->setCurrency($response->getRequestedAmount()->getCurrency());
        $transaction->setAmount($response->getRequestedAmount()->getValue());
        $transaction->setTransactionType($response->getTransactionType());
        $transaction->setResponse($response->getData());
        $transaction->setCreatedAt(new \DateTime());

        $this->em->persist($transaction);
        $this->em->flush();

        return new RedirectAction($this->router->assemble([
            'module'     => 'frontend',
            'controller' => 'checkout',
            'action'     => 'finish',
            'sUniqueID'  => $order->getTemporaryId()
        ]));
    }

    /**
     * @param FailureResponse $response
     * @return Action
     */
    protected function handleFailure(FailureResponse $response)
    {
        $this->logger->error('Return handling failed', $response->getData());

        return new ErrorAction(ErrorAction::FAILURE_RESPONSE, 'Failure response');
    }
}