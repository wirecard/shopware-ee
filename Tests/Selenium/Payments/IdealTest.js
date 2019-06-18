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
    getDriver
} = require('../common');

let driver;

describe('iDEAL test', () => {
    before(async () => {
        driver = await getDriver('ideal');
    });

    const paymentLabel = config.payments.ideal.label;
    const idealBank = config.payments.ideal.fields.bank;

    it('should check the ideal payment process', async () => {
        await loginWithExampleAccount(driver);
        await addProductToCartAndGotoCheckout(driver, '/genusswelten/tees-und-zubeh/tee-zubehoer/24/glas-teekaennchen');
        await selectPaymentMethod(driver, paymentLabel);

        // Select ideal bank
        console.log('select ' + idealBank + ' option');
        await driver.findElement(By.css('option[value=\'' + idealBank + '\']')).click();

        // Confirm order
        console.log('click button confirm--form');
        await driver.findElement(By.xpath('//button[@form="confirm--form"]')).click();

        console.log('wait for form .btnLink');
        await driver.wait(until.elementLocated(By.css('form .btnLink')), 20000);
        await driver.findElement(By.css('form .btnLink')).click();

        await checkConfirmationPage(driver, paymentLabel);
    });

    after(async () => driver.quit());
});
