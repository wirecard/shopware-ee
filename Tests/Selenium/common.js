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

const { expect } = require('chai');
const { Builder, By, until } = require('selenium-webdriver');
const { config } = require('./config');

exports.loginWithExampleAccount = async function (driver) {
    await driver.manage().deleteAllCookies();

    console.log(`get ${config.url}/account`);
    await driver.get(`${config.url}/account`);
    console.log('wait for [email]');
    await driver.wait(until.elementLocated(By.name('email')));
    await driver.findElement(By.name('email')).sendKeys(config.exampleAccount.email);
    await driver.findElement(By.name('password')).sendKeys(config.exampleAccount.password);
    await driver.findElement(By.className('register--login-btn')).click();

    // Check if login has succeeded
    try {
        console.log('wait for .account--welcome');
        await driver.wait(until.elementLocated(By.className('account--welcome')), 5000);
        console.log('login successful');
    } catch (e) {
        console.log('login failed, current url: ' + await driver.getCurrentUrl());
        console.log(`get ${config.url}/account/logout`);
        await driver.get(`${config.url}/account/logout`);
        driver.manage().deleteAllCookies();
        console.log(`retry: get ${config.url}/account/login`);
        await driver.get(`${config.url}/account/login`);
        console.log('wait for [email]');
        await driver.wait(until.elementLocated(By.name('email')), 5000);
        await driver.findElement(By.name('email')).sendKeys(config.exampleAccount.email);
        await driver.findElement(By.name('password')).sendKeys(config.exampleAccount.password);
        await driver.findElement(By.className('register--login-btn')).click();
        console.log('wait for .account--welcome');
        try {
            await driver.wait(until.elementLocated(By.className('account--welcome')), 5000);
            console.log('login successful');
        } catch (e) {
            console.log('login still failing, try to continue');
        }
    }
};

exports.addProductToCartAndGotoCheckout = async function (driver, url) {
    // Go to a product and buy it
    console.log(`get ${config.url}${url}`);
    await driver.get(`${config.url}${url}`);
    await driver.findElement(By.className('buybox--button')).click();

    // Wait for the cart to be shown
    console.log('wait for .button--checkout');
    await driver.wait(until.elementLocated(By.className('button--checkout')));

    // Go to checkout page
    await driver.findElement(By.className('button--checkout')).click();

    console.log('wait for .btn--change-payment');
    await driver.wait(until.elementLocated(By.className('btn--change-payment')));
};

exports.selectPaymentMethod = async function (driver, paymentLabel) {
    // Go to payment selection page, check if wirecard payments are present and select credit card
    await driver.findElement(By.className('btn--change-payment')).click();
    console.log('click "' + paymentLabel + '"');
    await driver.findElement(By.xpath("//*[contains(text(), '" + paymentLabel + "')]")).click();

    // Go back to checkout page and test if payment method has been selected
    await exports.waitUntilOverlayIsStale(driver, By.className('js--overlay'));
    console.log('click .main--actions');
    await driver.findElement(By.className('main--actions')).click();
    const paymentDescription = await driver.findElement(By.className('payment--description')).getText();
    expect(paymentDescription).to.include(paymentLabel);

    // Check AGB
    await driver.findElement(By.id('sAGB')).click();
};

exports.checkConfirmationPage = async function (driver, paymentLabel) {
    console.log('wait for .teaser--btn-print');
    await driver.wait(until.elementLocated(By.className('teaser--btn-print')), 30000);
    console.log('expect content .panel--title');
    const panelTitle = await driver.findElement(By.className('panel--title')).getText();
    expect(panelTitle).to.include('Vielen Dank');
    console.log('expect content .payment--content');
    const paymentContent = await driver.findElement(By.className('payment--content')).getText();
    expect(paymentContent).to.include(paymentLabel);
    console.log('done');
};

exports.waitUntilOverlayIsNotVisible = async function (driver, locator) {
    const overlay = await driver.findElements(locator);
    if (overlay.length) {
        console.log('wait for elementIsNotVisible');
        await driver.wait(until.elementIsNotVisible(overlay[0]));
    }
};

exports.waitUntilOverlayIsStale = async function (driver, locator) {
    const overlay = await driver.findElements(locator);
    if (overlay.length) {
        console.log('wait for staleness');
        await driver.wait(until.stalenessOf(overlay[0]));
    }
};

exports.waitForAlert = async function (driver, timeout) {
    try {
        console.log('wait for alert');
        const alert = await driver.wait(until.alertIsPresent(), timeout);
        console.log('accept alert');
        await alert.accept();
        await driver.switchTo().defaultContent();
    } catch (e) {
        console.log('no alert popup');
    }
};

exports.asyncForEach = async function (arr, cb) {
    for (let i = 0; i < arr.length; i++) {
        await cb(arr[i], i, arr);
    }
};

exports.getDriver = () => {
    if (global.driver) {
        return global.driver;
    }

    return new Builder()
        .forBrowser('chrome')
        .build();
};
