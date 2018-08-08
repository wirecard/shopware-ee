<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
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
