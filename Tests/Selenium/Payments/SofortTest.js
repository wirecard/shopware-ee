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

/* eslint-env mocha */

const { By, until, Key } = require('selenium-webdriver');
const { config } = require('../config');
const {
    loginWithExampleAccount,
    checkConfirmationPage,
    addProductToCartAndGotoCheckout,
    selectPaymentMethod,
    waitForAlert,
    getDriver
} = require('../common');

describe('Sofort. test', () => {
    const driver = getDriver();

    const paymentLabel = config.payments.sofort.label;
    const formFields = config.payments.sofort.fields;

    it('should check the sofort payment process', async () => {
        await loginWithExampleAccount(driver);
        await addProductToCartAndGotoCheckout(driver, '/genusswelten/tees-und-zubeh/tee-zubehoer/24/glas-teekaennchen');
        await selectPaymentMethod(driver, paymentLabel);

        // Confirm order
        console.log('click button confirm--form');
        await driver.findElement(By.xpath('//button[@form="confirm--form"]')).click();

        // Wait for Sofort. page and fill out forms
        console.log('wait for #MultipaysSessionSenderCountryId');
        await driver.wait(until.elementLocated(By.id('MultipaysSessionSenderCountryId')), 20000);
        await driver.findElement(By.css('#MultipaysSessionSenderCountryId > option[value=\'AT\']')).click();
        await driver.findElement(By.id('BankCodeSearch')).sendKeys(formFields.bankCode, Key.ENTER);

        console.log('wait for #BackendFormLOGINNAMEUSERID');
        await driver.wait(until.elementLocated(By.id('BackendFormLOGINNAMEUSERID')), 20000);
        await driver.findElement(By.id('BackendFormLOGINNAMEUSERID')).sendKeys(formFields.userId);
        await driver.findElement(By.id('BackendFormUSERPIN')).sendKeys(formFields.password, Key.ENTER);

        console.log('wait for #account-1');
        await driver.wait(until.elementLocated(By.id('account-1')), 20000);
        await driver.findElement(By.id('account-1')).click();
        await driver.findElement(By.css('button.primary')).click();

        console.log('wait for #BackendFormTAN');
        await driver.wait(until.elementLocated(By.id('BackendFormTAN')), 20000);
        await driver.findElement(By.id('BackendFormTAN')).sendKeys(formFields.tan, Key.ENTER);

        await waitForAlert(driver, 10000);

        await checkConfirmationPage(driver, paymentLabel);
    });

    after(async () => driver.quit());
});
