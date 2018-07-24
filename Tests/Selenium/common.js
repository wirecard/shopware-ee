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
const { By, until } = require('selenium-webdriver');
const { config } = require('./config');

exports.loginWithExampleAccount = async function (driver) {
    await driver.get(`${config.url}/account`);
    await driver.wait(until.elementLocated(By.name('email')));
    await driver.findElement(By.name('email')).sendKeys(config.exampleAccount.email);
    await driver.findElement(By.name('password')).sendKeys(config.exampleAccount.password);
    await driver.findElement(By.className('register--login-btn')).click();

    // Check if login has succeeded
    await driver.wait(until.elementLocated(By.className('account--welcome')));
};

exports.checkConfirmationPage = async function (driver, paymentLabel) {
    await driver.wait(until.elementLocated(By.className('teaser--btn-print')));
    const panelTitle = await driver.findElement(By.className('panel--title')).getText();
    expect(panelTitle).to.include('Vielen Dank');
    const paymentContent = await driver.findElement(By.className('payment--content')).getText();
    expect(paymentContent).to.include(paymentLabel);
};

exports.waitUntilOverlayIsNotVisible = async function (driver, locator) {
    const overlay = await driver.findElements(locator);
    if (overlay.length) {
        await driver.wait(until.elementIsNotVisible(overlay[0]));
    }
};

exports.waitUntilOverlayIsStale = async function (driver, locator) {
    const overlay = await driver.findElements(locator);
    if (overlay.length) {
        await driver.wait(until.stalenessOf(overlay[0]));
    }
};
