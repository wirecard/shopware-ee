<?xml version="1.0" encoding="utf-8"?>
<!--
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 -->
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="https://raw.githubusercontent.com/shopware/shopware/5.4/engine/Shopware/Components/Plugin/schema/config.xsd">
    <elements>
        <!-- General -->
        <element type="button" scope="shop">
            <name>wirecardElasticEngineGeneralInformation</name>
            @forlang
            <label lang="{{ lang }}">{{ strings.plugin_general_information }}</label>
            @endforlang
            <options>
                <handler>
                    <![CDATA[
function (button) {
    var message = Ext.userLanguage == "de"
        ? "{{ strings.plugin_activated_refresh_notice | de_DE }}"
        : "{{ strings.plugin_activated_refresh_notice }}"

    if (Shopware.apps.WirecardElasticEngineExtendOrder) {
        Ext.create('Shopware.apps.WirecardElasticEngineExtendOrder.view.GeneralInformationWindow').show();
    } else {
        if (confirm(message)) {
            window.location.reload();
        }
    }
}
                    ]]>
                </handler>
            </options>
        </element>
        <element type="button" scope="shop">
            <name>wirecardElasticEngineCreditCardPSDTwoHint</name>
            @forlang
            <label lang="{{ lang }}">{{ strings.config_PSD2_information_desc }}</label>
            @endforlang
            <options>
                <handler>
                    <![CDATA[
                    function (button) {
                    window.open('https://github.com/wirecard/shopware-ee/wiki/Shopware-Checkout','_blank');
                    }
                    ]]>
                </handler>
            </options>
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEngineNotifyMail</name>
            @forlang
            <label lang="{{ lang }}">{{ strings.config_payment_notifications_email }}</label>
            @endforlang
            <value></value>
            @forlang
            <description lang="{{ lang }}">
                {{ strings.config_payment_notifications_email_desc }}
            </description>
            @endforlang
            <options>
                <vtype>email</vtype>
            </options>
        </element>
        <element type="boolean" scope="shop">
            <name>wirecardElasticEnginePendingMail</name>
            @forlang
            <label lang="{{ lang }}">{{ strings.config_send_pending_status_mails }}</label>
            @endforlang
            <value>false</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_send_pending_status_mails_desc }}</description>
            @endforlang
        </element>
        <!-- General End -->
        <!-- Credit Card -->
        <element type="text" scope="shop">
            <name>wirecardElasticEngineCreditCardServer</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.creditcard }}] {{ strings.config_base_url }}</label>
            @endforlang
            <value>https://api-test.wirecard.com</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_base_url_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEngineCreditCardHttpUser</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.creditcard }}] {{ strings.config_http_user }}</label>
            @endforlang
            <value>70000-APITEST-AP</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_http_user_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEngineCreditCardHttpPassword</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.creditcard }}] {{ strings.config_http_password }}</label>
            @endforlang
            <value>qD2wzQ_hrc!8</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_http_password_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEngineCreditCardMerchantId</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.creditcard }}] {{ strings.config_merchant_account_id }}</label>
            @endforlang
            <value>53f2895a-e4de-4e82-a813-0d87a10e55e6</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_merchant_account_id_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEngineCreditCardSecret</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.creditcard }}] {{ strings.config_merchant_secret }}</label>
            @endforlang
            <value>dbc5a498-9a66-43b9-bf1d-a618dd399684</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_merchant_secret_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEngineCreditCardThreeDMAID</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.creditcard }}] {{ strings.config_three_d_merchant_account_id }}</label>
            @endforlang
            <value>508b8896-b37d-4614-845c-26bf8bf2c948</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_three_d_merchant_account_id_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEngineCreditCardThreeDSecret</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.creditcard }}] {{ strings.config_three_d_merchant_secret }}</label>
            @endforlang
            <value>dbc5a498-9a66-43b9-bf1d-a618dd399684</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_three_d_merchant_secret_desc }}</description>
            @endforlang
        </element>
        <element type="select" scope="shop">
            <name>wirecardElasticEngineCreditCardTransactionType</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.creditcard }}] {{ strings.config_payment_action }}</label>
            @endforlang
            <value>pay</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_payment_action_desc }}</description>
            @endforlang
            <store>
                <option>
                    <value>reserve</value>
                    @forlang
                    <label lang="{{ lang }}">{{ strings.text_payment_action_reserve }}</label>
                    @endforlang
                </option>
                <option>
                    <value>pay</value>
                    @forlang
                    <label lang="{{ lang }}">{{ strings.text_payment_action_pay }}</label>
                    @endforlang
                </option>
            </store>
        </element>
        <element type="select" scope="shop">
            <name>wirecardElasticEngineCreditCardChallengeIndicator</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.creditcard }}] {{ strings.config_challenge_indicator }}</label>
            @endforlang
            <value>1</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_challenge_indicator_desc }}</description>
            @endforlang
            <store>
                <option>
                    <value>1</value>
                    @forlang
                    <label lang="{{ lang }}">{{ strings.config_challenge_indicator_no_preference }}</label>
                    @endforlang
                </option>
                <option>
                    <value>2</value>
                    @forlang
                    <label lang="{{ lang }}">{{ strings.config_challenge_indicator_no_challenge_requested }}</label>
                    @endforlang
                </option>
                <option>
                    <value>3</value>
                    @forlang
                    <label lang="{{ lang }}">{{ strings.config_challenge_indicator_challenge_requested }}</label>
                    @endforlang
                </option>
            </store>
        </element>
        <element type="boolean" scope="shop">
            <name>wirecardElasticEngineCreditCardFraudPrevention</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.creditcard }}] {{ strings.config_additional_info }}</label>
            @endforlang
            <value>true</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_additional_info_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEngineCreditCardSslMaxLimit</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.creditcard }}] {{ strings.config_ssl_max_limit }}</label>
            @endforlang
            <value>300</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_ssl_max_limit_desc }}</description>
            @endforlang
        </element>
        <element type="combo" scope="shop">
            <name>wirecardElasticEngineCreditCardSslMaxLimitCurrency</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.creditcard }}] {{ strings.config_ssl_max_limit_currency }}</label>
            @endforlang
            <value></value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_currency_default }}</description>
            @endforlang
            <store>Shopware.apps.Base.store.Currency</store>
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEngineCreditCardThreeDMinLimit</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.creditcard }}] {{ strings.config_three_d_min_limit }}</label>
            @endforlang
            <value>100</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_three_d_min_limit_desc }}</description>
            @endforlang
        </element>
        <element type="combo" scope="shop">
            <name>wirecardElasticEngineCreditCardThreeDMinLimitCurrency</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.creditcard }}] {{ strings.config_three_d_min_limit_currency }}</label>
            @endforlang
            <value></value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_currency_default }}</description>
            @endforlang
            <store>Shopware.apps.Base.store.Currency</store>
        </element>
        <element type="boolean" scope="shop">
            <name>wirecardElasticEngineCreditCardEnableVault</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.creditcard }}] {{ strings.enable_vault }}</label>
            @endforlang
            <value>false</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_vault_desc }}</description>
            @endforlang
        </element>
        <element type="boolean" scope="shop">
            <name>wirecardElasticEngineCreditCardAllowAddressChanges</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.creditcard }}] {{ strings.config_allow_changed_shipping }}</label>
            @endforlang
            <value>false</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_allow_changed_shipping_desc }}</description>
            @endforlang
        </element>
        <element type="boolean" scope="shop">
            <name>wirecardElasticEngineCreditCardThreeDUsageOnTokens</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.creditcard }}] {{ strings.config_vault_enable_three_d }}</label>
            @endforlang
            <value>true</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_vault_enable_three_d_desc }}</description>
            @endforlang
        </element>
        <element type="button" scope="shop">
            <name>wirecardElasticEngineCreditCardTestApi</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.creditcard }}] {{ strings.test_config }}</label>
            @endforlang
            <options>
                <handler>
                    <![CDATA[
function (button) {
    var message = Ext.userLanguage == "de"
        ? "{{ strings.plugin_activated_refresh_notice | de_DE }}"
        : "{{ strings.plugin_activated_refresh_notice }}"

    if (typeof wirecardeeTestPaymentCredentials !== "undefined") {
        wirecardeeTestPaymentCredentials(button, 'CreditCard', 'Wirecard Credit Card Test');
    } else {
        if (confirm(message)) {
            window.location.reload();
        }
    }
}
                    ]]>
                </handler>
            </options>
        </element>
        <element type="button" scope="shop">
            <name>wirecardElasticEngineCreditCardThreeDDescription</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.creditcard }}] {{ strings.creditcard_limit_desc_button }}</label>
            @endforlang
            <options>
                <handler>
                    <![CDATA[
function (button) {
    window.open('https://raw.githubusercontent.com/wiki/wirecard/shopware-ee/img/creditcard-ssl-3d-new.png','_blank');
}
                    ]]>
                </handler>
            </options>
        </element>
        <!-- Credit Card End -->
        <!-- Alipay -->
        <element type="text" scope="shop">
            <name>wirecardElasticEngineAlipayServer</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.alipay_crossborder }}] {{ strings.config_base_url }}</label>
            @endforlang
            <value>https://api-test.wirecard.com</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_base_url_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEngineAlipayHttpUser</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.alipay_crossborder }}] {{ strings.config_http_user }}</label>
            @endforlang
            <value>70000-APITEST-AP</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_http_user_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEngineAlipayHttpPassword</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.alipay_crossborder }}] {{ strings.config_http_password }}</label>
            @endforlang
            <value>qD2wzQ_hrc!8</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_http_password_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEngineAlipayMerchantId</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.alipay_crossborder }}] {{ strings.config_merchant_account_id }}</label>
            @endforlang
            <value>7ca48aa0-ab12-4560-ab4a-af1c477cce43</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_merchant_account_id_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEngineAlipaySecret</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.alipay_crossborder }}] {{ strings.config_merchant_secret }}</label>
            @endforlang
            <value>dbc5a498-9a66-43b9-bf1d-a618dd399684</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_merchant_secret_desc }}</description>
            @endforlang
        </element>
        <element type="boolean" scope="shop">
            <name>wirecardElasticEngineAlipayFraudPrevention</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.alipay_crossborder }}] {{ strings.config_additional_info }}</label>
            @endforlang
            <value>true</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_additional_info_desc }}</description>
            @endforlang
        </element>
        <element type="button" scope="shop">
            <name>wirecardElasticEngineAlipayTestApi</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.alipay_crossborder }}] {{ strings.test_config }}</label>
            @endforlang
            <options>
                <handler>
                <![CDATA[
function (button) {
    var message = Ext.userLanguage == "de"
        ? "{{ strings.plugin_activated_refresh_notice | de_DE }}"
        : "{{ strings.plugin_activated_refresh_notice }}"

    if (typeof wirecardeeTestPaymentCredentials !== "undefined") {
        wirecardeeTestPaymentCredentials(button, 'Alipay', 'Wirecard Alipay Cross-border Test');
    } else {
        if (confirm(message)) {
            window.location.reload();
        }
    }
}
                    ]]>
                </handler>
            </options>
        </element>
        <!-- Alipay End -->
        <!-- Ratepay Invoice -->
        <element type="text" scope="shop">
            <name>wirecardElasticEngineRatepayInvoiceServer</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.ratepayinvoice }}] {{ strings.config_base_url }}</label>
            @endforlang
            <value>https://api-test.wirecard.com</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_base_url_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEngineRatepayInvoiceHttpUser</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.ratepayinvoice }}] {{ strings.config_http_user }}</label>
            @endforlang
            <value>70000-APITEST-AP</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_http_user_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEngineRatepayInvoiceHttpPassword</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.ratepayinvoice }}] {{ strings.config_http_password }}</label>
            @endforlang
            <value>qD2wzQ_hrc!8</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_http_password_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEngineRatepayInvoiceMerchantId</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.ratepayinvoice }}] {{ strings.config_merchant_account_id }}</label>
            @endforlang
            <value>fa02d1d4-f518-4e22-b42b-2abab5867a84</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_merchant_account_id_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEngineRatepayInvoiceSecret</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.ratepayinvoice }}] {{ strings.config_merchant_secret }}</label>
            @endforlang
            <value>dbc5a498-9a66-43b9-bf1d-a618dd399684</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_merchant_secret_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEngineRatepayInvoiceMinAmount</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.ratepayinvoice }}] {{ strings.config_basket_min }}</label>
            @endforlang
            <value>20</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_basket_min_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEngineRatepayInvoiceMaxAmount</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.ratepayinvoice }}] {{ strings.config_basket_max }}</label>
            @endforlang
            <value>3500</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_basket_max_desc }}</description>
            @endforlang
        </element>
        <element type="combo" scope="shop">
            <name>wirecardElasticEngineRatepayInvoiceAcceptedCurrencies</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.ratepayinvoice }}] {{ strings.config_allowed_currencies }}</label>
            @endforlang
            <value>-</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_allowed_currencies_desc }}</description>
            @endforlang
            <store>Shopware.apps.Base.store.Currency</store>
            <options>
                <multiSelect>true</multiSelect>
            </options>
        </element>
        <element type="combo" scope="shop">
            <name>wirecardElasticEngineRatepayInvoiceShippingCountries</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.ratepayinvoice }}] {{ strings.config_shipping_countries }}</label>
            @endforlang
            <value>-</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_shipping_countries_desc }}</description>
            @endforlang
            <store>Shopware.apps.Base.store.Country</store>
            <options>
                <multiSelect>true</multiSelect>
            </options>
        </element>
        <element type="combo" scope="shop">
            <name>wirecardElasticEngineRatepayInvoiceBillingCountries</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.ratepayinvoice }}] {{ strings.config_billing_countries }}</label>
            @endforlang
            <value>-</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_billing_countries_desc }}</description>
            @endforlang
            <store>Shopware.apps.Base.store.Country</store>
            <options>
                <multiSelect>true</multiSelect>
            </options>
        </element>
        <element type="boolean" scope="shop">
            <name>wirecardElasticEngineRatepayInvoiceBillingShippingMustBeIdentical</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.ratepayinvoice }}] {{ strings.config_billing_shipping }}</label>
            @endforlang
            <value>true</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_billing_shipping_desc }}</description>
            @endforlang
        </element>
        <element type="boolean" scope="shop">
            <name>wirecardElasticEngineRatepayInvoiceFraudPrevention</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.ratepayinvoice }}] {{ strings.config_additional_info }}</label>
            @endforlang
            <value>true</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_additional_info_desc }}</description>
            @endforlang
        </element>
        <element type="button" scope="shop">
            <name>wirecardElasticEngineRatepayInvoiceTestApi</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.ratepayinvoice }}] {{ strings.test_config }}</label>
            @endforlang
            <options>
                <handler>
                    <![CDATA[
function (button) {
    var message = Ext.userLanguage == "de"
        ? "{{ strings.plugin_activated_refresh_notice | de_DE }}"
        : "{{ strings.plugin_activated_refresh_notice }}"

    if (typeof wirecardeeTestPaymentCredentials !== "undefined") {
        wirecardeeTestPaymentCredentials(button, 'RatepayInvoice', 'Guaranteed Invoice by Wirecard Test');
    } else {
        if (confirm(message)) {
            window.location.reload();
        }
    }
}
                    ]]>
                </handler>
            </options>
        </element>
        <!-- Ratepay Invoice End -->
        <!-- iDEAL -->
        <element type="text" scope="shop">
            <name>wirecardElasticEngineIdealServer</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.ideal }}] {{ strings.config_base_url }}</label>
            @endforlang
            <value>https://api-test.wirecard.com</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_base_url_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEngineIdealHttpUser</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.ideal }}] {{ strings.config_http_user }}</label>
            @endforlang
            <value>16390-testing</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_http_user_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEngineIdealHttpPassword</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.ideal }}] {{ strings.config_http_password }}</label>
            @endforlang
            <value>3!3013=D3fD8X7</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_http_password_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEngineIdealMerchantId</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.ideal }}] {{ strings.config_merchant_account_id }}</label>
            @endforlang
            <value>4aeccf39-0d47-47f6-a399-c05c1f2fc819</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_merchant_account_id_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEngineIdealSecret</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.ideal }}] {{ strings.config_merchant_secret }}</label>
            @endforlang
            <value>dbc5a498-9a66-43b9-bf1d-a618dd399684</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_merchant_secret_desc }}</description>
            @endforlang
        </element>
        <element type="boolean" scope="shop">
            <name>wirecardElasticEngineIdealFraudPrevention</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.ideal }}] {{ strings.config_additional_info }}</label>
            @endforlang
            <value>true</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_additional_info_desc }}</description>
            @endforlang
        </element>
        <element type="button" scope="shop">
            <name>wirecardElasticEngineIdealTestApi</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.ideal }}] {{ strings.test_config }}</label>
            @endforlang
            <options>
                <handler>
                    <![CDATA[
function (button) {
    var message = Ext.userLanguage == "de"
        ? "{{ strings.plugin_activated_refresh_notice | de_DE }}"
        : "{{ strings.plugin_activated_refresh_notice }}"

    if (typeof wirecardeeTestPaymentCredentials !== "undefined") {
        wirecardeeTestPaymentCredentials(button, 'Ideal', 'Wirecard iDEAL Test');
    } else {
        if (confirm(message)) {
            window.location.reload();
        }
    }
}
                    ]]>
                </handler>
            </options>
        </element>
        <!-- iDEAL End -->
        <!-- eps-Überweisung -->
        <element type="text" scope="shop">
            <name>wirecardElasticEngineEpsServer</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.eps }}] {{ strings.config_base_url }}</label>
            @endforlang
            <value>https://api-test.wirecard.com</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_base_url_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEngineEpsHttpUser</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.eps }}] {{ strings.config_http_user }}</label>
            @endforlang
            <value>16390-testing</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_http_user_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEngineEpsHttpPassword</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.eps }}] {{ strings.config_http_password }}</label>
            @endforlang
            <value>3!3013=D3fD8X7</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_http_password_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEngineEpsMerchantId</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.eps }}] {{ strings.config_merchant_account_id }}</label>
            @endforlang
            <value>1f629760-1a66-4f83-a6b4-6a35620b4a6d</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_merchant_account_id_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEngineEpsSecret</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.eps }}] {{ strings.config_merchant_secret }}</label>
            @endforlang
            <value>20c6a95c-e39b-4e6a-971f-52cfb347d359</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_merchant_secret_desc }}</description>
            @endforlang
        </element>
        <element type="boolean" scope="shop">
            <name>wirecardElasticEngineEpsFraudPrevention</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.eps }}] {{ strings.config_additional_info }}</label>
            @endforlang
            <value>true</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_additional_info_desc }}</description>
            @endforlang
        </element>
        <element type="button" scope="shop">
            <name>wirecardElasticEngineEpsTestApi</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.eps }}] {{ strings.test_config }}</label>
            @endforlang
            <options>
                <handler>
                    <![CDATA[
function (button) {
    var message = Ext.userLanguage == "de"
        ? "{{ strings.plugin_activated_refresh_notice | de_DE }}"
        : "{{ strings.plugin_activated_refresh_notice }}"

    if (typeof wirecardeeTestPaymentCredentials !== "undefined") {
        wirecardeeTestPaymentCredentials(button, 'Eps', 'Wirecard eps-Überweisung Test');
    } else {
        if (confirm(message)) {
            window.location.reload();
        }
    }
}
                    ]]>
                </handler>
            </options>
        </element>
        <!-- eps-Überweisung End -->
        <!-- PayPal -->
        <element type="text" scope="shop">
            <name>wirecardElasticEnginePaypalServer</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.paypal }}] {{ strings.config_base_url }}</label>
            @endforlang
            <value>https://api-test.wirecard.com</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_base_url_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEnginePaypalHttpUser</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.paypal }}] {{ strings.config_http_user }}</label>
            @endforlang
            <value>70000-APITEST-AP</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_http_user_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEnginePaypalHttpPassword</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.paypal }}] {{ strings.config_http_password }}</label>
            @endforlang
            <value>qD2wzQ_hrc!8</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_http_password_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEnginePaypalMerchantId</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.paypal }}] {{ strings.config_merchant_account_id }}</label>
            @endforlang
            <value>2a0e9351-24ed-4110-9a1b-fd0fee6bec26</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_merchant_account_id_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEnginePaypalSecret</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.paypal }}] {{ strings.config_merchant_secret }}</label>
            @endforlang
            <value>dbc5a498-9a66-43b9-bf1d-a618dd399684</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_merchant_secret_desc }}</description>
            @endforlang
        </element>
        <element type="select" scope="shop">
            <name>wirecardElasticEnginePaypalTransactionType</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.paypal }}] {{ strings.config_payment_action }}</label>
            @endforlang
            <value>pay</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_payment_action_desc }}</description>
            @endforlang
            <store>
                <option>
                    <value>reserve</value>
                    @forlang
                    <label lang="{{ lang }}">{{ strings.text_payment_action_reserve }}</label>
                    @endforlang
                </option>
                <option>
                    <value>pay</value>
                    @forlang
                    <label lang="{{ lang }}">{{ strings.text_payment_action_pay }}</label>
                    @endforlang
                </option>
            </store>
        </element>
        <element type="boolean" scope="shop">
            <name>wirecardElasticEnginePaypalSendBasket</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.paypal }}] {{ strings.config_shopping_basket }}</label>
            @endforlang
            <value>true</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_shopping_basket_desc }}</description>
            @endforlang
        </element>
        <element type="boolean" scope="shop">
            <name>wirecardElasticEnginePaypalFraudPrevention</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.paypal }}] {{ strings.config_additional_info }}</label>
            @endforlang
            <value>true</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_additional_info_desc }}</description>
            @endforlang
        </element>
        <element type="boolean" scope="shop">
            <name>wirecardElasticEnginePaypalDescriptor</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.paypal }}] {{ strings.config_descriptor }}</label>
            @endforlang
            <value>true</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_descriptor_desc }}</description>
            @endforlang
        </element>
        <element type="button" scope="shop">
            <name>wirecardElasticEngineTestApi</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.paypal }}] {{ strings.test_config }}</label>
            @endforlang
            <options>
                <handler>
                    <![CDATA[
function (button) {
    var message = Ext.userLanguage == "de"
        ? "{{ strings.plugin_activated_refresh_notice | de_DE }}"
        : "{{ strings.plugin_activated_refresh_notice }}"

    if (typeof wirecardeeTestPaymentCredentials !== "undefined") {
        wirecardeeTestPaymentCredentials(button, 'Paypal', 'Wirecard PayPal Test');
    } else {
        if (confirm(message)) {
            window.location.reload();
        }
    }
}
                    ]]>
                </handler>
            </options>
        </element>
        <!-- PayPal End -->
        <!-- Poi / Pia -->
        <element type="text" scope="shop">
            <name>wirecardElasticEnginePoiPiaServer</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.poi_pia }}] {{ strings.config_base_url }}</label>
            @endforlang
            <value>https://api-test.wirecard.com</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_base_url_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEnginePoiPiaHttpUser</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.poi_pia }}] {{ strings.config_http_user }}</label>
            @endforlang
            <value>70000-APITEST-AP</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_http_user_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEnginePoiPiaHttpPassword</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.poi_pia }}] {{ strings.config_http_password }}</label>
            @endforlang
            <value>qD2wzQ_hrc!8</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_http_password_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEnginePoiPiaMerchantId</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.poi_pia }}] {{ strings.config_merchant_account_id }}</label>
            @endforlang
            <value>105ab3e8-d16b-4fa0-9f1f-18dd9b390c94</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_merchant_account_id_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEnginePoiPiaSecret</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.poi_pia }}] {{ strings.config_merchant_secret }}</label>
            @endforlang
            <value>2d96596b-9d10-4c98-ac47-4d56e22fd878</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_merchant_secret_desc }}</description>
            @endforlang
        </element>
        <element type="boolean" scope="shop">
            <name>wirecardElasticEnginePoiPiaFraudPrevention</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.poi_pia }}] {{ strings.config_additional_info }}</label>
            @endforlang
            <value>true</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_additional_info_desc }}</description>
            @endforlang
        </element>
        <element type="button" scope="shop">
            <name>wirecardElasticEnginePoiPiaTestApi</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.poi_pia }}] {{ strings.test_config }}</label>
            @endforlang
            <options>
                <handler>
                    <![CDATA[
function (button) {
    var message = Ext.userLanguage == "de"
        ? "{{ strings.plugin_activated_refresh_notice | de_DE }}"
        : "{{ strings.plugin_activated_refresh_notice }}"

    if (typeof wirecardeeTestPaymentCredentials !== "undefined") {
        wirecardeeTestPaymentCredentials(button, 'PoiPia', 'Wirecard Payment on Invoice / Payment in Advance Test');
    } else {
        if (confirm(message)) {
            window.location.reload();
        }
    }
}
                    ]]>
                </handler>
            </options>
        </element>
        <!-- Poi / Pia End -->
        <!-- SEPA Direct Debit -->
        <element type="text" scope="shop">
            <name>wirecardElasticEngineSepaServer</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.sepadd }}] {{ strings.config_base_url }}</label>
            @endforlang
            <value>https://api-test.wirecard.com</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_base_url_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEngineSepaHttpUser</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.sepadd }}] {{ strings.config_http_user }}</label>
            @endforlang
            <value>16390-testing</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_http_user_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEngineSepaHttpPassword</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.sepadd }}] {{ strings.config_http_password }}</label>
            @endforlang
            <value>3!3013=D3fD8X7</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_http_password_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEngineSepaMerchantId</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.sepadd }}] {{ strings.config_merchant_account_id }}</label>
            @endforlang
            <value>933ad170-88f0-4c3d-a862-cff315ecfbc0</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_merchant_account_id_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEngineSepaSecret</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.sepadd }}] {{ strings.config_merchant_secret }}</label>
            @endforlang
            <value>ecdf5990-0372-47cd-a55d-037dccfe9d25</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_merchant_secret_desc }}</description>
            @endforlang
        </element>
        <element type="select" scope="shop">
            <name>wirecardElasticEngineSepaTransactionType</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.sepadd }}] {{ strings.config_payment_action }}</label>
            @endforlang
            <value>pay</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_payment_action_desc }}</description>
            @endforlang
            <store>
                <option>
                    <value>reserve</value>
                    @forlang
                    <label lang="{{ lang }}">{{ strings.text_payment_action_reserve }}</label>
                    @endforlang
                </option>
                <option>
                    <value>pay</value>
                    @forlang
                    <label lang="{{ lang }}">{{ strings.text_payment_action_pay }}</label>
                    @endforlang
                </option>
            </store>
        </element>
        <element type="boolean" scope="shop">
            <name>wirecardElasticEngineSepaFraudPrevention</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.sepadd }}] {{ strings.config_additional_info }}</label>
            @endforlang
            <value>true</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_additional_info_desc }}</description>
            @endforlang
        </element>
        <element type="boolean" scope="shop">
            <name>wirecardElasticEngineSepaShowBic</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.sepadd }}] {{ strings.config_enable_bic }}</label>
            @endforlang
            <value>false</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_enable_bic_desc }}</description>
            @endforlang
        </element>
        <element required="true" type="text" scope="shop">
            <name>wirecardElasticEngineSepaCreditorId</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.sepadd }}] {{ strings.config_creditor_id }}</label>
            @endforlang
            <value>DE98ZZZ09999999999</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_creditor_id_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEngineSepaCreditorName</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.sepadd }}] {{ strings.config_creditor_name }}</label>
            @endforlang
            <value></value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_creditor_name_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEngineSepaCreditorAddress</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.sepadd }}] {{ strings.config_creditor_city }}</label>
            @endforlang
            <value></value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_creditor_city_desc }}</description>
            @endforlang
        </element>
        <element type="button" scope="shop">
            <name>wirecardElasticEngineSepaTestApi</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.sepadd }}] {{ strings.test_config }}</label>
            @endforlang
            <options>
                <handler>
                    <![CDATA[
function (button) {
    var message = Ext.userLanguage == "de"
        ? "{{ strings.plugin_activated_refresh_notice | de_DE }}"
        : "{{ strings.plugin_activated_refresh_notice }}"

    if (typeof wirecardeeTestPaymentCredentials !== "undefined") {
        wirecardeeTestPaymentCredentials(button, 'Sepa', 'Wirecard SEPA Direct Debit Test');
    } else {
        if (confirm(message)) {
            window.location.reload();
        }
    }
}
                    ]]>
                </handler>
            </options>
        </element>
        <!-- SEPA Direct Debit End -->
        <!-- SEPA Credit Transfer -->
        <element type="text" scope="shop">
            <name>wirecardElasticEngineSepaBackendMerchantId</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.sepact }}] {{ strings.config_merchant_account_id }}</label>
            @endforlang
            <value>59a01668-693b-49f0-8a1f-f3c1ba025d45</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_merchant_account_id_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEngineSepaBackendSecret</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.sepact }}] {{ strings.config_merchant_secret }}</label>
            @endforlang
            <value>ecdf5990-0372-47cd-a55d-037dccfe9d25</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_merchant_secret_desc }}</description>
            @endforlang
        </element>
        <element required="true" type="text" scope="shop">
            <name>wirecardElasticEngineSepaBackendCreditorId</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.sepact }}] {{ strings.config_creditor_id }}</label>
            @endforlang
            <value>DE98ZZZ09999999999</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_creditor_id_desc }}</description>
            @endforlang
        </element>
        <!-- SEPA Credit Transfer End -->
        <!-- Sofort. -->
        <element type="text" scope="shop">
            <name>wirecardElasticEngineSofortServer</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.sofortbanking }}] {{ strings.config_base_url }}</label>
            @endforlang
            <value>https://api-test.wirecard.com</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_base_url_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEngineSofortHttpUser</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.sofortbanking }}] {{ strings.config_http_user }}</label>
            @endforlang
            <value>16390-testing</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_http_user_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEngineSofortHttpPassword</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.sofortbanking }}] {{ strings.config_http_password }}</label>
            @endforlang
            <value>3!3013=D3fD8X7</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_http_password_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEngineSofortMerchantId</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.sofortbanking }}] {{ strings.config_merchant_account_id }}</label>
            @endforlang
            <value>6c0e7efd-ee58-40f7-9bbd-5e7337a052cd</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_merchant_account_id_desc }}</description>
            @endforlang
        </element>
        <element type="text" scope="shop">
            <name>wirecardElasticEngineSofortSecret</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.sofortbanking }}] {{ strings.config_merchant_secret }}</label>
            @endforlang
            <value>dbc5a498-9a66-43b9-bf1d-a618dd399684</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_merchant_secret_desc }}</description>
            @endforlang
        </element>
        <element type="boolean" scope="shop">
            <name>wirecardElasticEngineSofortFraudPrevention</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.sofortbanking }}] {{ strings.config_additional_info }}</label>
            @endforlang
            <value>true</value>
            @forlang
            <description lang="{{ lang }}">{{ strings.config_additional_info_desc }}</description>
            @endforlang
        </element>
        <element type="button" scope="shop">
            <name>wirecardElasticEngineSofortTestApi</name>
            @forlang
            <label lang="{{ lang }}">[{{ strings.sofortbanking }}] {{ strings.test_config }}</label>
            @endforlang
            <options>
                <handler>
                    <![CDATA[
function (button) {
    var message = Ext.userLanguage == "de"
        ? "{{ strings.plugin_activated_refresh_notice | de_DE }}"
        : "{{ strings.plugin_activated_refresh_notice }}"

    if (typeof wirecardeeTestPaymentCredentials !== "undefined") {
        wirecardeeTestPaymentCredentials(button, 'Sofort', 'Wirecard Sofort. Test');
    } else {
        if (confirm(message)) {
            window.location.reload();
        }
    }
}
                    ]]>
                </handler>
            </options>
        </element>
        <!-- Sofort. End -->
    </elements>
</config>
