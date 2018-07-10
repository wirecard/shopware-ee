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
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;
use Shopware_Controllers_Frontend_Payment;
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\Response;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use WirecardShopwareElasticEngine\Components\Actions\Action;
use WirecardShopwareElasticEngine\Components\Actions\RedirectAction;
use WirecardShopwareElasticEngine\Models\OrderNumberAssignment;

class ReturnHandler
{
    /**
     * @var Shopware_Controllers_Frontend_Payment
     */
    protected $controller;

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(Shopware_Controllers_Frontend_Payment $controller)
    {
        $this->controller = $controller;
        $this->em         = $controller->getModelManager();
        $this->logger     = $controller->get('logger');
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
        $transactionId   = $response->getTransactionId();
        $paymentUniqueId = $response->getProviderTransactionId();

        // This is actually NOT the order number but the ID of our generated OrderNumberAssignment!
        $orderNumberAssignmentId = $response->findElement('order-number');

        $orderNumberAssignment = $this->em
            ->getRepository(OrderNumberAssignment::class)
            ->find($orderNumberAssignmentId);

        if (! $orderNumberAssignment) {
            // todo: error handling
        }

        $orderNumberAssignment->setTransactionId($transactionId);
        $orderNumberAssignment->setProviderTransactionId($paymentUniqueId);

        $order = $this->em
            ->getRepository(Order::class)
            ->findOneBy([
                'transactionId' => $transactionId,
                'temporaryId'   => $paymentUniqueId,
                'status'        => Status::ORDER_STATE_CANCELLED,
            ]);

        if ($order) {
            $this->em->flush();
            return new RedirectAction($this->controller->get('router')->assemble([
                'module'     => 'frontend',
                'controller' => 'checkout',
                'action'     => 'finish',
                'sUniqueID'  => $paymentUniqueId,
            ]));
        }

        return new RedirectAction(null);
    }

    /**
     * @param FailureResponse $response
     * @return Action
     */
    protected function handleFailure(FailureResponse $response)
    {
        return new RedirectAction(null);
    }
}
