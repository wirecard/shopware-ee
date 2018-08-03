<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Components\Services;

use Doctrine\ORM\EntityManagerInterface;
use Shopware\Bundle\PluginInstallerBundle\Service\InstallerService;
use Shopware\Components\Routing\RouterInterface;
use WirecardElasticEngine\Components\Payments\CreditCardPayment;
use WirecardElasticEngine\Components\Payments\Payment;
use WirecardElasticEngine\Components\Payments\PaymentInterface;
use WirecardElasticEngine\Components\Payments\PaypalPayment;
use WirecardElasticEngine\Components\Payments\SepaPayment;
use WirecardElasticEngine\Components\Payments\SofortPayment;
use WirecardElasticEngine\Exception\UnknownPaymentException;
use WirecardElasticEngine\WirecardElasticEngine;

/**
 * Responsible for creating payment objects based on their name.
 *
 * @package WirecardElasticEngine\Components\Services
 *
 * @since   1.0.0
 */
class PaymentFactory
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var \Shopware_Components_Config
     */
    protected $shopwareConfig;

    /**
     * @var InstallerService
     */
    protected $installerService;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var \Enlight_Event_EventManager
     */
    protected $eventManager;

    /**
     * @param EntityManagerInterface      $em
     * @param \Shopware_Components_Config $shopwareConfig
     * @param InstallerService            $installerService
     * @param RouterInterface             $router
     * @param \Enlight_Event_EventManager $eventManager
     *
     * @since 1.0.0
     */
    public function __construct(
        EntityManagerInterface $em,
        \Shopware_Components_Config $shopwareConfig,
        InstallerService $installerService,
        RouterInterface $router,
        \Enlight_Event_EventManager $eventManager
    ) {
        $this->em               = $em;
        $this->shopwareConfig   = $shopwareConfig;
        $this->installerService = $installerService;
        $this->router           = $router;
        $this->eventManager     = $eventManager;
    }

    /**
     * @param string $paymentName
     *
     * @return Payment
     * @throws UnknownPaymentException
     *
     * @since 1.0.0
     */
    public function create($paymentName)
    {
        $mapping = $this->getMappedPayments();
        if (! isset($mapping[$paymentName])) {
            throw new UnknownPaymentException($paymentName);
        }

        return new $mapping[$paymentName](
            $this->em,
            $this->shopwareConfig,
            $this->installerService,
            $this->router,
            $this->eventManager
        );
    }

    /**
     * Used to register payments in Shopware.
     *
     * @see   WirecardElasticEngine::getSupportedPayments()
     *
     * @return PaymentInterface[]
     * @throws UnknownPaymentException
     *
     * @since 1.0.0
     */
    public function getSupportedPayments()
    {
        $payments = [];

        foreach (array_keys($this->getMappedPayments()) as $identifier) {
            $payments[] = $this->create($identifier);
        }

        return $payments;
    }

    /**
     * Contains a list of actual supported payments by the plugin.
     *
     * @return array
     *
     * @since 1.0.0
     */
    private function getMappedPayments()
    {
        return [
            PaypalPayment::PAYMETHOD_IDENTIFIER     => PaypalPayment::class,
            CreditCardPayment::PAYMETHOD_IDENTIFIER => CreditCardPayment::class,
            SepaPayment::PAYMETHOD_IDENTIFIER       => SepaPayment::class,
            SofortPayment::PAYMETHOD_IDENTIFIER     => SofortPayment::class,
        ];
    }
}
