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
    getDriver,
    asyncForEach
} = require('../common');

let driver;

describe('SEPA Direct Debit test', () => {
    before(async () => {
        driver = await getDriver('sepa');
    });

    const paymentLabel = config.payments.sepa.label;
    const formFields = config.payments.sepa.fields;

    it('should check the sepa payment process', async () => {
        await loginWithExampleAccount(driver);
        await addProductToCartAndGotoCheckout(driver, '/genusswelten/tees-und-zubeh/tee-zubehoer/24/glas-teekaennchen');
        await selectPaymentMethod(driver, paymentLabel);

        // Fill sepa fields
        await asyncForEach(Object.keys(formFields), async field => {
            await driver.findElement(By.id(field)).sendKeys(formFields[field]);
        });

        // Confirm order
        console.log('click button confirm--form');
        await driver.findElement(By.xpath('//button[@form="confirm--form"]')).click();

        // Confirm sepa mandate in modal dialog
        console.log('wait for .wirecardee--sepa-mandate');
        await driver.wait(until.elementLocated(By.className('wirecardee--sepa-mandate')), 10000);
        console.log('click .wirecardee-sepa--confirm-check');
        await driver.findElement(By.id('wirecardee-sepa--confirm-check')).click();
        console.log('click .wirecardee-sepa--confirm-button');
        await driver.findElement(By.id('wirecardee-sepa--confirm-button')).click();

        await checkConfirmationPage(driver, paymentLabel);
    });

    after(async () => driver.quit());
});
