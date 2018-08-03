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

namespace WirecardElasticEngine;

// PSR1.Files.SideEffects is disabled for this file, see `phpcs.xml`
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once(__DIR__ . '/vendor/autoload.php');
}

use Shopware\Models\Config\Element;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Components\Plugin\Context\UpdateContext;
use Shopware\Models\Payment\Payment;
use Shopware\Models\Plugin\Plugin as PluginModel;
use WirecardElasticEngine\Components\Services\PaymentFactory;
use WirecardElasticEngine\Models\Transaction;
use Doctrine\ORM\Tools\SchemaTool;

/**
 * @package WirecardElasticEngine
 *
 * @since   1.0.0
 */
class WirecardElasticEngine extends Plugin
{
    const NAME = 'WirecardElasticEngine';

    /**
     * @param InstallContext $context
     *
     * @throws Exception\UnknownPaymentException
     *
     * @since 1.0.0
     */
    public function install(InstallContext $context)
    {
        $this->registerPayments($context->getPlugin());
        $this->updateDatabase();

        $this->changeDefaultConfigValues();
    }

    /**
     * @param UninstallContext $context
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @since 1.0.0
     */
    public function uninstall(UninstallContext $context)
    {
        parent::uninstall($context);

        $this->deactivatePayments($context->getPlugin());
    }

    /**
     * @param UpdateContext $context
     *
     * @throws Exception\UnknownPaymentException
     *
     * @since 1.0.0
     */
    public function update(UpdateContext $context)
    {
        parent::update($context);

        $this->updatePayments($context->getPlugin());
        $this->updateDatabase();
    }

    /**
     * @param ActivateContext $context
     *
     * @since 1.0.0
     */
    public function activate(ActivateContext $context)
    {
        parent::activate($context);
        $context->scheduleClearCache(ActivateContext::CACHE_LIST_ALL);
    }

    /**
     * @param DeactivateContext $context
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @since 1.0.0
     */
    public function deactivate(DeactivateContext $context)
    {
        parent::deactivate($context);
        $this->deactivatePayments($context->getPlugin());
        $context->scheduleClearCache(DeactivateContext::CACHE_LIST_ALL);
    }

    /**
     * Update database to latest plugin database schema.
     *
     * @since 1.0.0
     */
    protected function updateDatabase()
    {
        $entityManager = $this->container->get('models');
        $schemaTool    = new SchemaTool($entityManager);

        $schemaTool->updateSchema(
            [
                $entityManager->getClassMetadata(Transaction::class),
            ],
            true
        );
    }

    /**
     * Create or update wirecard payments. Existing payment option will be overwritten (on install).
     *
     * @param PluginModel $plugin
     *
     * @throws Exception\UnknownPaymentException
     *
     * @since 1.0.0
     */
    protected function registerPayments(PluginModel $plugin)
    {
        $installer = $this->container->get('shopware.plugin_payment_installer');

        $payments = [];
        foreach ($this->getSupportedPayments() as $payment) {
            $payments[] = $installer->createOrUpdate($plugin->getName(), $payment->getPaymentOptions());
        }
        if (! empty($payments)) {
            $this->translatePayments($payments);
        }
    }

    /**
     * Create new wirecard payments. Existing payments remain unchanged (on update).
     *
     * @param PluginModel $plugin
     *
     * @throws Exception\UnknownPaymentException
     *
     * @since 1.0.0
     */
    protected function updatePayments(PluginModel $plugin)
    {
        $installer = $this->container->get('shopware.plugin_payment_installer');

        $payments = [];
        foreach ($plugin->getPayments() as $payment) {
            $payments[$payment->getName()] = $payment;
        }

        $payments = [];
        foreach ($this->getSupportedPayments() as $payment) {
            if (isset($payments[$payment->getName()])) {
                continue;
            }
            $payments[] = $installer->createOrUpdate($plugin->getName(), $payment->getPaymentOptions());
        }
        if (! empty($payments)) {
            $this->translatePayments($payments);
        }
    }

    /**
     * Deactivate wirecard payments (on uninstall and plugin deactivation).
     *
     * @param PluginModel $plugin
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @since 1.0.0
     */
    private function deactivatePayments(PluginModel $plugin)
    {
        foreach ($plugin->getPayments() as $payment) {
            $payment->setActive(false);
        }

        $em = $this->container->get('models');
        $em->flush();
    }

    /**
     * Return wirecard payment instances.
     *
     * @return Components\Payments\PaymentInterface[]
     * @throws Exception\UnknownPaymentException
     *
     * @since 1.0.0
     */
    public function getSupportedPayments()
    {
        $paymentFactory = new PaymentFactory(
            $this->container->get('models'),
            $this->container->get('config'),
            $this->container->get('shopware_plugininstaller.plugin_manager'),
            $this->container->get('router'),
            $this->container->get('events')
        );
        return $paymentFactory->getSupportedPayments();
    }

    /**
     * Changes default values of specific config options
     */
    private function changeDefaultConfigValues()
    {
        $em = $this->container->get('models');
        $acceptedCurrencies = $em->getRepository(Element::class)->findOneBy([
            'name' => 'wirecardElasticEngineRatepayInvoiceAcceptedCurrencies'
        ]);
        if ($acceptedCurrencies) {
            $acceptedCurrencies->setValue([1]);
            $em->flush();
        }

        $shippingCountry = $em->getRepository(Element::class)->findOneBy([
            'name' => 'wirecardElasticEngineRatepayInvoiceShippingCountries'
        ]);
        if ($shippingCountry) {
            $shippingCountry->setValue([2,23,26]);
            $em->flush();
        }

        $billingCountry = $em->getRepository(Element::class)->findOneBy([
            'name' => 'wirecardElasticEngineRatepayInvoiceBillingCountries'
        ]);
        if ($billingCountry) {
            $billingCountry->setValue([2,23,26]);
            $em->flush();
        }
    }

    /**
     * Translate payment descriptions
     *
     * @param Payment[] $payments
     *
     * @throws \Exception
     */
    private function translatePayments($payments)
    {
        $iniFile  = dirname(__FILE__) . '/Resources/snippets/frontend/wirecard_elastic_engine/payments.ini';
        $snippets = parse_ini_file($iniFile, true);
        $db       = Shopware()->Db();

        try {
            $shops = $db->select()
                        ->from('s_core_shops', ['id', 'default'])
                        ->joinInner('s_core_locales', '`s_core_shops`.`locale_id`=`s_core_locales`.`id`', 'locale')
                        ->query()->fetchAll();

            foreach ($shops as $shop) {
                $locale = $shop['locale'];
                foreach ($payments as $payment) {
                    if (! isset($snippets[$locale][$payment->getDescription()])) {
                        continue;
                    }
                    $this->updatePaymentTranslation(
                        $shop['id'],
                        $payment->getId(),
                        $snippets[$locale][$payment->getDescription()],
                        $shop['default']
                    );
                }
            }
        } catch (\Exception $exception) {
            throw new \Exception('Payment translation failed: ' . $exception->getMessage());
        }
    }

    /**
     * Update translation of payment description
     *
     * @param int    $shopId
     * @param int    $paymentId
     * @param string $description
     * @param int    $defaultShop
     *
     * @throws \Zend_Db_Adapter_Exception
     */
    private function updatePaymentTranslation($shopId, $paymentId, $description, $defaultShop)
    {
        if ($defaultShop) {
            Shopware()->Db()->update('s_core_paymentmeans', ['description' => $description], 'id=' . $paymentId);
            return;
        }

        $translationObject = new \Shopware_Components_Translation();
        $translationObject->write($shopId, 'config_payment', $paymentId, ['description' => $description], true);
    }
}
