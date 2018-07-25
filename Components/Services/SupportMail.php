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
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use WirecardShopwareElasticEngine\Components\Services\PaymentFactory;
use WirecardShopwareElasticEngine\WirecardShopwareElasticEngine;

class SupportMail
{
    private $em;
    private $mail;
    private $installerService;
    private $paymentFactory;

    const SUPPORT_ADDRESS = 'shop-systems-support@wirecard.com';

    public function __construct(
        EntityManagerInterface $em,
        \Enlight_Components_Mail $mail,
        InstallerService $installerService,
        PaymentFactory $paymentFactory
    ) {
        $this->em = $em;
        $this->mail = $mail;
        $this->installerService = $installerService;
        $this->paymentFactory = $paymentFactory;
    }

    /**
     * Sends Email to Wirecard Support
     *
     * @param ParameterBagInterface $parameterBag
     * @param string $senderAddress
     * @param string $content
     * @param string $replyTo
     * @return bool
     */
    public function sendSupportMail(ParameterBagInterface $parameterBag, $senderAddress, $message, $replyTo = null)
    {
        $serverInfo = $this->getServerInfo();
        $shopInfo = $this->getShopInfo($parameterBag);
        $pluginInfo = $this->getPluginInfo();
        $pluginList = $this->getPluginList();

        $message .= PHP_EOL . PHP_EOL . PHP_EOL;
        $message .= '***Server Info:***';
        $message .= $this->arrayToText($serverInfo);

        $message .= '***Server Info:***';
        $message .= $this->arrayToText($shopInfo);

        $message .= '***Plugin Info:***';
        $message .= $this->arrayToText($pluginInfo);

        $message .= '***Plugin List:***';
        $message .= $this->arrayToText($pluginList);

        $this->mail->setFrom($senderAddress);

        if ($replyTo) {
             $this->mail->setReplyTo($replyTo);
        } else {
             $this->mail->setReplyTo($senderAddress);
        }

        $this->mail->addTo(self::SUPPORT_ADDRESS);
        $this->mail->setSubject('Shopware support request');
        $this->mail->setBodyText($message);

        return $this->mail->send();
    }

    /**
     * Formats array to readable string
     *
     * @param array $array
     * return string
     */
    protected function arrayToText(array $array)
    {
        $result = PHP_EOL;
        foreach ($array as $key => $val) {
            if (is_bool($val)) {
                $result .= $key . ': ' . ($val ? 'true' : 'false') . PHP_EOL;
            } elseif (is_array($val)) {
                $result .= PHP_EOL . '[' . $key . ']';
                $result .= $this->arrayToText($val);
            } else {
                $result .= $key . ': ' . $val . PHP_EOL;
            }
        }
        $result .= PHP_EOL;

        return $result;
    }

    /**
     * @return array
     */
    protected function getServerInfo()
    {
        return [
            'os'     => php_uname(),
            'server' => $_SERVER['SERVER_SOFTWARE'],
            'php'    => phpversion()
        ];
    }

    /**
     * @param ParameterBagInterface $parameterBag
     * @return array
     */
    protected function getShopInfo(ParameterBagInterface $parameterBag)
    {
        return [
            'name'    => $parameterBag->get('kernel.name'),
            'version' =>$parameterBag->get('shopware.release.version')
        ];
    }

    /**
     * @return array
     */
    protected function getPluginInfo()
    {
        $plugin = $this->installerService->getPluginByName(WirecardShopwareElasticEngine::NAME);

        $payments = $this->paymentFactory->getSupportedPayments();

        $paymentConfigs = [];


        foreach ($payments as $payment) {
            $paymentModel = $this->em->getRepository(\Shopware\Models\Payment\Payment::class)
                          ->findOneBy(['name' => $payment->getName()]);
            if (! $paymentModel) {
                continue;
            }

            $paymentConfig = $payment->getPaymentConfig();

            $paymentConfigs[$payment->getName()] = array_merge(
                [ 'active' => $paymentModel->getActive() ],
                $paymentConfig->toArray()
            );
        }

        return [
            'name'    => WirecardShopwareElasticEngine::NAME,
            'version' => $plugin->getVersion(),
            'config'  => $paymentConfigs
        ];
    }

    /**
     * @return array
     */
    protected function getPluginList()
    {
        $repository = $this->em->getRepository(\Shopware\Models\Plugin\Plugin::class);
        $builder = $repository->createQueryBuilder('plugin');
        $builder->andWhere('plugin.capabilityEnable = true');
        $builder->addOrderBy('plugin.active', 'desc');
        $builder->addOrderBy('plugin.name');

        $plugins = $builder->getQuery()->execute();

        $rows = [];

        /** @var Plugin $plugin */
        foreach ($plugins as $plugin) {
            $rows[] = [
                'name'      => $plugin->getName(),
                'label'     =>$plugin->getLabel(),
                'version'   =>$plugin->getVersion(),
                'author'    =>$plugin->getAuthor(),
                'active'    =>$plugin->getActive() ? 'Yes' : 'No',
                'installed' =>$plugin->getInstalled() ? 'Yes' : 'No',
            ];
        }

        return $rows;
    }
}
