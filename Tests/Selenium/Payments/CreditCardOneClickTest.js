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

/**
 * Credit Card One-click has to be enabled and a token must already exist (change config tokenId)
 */
describe('Credit Card One-Click Checkout test (NOTE: has requirements)', () => {
    const driver = getDriver();

    const paymentLabel = config.payments.creditCardOneClick.label;
    const tokenId = config.payments.creditCardOneClick.tokenId;

    it('should check the credit card one-click payment process', async () => {
        await loginWithExampleAccount(driver);
        await addProductToCartAndGotoCheckout(driver, '/genusswelten/tees-und-zubeh/tee-zubehoer/24/glas-teekaennchen');
        await selectPaymentMethod(driver, paymentLabel);

        await driver.findElement(By.id('wirecardee--token-' + tokenId)).click();

        // Confirm order
        console.log('click button confirm--form');
        await driver.findElement(By.xpath('//button[@form="confirm--form"]')).click();

        await checkConfirmationPage(driver, paymentLabel);
    });

    after(async () => driver.quit());
});
