/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

/* eslint-env mocha */

const { By } = require('selenium-webdriver');
const {
    loginWithExampleAccount,
    waitUntilOverlayIsStale,
    checkConfirmationPage,
    addProductToCartAndGotoCheckout,
    asyncForEach,
    getDriver
} = require('../common');

describe('default test', () => {
    const driver = getDriver();

    const wirecardPaymentLabels = [
        'Wirecard Kreditkarte',
        'Wirecard PayPal',
        'Wirecard SEPA-Lastschrift',
        'Wirecard Sofort.'
    ];

    it('should check the default checkout', async () => {
        await loginWithExampleAccount(driver);
        await addProductToCartAndGotoCheckout(driver, '/genusswelten/tees-und-zubeh/tee-zubehoer/24/glas-teekaennchen');

        // Go to payment selection page select "prepayment"
        await driver.findElement(By.className('btn--change-payment')).click();
        // Check if all wirecard payments are present
        await asyncForEach(wirecardPaymentLabels, async paymentLabel => {
            await driver.findElement(By.xpath("//*[contains(text(), '" + paymentLabel + "')]"));
        });
        await driver.findElement(By.xpath("//*[contains(text(), 'Vorkasse')]")).click();

        // Go back to checkout page and test if payment method has been selected
        await waitUntilOverlayIsStale(driver, By.className('js--overlay'));
        await driver.findElement(By.className('main--actions')).click();

        // Check AGB and confirm order
        await driver.findElement(By.id('sAGB')).click();
        console.log('click button confirm--form');
        await driver.findElement(By.xpath('//button[@form="confirm--form"]')).click();

        await checkConfirmationPage(driver, 'Vorkasse');
    });

    after(async () => driver.quit());
});
