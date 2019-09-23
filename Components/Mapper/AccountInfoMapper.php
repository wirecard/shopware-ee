<?php
/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

namespace WirecardElasticEngine\Components\Mapper;

use DateTime;
use Exception;
use Shopware\Models\Customer\Customer;
use Wirecard\PaymentSdk\Constant\AuthMethod;
use Wirecard\PaymentSdk\Constant\ChallengeInd;
use Wirecard\PaymentSdk\Entity\AccountInfo;
use WirecardElasticEngine\Components\Services\SessionManager;

/**
 * @since 1.4.0
 */
class AccountInfoMapper extends ArrayMapper
{
    /**
     * @var int|null
     */
    protected $customerId;

    /**
     * the configured challenge indicator
     *
     * @var string
     */
    protected $challengeIndicator;

    /**
     * @var SessionManager
     */
    protected $sessionManager;

    /**
     * @var DateTime|null
     */
    protected $shippingFirstUsed;

    /**
     * @var DateTime|null
     */
    protected $cardCreationDate;

    /**
     * @var int
     */
    protected $numPurchasesSixMonth;

    /**
     * @var bool
     */
    protected $isNewToken;

    public function __construct(
        SessionManager $sessionManager,
        array $shopwareUser,
        $challengeIndicator,
        $isNewToken,
        $shippingFirstUsed,
        $cardCreationDate,
        $numPurchasesSixMonth
    ) {
        $this->sessionManager       = $sessionManager;
        $this->arrayEntity          = $shopwareUser;
        $this->challengeIndicator   = $challengeIndicator;
        $this->isNewToken           = $isNewToken;
        $this->shippingFirstUsed    = $shippingFirstUsed;
        $this->cardCreationDate     = $cardCreationDate;
        $this->numPurchasesSixMonth = $numPurchasesSixMonth;
    }

    /**
     * needed for testing only
     *
     * @param $isNew
     */
    public function setIsNewToken($isNew)
    {
        $this->isNewToken = $isNew;
    }

    /**
     * @param $tokenId
     *
     * @return AccountInfo
     * @throws Exception
     */
    public function getAccountInfo($tokenId)
    {
        $accountInfo = new AccountInfo();
        $authMethod  = $this->isCustomer() ? AuthMethod::USER_CHECKOUT : AuthMethod::GUEST_CHECKOUT;
        $accountInfo->setAuthMethod($authMethod);
        $accountInfo->setAuthTimestamp($this->getLastLogin());
        $accountInfo->setChallengeInd($this->getChallengeIndicator($tokenId));
        $this->addAuthenticatedUserData($accountInfo);

        return $accountInfo;
    }

    /**
     * whether user is a registered customer
     * @return bool
     */
    protected function isCustomer()
    {
        return $this->sessionManager->getUserId() && $this->getAccountMode() === Customer::ACCOUNT_MODE_CUSTOMER;
    }

    /**
     * Get challenge indicator depending on existing token
     * - return config setting: for non one-click-checkout, guest checkout, existing token
     * - return 04/CHALLENGE_MANDATE: for new one-click-checkout token
     *
     * @param string|null $tokenId
     *
     * @return string
     * @see WirecardEE_PaymentGateway_Helper_Threeds::isNewToken()
     */
    protected function getChallengeIndicator($tokenId)
    {
        // non one-click-checkout, is_null check is important, do not check with empty() or strlen()
        if (is_null($tokenId)) {
            return $this->challengeIndicator;
        }

        // guest
        if (!$this->isCustomer()) {
            return $this->challengeIndicator;
        }

        // new token
        if ($this->isNewToken) {
            return ChallengeInd::CHALLENGE_MANDATE;
        }

        // existing token
        return $this->challengeIndicator;
    }

    /**
     * @param AccountInfo $accountInfo
     *
     * @return $this
     * @throws Exception
     */
    protected function addAuthenticatedUserData(AccountInfo $accountInfo)
    {
        if ($this->isCustomer()) {
            $accountInfo->setCreationDate($this->getFirstLogin());
            $accountInfo->setUpdateDate($this->getChanged());
            $accountInfo->setPassChangeDate($this->getPasswordChangeDate());
            $accountInfo->setShippingAddressFirstUse($this->shippingFirstUsed);
            $accountInfo->setCardCreationDate($this->cardCreationDate === null ?
                new DateTime() : $this->cardCreationDate);
            $accountInfo->setAmountPurchasesLastSixMonths($this->numPurchasesSixMonth);
        }

        return $this;
    }

    public function getFirstLogin()
    {
        $dateStr = $this->getOptional([UserMapper::ADDITIONAL, UserMapper::ADDITIONAL_USER, 'firstlogin']);

        return new DateTime($dateStr);
    }

    public function getLastLogin()
    {
        $dateStr = $this->getOptional([UserMapper::ADDITIONAL, UserMapper::ADDITIONAL_USER, 'lastlogin']);

        return new DateTime($dateStr);
    }

    public function getChanged()
    {
        $dateStr = $this->getOptional([UserMapper::ADDITIONAL, UserMapper::ADDITIONAL_USER, 'changed']);

        return new DateTime($dateStr);
    }

    public function getAccountMode()
    {
        return $this->get([UserMapper::ADDITIONAL, UserMapper::ADDITIONAL_USER, 'accountmode']);
    }

    public function getPasswordChangeDate()
    {
        $dateStr = $this->getOptional([UserMapper::ADDITIONAL, UserMapper::ADDITIONAL_USER, 'password_change_date']);

        return is_null($dateStr) ? $dateStr : new DateTime($dateStr);
    }
}
