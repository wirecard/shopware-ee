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
const { config } = require('../config');
const { loginWithExampleAccount, waitUntilOverlayIsStale, checkConfirmationPage } = require('../common');

describe('default test', () => {
    const driver = new Builder()
        .forBrowser('chrome')
        .build();
    driver.manage().deleteAllCookies();

    const wirecardPaymentLabels = [
        'Wirecard Credit Card',
        'Wirecard PayPal',
        'Wirecard SEPA Direct Debit',
        'Wirecard Sofort.'
    ];

    it('should check the default checkout', async () => {
        await loginWithExampleAccount(driver);

        // Go to a product and buy it
        await driver.get(`${config.url}/genusswelten/tees-und-zubeh/tee-zubehoer/24/glas-teekaennchen`);
        await driver.findElement(By.className('buybox--button')).click();

        // Wait for the cart to be shown
        await driver.wait(until.elementLocated(By.className('button--checkout')));

        // Go to checkout page
        await driver.findElement(By.className('button--checkout')).click();

        // Go to payment selection page select "prepayment"
        await driver.findElement(By.className('btn--change-payment')).click();
        // Check if all wirecard payments are present
        wirecardPaymentLabels.forEach(async paymentLabel => {
            await driver.findElement(By.xpath("//*[contains(text(), '" + paymentLabel + "')]"));
        });
        await driver.findElement(By.xpath("//*[contains(text(), 'Vorkasse')]")).click();

        // Go back to checkout page and test if payment method has been selected
        await waitUntilOverlayIsStale(driver, By.className('js--overlay'));
        await driver.findElement(By.className('main--actions')).click();

        // Check AGB and confirm order
        await driver.findElement(By.id('sAGB')).click();
        await driver.findElement(By.xpath('//button[@form="confirm--form"]')).click();

        await checkConfirmationPage(driver, 'Vorkasse');
    });

    after(async () => driver.quit());
});
