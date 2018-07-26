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

const { Builder, By, until, Key } = require('selenium-webdriver');
const { config } = require('../config');
const {
    loginWithExampleAccount,
    waitUntilOverlayIsNotVisible,
    checkConfirmationPage,
    addProductToCartAndGotoCheckout,
    selectPaymentMethod
} = require('../common');

describe('PayPal test', () => {
    const driver = new Builder()
        .forBrowser('chrome')
        .build();
    driver.manage().deleteAllCookies();

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
            // Log in to PayPal
            console.log('wait for #loginSection');
            await driver.wait(until.elementLocated(By.id('loginSection')), 10000);
            await waitUntilOverlayIsNotVisible(driver, By.id('preloaderSpinner'));
            console.log('click #loginSection .btn');
            await driver.wait(driver.findElement(By.css('#loginSection .btn')).click());
            await waitUntilOverlayIsNotVisible(driver, By.id('preloaderSpinner'));
        } catch (e) {
            console.log('PayPal skipped loginSection, proceed with credentials');
        }

        // Enter PayPal credentials
        console.log('wait for #btnNext');
        await driver.wait(until.elementLocated(By.id('btnNext')));
        console.log('wait for #email');
        await driver.wait(until.elementLocated(By.id('email')));
        await driver.findElement(By.id('email')).sendKeys(formFields.email, Key.ENTER);

        await waitUntilOverlayIsNotVisible(driver, By.className('spinnerWithLockIcon'));

        console.log('wait for #btnLogin');
        await driver.wait(until.elementLocated(By.id('btnLogin')));
        console.log('wait for #password');
        await driver.wait(until.elementLocated(By.id('password')));
        await driver.findElement(By.id('password')).sendKeys(formFields.password, Key.ENTER);

        await waitUntilOverlayIsNotVisible(driver, By.id('preloaderSpinner'));

        console.log('wait for #confirmButtonTop');
        await driver.wait(until.elementLocated(By.id('confirmButtonTop')));
        console.log('click #confirmButtonTop');
        await driver.wait(driver.findElement(By.id('confirmButtonTop')).click());

        await checkConfirmationPage(driver, paymentLabel);
    });

    after(async () => driver.quit());
});
