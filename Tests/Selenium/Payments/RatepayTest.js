/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

/* eslint-env mocha */

const { By, until } = require('selenium-webdriver');
const { config } = require('../config');
const {
    loginWithExampleAccount,
    checkConfirmationPage,
    addProductToCartAndGotoCheckout,
    selectPaymentMethod,
    waitUntilOverlayIsStale,
    getDriver
} = require('../common');

describe('Guaranteed Invoice by Wirecard test', () => {
    const driver = getDriver();

    const paymentLabel = config.payments.ratepay.label;

    it('should check the ratepay invoice process', async () => {
        await loginWithExampleAccount(driver);
        await addProductToCartAndGotoCheckout(driver, '/genusswelten/tees-und-zubeh/tee-zubehoer/24/glas-teekaennchen');

        console.log('change shipping address');
        await driver.findElement(By.css('.information--panel-item-shipping a[data-address-selection="true"]')).click();
        await waitUntilOverlayIsStale(driver, By.className('js--overlay'));
        console.log('wait for .address-manager--selection-form');
        await driver.wait(until.elementLocated(By.css('.address-manager--selection-form')));
        await driver.findElement(By.css('.address-manager--selection-form button')).click();

        console.log('wait for .choose-different-address');
        await driver.wait(until.elementLocated(By.className('choose-different-address')));

        await selectPaymentMethod(driver, paymentLabel);

        // Select birthday
        console.log('select birthday 1/1/2000 option');
        await driver.findElement(By.css('.wirecardee--birthday-year option[value=\'2000\']')).click();

        // Confirm order
        console.log('click button confirm--form');
        await driver.findElement(By.xpath('//button[@form="confirm--form"]')).click();

        await checkConfirmationPage(driver, paymentLabel);
    });

    after(async () => driver.quit());
});
