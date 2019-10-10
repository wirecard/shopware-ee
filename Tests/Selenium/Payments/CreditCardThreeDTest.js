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
    getDriver,
    asyncForEach,
    waitForAlert,
    updateDatabaseTransactionType,
    checkTransactionTypeInDatabase
} = require('../common');

let driver;

describe('Credit Card 3-D Secure test', () => {
    before(async () => {
        driver = await getDriver('credit card 3ds');
    });

    const paymentLabel = config.payments.creditCardThreeD.label;
    const formFields = config.payments.creditCardThreeD.fields;

    it('should check the credit card 3ds payment process', async () => {
        await updateDatabaseTransactionType('pay', 'wirecardElasticEngineCreditCardTransactionType');
        await loginWithExampleAccount(driver);
        await addProductToCartAndGotoCheckout(driver, '/wohnwelten/moebel/68/kommode-shabby-chic');
        await selectPaymentMethod(driver, paymentLabel);

        try {
            // Do not use saved card
            console.log('click #wirecardee--token-no-card');
            await driver.findElement(By.id('wirecardee--token-no-card')).click();

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
                await driver.findElement(By.id(field)).sendKeys(formFields[field]);
            });
            await driver.findElement(By.css('#expiration_month_list > option[value=\'01\']')).click();
            await driver.findElement(By.css('#expiration_year_list > option[value=\'2023\']')).click();

            // Switch back from iframe and click Send button
            console.log('switch back from iframe to default content');
            await driver.switchTo().defaultContent();
            console.log('wait for #wirecardee-credit-card--form-submit');
            await driver.wait(until.elementLocated(By.id('wirecardee-credit-card--form-submit')), 10000);
            console.log('click #wirecardee-credit-card--form-submit');
            await driver.findElement(By.id('wirecardee-credit-card--form-submit')).click();

            console.log('wait for #password');
            await driver.wait(until.elementLocated(By.id('password')), 20000);
            await driver.findElement(By.id('password')).sendKeys(config.payments.creditCardThreeD.password, Key.ENTER);

            await waitForAlert(driver, 20000);

            await checkConfirmationPage(driver, paymentLabel);
        } catch (e) {
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
                await driver.findElement(By.id(field)).sendKeys(formFields[field]);
            });
            await driver.findElement(By.css('#expiration_month_list > option[value=\'01\']')).click();
            await driver.findElement(By.css('#expiration_year_list > option[value=\'2023\']')).click();

            // Switch back from iframe and click Send button
            console.log('switch back from iframe to default content');
            await driver.switchTo().defaultContent();
            console.log('wait for #wirecardee-credit-card--form-submit');
            await driver.wait(until.elementLocated(By.id('wirecardee-credit-card--form-submit')), 10000);
            console.log('click #wirecardee-credit-card--form-submit');
            await driver.findElement(By.id('wirecardee-credit-card--form-submit')).click();

            console.log('wait for #password');
            await driver.wait(until.elementLocated(By.id('password')), 20000);
            await driver.findElement(By.id('password')).sendKeys(config.payments.creditCardThreeD.password, Key.ENTER);

            await waitForAlert(driver, 20000);

            await checkConfirmationPage(driver, paymentLabel);
        }
        checkTransactionTypeInDatabase('purchase');
    });

    after(async () => driver.quit());
});
