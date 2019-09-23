<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Components\Payments;

use Shopware\Models\Customer\Customer;
use Shopware\Models\Shop\Currency;
use Shopware\Models\Shop\Shop;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Wirecard\PaymentSdk\Config\CreditCardConfig;
use Wirecard\PaymentSdk\Constant\IsoTransactionType;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\CreditCardTransaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardElasticEngine\Components\Actions\ErrorAction;
use WirecardElasticEngine\Components\Actions\ViewAction;
use WirecardElasticEngine\Components\Data\OrderSummary;
use WirecardElasticEngine\Components\Data\CreditCardPaymentConfig;
use WirecardElasticEngine\Components\Mapper\UserMapper;
use WirecardElasticEngine\Components\Payments\Contracts\AdditionalViewAssignmentsInterface;
use WirecardElasticEngine\Components\Payments\Contracts\ProcessPaymentInterface;
use WirecardElasticEngine\Components\Payments\Contracts\ProcessReturnInterface;
use WirecardElasticEngine\Components\Services\SessionManager;
use WirecardElasticEngine\Models\CreditCardVault;
use WirecardElasticEngine\Models\Transaction;

/**
 * @package WirecardElasticEngine\Components\Payments
 *
 * @since   1.1.0 Implements AdditionalViewAssignmentsInterface; Support for One-Click Checkout
 * @since   1.0.0
 */
class CreditCardPayment extends Payment implements
    ProcessReturnInterface,
    ProcessPaymentInterface,
    AdditionalViewAssignmentsInterface
{
    const PAYMETHOD_IDENTIFIER = 'wirecard_elastic_engine_credit_card';

    /**
     * @var CreditCardTransaction
     */
    private $transactionInstance;

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'WirecardCreditCard';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::PAYMETHOD_IDENTIFIER;
    }

    /**
     * {@inheritdoc}
     */
    public function getPosition()
    {
        return 0;
    }

    /**
     * @return CreditCardTransaction
     *
     * @since 1.0.0
     */
    public function getTransaction()
    {
        if (!$this->transactionInstance) {
            $this->transactionInstance = new CreditCardTransaction();
        }

        return $this->transactionInstance;
    }

    /**
     * {@inheritdoc}
     */
    public function getTransactionConfig(Shop $shop, ParameterBagInterface $parameterBag, $selectedCurrency)
    {
        $transactionConfig = parent::getTransactionConfig($shop, $parameterBag, $selectedCurrency);
        $paymentConfig     = $this->getPaymentConfig();
        $creditCardConfig  = new CreditCardConfig();

        if ($paymentConfig->getTransactionMAID() && strtolower($paymentConfig->getTransactionMAID()) !== 'null') {
            $creditCardConfig->setSSLCredentials(
                $paymentConfig->getTransactionMAID(),
                $paymentConfig->getTransactionSecret()
            );
        }

        if ($paymentConfig->getThreeDMAID() && strtolower($paymentConfig->getThreeDMAID()) !== 'null') {
            $creditCardConfig->setThreeDCredentials(
                $paymentConfig->getThreeDMAID(),
                $paymentConfig->getThreeDSecret()
            );
        }

        if (strtolower($paymentConfig->getSslMaxLimit()) !== 'null') {
            $creditCardConfig->addSslMaxLimit(
                $this->getLimit(
                    $selectedCurrency,
                    $paymentConfig->getSslMaxLimit(),
                    $paymentConfig->getSslMaxLimitCurrency()
                )
            );
        }
        if (strtolower($paymentConfig->getThreeDMinLimit()) !== 'null') {
            $creditCardConfig->addThreeDMinLimit(
                $this->getLimit(
                    $selectedCurrency,
                    $paymentConfig->getThreeDMinLimit(),
                    $paymentConfig->getThreeDMinLimitCurrency()
                )
            );
        }

        $transactionConfig->add($creditCardConfig);
        $this->getTransaction()->setConfig($creditCardConfig);

        return $transactionConfig;
    }

    /**
     * @param string $selectedCurrency
     * @param float|string $limitValue
     * @param mixed $limitCurrencyId
     *
     * @return Amount
     * @throws \Enlight_Event_Exception
     *
     * @since 1.0.0
     */
    private function getLimit($selectedCurrency, $limitValue, $limitCurrencyId)
    {
        $repo          = $this->em->getRepository(Currency::class);
        $limitCurrency = $limitCurrencyId;
        if (is_numeric($limitCurrencyId)) {
            /** @var \Shopware\Models\Shop\Currency $limitCurrencyEntity */
            $limitCurrencyEntity = $repo->find($limitCurrencyId);
            if ($limitCurrencyEntity !== null) {
                $limitCurrency = $limitCurrencyEntity->getCurrency();
            }
        }

        $limit  = new Amount(floatval($limitValue), strtoupper($limitCurrency));
        $factor = $this->getCurrencyConversionFactor(strtoupper($selectedCurrency), $limit);

        $factor = Shopware()->Events()->filter(
            'WirecardElasticEngine_CreditCardPayment_getLimitCurrencyConversionFactor',
            $factor,
            [
                'subject' => $this,
                'limit'   => $limit,
            ]
        );

        return new Amount($limit->getValue() * $factor, $selectedCurrency);
    }

    /**
     * Return conversion factor from currently selected currency to limit currency of the plugin configuration.
     * If no limit currency has been set, the default currency of the shopware installation is used as fallback.
     *
     * @param string $selectedCurrency
     * @param Amount $limit
     *
     * @return float
     *
     * @since 1.0.0
     */
    private function getCurrencyConversionFactor($selectedCurrency, Amount $limit)
    {
        if ($limit->getCurrency() === $selectedCurrency) {
            return 1.0;
        }

        $selectedFactor = 1.0;
        $limitFactor    = 1.0;
        $repo           = $this->em->getRepository(Currency::class);
        $currency       = $repo->findOneBy(['currency' => $selectedCurrency]);

        // Get factor of the selected currency (if it is the default currency, use factor 1.0)
        if ($currency && !$currency->getDefault()) {
            $selectedFactor = $currency->getFactor();
        }

        // Check if limit currency has been configured
        if ($limit->getCurrency() && $limit->getCurrency() !== 'NULL') {
            // Get factor of the limit currency (if it is the default currency, use factor 1.0)
            $limitCurrency = $repo->findOneBy(['currency' => $limit->getCurrency()]);
            if ($limitCurrency && !$limitCurrency->getDefault()) {
                $limitFactor = $limitCurrency->getFactor();
            }
        }
        if (!$selectedFactor) {
            $selectedFactor = 1.0;
        }
        if (!$limitFactor) {
            $limitFactor = 1.0;
        }

        return $selectedFactor / $limitFactor;
    }

    /**
     * @return CreditCardPaymentConfig
     *
     * @since 1.0.0
     */
    public function getPaymentConfig()
    {
        $paymentConfig = new CreditCardPaymentConfig(
            $this->getPluginConfig('CreditCardServer'),
            $this->getPluginConfig('CreditCardHttpUser'),
            $this->getPluginConfig('CreditCardHttpPassword')
        );

        $paymentConfig->setTransactionMAID($this->getPluginConfig('CreditCardMerchantId'));
        $paymentConfig->setTransactionSecret($this->getPluginConfig('CreditCardSecret'));
        $paymentConfig->setTransactionOperation($this->getPluginConfig('CreditCardTransactionType'));

        $paymentConfig->setThreeDMAID($this->getPluginConfig('CreditCardThreeDMAID'));
        $paymentConfig->setThreeDSecret($this->getPluginConfig('CreditCardThreeDSecret'));
        $paymentConfig->setSslMaxLimit($this->getPluginConfig('CreditCardSslMaxLimit'));
        $paymentConfig->setSslMaxLimitCurrency($this->getPluginConfig('CreditCardSslMaxLimitCurrency'));
        $paymentConfig->setThreeDMinLimit($this->getPluginConfig('CreditCardThreeDMinLimit'));
        $paymentConfig->setThreeDMinLimitCurrency($this->getPluginConfig('CreditCardThreeDMinLimitCurrency'));

        $paymentConfig->setFraudPrevention($this->getPluginConfig('CreditCardFraudPrevention'));

        $paymentConfig->setVaultEnabled($this->getPluginConfig('CreditCardEnableVault'));
        $paymentConfig->setAllowAddressChanges($this->getPluginConfig('CreditCardAllowAddressChanges'));
        $paymentConfig->setThreeDUsageOnTokens($this->getPluginConfig('CreditCardThreeDUsageOnTokens'));

        return $paymentConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function processPayment(
        OrderSummary $orderSummary,
        TransactionService $transactionService,
        Shop $shop,
        Redirect $redirect,
        \Enlight_Controller_Request_Request $request,
        \sOrder $shopwareOrder
    ) {
        $transaction = $this->getTransaction();
        $transaction->setTermUrl($redirect->getSuccessUrl());

        // $tokenId is an empty string for a new token, non empty string for existing token,
        // null for disabled one-click checkout
        $tokenId = null;
        if ($this->getPaymentConfig()->isVaultEnabled()) {
            $paymentData = $orderSummary->getAdditionalPaymentData();
            $tokenId     = isset($paymentData['token']) ? $paymentData['token'] : null;
        }

        $accountInfoMapper = $orderSummary->getAccountInfoMapper();

        $shippingAccount = $orderSummary->getUserMapper()->getWirecardShippingAccountHolder();
        $accountHolder   = $orderSummary->getUserMapper()->getWirecardBillingAccountHolder();
        $accountInfo     = $accountInfoMapper->getAccountInfo($tokenId);
        $riskInfo        = $orderSummary->getRiskInfoMapper()->getRiskInfo();

        $accountHolder->setAccountInfo($accountInfo);

        $transaction->setAccountHolder($accountHolder);
        $transaction->setRiskInfo($riskInfo);
        $transaction->setShipping($shippingAccount);
        $transaction->setIsoTransactionType(IsoTransactionType::GOODS_SERVICE_PURCHASE);

        if (strlen($tokenId)) {
            return $this->useToken($transaction, $tokenId, $orderSummary);
        }

        $requestData = $transactionService->getCreditCardUiWithData(
            $transaction,
            $orderSummary->getPayment()->getTransactionType(),
            $shop->getLocale()->getLocale()
        );

        $transactionModel = new Transaction(Transaction::TYPE_INITIAL_REQUEST);
        $transactionModel->setPaymentUniqueId($orderSummary->getPaymentUniqueId());
        $transactionModel->setBasketSignature($orderSummary->getBasketMapper()->getSignature());
        $transactionModel->setRequest(json_decode($requestData, true));
        $this->em->persist($transactionModel);
        $this->em->flush();

        return new ViewAction('credit_card.tpl', [
            'wirecardUrl'         => $orderSummary->getPayment()->getPaymentConfig()->getBaseUrl(),
            'wirecardRequestData' => $requestData,
            'url'                 => $this->router->assemble([
                'action' => 'return',
                'method' => CreditCardPayment::PAYMETHOD_IDENTIFIER,
            ]),
        ]);
    }

    /**
     * @param CreditCardTransaction $transaction
     * @param string $tokenId
     * @param OrderSummary $orderSummary
     *
     * @return ErrorAction|null
     * @throws \WirecardElasticEngine\Exception\ArrayKeyNotFoundException
     *
     * @since 1.1.0
     */
    private function useToken(CreditCardTransaction $transaction, $tokenId, OrderSummary $orderSummary)
    {
        $userMapper = $orderSummary->getUserMapper();
        $conditions = [
            'id'     => $tokenId,
            'userId' => $userMapper->getUserId(),
        ];
        if (!$this->getPaymentConfig()->allowAddressChanges()) {
            $conditions['bindBillingAddressHash']  = $this->createAddressHash($userMapper->getBillingAddress());
            $conditions['bindShippingAddressHash'] = $this->createAddressHash($userMapper->getShippingAddress());
        }

        $creditCardVault = $this->em->getRepository(CreditCardVault::class)->findOneBy($conditions);
        if (!$creditCardVault) {
            return new ErrorAction(ErrorAction::PROCESSING_FAILED, 'no valid credit card for token found');
        }

        if (!$this->getPaymentConfig()->useThreeDOnTokens()) {
            $transaction->setThreeD(false);
        }

        $creditCardVault->setLastUsed(new \DateTime());
        $this->em->flush();

        $transaction->setTokenId($creditCardVault->getToken());

        return null;
    }

    /**
     * @return array
     *
     * @since 1.1.0
     */
    private function getComparableAddressKeys()
    {
        return [
            UserMapper::ADDRESS_FIRST_NAME,
            UserMapper::ADDRESS_LAST_NAME,
            UserMapper::ADDRESS_STREET,
            UserMapper::ADDRESS_ZIP,
            UserMapper::ADDRESS_CITY,
            UserMapper::ADDRESS_COUNTRY_ID,
            UserMapper::ADDRESS_STATE_ID,
        ];
    }

    /**
     * @param array $address
     *
     * @return string
     *
     * @since 1.1.0
     */
    private function createAddressHash(array $address)
    {
        $hashAddress = [];
        foreach ($this->getComparableAddressKeys() as $key) {
            if (isset($address[$key])) {
                $hashAddress[$key] = $address[$key];
            }
        }

        return md5(serialize($hashAddress));
    }

    /**
     * {@inheritdoc}
     */
    public function processReturn(
        TransactionService $transactionService,
        \Enlight_Controller_Request_Request $request,
        SessionManager $sessionManager
    ) {
        $params = $request->getParams();

        if ($this->getPaymentConfig()->isVaultEnabled()) {
            $this->saveToken($transactionService, $sessionManager, $params);
        }

        if (!empty($params['jsresponse'])) {
            return $transactionService->processJsResponse($request->getParams(), $this->router->assemble([
                'action' => 'return',
                'method' => CreditCardPayment::PAYMETHOD_IDENTIFIER,
            ]));
        }

        return null;
    }

    /**
     * @param TransactionService $transactionService
     * @param SessionManager $sessionManager
     * @param array $params
     *
     * @since 1.1.0
     */
    private function saveToken(TransactionService $transactionService, SessionManager $sessionManager, $params)
    {
        $paymentData = $sessionManager->getPaymentData();
        if (empty($paymentData['saveToken'])
            || !isset($params['token_id'])
            || !isset($params['transaction_id'])
            || !isset($params['masked_account_number'])
        ) {
            return;
        }

        $token               = $params['token_id'];
        $userId              = $sessionManager->getUserId();
        $billingAddress      = $sessionManager->getOrderBillingAddress();
        $shippingAddress     = $sessionManager->getOrderShippingAddress();
        $billingAddressHash  = $this->createAddressHash($billingAddress);
        $shippingAddressHash = $this->createAddressHash($shippingAddress);

        $transaction = $transactionService->getTransactionByTransactionId(
            $params['transaction_id'],
            CreditCardTransaction::NAME
        );
        $cardInfo    = isset($transaction['payment']['card']) ? $transaction['payment']['card'] : [];

        $creditCardVault = $this->em->getRepository(CreditCardVault::class)->findOneBy([
            'token'                   => $token,
            'userId'                  => $userId,
            'bindBillingAddressHash'  => $billingAddressHash,
            'bindShippingAddressHash' => $shippingAddressHash,
        ]);

        if (!$creditCardVault) {
            $creditCardVault = new CreditCardVault();
            $creditCardVault->setToken($token);
            $creditCardVault->setMaskedAccountNumber($params['masked_account_number']);
            $creditCardVault->setUserId($userId);
            $creditCardVault->setBindBillingAddress($billingAddress);
            $creditCardVault->setBindBillingAddressHash($billingAddressHash);
            $creditCardVault->setBindShippingAddress($shippingAddress);
            $creditCardVault->setBindShippingAddressHash($shippingAddressHash);
            $this->em->persist($creditCardVault);
        }
        $creditCardVault->setLastUsed(new \DateTime());
        $creditCardVault->setAdditionalData([
            'firstName'       => isset($params['first_name']) ? $params['first_name'] : '',
            'lastName'        => isset($params['last_name']) ? $params['last_name'] : '',
            'expirationMonth' => isset($cardInfo['expiration-month']) ? $cardInfo['expiration-month'] : '',
            'expirationYear'  => isset($cardInfo['expiration-year']) ? $cardInfo['expiration-year'] : '',
            'cardType'        => isset($cardInfo['card-type']) ? $cardInfo['card-type'] : '',
        ]);
        $this->em->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getAdditionalViewAssignments(SessionManager $sessionManager)
    {
        $paymentConfig = $this->getPaymentConfig();
        $userInfo      = $sessionManager->getUserInfo();
        $accountMode   = isset($userInfo['accountmode']) ? intval($userInfo['accountmode']) : 0;

        $formData = [
            'method'       => $this->getName(),
            'vaultEnabled' => $accountMode === Customer::ACCOUNT_MODE_CUSTOMER && $paymentConfig->isVaultEnabled(),
            'savedCards'   => [],
        ];
        if (!$paymentConfig->isVaultEnabled()) {
            return $formData;
        }

        $billingAddressHash  = $this->createAddressHash($sessionManager->getOrderBillingAddress());
        $shippingAddressHash = $this->createAddressHash($sessionManager->getOrderShippingAddress());

        $builder = $this->em->createQueryBuilder();
        $builder->select('ccv')
            ->from(CreditCardVault::class, 'ccv')
            ->where('ccv.userId = :userId')
            ->setParameter('userId', $sessionManager->getUserId())
            ->orderBy('ccv.lastUsed', 'DESC');
        $savedCards = $builder->getQuery()->getResult();

        /** @var CreditCardVault $card */
        foreach ($savedCards as $card) {
            $formData['savedCards'][] = [
                'token'               => $card->getId(),
                'maskedAccountNumber' => $card->getMaskedAccountNumber(),
                'additionalData'      => $card->getAdditionalData(),
                'acceptedCriteria'    => $paymentConfig->allowAddressChanges()
                                         || ($billingAddressHash === $card->getBindBillingAddressHash()
                                             && $shippingAddressHash === $card->getBindShippingAddressHash()),
            ];
        }

        return $formData;
    }
}
