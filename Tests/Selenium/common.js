/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

const { expect } = require('chai');
const { Builder, By, until } = require('selenium-webdriver');
const { config, browsers } = require('./config');

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
    await driver.findElement(By.className('btn is--small btn--change-payment')).click();
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
    console.log('expect content h2.panel--title');
    const panelTitle = await driver.findElement(By.css('h2.panel--title')).getText();
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

exports.getDriver = async (testCase = 'generic') => {
    if (global.driver) {
        return global.driver;
    }

    const browser = browsers[0];
    const bsConfig = Object.assign({
        'browserstack.user': process.env.BROWSERSTACK_USER,
        'browserstack.key': process.env.BROWSERSTACK_KEY,
        'browserstack.local': 'true',
        'browserstack.localIdentifier': process.env.BROWSERSTACK_LOCAL_IDENTIFIER
    }, browser);

    let builder = await new Builder()
        .usingServer('http://hub-cloud.browserstack.com/wd/hub')
        .withCapabilities(Object.assign({
            name: testCase,
            build: process.env.TRAVIS ? `${process.env.TRAVIS_JOB_NUMBER}` : 'local',
            project: `Shopware:WirecardElasticEngine-${process.env.GATEWAY}-${process.env.SHOPWARE_VERSION}`
        }, bsConfig))
        .build();

    return builder;
};
