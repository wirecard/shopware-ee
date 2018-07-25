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

const { expect } = require('chai');
const { Builder, By, until } = require('selenium-webdriver');

describe('Credit Card test', () => {
    const driver = new Builder()
        .forBrowser('chrome')
        .build();

    const url = 'http://localhost:8000';
    const mail = 'test@example.com';
    const password = 'shopware';
    const paymentLabel = 'Wirecard Credit Card';
    const creditCardFields = {
        last_name: 'Lastname',
        account_number: '4012000300001003',
        card_security_code: '003'
    };

    it('should check the credit card payment process', async () => {
        // Log in with example account
        await driver.get(`${url}/account`);
        await driver.wait(until.elementLocated(By.name('email')));
        await driver.findElement(By.name('email')).sendKeys(mail);
        await driver.findElement(By.name('password')).sendKeys(password);
        await driver.findElement(By.className('register--login-btn')).click();

        // Check if login has succeeded
        await driver.wait(until.elementLocated(By.className('account--welcome')));

        // Go to a product and buy it
        await driver.get(`${url}/genusswelten/tees-und-zubeh/tee-zubehoer/24/glas-teekaennchen`);
        await driver.findElement(By.className('buybox--button')).click();

        // Wait for the cart to be shown
        await driver.wait(until.elementLocated(By.className('button--checkout')));

        // Go to checkout page
        await driver.findElement(By.className('button--checkout')).click();

        // Go to payment selection page, check if wirecard payments are present and select credit card
        await driver.findElement(By.className('btn--change-payment')).click();
        ['payment_mean7', 'payment_mean8'].forEach(async id => {
            await driver.findElement(By.id(id));
        });
        await driver.findElement(By.xpath("//*[contains(text(), '" + paymentLabel + "')]")).click();

        // Go back to checkout page and test if payment method has been selected
        const overlay = await driver.findElements(By.className('js--overlay'));
        if (overlay.length) {
            await driver.wait(until.stalenessOf(overlay[0]));
        }
        await driver.findElement(By.className('main--actions')).click();
        const paymentDescription = await driver.findElement(By.className('payment--description')).getText();
        expect(paymentDescription).to.include(paymentLabel);

        // Check AGB and confirm order
        await driver.findElement(By.id('sAGB')).click();
        await driver.findElement(By.xpath('//button[@form="confirm--form"]')).click();

        // Fill out credit card iframe
        await driver.wait(until.elementLocated(By.className('wirecard-seamless-frame')));
        await driver.wait(until.ableToSwitchToFrame(By.className('wirecard-seamless-frame')));
        await driver.wait(until.elementLocated(By.id('account_number')));
        Object.keys(creditCardFields).forEach(async field => {
            await driver.findElement(By.id(field)).sendKeys(creditCardFields[field]);
        });
        await driver.findElement(By.css('#expiration_month_list > option[value=\'01\']')).click();
        await driver.findElement(By.css('#expiration_year_list > option[value=\'2030\']')).click();

        driver.switchTo().defaultContent();
        await driver.findElement(By.id('wirecardee-credit-card--form-submit')).click();

        // Check confirmation page
        await driver.wait(until.elementLocated(By.className('teaser--btn-print')));
        const panelTitle = await driver.findElement(By.className('panel--title')).getText();
        expect(panelTitle).to.include('Vielen Dank');
        const paymentContent = await driver.findElement(By.className('payment--content')).getText();
        expect(paymentContent).to.include(paymentLabel);
    });

    after(async () => driver.quit());
});
