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
use Shopware\Bundle\PluginInstallerBundle\Service\InstallerService;
use Shopware\Components\Routing\RouterInterface;
use WirecardShopwareElasticEngine\Components\Payments\CreditCardPayment;
use WirecardShopwareElasticEngine\Components\Payments\Payment;
use WirecardShopwareElasticEngine\Components\Payments\PaymentInterface;
use WirecardShopwareElasticEngine\Components\Payments\PaypalPayment;
use WirecardShopwareElasticEngine\Components\Payments\SepaPayment;
use WirecardShopwareElasticEngine\Exception\UnknownPaymentException;

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
     * @param EntityManagerInterface      $em
     * @param \Shopware_Components_Config $shopwareConfig
     * @param InstallerService            $installerService
     * @param RouterInterface             $router
     */
    public function __construct(
        EntityManagerInterface $em,
        \Shopware_Components_Config $shopwareConfig,
        InstallerService $installerService,
        RouterInterface $router
    ) {
        $this->em               = $em;
        $this->shopwareConfig   = $shopwareConfig;
        $this->installerService = $installerService;
        $this->router           = $router;
    }

    /**
     * @param string $paymentName
     *
     * @return Payment
     * @throws UnknownPaymentException
     */
    public function create($paymentName)
    {
        $class = null;
        switch ($paymentName) {
            case PaypalPayment::PAYMETHOD_IDENTIFIER:
                $class = PaypalPayment::class;
                break;

            case CreditCardPayment::PAYMETHOD_IDENTIFIER:
                $class = CreditCardPayment::class;
                break;

            case SepaPayment::PAYMETHOD_IDENTIFIER:
                $class = SepaPayment::class;
                break;
        }

        if (! $class) {
            throw new UnknownPaymentException($paymentName);
        }

        return new $class($this->em, $this->shopwareConfig, $this->installerService, $this->router);
    }

    /**
     * @return PaymentInterface[]
     * @throws UnknownPaymentException
     */
    public function getSupportedPayments()
    {
        return [
            $this->create(PaypalPayment::PAYMETHOD_IDENTIFIER),
            $this->create(CreditCardPayment::PAYMETHOD_IDENTIFIER),
            $this->create(SepaPayment::PAYMETHOD_IDENTIFIER)
        ];
    }
}
