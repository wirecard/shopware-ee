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
    asyncForEach,
    updateDatabaseTransactionType,
    checkTransactionTypeInDatabase
} = require('../common');

let driver;

describe('Credit Card test', () => {
    before(async () => {
        driver = await getDriver('credit card');
    });

    const paymentLabel = config.payments.creditCard.label;
    const formFields = config.payments.creditCard.fields;

    it('should check the credit card payment process', async () => {
        await updateDatabaseTransactionType('pay', 'wirecardElasticEngineCreditCardTransactionType');
        await loginWithExampleAccount(driver);
        await addProductToCartAndGotoCheckout(driver, '/genusswelten/tees-und-zubeh/tee-zubehoer/24/glas-teekaennchen');
        await selectPaymentMethod(driver, paymentLabel);

        // Confirm order
        console.log('click button confirm--form');
        await driver.findElement(By.xpath('//button[@form="confirm--form"]')).click();

        // Fill out credit card iframe
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
        await driver.findElement(By.css('#expiration_month_list > option[value=\'01\']')).click();
        await driver.findElement(By.css('#expiration_year_list > option[value=\'2030\']')).click();

        // Switch back from iframe and click Send button
        console.log('switch back from iframe to default content');
        await driver.switchTo().defaultContent();
        console.log('wait for #wirecardee-credit-card--form-submit');
        await driver.wait(until.elementLocated(By.id('wirecardee-credit-card--form-submit')));
        console.log('click #wirecardee-credit-card--form-submit');
        await driver.findElement(By.id('wirecardee-credit-card--form-submit')).click();

        await checkConfirmationPage(driver, paymentLabel);
        checkTransactionTypeInDatabase('purchase');
    });

    after(async () => driver.quit());
});
