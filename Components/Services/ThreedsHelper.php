<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Components\Services;

use DateInterval;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Model\QueryBuilder;
use Shopware\Models\Order\Detail;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;
use Shopware_Components_Config;
use WirecardElasticEngine\Components\Mapper\BasketMapper;
use WirecardElasticEngine\Models\CreditCardVault;
use WirecardElasticEngine\WirecardElasticEngine;

/**
 * @package WirecardElasticEngine\Components\Services
 *
 * @since   1.4.0
 */
class ThreedsHelper
{
    /**
     * @var ModelManager
     */
    protected $models;

    /**
     * @var Shopware_Components_Config
     */
    protected $shopwareConfig;

    public function __construct(ModelManager $models, Shopware_Components_Config $shopwareConfig)
    {
        $this->models         = $models;
        $this->shopwareConfig = $shopwareConfig;
    }

    /**
     * @param array $paymentData
     *
     * @return mixed|null
     */
    public function getTokenFromPaymentData($paymentData)
    {
        return is_array($paymentData) && isset($paymentData['token']) ? $paymentData['token'] : null;
    }

    /**
     * Check if a token is a new one
     *
     * @param $userId
     * @param array|null $paymentData
     *
     * @return bool
     * @throws NonUniqueResultException
     */
    public function isNewToken($userId, $paymentData)
    {
        if (!is_array($paymentData)) {
            return true;
        }

        $saveToken = false;
        if (isset($paymentData['saveToken'])) {
            $saveToken = $paymentData['saveToken'] === 'true';
        }

        $tokenId = $this->getTokenFromPaymentData($paymentData);

        if (!strlen($tokenId) && $saveToken) {
            return true;
        }

        return is_null($this->getCreditCardVault($userId, $tokenId));
    }

    /**
     * creation date of used card token
     *
     * @param $userId
     * @param $tokenId
     *
     * @return mixed|string
     * @throws Exception
     */
    public function getCardCreationDate($userId, $tokenId)
    {
        $vaultToken = $this->getCreditCardVault($userId, $tokenId);

        if (is_null($vaultToken)) {
            return new DateTime();
        }

        if (!$vaultToken->getCreated()) {
            return new DateTime();
        }

        return $vaultToken->getCreated();
    }

    /**
     * @param string $userId
     * @param string $tokenId
     *
     * @return CreditCardVault|null
     * @throws NonUniqueResultException
     */
    protected function getCreditCardVault($userId, $tokenId)
    {
        if (!strlen($tokenId)) {
            return null;
        }

        if (!strlen($userId)) {
            return null;
        }

        /** @var QueryBuilder $builder */
        $builder = $this->models->createQueryBuilder();
        $builder->select('v')
            ->from(CreditCardVault::class, 'v')
            ->where('v.id = :tokenId')
            ->andWhere('v.userId = :userId')
            ->setParameter('userId', $userId)
            ->setParameter('tokenId', $tokenId);

        /** @var CreditCardVault $vaultToken */
        return $builder->getQuery()->getOneOrNullResult();
    }

    /**
     * return datetime of first address usage
     *
     * @param $addressId
     *
     * @return DateTimeInterface|null
     * @throws Exception
     */
    public function getShippingAddressFirstUsed($addressId)
    {
        if (!strlen($addressId)) {
            new DateTime();
        }

        $builder = $this->models->createQueryBuilder();
        $builder->select('o')
            ->from(Order::class, 'o')
            ->innerJoin('o.shipping', 'sa')
            ->where('sa.id = :addressId')
            ->orderBy('o.id')
            ->setMaxResults(1)
            ->setParameter('addressId', $addressId);

        /** @var Order $order */
        $order = $builder->getQuery()->getOneOrNullResult();
        if (is_null($order)) {
            return new DateTime();
        }

        return $order->getOrderTime();
    }

    /**
     * retreive successful number of orders within the last 6 months
     *
     * @param $userId
     *
     * @return int
     * @throws NonUniqueResultException
     */
    public function getSuccessfulOrdersLastSixMonths($userId)
    {
        if (!$userId) {
            return 0;
        }

        $excludeStates = [
            Status::ORDER_STATE_OPEN,
            Status::ORDER_STATE_CLARIFICATION_REQUIRED,
            Status::ORDER_STATE_CANCELLED_REJECTED
        ];

        $now       = new DateTime();
        $dateStart = $now->sub(new DateInterval('P6M'))->format('Y-m-d H:i:s');

        $builder = $this->models->createQueryBuilder();
        $builder->select('count(o.id)')
            ->from(Order::class, 'o')
            ->where('o.customerId = :userId')
            ->andWhere('o.status IN (:states)')
            ->andWhere('o.orderTime > :datestart')
            ->setParameter('userId', $userId)
            ->setParameter('states', $excludeStates)
            ->setParameter('datestart', $dateStart);

        return (int)$builder->getQuery()->getSingleScalarResult();
    }

    /**
     * check if order has at least one reordered item
     *
     * @param $userId
     * @param array $basket
     *
     * @return bool
     * @throws NonUniqueResultException
     */
    public function hasReorderedItems($userId, array $basket)
    {
        if (!isset($basket[BasketMapper::CONTENT]) || !is_array($basket[BasketMapper::CONTENT])) {
            return false;
        }

        $articleIds = array_map(function ($basket) {
            return isset($basket['articleID']) ? $basket['articleID'] : 0;
        }, $basket['content']);

        $builder = $this->models->createQueryBuilder();
        $builder->select('count(det.id)')
            ->from(Detail::class, 'det')
            ->innerJoin('det.order', 'o')
            ->where('o.customerId = :userId')
            ->andWhere('det.articleId IN (:articleIds)')
            ->setParameter('userId', $userId)
            ->setParameter('articleIds', $articleIds);

        return (int)$builder->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * Get configured challenge indicator
     *
     * @return mixed|string
     * @throws Exception
     */
    public function getChallengeIndicator()
    {
        return sprintf('%02d', $this->shopwareConfig->getByNamespace(
            WirecardElasticEngine::NAME,
            'wirecardElasticEngineCreditCardChallengeIndicator'
        ));
    }
}
