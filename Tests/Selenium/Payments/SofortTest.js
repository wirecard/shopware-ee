/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
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
        await driver.findElement(By.id('BankCodeSearch')).sendKeys(formFields.bankCode);

        console.log('wait for .js-bank-searcher-result-list');
        await driver.wait(until.elementLocated(By.className('js-bank-searcher-result-list')), 5000);
        await driver.findElement(By.css('button.primary')).click();

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

        await waitForAlert(driver, 20000);

        await checkConfirmationPage(driver, paymentLabel);
    });

    after(async () => driver.quit());
});
