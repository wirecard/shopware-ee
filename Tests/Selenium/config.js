/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
 */

exports.config = {
    url: 'http://localhost:8000',
    exampleAccount: {
        email: 'test@example.com',
        password: 'shopware'
    },
    payments: {
        paypal: {
            label: 'Wirecard PayPal',
            fields: {
                email: 'paypal.shopware.buyer@wirecard.com',
                password: 'Wirecardbuyer'
            }
        }
    }
};

/**
 * List of browsers to test against.
 * See https://www.browserstack.com/automate/capabilities
 */
const WINDOWS = {
    name: 'Windows',
    versions: {
        win10: '10',
        win8: '8',
        win7: '7'
    }
};

const CHROME = {
    name: 'Chrome',
    currentVersion: '72.0'
};

const DEFAULT_RESOLUTION = '1920x1080';

exports.browsers = [
    {
        browserName: CHROME.name,
        browser_version: CHROME.currentVersion,
        os: WINDOWS.name,
        os_version: WINDOWS.versions.win10,
        resolution: DEFAULT_RESOLUTION
    }
];

/**
 * List of tests to be executed. All tests must be located in `./Tests/Selenium`.
 */
exports.apiTests = [
    {
        file: 'Payments/DefaultTest',
        timeout: 120000
    },
    {
        file: 'Payments/CreditCardTest',
        timeout: 120000
    },
    {
        file: 'Payments/CreditCardThreeDTest',
        timeout: 120000
    },
    {
        file: 'Payments/AlipayTest',
        timeout: 120000
    },
    {
        file: 'Payments/RatepayTest',
        timeout: 120000
    },
    {
        file: 'Payments/IdealTest',
        timeout: 120000
    },
    // {
    //     file: 'Payments/MasterpassTest',
    //     timeout: 120000
    // },
    {
        file: 'Payments/PaypalTest',
        timeout: 180000
    },
    {
        file: 'Payments/PiaTest',
        timeout: 90000
    },
    {
        file: 'Payments/PoiTest',
        timeout: 90000
    },
    {
        file: 'Payments/SepaTest',
        timeout: 90000
    },
    {
        file: 'Payments/SofortTest',
        timeout: 120000
    },
    {
        file: 'Payments/UpiTest',
        timeout: 120000
    }
];

exports.novaTests = [
    {
        file: 'Payments/CreditCardThreeDTest',
        timeout: 120000
    }
];
