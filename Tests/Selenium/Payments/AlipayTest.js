/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

/* eslint-env mocha */

const { expect } = require('chai');
const { By, until } = require('selenium-webdriver');
const { config } = require('../config');
const {
    loginWithExampleAccount,
    addProductToCartAndGotoCheckout,
    selectPaymentMethod,
    getDriver
} = require('../common');

let driver;

describe('Alipay Cross-border test', () => {
    before(async () => {
        driver = await getDriver('alipay');
    });

    const paymentLabel = config.payments.alipay.label;
    // const formFields = config.payments.alipay.fields;

    it('should check the alipay crossborder payment process', async () => {
        await loginWithExampleAccount(driver);
        await addProductToCartAndGotoCheckout(driver, '/genusswelten/tees-und-zubeh/tee-zubehoer/24/glas-teekaennchen');
        await selectPaymentMethod(driver, paymentLabel);

        // Confirm order
        console.log('click button confirm--form');
        await driver.findElement(By.xpath('//button[@form="confirm--form"]')).click();

        console.log('wait for .payAmount-area');
        await driver.wait(until.elementLocated(By.css('.payAmount-area')), 20000);
        const paymentContent = await driver.findElement(By.css('.payAmount-area')).getText();
        expect(paymentContent).to.include('70.99 EUR');

        // We cannot perform the full payment process because of a captcha at Alipay login page
        /*
        await driver.wait(until.elementLocated(By.css('.mi-input-account')), 20000);
        await driver.findElement(By.css('.mi-input-account')).sendKeys(formFields.email);
        await driver.findElement(By.id('payPasswd_rsainput')).sendKeys(formFields.password);

        console.log('wait for .sixDigitPassword');
        await driver.wait(until.elementLocated(By.css('.sixDigitPassword')), 15000);
        await driver.findElement(By.css('.sixDigitPassword i')).sendKeys(formFields.paymentPasswordDigit);
        await driver.findElement(By.id('J_authSubmit')).click();

        await waitForAlert(driver, 20000);

        await checkConfirmationPage(driver, paymentLabel);
        */
    });

    after(async () => driver.quit());
});
