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

namespace WirecardElasticEngine\Commands;

use Shopware\Commands\ShopwareCommand;
use Shopware\Models\Plugin\Plugin;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WirecardElasticEngine\WirecardElasticEngine;

/**
 * Enables all Wirecard payments methods.
 * For testing purposes all Wirecard related payments requires to be activated, hence this command is usually only
 * called in testing environments.
 *
 * @package WirecardElasticEngine\Commands
 * @since   1.0.0
 */
class PaymentActivate extends ShopwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('wirecardelasticengine:payment:activate')
            ->setDescription('Activate all Wirecard EE payment methods.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->container->get('models');

        /** @var Plugin $plugin */
        $plugin = $em->getRepository(Plugin::class)->findOneBy(['name' => WirecardElasticEngine::NAME]);
        if (! $plugin) {
            throw new RuntimeException("Plugin '" . WirecardElasticEngine::NAME . "' not found");
        }

        foreach ($plugin->getPayments() as $payment) {
            $action = 'already active';
            if (! $payment->getActive()) {
                $payment->setActive(true);
                $action = 'activated';
            }
            $name = $payment->getName() . ' (' . $payment->getDescription() . ')';
            $output->writeln("Payment method <info>$name</info> $action.");
        }
        $em->flush();
    }
}
