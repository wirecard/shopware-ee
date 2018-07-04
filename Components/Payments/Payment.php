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

namespace WirecardShopwareElasticEngine\Components\Payments;

use Wirecard\PaymentSdk\BackendService;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Basket;
use Wirecard\PaymentSdk\Entity\CustomField;
use Wirecard\PaymentSdk\Entity\CustomFieldCollection;
use Wirecard\PaymentSdk\Entity\Item;
use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\Address;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Entity\Status;
use Wirecard\PaymentSdk\Response\InteractionResponse;
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\Response\SuccessResponse;
use Wirecard\PaymentSdk\Transaction\Operation;
use Wirecard\PaymentSdk\Transaction\Reservable;
use Wirecard\PaymentSdk\Transaction\Transaction as WirecardTransaction;
use Wirecard\PaymentSdk\TransactionService;

use WirecardShopwareElasticEngine\Models\OrderTransaction;
use WirecardShopwareElasticEngine\Models\Transaction;

abstract class Payment implements PaymentInterface
{
    const TRANSACTION_TYPE_AUTHORIZATION = 'authorization';
    const TRANSACTION_TYPE_PURCHASE = 'purchase';

    private $configData = [];
    private $config = null;
    private $orderNumber;

    /**
     * @inheritdoc
     */
    public function getLabel()
    {
        return 'Wirecard EE ' . preg_replace('/Payment$/', '', get_class($this));
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return str_replace(' ', '_', strtolower($this->getLabel()));
    }

    /**
     * @inheritdoc
     */
    public function getPaymentOptions()
    {
        return [
            'name'                  => $this->getName(),
            'description'           => $this->getLabel(),
            'action'                => 'WirecardElasticEnginePayment',
            'active'                => 0,
            'position'              => 0,
            'additionalDescription' => '',
        ];
    }

    /**
     * @inheritdoc
     */
    public function createTransaction(array $paymentData)
    {
        $configData = $this->getConfigData();
        $this->configData = $configData;

        $this->config = $this->getConfig($configData);

        $transaction = $this->getTransaction();

        $amount = new Amount($paymentData['amount'], $paymentData['currency']);

        $redirectUrls = new Redirect($paymentData['returnUrl'], $paymentData['cancelUrl']);
        $notificationUrl = $paymentData['notifyUrl'];

        $transaction->setNotificationUrl($notificationUrl);
        $transaction->setRedirect($redirectUrls);
        $transaction->setAmount($amount);

        $customFields = new CustomFieldCollection();
        $customFields->add(new CustomField('signature', $paymentData['signature']));
        $transaction->setCustomFields($customFields);

        if ($configData['sendBasket']) {
            $basket = $this->createBasket($transaction, $paymentData['basket'], $paymentData['currency']);
            $transaction->setBasket($basket);
        }

        if ($configData['fraudPrevention']) {
            $this->addConsumer($transaction, $paymentData['user']);
            $transaction->setIpAddress($paymentData['ipAddr']);

            $locale = Shopware()->Locale()->getLanguage();
            if (strpos($locale, '@') !== false) {
                $localeArr = explode('@', $locale);
                $locale = $localeArr[0];
            }
            $transaction->setLocale($locale);
        }

        $elasticEngineTransaction = $this->createElasticEngineTransaction($paymentData['signature']);
        $orderNumber              = $elasticEngineTransaction->getId();
        $transaction->setOrderNumber($orderNumber);

        if ($configData['descriptor']) {
            $descriptor = Shopware()->Config()->get('shopName') . ' ' . $orderNumber;
            $transaction->setDescriptor($descriptor);
        }

        $this->addPaymentSpecificData($transaction, $paymentData, $configData);

        return $transaction;
    }

    /**
     * @inheritdoc
     */
    public function processPayment(array $paymentData)
    {
        $transaction = $this->createTransaction($paymentData);

        $transactionService = new TransactionService($this->config, Shopware()->PluginLogger());

        $response = null;
        if ($this->configData['transactionType'] === self::TRANSACTION_TYPE_AUTHORIZATION
            && $transaction instanceof Reservable) {
            $response = $transactionService->reserve($transaction);
        } elseif ($this->configData['transactionType'] === self::TRANSACTION_TYPE_PURCHASE) {
            $response = $transactionService->pay($transaction);
        }

        if ($response instanceof InteractionResponse) {
            return [
                'status'   => 'success',
                'redirect' => $response->getRedirectUrl()
            ];
        }

        if ($response instanceof FailureResponse) {
            $errors = '';

            foreach ($response->getStatusCollection()->getIterator() as $item) {
                /** @var $item Status */
                $errors .= $item->getDescription() . "\n";
            }

            Shopware()->PluginLogger()->error($errors);
        }

        return ['status' => 'error'];
    }

    public function processJsResponse($params, $return)
    {
        $configData = $this->getConfigData();

        $config = $this->getConfig($configData);

        $transactionService = new TransactionService($config);

        return $transactionService->processJsResponse($params, $return);
    }

    /**
     * get payment settings
     *
     * @return array
     */
    public function getConfigData()
    {
        return [
            'baseUrl'         => '',
            'httpUser'        => '',
            'httpPass'        => '',
            'transactionMAID' => '',
            'transactionKey'  => '',
            'transactionType' => '',
            'sendBasket'      => false,
            'fraudPrevention' => false,
            'descriptor'      => ''
        ];
    }

    /**
     * @inheritdoc
     */
    abstract public function getConfig(array $configData);

    /**
     * @inheritdoc
     */
    abstract public function getTransaction();

    /**
     * Adds consumer personal information, billing and shipping address to Transaction
     *
     * @param WirecardTransaction $transaction
     * @param array $userData
     * @return WirecardTransaction
     */
    protected function addConsumer(WirecardTransaction $transaction, array $userData)
    {
        $user = $userData['additional']['user'];
        $transaction->setConsumerId($user['userID']);

        $firstName = $user['firstname'];
        $lastName = $user['lastname'];
        $email = $user['email'];

        $accountHolder = new AccountHolder();
        $accountHolder->setFirstName($firstName);
        $accountHolder->setLastName($lastName);
        $accountHolder->setEmail($email);

        if (isset($user['birthday']) && $user['birthday']) {
            $birthDate = new \DateTime($user['birthday']);
            $accountHolder->setDateOfBirth($birthDate);
        }

        $billingData = $userData['billingaddress'];

        if ($billingData['phone']) {
            $accountHolder->setPhone($billingData['phone']);
        }

        $country = $userData['additional']['country']['countryiso'];

        // SDK doesn't support state yet
        //
        // if (isset($userData['additional']['state']) &&
        //    isset($userData['additional']['state']['shortcode']) &&
        //    $userData['additional']['state']['shortcode']) {
        //    $country .= '-' .  $userData['additional']['state']['shortcode'];
        // }

        $city = $billingData['city'];
        $street = $billingData['street'];
        $zip = $billingData['zipcode'];

        $billingAddress = new Address($country, $city, $street);
        $billingAddress->setPostalCode($zip);
        if ($billingData['additionalAddressLine1']) {
            $billingAddress->setStreet2($billingData['additionalAddressLine1']);
        }

        $accountHolder->setAddress($billingAddress);

        $shippingData = $userData['shippingaddress'];

        $shippingUser = new AccountHolder();
        $shippingUser->setFirstName($shippingData['firstname']);
        $shippingUser->setLastName($shippingData['lastname']);
        $shippingUser->setPhone($shippingData['phone']);

        $shippingCountry = $userData['additional']['countryShipping']['countryiso'];
        $shippingCity = $shippingData['city'];
        $shippingStreet = $shippingData['street'];
        $shippingZip = $shippingData['zipcode'];

        // SDK doesn't support state yet
        //
        //if (isset($userData['additional']['stateShipping']) &&
        //    isset($userData['additional']['stateShipping']['shortcode']) &&
        //    $userData['additional']['stateShipping']['shortcode']) {
        //    $shippingCountry .= '-' . $userData['additional']['stateShipping']['shortcode'];
        //}

        $shippingAddress = new Address($shippingCountry, $shippingCity, $shippingStreet);
        $shippingAddress->setPostalCode($shippingZip);

        if ($shippingData['additionalAddressLine1']) {
            $shippingAddress->setStreet2($shippingData['additionalAddressLine1']);
        }

        $shippingUser->setAddress($shippingAddress);

        $transaction->setAccountHolder($accountHolder);
        $transaction->setShipping($shippingUser);

        return $transaction;
    }

    /**
     * creates paymentSDK basket object
     *
     * @param WirecardTransaction $transaction
     * @param array $cart
     * @param string $currency
     * @return Basket
     */
    protected function createBasket(WirecardTransaction $transaction, array $cart, $currency)
    {
        $basket = new Basket();
        $basket->setVersion($transaction);

        if (isset($cart['content'])) {
            foreach ($cart['content'] as $item) {
                $name        = $item['articlename'];
                $sku         = $item['ordernumber'];
                $description = $item['additional_details']['description'];
                $taxRate     = floatval($item['tax_rate']);
                $quantity    = $item['quantity'];

                if (isset($item['additional_details'])) {
                    if (isset($item['additional_details']['prices'])
                        && count($item['additional_details']['prices']) === 1) {
                        $price = $item['additional_details']['prices'][0]['price_numeric'];
                    } else {
                        $price = $item['additional_details']['price_numeric'];
                    }
                } else {
                    $amountStr = $item['price'];
                    $price     = floatval(str_replace(',', '.', $amountStr));
                }
                $amount = new Amount($price, $currency);

                $taxStr = $item['tax'];
                $taxStr = str_replace(',', '.', $taxStr);
                $tax    = new Amount(floatval($taxStr) / $quantity, $currency);

                $basketItem = new Item($name, $amount, $quantity);

                $basketItem->setDescription($description);
                $basketItem->setArticleNumber($sku);
                $basketItem->setTaxRate($taxRate);
                $basketItem->setTaxAmount($tax);

                $basket->add($basketItem);
            }
        }

        if (isset($cart["sShippingcostsWithTax"]) && $cart["sShippingcostsWithTax"]) {
            $shippingAmount = new Amount($cart["sShippingcostsWithTax"], $currency);
            $basketItem = new Item('Shipping', $shippingAmount, 1);

            $basketItem->setDescription('Shipping');
            $basketItem->setArticleNumber('shipping');

            $shippingTaxValue = $cart["sShippingcostsWithTax"] - $cart['sShippingcostsNet'];
            $shippingTax = new Amount($shippingTaxValue, $currency);
            $basketItem->setTaxAmount($shippingTax);
            $basketItem->setTaxRate($cart["sShippingcostsTax"]);
            $basket->add($basketItem);
        }

        return $basket;
    }

    /**
     * creates text representing basket
     *
     * @param array $cart
     * @param string $currency
     * @return string
     */
    protected function createBasketText(array $cart, $currency)
    {
        $basketString = '';

        foreach ($cart['content'] as $item) {
            $name = $item['articlename'];
            $sku = $item['ordernumber'];
            $taxRate = floatval($item['tax_rate']);
            $quantity = $item['quantity'];

            if (isset($item['additional_details'])) {
                if (isset($item['additional_details']['prices'])
                    && count($item['additional_details']['prices']) === 1) {
                    $price = $item['additional_details']['prices'][0]['price_numeric'];
                } else {
                    $price = $item['additional_details']['price_numeric'];
                }
            } else {
                $amountStr = $item['price'];
                $price = floatval(str_replace(',', '.', $amountStr));
            }

            $itemLine = $name . ' - ' . $sku . ' - ' . $price . ' ' . $currency . ' - ' .
                      $quantity . ' - ' . $taxRate . '%';
            $basketString .= $itemLine . "\n";
        }

        if (isset($cart["sShippingcostsWithTax"]) && $cart["sShippingcostsWithTax"]) {
            $shippingLine = 'Shipping - shipping - ' . $cart["sShippingcostsWithTax"] . ' ' . $currency . ' - '
                            . $cart["sShippingcostsTax"] . '%';
            $basketString .= $shippingLine;
        }

        return $basketString;
    }

    /**
     * @inheritdoc
     */
    public function createElasticEngineTransaction($basketSignature = null)
    {
        $transactionModel = new Transaction();
        if ($basketSignature) {
            $transactionModel->setBasketSignature($basketSignature);
        }
        Shopware()->Models()->persist($transactionModel);
        Shopware()->Models()->flush();

        $this->orderNumber = $transactionModel->getId();

        return $transactionModel;
    }

    /**
     * adds request id to transaction model
     *
     * @params string $requestId
     * @return boolean
     */
    public function addTransactionRequestId($requestId)
    {
        if (!$this->orderNumber) {
            return false;
        }

        $transactionModel = Shopware()->Models()
            ->getRepository(Transaction::class)
            ->findOneBy(['id' => $this->orderNumber]);

        if (!$transactionModel) {
            return false;
        }
        $transactionModel->setRequestId($requestId);
        Shopware()->Models()->persist($transactionModel);
        Shopware()->Models()->flush();

        return true;
    }

    /**
     * Extra Options for payments are added here
     *
     * @param WirecardTransaction $transaction
     * @param array               $paymentData
     * @param array               $configData
     * @return WirecardTransaction
     */
    protected function addPaymentSpecificData(WirecardTransaction $transaction, array $paymentData, array $configData)
    {
        return $transaction;
    }

    /**
     * @inheritdoc
     */
    public function getPaymentResponse(array $request)
    {
        $configData = $this->getConfigData();
        $config = $this->getConfig($configData);
        $service = new TransactionService($config);
        $response = $service->handleResponse($request);

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function getPaymentNotification($request)
    {
        $configData = $this->getConfigData();
        $config = $this->getConfig($configData);
        $service = new TransactionService($config);
        $notification = $service->handleNotification($request);

        return $notification;
    }

    /**
     * @inheritdoc
     */
    public function getBackendOperations($transactionId)
    {
        $configData = $this->getConfigData();
        $config = $this->getConfig($configData);

        $transaction = $this->getTransaction();
        $transaction->setParentTransactionId($transactionId);
        $service = new BackendService($config, Shopware()->PluginLogger());

        return $service->retrieveBackendOperations($transaction, true);
    }

    /**
     * @inheritdoc
     */
    public function processBackendOperationsForOrder($orderNumber, $operation, $amount = 0, $currency = '')
    {
        if ($amount && !$currency) {
            return [ 'success' => false, 'msg' => 'AmountWithoutCurrency'];
        }

        if ($operation === 'Refund') {
            return $this->refundForOrder($orderNumber, $amount, $currency);
        }

        if ($operation === 'Capture') {
            return $this->captureForOrder($orderNumber, $amount, $currency);
        }

        if ($operation === 'Cancel') {
            return $this->cancelOrder($orderNumber);
        }

        return [ 'success' => false, 'msg' => 'InvalidOperation'];
    }

    /**
     * @param string $orderNumber
     * @param float $amount
     * @param string $currency
     * @return array
     */
    protected function refundForOrder($orderNumber, $amount = 0, $currency = '')
    {
        $elasticEngineTransaction = Shopware()->Models()->getRepository(Transaction::class)
                                  ->findOneBy(['orderNumber' => $orderNumber]);

        $parentTransactionId = $elasticEngineTransaction->getTransactionId();
        if (!$elasticEngineTransaction) {
            return [ 'success' => false, 'msg' => 'NoTransactionFound' ];
        }

        $configData = $this->getConfigData();
        $config = $this->getConfig($configData);

        $transaction = $this->getTransaction();
        $transaction->setParentTransactionId($parentTransactionId);
        $notificationUrl = Shopware()->Front()->Router()->assemble([
            'module' => 'frontend',
            'controller' => 'WirecardElasticEnginePayment',
            'action' => 'notifyBackend',
            'method' => $this->getName(),
            'forceSecure' => true
        ]);
        $transaction->setNotificationUrl($notificationUrl);

        if ($amount) {
            $amountObj = new Amount($amount, $currency);
            $transaction->setAmount($amountObj);
        }

        $transactionService = new TransactionService($config, Shopware()->PluginLogger());

        try {
            $response = $transactionService->process($transaction, Operation::CANCEL);
        } catch (\Exception $exception) {
            Shopware()->PluginLogger()->error('Processing refund failed: '  .
                                              get_class($exception) . ' ' .
                                              $exception->getMessage());
            return [ 'success' => false, 'msg' => 'RefundFailed'];
        }

        if ($response instanceof SuccessResponse) {
            Shopware()->PluginLogger()->info($response->getData());
            $transactionId = $response->getTransactionId();
            $providerTransactionId = $response->getProviderTransactionId() ? $response->getProviderTransactionId() : '';

            $orderTransaction = Shopware()->Models()->getRepository(OrderTransaction::class)
                ->findOneBy(['transactionId' => $parentTransactionId, 'parentTransactionId' => $transactionId]);

            if (!$orderTransaction) {
                $orderTransaction = new OrderTransaction();
                $orderTransaction->setOrderNumber($orderNumber);
                $orderTransaction->setParentTransactionId($parentTransactionId);
                $orderTransaction->setTransactionId($transactionId);
                $orderTransaction->setProviderTransactionId($providerTransactionId);
                $orderTransaction->setCreatedAt(new \DateTime('now'));
                $orderTransaction->setTransactionType('pending');
            }

            $orderTransaction->setReturnResponse(serialize($response->getData()));

            Shopware()->Models()->persist($orderTransaction);
            Shopware()->Models()->flush();

            return [ 'success' => true, 'transactionId' => $response->getTransactionId() ];
        }
        if ($response instanceof FailureResponse) {
            $orderTransaction = Shopware()->Models()->getRepository(OrderTransaction::class)
                ->findOneBy(['transactionId' => $parentTransactionId, 'parentTransactionId' => $transactionId]);
            $rawData = $response->getData();
            if (!$orderTransaction) {
                $orderTransaction = new OrderTransaction();
                $orderTransaction->setOrderNumber($orderNumber);
                $orderTransaction->setParentTransactionId($parentTransactionId);
                $orderTransaction->setTransactionId($rawData['transaction-id']);
                $orderTransaction->setCreatedAt(new \DateTime('now'));
                $orderTransaction->setTransactionType('failed');
            }

            $orderTransaction->setReturnResponse(serialize($response->getData()));

            Shopware()->Models()->persist($orderTransaction);
            Shopware()->Models()->flush();
            return [ 'success' => false, 'msg' => 'RefundFailed'];
        }

        return [ 'success' => false, 'msg' => 'RefundFailed'];
    }

    /**
     * @param string $orderNumber
     * @param float $amount
     * @param string $currency
     * @return array
     */
    protected function captureForOrder($orderNumber, $amount = 0, $currency = '')
    {
        $elasticEngineTransaction = Shopware()->Models()->getRepository(Transaction::class)
                                  ->findOneBy(['orderNumber' => $orderNumber]);

        $parentTransactionId = $elasticEngineTransaction->getTransactionId();
        if (!$elasticEngineTransaction) {
            return [ 'success' => false, 'msg' => 'NoTransactionFound' ];
        }

        $configData = $this->getConfigData();
        $config = $this->getConfig($configData);

        $transaction = $this->getTransaction();
        $transaction->setParentTransactionId($parentTransactionId);
        $notificationUrl = Shopware()->Front()->Router()->assemble([
            'module' => 'frontend',
            'controller' => 'WirecardElasticEnginePayment',
            'action' => 'notifyBackend',
            'method' => $this->getName(),
            'forceSecure' => true
        ]);

        $transaction->setNotificationUrl($notificationUrl);

        if ($amount) {
            $amountObj = new Amount($amount, $currency);
            $transaction->setAmount($amountObj);
        }

        $transactionService = new BackendService($config, Shopware()->PluginLogger());

        try {
            $response = $transactionService->process($transaction, Operation::PAY);
        } catch (\Exception $exception) {
            Shopware()->PluginLogger()->error('Processing capture failed:' . $exception->getMessage());
            return [ 'success' => false, 'msg' => 'CaptureFailed'];
        }

        if ($response instanceof SuccessResponse) {
            Shopware()->PluginLogger()->info($response->getData());
            $transactionId = $response->getTransactionId();
            $providerTransactionId = $response->getProviderTransactionId() ? $response->getProviderTransactionId() : '';

            $orderTransaction = Shopware()->Models()->getRepository(OrderTransaction::class)
                ->findOneBy(['transactionId' => $parentTransactionId, 'parentTransactionId' => $transactionId]);

            if (!$orderTransaction) {
                $orderTransaction = new OrderTransaction();
                $orderTransaction->setOrderNumber($orderNumber);
                $orderTransaction->setParentTransactionId($parentTransactionId);
                $orderTransaction->setTransactionId($transactionId);
                $orderTransaction->setProviderTransactionId($providerTransactionId);
                $orderTransaction->setCreatedAt(new \DateTime('now'));
                $orderTransaction->setTransactionType('pending');
            }

            $orderTransaction->setReturnResponse(serialize($response->getData()));

            Shopware()->Models()->persist($orderTransaction);
            Shopware()->Models()->flush();

            return [ 'success' => true, 'transactionId' => $response->getTransactionId() ];
        }
        if ($response instanceof FailureResponse) {
            return [ 'success' => false, 'msg' => 'CaptureFailed'];
        }

        return [ 'success' => false, 'msg' => 'CaptureFailed'];
    }

    /**
     * @param string $orderNumber
     * @return array
     */
    protected function cancelOrder($orderNumber)
    {
        $elasticEngineTransaction = Shopware()->Models()->getRepository(Transaction::class)
                                  ->findOneBy(['orderNumber' => $orderNumber]);

        $parentTransactionId = $elasticEngineTransaction->getTransactionId();
        if (!$elasticEngineTransaction) {
            return [ 'success' => false, 'msg' => 'NoTransactionFound' ];
        }

        $configData = $this->getConfigData();
        $config = $this->getConfig($configData);

        $transaction = $this->getTransaction();
        $transaction->setParentTransactionId($parentTransactionId);
        $notificationUrl = Shopware()->Front()->Router()->assemble([
            'module' => 'frontend',
            'controller' => 'WirecardElasticEnginePayment',
            'action' => 'notifyBackend',
            'method' => $this->getName(),
            'forceSecure' => true
        ]);

        $transaction->setNotificationUrl($notificationUrl);

        $transactionService = new BackendService($config, Shopware()->PluginLogger());

        try {
            $response = $transactionService->process($transaction, Operation::CANCEL);
        } catch (\Exception $exception) {
            Shopware()->PluginLogger()->error('Processing cancel failed:' . $exception->getMessage());
            return [ 'success' => false, 'msg' => 'CancelFailed'];
        }

        if ($response instanceof SuccessResponse) {
            Shopware()->PluginLogger()->info($response->getData());
            $transactionId = $response->getTransactionId();
            $providerTransactionId = $response->getProviderTransactionId() ? $response->getProviderTransactionId() : '';

            $orderTransaction = Shopware()->Models()->getRepository(OrderTransaction::class)
                ->findOneBy(['transactionId' => $parentTransactionId, 'parentTransactionId' => $transactionId]);

            if (!$orderTransaction) {
                $orderTransaction = new OrderTransaction();
                $orderTransaction->setOrderNumber($orderNumber);
                $orderTransaction->setParentTransactionId($parentTransactionId);
                $orderTransaction->setTransactionId($transactionId);
                $orderTransaction->setProviderTransactionId($providerTransactionId);
                $orderTransaction->setCreatedAt(new \DateTime('now'));
                $orderTransaction->setTransactionType('pending');
            }

            $orderTransaction->setReturnResponse(serialize($response->getData()));

            Shopware()->Models()->persist($orderTransaction);
            Shopware()->Models()->flush();

            return [ 'success' => true, 'transactionId' => $response->getTransactionId() ];
        }
        if ($response instanceof FailureResponse) {
            return [ 'success' => false, 'msg' => 'CancelFailed'];
        }

        return [ 'success' => false, 'msg' => 'CancelFailed'];
    }
}
