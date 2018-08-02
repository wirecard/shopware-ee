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

namespace WirecardElasticEngine\Tests\Unit\Components\Mail;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Shopware\Bundle\PluginInstallerBundle\Service\InstallerService;
use Shopware\Models\Plugin\Plugin;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use WirecardElasticEngine\Components\Mail\SupportMail;
use WirecardElasticEngine\Components\Services\PaymentFactory;

class SupportMailTest extends TestCase
{
    public function testSend()
    {
        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('addOrderBy')->willReturnSelf();
        $qb->method('getQuery')->willReturn($this->createMock(AbstractQuery::class));
        $repo = $this->createMock(EntityRepository::class);
        $repo->method('createQueryBuilder')->willReturn($qb);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);
        $installerService = $this->createMock(InstallerService::class);
        $installerService->method('getPluginByName')->willReturn($this->createMock(Plugin::class));
        $paymentFactory = $this->createMock(PaymentFactory::class);

        $mail = $this->createMock(\Enlight_Components_Mail::class);
        $mail->expects($this->once())->method('send')->willReturn($mail);

        $supportMail = new SupportMail(
            $mail,
            $em,
            $installerService,
            $paymentFactory
        );

        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $sentMail     = $supportMail->send($parameterBag, 'test@example.com', 'Foo message', 'reply@example.com');
        $this->assertSame($mail, $sentMail);
    }
}
