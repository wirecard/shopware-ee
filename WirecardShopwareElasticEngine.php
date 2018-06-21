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

namespace WirecardShopwareElasticEngine;

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once(__DIR__ . '/vendor/autoload.php');
}

use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Components\Plugin\Context\UpdateContext;
use WirecardShopwareElasticEngine\Components\Payments\PaymentInterface;
use WirecardShopwareElasticEngine\Components\Payments\PaypalPayment;

use WirecardShopwareElasticEngine\Models\Transaction;

use Doctrine\ORM\Tools\SchemaTool;

class WirecardShopwareElasticEngine extends Plugin
{
    public function install(InstallContext $context)
    {
        $this->registerPayments();
        
        $entityManager = $this->container->get('models');
        $schemaTool = new SchemaTool($entityManager);

        $schemaTool->updateSchema(
            [ $entityManager->getClassMetadata(Transaction::class) ],
            true
        );
    }

    public function uninstall(UninstallContext $context)
    {
        parent::uninstall($context);

        if ($context->keepUserData()) {
            return;
        }
    }

    public function update(UpdateContext $context)
    {
        parent::update($context);
    }

    public function activate(ActivateContext $context)
    {
        parent::activate($context);
    }

    public function deactivate(DeactivateContext $context)
    {
        parent::deactivate($context);
    }

    protected function registerPayments()
    {
        $installer = $this->container->get('shopware.plugin_payment_installer');

        foreach ($this->getSupportedPayments() as $payment) {
            $installer->createOrUpdate($payment->getName(), $payment->getPaymentOptions());
        }
    }

    /**
     * @return PaymentInterface[]
     */
    protected function getSupportedPayments()
    {
        return [
            new PaypalPayment()
        ];
    }
}
