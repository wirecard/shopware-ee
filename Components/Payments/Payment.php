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

use Wirecard\PaymentSdk\Config\Config;
use Wirecard\PaymentSdk\Config\PaymentMethodConfig;
use Wirecard\PaymentSdk\Entity\Amount;
use Wirecard\PaymentSdk\Entity\Basket;
use Wirecard\PaymentSdk\Entity\CustomField;
use Wirecard\PaymentSdk\Entity\CustomFieldCollection;
use Wirecard\PaymentSdk\Entity\Item;
use Wirecard\PaymentSdk\Entity\AccountHolder;
use Wirecard\PaymentSdk\Entity\Address;
use Wirecard\PaymentSdk\Entity\Redirect;
use Wirecard\PaymentSdk\Response\InteractionResponse;
use Wirecard\PaymentSdk\Response\FailureResponse;
use Wirecard\PaymentSdk\TransactionService;

abstract class Payment implements PaymentInterface
{
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
            'action'                => 'WirecardEEPayment',
            'active'                => 0,
            'position'              => 0,
            'additionalDescription' => '',
        ];
    }

    /**
     * @inheritdoc
     */
    public function processPayment(array $paymentData)
    {
        $configData = $this->getConfigData();

        $config = $this->getConfig($configData);

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
            $consumer = $this->addConsumer($transaction, $paymentData['user']);
            $transaction->setIpAddress($paymentData['ipAddr']);

            $locale = \Locale::getDefault();
            if (strpos($locale, '@') !== false) {
                $localeArr = explode('@', $locale);
                $locale = $localeArr[0];
            }
            $transaction->setLocale($locale);
        }

        $transactionService = new TransactionService($config, Shopware()->PluginLogger());
        
        $response = null;
        if ($configData['transactionType'] == 'authorization') {
            $response = $transactionService->reserve($transaction);
        } elseif ($configData['transactionType'] == 'purchase') {
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
                $errors .= $item->getDescription() . "\n";
            }

            Shopware()->PluginLogger()->error($errors);
        }

        return ['status' => 'error'];
    }

    /**
     * get payment settings
     * @return array
     */
    protected function getConfigData()
    {
        return [];
    }

    /**
     * Creates PaymentSDK config object
     * @param array $configData
     * @return Config
     */
    protected function getConfig(array $configData)
    {
        return null;
    }

    /**
     * Creates payment spectific transaction
     * @return Transaction
     */
    protected function getTransaction()
    {
        return null;
    }

    /**
     * Adds consumer personal information, billing and shipping address to Transaction
     *
     * @param Transaction $transaction
     * @param array $userData
     */
    protected function addConsumer($transaction, $userData)
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
            $birthdate = new \DateTime($user['birthday']);
            $accountHolder->setDateOfBirth($birthdate);
        }

        $billingData = $userData['billingaddress'];

        if ($billingData['phone']) {
            $accountHolder->setPhone($billingData['phone']);
        }
        
        $country = $userData['additional']['country']['countryiso'];

        if (isset($userData['additional']['state']) &&
            isset($userData['additional']['state']['shortcode']) &&
            $userData['additional']['state']['shortcode']) {
            // $country .= '-' .  $userData['additional']['state']['shortcode'];
        }
        
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

        if (isset($userData['additional']['stateShipping']) &&
            isset($userData['additional']['stateShipping']['shortcode']) &&
            $userData['additional']['stateShipping']['shortcode']) {
            // $shippingCountry .= '-' . $userData['additional']['stateShipping']['shortcode'];
        }

        $shippingAddress = new Address($shippingCountry, $shippingCity, $shippingStreet);
        $shippingAddress->setPostalCode($shippingZip);
        
        if ($shippingData['additionalAddressLine1']) {
            $shippingAddress->setStreet2($shippingData['additionalAddressLine1']);
        }

        $shippingUser->setAddress($shippingAddress);
        
        $transaction->setAccountHolder($accountHolder);
        $transaction->setShipping($shippingUser);
    }
    
    /**
     * creates paymentSDK basket object
     * @param Transaction $transaction
     * @param array $cart
     * @param string $currency
     * @return Basket
     */
    protected function createBasket($transaction, $cart, $currency)
    {
        $basket = new Basket();
        $basket->setVersion($transaction);

        $tmp = 0;
        foreach ($cart['content'] as $item) {
            $name = $item['articlename'];
            $sku = $item['ordernumber'];
            $description = $item['additional_details']['description'];
            $tax_rate = floatval($item['tax_rate']);
            $quantity = $item['quantity'];
            $price = 0;
            
            if (isset($item['additional_details'])) {
                $price = $item['additional_details']['price_numeric'];
            } else {
                $amountStr = $item['price'];
                $price = floatval(str_replace(',', '.', $amountStr));
            }
            $amount = new Amount($price, $currency);

            $taxStr = $item['tax'];

            $taxStr = str_replace(',', '.', $taxStr);
            $tax = new Amount(floatval($taxStr) / $quantity, $currency);

            $basketItem = new Item($name, $amount, $quantity);
            
            $basketItem->setDescription($description);
            $basketItem->setArticleNumber($sku);
            $basketItem->setTaxRate($tax_rate);
            $basketItem->setTaxAmount($tax);

            $basket->add($basketItem);
        }

        // TODO Shipping name and description
        if (isset($cart["sShippingcostsWithTax"]) &&
            $cart["sShippingcostsWithTax"]) {
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
     *
     */
    public function getPaymentResponse($request)
    {
        $configData = $this->getConfigData();
        $config = new Config($configData['baseUrl'], $configData['httpUser'], $configData['httpPass']);
        $service = new TransactionService($config);
        $response = $service->handleResponse($request);

        return $response;
    }
}
