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
use Psr\Log\LoggerInterface;
use Shopware\Components\Routing\RouterInterface;

/**
 * Base class for handler implementations. Handlers are used to perform specific tasks, e.g. payment processing,
 * handling return actions, etc..
 *
 * @package WirecardElasticEngine\Components\Services
 *
 * @since   1.0.0
 */
abstract class Handler
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var \Shopware_Components_Config
     */
    protected $shopwareConfig;

    /**
     * @var TransactionManager
     */
    protected $transactionManager;

    /**
     * @param EntityManagerInterface      $em
     * @param RouterInterface             $router
     * @param LoggerInterface             $logger
     * @param \Shopware_Components_Config $config
     * @param TransactionManager          $transactionManager
     *
     * @since 1.0.0
     */
    public function __construct(
        EntityManagerInterface $em,
        RouterInterface $router,
        LoggerInterface $logger,
        \Shopware_Components_Config $config,
        TransactionManager $transactionManager
    ) {
        $this->em                 = $em;
        $this->router             = $router;
        $this->logger             = $logger;
        $this->shopwareConfig     = $config;
        $this->transactionManager = $transactionManager;
    }
}
