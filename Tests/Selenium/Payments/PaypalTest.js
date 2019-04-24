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
    waitUntilOverlayIsNotVisible,
    checkConfirmationPage,
    addProductToCartAndGotoCheckout,
    selectPaymentMethod,
    waitForAlert,
    getDriver
} = require('../common');

describe('PayPal test', () => {
    const driver = getDriver();

    const paymentLabel = config.payments.paypal.label;
    const formFields = config.payments.paypal.fields;

    it('should check the paypal payment process', async () => {
        await loginWithExampleAccount(driver);
        await addProductToCartAndGotoCheckout(driver, '/genusswelten/tees-und-zubeh/tee-zubehoer/24/glas-teekaennchen');
        await selectPaymentMethod(driver, paymentLabel);

        // Confirm order
        console.log('click button confirm--form');
        await driver.findElement(By.xpath('//button[@form="confirm--form"]')).click();

        try {
            console.log('wait for #email');
            await driver.wait(until.elementLocated(By.id('email')), 10000);
            await driver.findElement(By.id('email')).sendKeys(formFields.email);
            console.log('wait for #password');
            await driver.wait(until.elementLocated(By.id('password')), 10000);
            await driver.findElement(By.id('password')).sendKeys(formFields.password, Key.ENTER);
            console.log('wait for #confirmButtonTop');
            await driver.wait(until.elementLocated(By.id('confirmButtonTop')));
            console.log('#confirmButtonTop located');
            await driver.findElement(By.id('confirmButtonTop')).click();
            console.log('#confirmButtonTop clicked');

            await waitForAlert(driver, 25000);

            await checkConfirmationPage(driver, paymentLabel);
        } catch (e) {
            console.log('wait for #btnNext');
            await driver.wait(until.elementLocated(By.id('btnNext')), 10000);
            console.log('wait for #email');
            await driver.wait(until.elementLocated(By.id('email')), 10000);
            await driver.findElement(By.id('email')).sendKeys(formFields.email, Key.ENTER);

            await waitUntilOverlayIsNotVisible(driver, By.className('spinnerWithLockIcon'));

            console.log('wait for #btnLogin');
            await driver.wait(until.elementLocated(By.id('btnLogin')), 25000);
            console.log('wait for #password');
            await driver.wait(until.elementLocated(By.id('password')), 10000);
            await driver.findElement(By.id('password')).sendKeys(formFields.password, Key.ENTER);

            await waitUntilOverlayIsNotVisible(driver, By.id('preloaderSpinner'));

            console.log('wait for #confirmButtonTop');
            await driver.wait(until.elementLocated(By.id('confirmButtonTop')), 25000);
            await waitUntilOverlayIsNotVisible(driver, By.id('preloaderSpinner'));
            console.log('click #confirmButtonTop');
            await driver.wait(driver.findElement(By.id('confirmButtonTop')).click(), 10000);

            await waitForAlert(driver, 25000);

            await checkConfirmationPage(driver, paymentLabel);
        }
    });

    after(async () => driver.quit());
});
