<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
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
