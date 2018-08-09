/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

/* eslint-env mocha */

const { expect } = require('chai');
const { By } = require('selenium-webdriver');
const { config } = require('../config');
const {
    loginWithExampleAccount,
    checkConfirmationPage,
    addProductToCartAndGotoCheckout,
    selectPaymentMethod,
    getDriver
} = require('../common');

describe('Payment in Advance test', () => {
    const driver = getDriver();

    const paymentLabel = config.payments.pia.label;

    it('should check the pia payment process', async () => {
        await loginWithExampleAccount(driver);
        await addProductToCartAndGotoCheckout(driver, '/genusswelten/tees-und-zubeh/tee-zubehoer/24/glas-teekaennchen');
        await selectPaymentMethod(driver, paymentLabel);

        // Confirm order
        console.log('click button confirm--form');
        await driver.findElement(By.xpath('//button[@form="confirm--form"]')).click();

        await checkConfirmationPage(driver, paymentLabel);

        console.log('expect correct bankdata');
        const amount = await driver.findElement(By.className('wirecardee--bankdata-amount')).getText();
        expect(amount).to.include('70,99 €');
        const iban = await driver.findElement(By.className('wirecardee--bankdata-iban')).getText();
        expect(iban).to.include('DE82512308000005599148');
        const bic = await driver.findElement(By.className('wirecardee--bankdata-bic')).getText();
        expect(bic).to.include('WIREDEMMXXX');
    });

    after(async () => driver.quit());
});
