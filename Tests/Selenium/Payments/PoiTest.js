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

describe('Payment on Invoice test', () => {
    const driver = getDriver();

    const paymentLabel = config.payments.poi.label;

    it('should check the poi payment process', async () => {
        await loginWithExampleAccount(driver);
        await addProductToCartAndGotoCheckout(driver, '/genusswelten/tees-und-zubeh/tee-zubehoer/24/glas-teekaennchen');
        await selectPaymentMethod(driver, paymentLabel);

        // Confirm order
        console.log('click button confirm--form');
        await driver.findElement(By.xpath('//button[@form="confirm--form"]')).click();

        await checkConfirmationPage(driver, paymentLabel);
    });

    after(async () => driver.quit());
});
