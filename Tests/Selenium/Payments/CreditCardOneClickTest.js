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

describe('Credit Card One-Click Checkout test', () => {
    before(async () => {
        driver = await getDriver('credit card one click');
    });

    const paymentLabel = config.payments.creditCardOneClick.label;
    const tokenId = config.payments.creditCardOneClick.tokenId;
    const formFields = config.payments.creditCardOneClick.fields;

    it('should check the credit card one-click payment process', async () => {
        await loginWithExampleAccount(driver);
        await addProductToCartAndGotoCheckout(driver, '/genusswelten/tees-und-zubeh/tee-zubehoer/24/glas-teekaennchen');
        await selectPaymentMethod(driver, paymentLabel);

        // Make a purchase using new credit card
        console.log('click #wirecardee--save-token');
        await driver.findElement(By.id('wirecardee--save-token')).click();

        console.log('click button confirm--form');
        await driver.findElement(By.xpath('//button[@form="confirm--form"]')).click();

        console.log('wait for .wirecard-seamless-frame');
        await driver.wait(until.elementLocated(By.className('wirecard-seamless-frame')), 20000);
        console.log('wait for switch to iframe .wirecard-seamless-frame');
        await driver.wait(until.ableToSwitchToFrame(By.className('wirecard-seamless-frame')));
        console.log('wait for #account_number');
        await driver.wait(until.elementLocated(By.id('account_number')), 20000);
        await asyncForEach(Object.keys(formFields), async field => {
            console.log(`setting ${field} to ${formFields[field]}`);
            await driver.findElement(By.id(field)).sendKeys(formFields[field]);
        });

        console.log('switch back from iframe to default content');
        await driver.switchTo().defaultContent();
        console.log('wait for #wirecardee-credit-card--form-submit');
        await driver.wait(until.elementLocated(By.id('wirecardee-credit-card--form-submit')));
        console.log('click #wirecardee-credit-card--form-submit');
        await driver.findElement(By.id('wirecardee-credit-card--form-submit')).click();

        await checkConfirmationPage(driver, paymentLabel);

        // Make a purchase using existing credit card
        await loginWithExampleAccount(driver);
        await addProductToCartAndGotoCheckout(driver, '/genusswelten/tees-und-zubeh/tee-zubehoer/24/glas-teekaennchen');
        await selectPaymentMethod(driver, paymentLabel);

        await driver.findElement(By.id('wirecardee--token-' + tokenId)).click();

        console.log('click button confirm--form');
        await driver.findElement(By.xpath('//button[@form="confirm--form"]')).click();

        await checkConfirmationPage(driver, paymentLabel);
    });

    after(async () => driver.quit());
});
