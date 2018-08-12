/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

/* eslint-env mocha */

const { By } = require('selenium-webdriver');
const { config } = require('../config');
const {
    loginWithExampleAccount,
    checkConfirmationPage,
    addProductToCartAndGotoCheckout,
    selectPaymentMethod,
    getDriver
} = require('../common');

describe('Wirecard Guaranteed Invoice test', () => {
    const driver = getDriver();

    const paymentLabel = config.payments.ratepay.label;

    it('should check the ratepay invoice process', async () => {
        await loginWithExampleAccount(driver);
        await addProductToCartAndGotoCheckout(driver, '/genusswelten/tees-und-zubeh/tee-zubehoer/24/glas-teekaennchen');
        await addProductToCartAndGotoCheckout(driver, '/genusswelten/tees-und-zubeh/tee-zubehoer/24/glas-teekaennchen');
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
