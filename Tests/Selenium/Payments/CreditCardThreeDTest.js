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
    getDriver,
    asyncForEach,
    waitForAlert
} = require('../common');

describe('Credit Card 3-D Secure test', () => {
    const driver = getDriver();

    const paymentLabel = config.payments.creditCardThreeD.label;
    const formFields = config.payments.creditCardThreeD.fields;

    it('should check the credit card 3ds payment process', async () => {
        await loginWithExampleAccount(driver);
        await addProductToCartAndGotoCheckout(driver, '/wohnwelten/moebel/68/kommode-shabby-chic');
        await selectPaymentMethod(driver, paymentLabel);

        // Confirm order
        console.log('click button confirm--form');
        await driver.findElement(By.xpath('//button[@form="confirm--form"]')).click();

        // Fill out credit card iframe
        console.log('wait for .wirecard-seamless-frame');
        await driver.wait(until.elementLocated(By.className('wirecard-seamless-frame')), 20000);
        console.log('wait for switch to iframe .wirecard-seamless-frame');
        await driver.wait(until.ableToSwitchToFrame(By.className('wirecard-seamless-frame')));
        console.log('wait for #account_number');
        await driver.wait(until.elementLocated(By.id('account_number')), 20000);
        await asyncForEach(Object.keys(formFields), async field => {
            await driver.findElement(By.id(field)).sendKeys(formFields[field]);
        });
        await driver.findElement(By.css('#expiration_month_list > option[value=\'01\']')).click();
        await driver.findElement(By.css('#expiration_year_list > option[value=\'2019\']')).click();

        // Switch back from iframe and click Send button
        console.log('switch back from iframe to default content');
        await driver.switchTo().defaultContent();
        console.log('wait for #wirecardee-credit-card--form-submit');
        await driver.wait(until.elementLocated(By.id('wirecardee-credit-card--form-submit')), 10000);
        console.log('click #wirecardee-credit-card--form-submit');
        await driver.findElement(By.id('wirecardee-credit-card--form-submit')).click();

        console.log('wait for #password');
        await driver.wait(until.elementLocated(By.id('password')), 20000);
        await driver.findElement(By.id('password')).sendKeys(config.payments.creditCardThreeD.password, Key.ENTER);

        await waitForAlert(driver, 20000);

        await checkConfirmationPage(driver, paymentLabel);
    });

    after(async () => driver.quit());
});
