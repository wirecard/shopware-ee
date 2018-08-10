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
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Transaction\CreditCardTransaction;
use Wirecard\PaymentSdk\TransactionService;
use WirecardElasticEngine\Components\Actions\ErrorAction;
use WirecardElasticEngine\Components\Actions\ViewAction;
use WirecardElasticEngine\Components\Data\OrderSummary;
use WirecardElasticEngine\Components\Data\CreditCardPaymentConfig;
use WirecardElasticEngine\Components\Payments\Contracts\AdditionalViewAssignmentsInterface;
use WirecardElasticEngine\Components\Payments\Contracts\ProcessPaymentInterface;
use WirecardElasticEngine\Components\Payments\Contracts\ProcessReturnInterface;
use WirecardElasticEngine\Models\CreditCardVault;
use WirecardElasticEngine\Models\Transaction;

/**
 * @package WirecardElasticEngine\Components\Payments
 *
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
        if (! $this->transactionInstance) {
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
     * @param string       $selectedCurrency
     * @param float|string $limitValue
     * @param string       $limitCurrency
     *
     * @return Amount
     * @throws \Enlight_Event_Exception
     *
     * @since 1.0.0
     */
    private function getLimit($selectedCurrency, $limitValue, $limitCurrency)
    {
        $limit  = new Amount($limitValue, strtoupper($limitCurrency));
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
        if ($currency && ! $currency->getDefault()) {
            $selectedFactor = $currency->getFactor();
        }

        // Check if limit currency has been configured
        if ($limit->getCurrency() && $limit->getCurrency() !== 'NULL') {
            // Get factor of the limit currency (if it is the default currency, use factor 1.0)
            $limitCurrency = $repo->findOneBy(['currency' => $limit->getCurrency()]);
            if ($limitCurrency && ! $limitCurrency->getDefault()) {
                $limitFactor = $limitCurrency->getFactor();
            }
        }
        if (! $selectedFactor) {
            $selectedFactor = 1.0;
        }
        if (! $limitFactor) {
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

        if ($this->getPaymentConfig()->isVaultEnabled()) {
            $additionalPaymentData = $orderSummary->getAdditionalPaymentData();
            $tokenId = $additionalPaymentData['token'];

            if ($tokenId) {
                $user = $orderSummary->getUserMapper()->getShopwareUser();
                $userId = $user['additional']['user']['userID'];

                $conditions = [
                    'userId' => $userId,
                    'token'  => $tokenId,
                ];

                $billingAddress = $orderSummary->getUserMapper()->getBillingAddress();
                $shippingAddress = $orderSummary->getUserMapper()->getShippingAddress();
                $billingAddressHash = $this->createAddressHash($billingAddress);
                $shippingAddressHash = $this->createAddressHash($shippingAddress);

                if (!$this->getPaymentConfig()->allowAddressChanges()) {
                    $conditions['bindBillingAddressHash'] = $billingAddressHash;
                    $conditions['bindShippingAddressHash'] = $shippingAddressHash;
                }

                $creditCardVault = $this->em->getRepository(CreditCardVault::class)->findOneBy($conditions);

                if (!$creditCardVault) {
                    return new ErrorAction(
                        ErrorAction::PROCESSING_FAILED,
                        'no valid credit card for token found'
                    );
                }

                if (! $this->getPaymentConfig()->useThreeDOnTokens()) {
                    $transaction->setThreeD(false);
                }

                $creditCardVault->setLastUsed(new \DateTime());
                $this->em->flush();

                $transaction->setTokenId($tokenId);
                return;
            }
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
     * @return array
     */
    private function getAddressKeys()
    {
        return [
            "firstname",
            "lastname",
            "street",
            "zipcode",
            "city",
            "countryId",
            "stateId",
        ];
    }

    /**
     * @param array $oldAddress
     * @param array $newAddress
     *
     * @return bool
     */
    private function compareAddresses(array $oldAddress, array $newAddress)
    {
        $compareableKeys = $this->getAddressKeys();

        foreach ($compareableKeys as $key) {
            if ($oldAddress[$key] !== $newAddress[$key]) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array $address
     *
     * @return string
     */
    private function createAddressHash(array $address)
    {
        $compareableKeys = $this->getAddressKeys();

        $hashabelAddress = [];
        foreach ($compareableKeys as $key) {
            $hashabelAddress[$key] = $address[$key];
        }

        return md5(serialize($hashabelAddress));
    }

    /**
     * {@inheritdoc}
     */
    public function processReturn(
        TransactionService $transactionService,
        \Enlight_Controller_Request_Request $request
    ) {
        $params = $request->getParams();

        // FIXXXME use of Shopware()->Session()
        if ($this->getPaymentConfig()->isVaultEnabled()) {
            $additionalPaymentData = Shopware()->Session()->offsetGet('WirecardElasticEnginePaymentData');
            if ($additionalPaymentData['saveToken']) {
                $userId = Shopware()->Session()->offsetGet('sUserId');
                $tokenId = $params['token_id'];
                $maskedAccountNumber = $params['masked_account_number'];
                $billingAddress = Shopware()->Session()->sOrderVariables['sUserData']['billingaddress'];
                $shippingAddress = Shopware()->Session()->sOrderVariables['sUserData']['shippingaddress'];
                $billingAddressHash = $this->createAddressHash($billingAddress);
                $shippingAddressHash = $this->createAddressHash($shippingAddress);
                $firstName = $params['first_name'];
                $lastName = $params['last_name'];

                $transactionDetails = $transactionService->getTransactionByTransactionId(
                    $params['transaction_id'],
                    CreditCardTransaction::NAME
                );

                $additionalCardData = [
                        'firstName'       => $firstName,
                        'lastName'        => $lastName,
                        'expirationMonth' => $transactionDetails['payment']['card']['expiration-month'],
                        'expirationYear'  => $transactionDetails['payment']['card']['expiration-year'],
                        'cardType'        => $transactionDetails['payment']['card']['card-type'],
                ];

                $creditCardVault = $this->em->getRepository(CreditCardVault::class)->findOneBy([
                    'userId' => $userId,
                    'token'  => $tokenId,
                    'bindBillingAddressHash'  => $billingAddressHash,
                    'bindShippingAddressHash' => $shippingAddressHash,
                ]);

                if ($creditCardVault) {
                    $creditCardVault->setLastUsed(new \DateTime());
                } else {
                    $creditCardVault = new CreditCardVault();
                    $creditCardVault->setToken($tokenId);
                    $creditCardVault->setMaskedAccountNumber($maskedAccountNumber);
                    $creditCardVault->setUserId($userId);
                    $creditCardVault->setBindBillingAddress($billingAddress);
                    $creditCardVault->setBindBillingAddressHash($billingAddressHash);
                    $creditCardVault->setBindShippingAddress($shippingAddress);
                    $creditCardVault->setBindShippingAddressHash($shippingAddressHash);

                    $this->em->persist($creditCardVault);
                }
                $creditCardVault->setAdditionalData($additionalCardData);
                $this->em->flush();
            }
        }

        if (! empty($params['jsresponse'])) {
            return $transactionService->processJsResponse($request->getParams(), $this->router->assemble([
                'action' => 'return',
                'method' => CreditCardPayment::PAYMETHOD_IDENTIFIER,
            ]));
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getAdditionalViewAssignments()
    {
        $paymentConfig = $this->getPaymentConfig();

        // FIXXXME use of Shopware()->Session()
        $userInfo = Shopware()->Session()->offsetGet('userInfo');
        $accountMode = $userInfo['accountmode'];

        $formData = [
            'method'       => $this->getName(),
            'vaultEnabled' => intval($accountMode) === Customer::ACCOUNT_MODE_CUSTOMER
                              && $this->getPaymentConfig()->isVaultEnabled(),
        ];

        // FIXXXME use of Shopware()->Session()
        if ($this->getPaymentConfig()->isVaultEnabled()) {
            $userId = Shopware()->Session()->offsetGet('sUserId');
            $billingAddress = Shopware()->Session()->sOrderVariables['sUserData']['billingaddress'];
            $shippingAddress = Shopware()->Session()->sOrderVariables['sUserData']['shippingaddress'];
            $billingAddressHash = $this->createAddressHash($billingAddress);
            $shippingAddressHash = $this->createAddressHash($shippingAddress);

            $builder = $this->em->createQueryBuilder();
            $builder->select('ccv')
                ->from(CreditCardVault::class, 'ccv')
                ->where('ccv.userId = :userId')
                ->setParameter('userId', $userId)
                ->orderBy('ccv.lastUsed', 'DESC');
            $savedCards = $builder->getQuery()->getResult();

            foreach ($savedCards as $card) {
                $acceptedCriteria = $this->getPaymentConfig()->allowAddressChanges()
                                    || ( $billingAddressHash === $card->getBindBillingAddressHash()
                                         && $shippingAddressHash === $card->getBindShippingAddressHash() );

                $formData['savedCards'][] = [
                    'token'               => $card->getToken(),
                    'maskedAccountNumber' => $card->getMaskedAccountNumber(),
                    'additionalData'      => $card->getAdditionalData(),
                    'acceptedCriteria'    => $acceptedCriteria,
                ];
            }
        }

        return $formData;
    }
}
