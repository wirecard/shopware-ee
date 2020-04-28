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
            new \DateTime();
        }
        try {
            list($current, $previous, $orderTime) = $this->getCurrentAndPreviousShippingAddress($addressId);
            $diff = array_diff_assoc($current, $previous);
            if($diff) {
                return new \DateTime();
            } else {
                return \DateTime::createFromFormat('Y-m-d H:i:s', $orderTime);
            }
        } catch (\Exception $exception) {
            return new \DateTime();
        }
    }

    /**
     * @param $addressId
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     * @since 1.3.4
     *
     * There are multiple reasons why this is so complicated:
     * - oldest shopware version which we support does not have a doctrine model for s_user_addresses
     * - shopware duplicates the address, but does not keep track of the address id used originally to create the
     *   address
     * - even if it did, the user is able to rewrite history by editing an address; so we have to compare all fields 1:1
     *   to decide if the address is really the same or was changed
     */
    private function getCurrentAndPreviousShippingAddress($addressId)
    {
        $stmt = $this->models->getConnection()->executeQuery(
            'SELECT IFNULL(s_user_addresses.company, "") as current_company,
                       IFNULL(s_user_addresses.department,"") as current_department,
                       IFNULL(s_user_addresses.salutation,"") as current_salutation,
                       IFNULL(s_user_addresses.firstname,"") as current_firstname,
                       IFNULL(s_user_addresses.lastname,"") as current_lastname,
                       IFNULL(s_user_addresses.street,"") as current_street,
                       IFNULL(s_user_addresses.zipcode,"") as current_zipcode,
                       IFNULL(s_user_addresses.country_id,"") as current_countryID,
                       IFNULL(s_user_addresses.phone,"") as current_phone,
                       IFNULL(s_user_addresses.additional_address_line1,"") as current_additional_address_line1,
                       IFNULL(s_user_addresses.additional_address_line2,"") as current_additional_address_line2,
                       s_order.id AS orderID, s_order.ordernumber,
                       s_order.ordertime, s_order.changed,
                       s_order_shippingaddress.id        AS shippedAddressId,
                       s_order_shippingaddress.orderID   AS shippedOrderId,
                       IFNULL(s_order_shippingaddress.company, "") AS prev_company,
                       IFNULL(s_order_shippingaddress.department, "") AS prev_department,
                       IFNULL(s_order_shippingaddress.salutation, "") AS prev_salutation,
                       IFNULL(s_order_shippingaddress.firstname, "") AS prev_firstname,
                       IFNULL(s_order_shippingaddress.lastname, "") AS prev_lastname,
                       IFNULL(s_order_shippingaddress.street, "") AS prev_street,
                       IFNULL(s_order_shippingaddress.zipcode, "") AS prev_zipcode,
                       IFNULL(s_order_shippingaddress.phone, "") AS prev_phone,
                       IFNULL(s_order_shippingaddress.countryID, "") AS prev_countryID,
                       IFNULL(s_order_shippingaddress.additional_address_line1, "") AS prev_additional_address_line1,
                       IFNULL(s_order_shippingaddress.additional_address_line2, "") AS prev_additional_address_line2
                FROM s_user_addresses
                         INNER JOIN s_order_shippingaddress ON s_order_shippingaddress.userID = s_user_addresses.user_id
                         INNER JOIN s_order ON s_order.id = s_order_shippingaddress.orderID
                WHERE s_user_addresses.id=?
                HAVING
                        current_company = prev_company AND
                        current_department = prev_department AND
                        current_salutation = prev_salutation AND
                        current_firstname = prev_firstname AND
                        current_lastname = prev_lastname AND
                        current_street = prev_street AND
                        current_zipcode = prev_zipcode AND
                        current_phone = prev_phone AND
                        current_countryID = prev_countryID AND
                        current_additional_address_line1 = prev_additional_address_line1 AND
                        current_additional_address_line2 = prev_additional_address_line2
                ORDER BY s_order_shippingaddress.id
                LIMIT 1',
            [(int)$addressId], [\PDO::PARAM_INT]
        );

        $stmt->execute();
        $all = $stmt->fetchAll();
        if(!$all) {
            return [null, null, null];
        }
        $current = [];
        $previous = [];
        $all = $all[0];
        foreach ($all as $name => $value) {
            if(0 === strpos($name, 'current_')) {
                $current[substr($name, strlen('current_'))] = $value ? $value : null;
            }
            elseif(0 === strpos($name, 'prev_')) {
                $previous[substr($name, strlen('prev_'))] = $value ? $value : null;
            }
        }
        $orderTime = $all['ordertime'];
        return [$current, $previous, $orderTime];
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
            return isset($basket[BasketMapper::ARTICLE_ID]) ? $basket[BasketMapper::ARTICLE_ID] : 0;
        }, $basket[BasketMapper::CONTENT]);

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
