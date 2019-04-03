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
    getDriver
} = require('../common');

describe('Masterpass test', () => {
    const driver = getDriver();

    const paymentLabel = config.payments.masterpass.label;
    const formFields = config.payments.masterpass.fields;

    it('should check the masterpass payment process', async () => {
        await loginWithExampleAccount(driver);
        await addProductToCartAndGotoCheckout(driver, '/genusswelten/tees-und-zubeh/tee-zubehoer/24/glas-teekaennchen');
        await selectPaymentMethod(driver, paymentLabel);

        // Confirm order
        console.log('click button confirm--form');
        await driver.findElement(By.xpath('//button[@form="confirm--form"]')).click();

        // Wait for Masterpass page, change wallet and fill out forms

        console.log('wait for #MasterPass_frame');
        await driver.wait(until.elementLocated(By.id('MasterPass_frame')), 20000);
        console.log('switch to iframe #MasterPass_frame');
        await driver.wait(until.ableToSwitchToFrame(By.id('MasterPass_frame')));
        await driver.sleep(1000);

        // will fail for mobile!
        console.log('wait for .container-wallet-collision.visible-sm-inline-block .link');
        await driver.wait(until.elementLocated(By.css('.container-wallet-collision.visible-sm-inline-block .link')), 20000);
        await driver.findElement(By.css('.container-wallet-collision.visible-sm-inline-block .link')).click();
        await driver.sleep(1000);

        // will fail for mobile!
        console.log('wait for .locale-selector.visible-sm-inline-block .locale-selector-current');
        await driver.wait(until.elementLocated(By.css('.locale-selector.visible-sm-inline-block .locale-selector-current')), 20000);
        await driver.findElement(By.css('.locale-selector.visible-sm-inline-block .locale-selector-current')).click();
        await driver.sleep(1000);

        console.log('wait for a[data-locale-selector="de-DE"]');
        await driver.wait(until.elementLocated(By.css('a[data-locale-selector="de-DE"]')), 20000);
        await driver.findElement(By.css('a[data-locale-selector="de-DE"]')).click();
        await driver.sleep(1000);

        console.log('wait for div[data-automation="MasterpassDESBX"]');
        await driver.wait(until.elementLocated(By.css('div[data-automation="MasterpassDESBX"]')), 20000);
        await driver.findElement(By.css('div[data-automation="MasterpassDESBX"]')).click();
        await driver.sleep(1000);

        console.log('wait for #wallet');
        await driver.wait(until.elementLocated(By.id('wallet')), 20000);
        console.log('switch to iframe #wallet');
        await driver.wait(until.ableToSwitchToFrame(By.id('wallet')));

        console.log('wait for input[name="login"]');
        await driver.wait(until.elementLocated(By.css('input[name="login"]')), 20000);
        await driver.findElement(By.css('input[name="login"]')).sendKeys(formFields.email);
        await driver.findElement(By.css('input[name="password"]')).sendKeys(formFields.password, Key.ENTER);

        await driver.wait(until.elementLocated(By.css('button[type="submit"]')), 20000);
        await driver.findElement(By.css('button[type="submit"]')).click();

        console.log('switch back from iframe to default content');
        await driver.switchTo().defaultContent();

        await checkConfirmationPage(driver, paymentLabel);
    });

    after(async () => driver.quit());
});
