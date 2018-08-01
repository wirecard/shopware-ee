/**
 * Shop System Plugins - Terms of Use
 *
 * The plugins offered are provided free of charge by Wirecard AG and are explicitly not part
 * of the Wirecard AG range of products and services.
 *
 * They have been tested and approved for full functionality in the standard configuration
 * (status on delivery) of the corresponding shop system. They are under General Public
 * License version 3 (GPLv3) and can be used, developed and passed on to third parties under
 * the same terms.
 *
 * However, Wirecard AG does not provide any guarantee or accept any liability for any errors
 * occurring when used in an enhanced, customized shop system configuration.
 *
 * Operation in an enhanced, customized configuration is at your own risk and requires a
 * comprehensive test phase by the user of the plugin.
 *
 * Customers use the plugins at their own risk. Wirecard AG does not guarantee their full
 * functionality neither does Wirecard AG assume liability for any disadvantages related to
 * the use of the plugins. Additionally, Wirecard AG does not guarantee the full functionality
 * for customized shop systems or installed plugins of other vendors of plugins within the same
 * shop system.
 *
 * Customers are responsible for testing the plugin's functionality before starting productive
 * operation.
 *
 * By installing the plugin into the shop system the customer agrees to these terms of use.
 * Please do not use the plugin if you do not agree to these terms of use!
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
        },
        creditCard: {
            label: 'Wirecard Kreditkarte',
            fields: {
                last_name: 'Lastname',
                account_number: '4012000300001003',
                card_security_code: '003'
            }
        },
        creditCardThreeD: {
            label: 'Wirecard Kreditkarte',
            fields: {
                last_name: 'Lastname',
                account_number: '4012000300001003',
                card_security_code: '003'
            },
            password: 'wirecard'
        },
        sepa: {
            label: 'Wirecard SEPA-Lastschrift',
            fields: {
                'wirecardee-sepa--first-name': 'Firstname',
                'wirecardee-sepa--last-name': 'Lastname',
                'wirecardee-sepa--iban': 'DE42512308000000060004'
            }
        },
        sofort: {
            label: 'Wirecard Sofort.',
            fields: {
                bankCode: '00000',
                userId: '1234',
                password: 'passwd',
                tan: '12345'
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

const OSX = {
    name: 'OS X',
    versions: {
        highSierra: 'High Sierra', // 10.13
        sierra: 'Sierra' // 10.12
    }
};

const CHROME = {
    name: 'Chrome',
    currentVersion: '68.0'
};

const FIREFOX = {
    name: 'Firefox',
    currentVersion: '62.0'
};

const OPERA = {
    name: 'Opera',
    currentVersion: '12.16'
};

const IE = {
    name: 'IE',
    versions: {
        ie8: '8.0',
        ie9: '9.0',
        ie10: '10.0',
        ie11: '11.0'
    }
};

const SAFARI = {
    name: 'Safari',
    versions: {
        v11_1: '11.1', // Current, only available for High Sierra
        v10_1: '10.1' // Only available for Sierra
    }
};

const ANDROID_7_DEVICE = {
    name: 'Samsung Galaxy S8',
    version: '7.0'
};

const ANDROID_8_DEVICE = {
    name: 'Samsung Galaxy S9',
    version: '8.0'
};

const IOS_10_DEVICE = {
    name: 'iPhone 7',
    version: '10.3'
};

const IOS_11_DEVICE = {
    name: 'iPhone 8',
    version: '11.0'
};

const DEFAULT_RESOLUTION = '1920x1080';

exports.browsers = [
    // WINDOWS
    {
        browserName: CHROME.name,
        browser_version: CHROME.currentVersion,
        os: WINDOWS.name,
        os_version: WINDOWS.versions.win10,
        resolution: DEFAULT_RESOLUTION
    },
    {
        browserName: FIREFOX.name,
        browser_version: FIREFOX.currentVersion,
        os: WINDOWS.name,
        os_version: WINDOWS.versions.win8,
        resolution: DEFAULT_RESOLUTION
    },
    {
        browserName: OPERA.name,
        browser_version: OPERA.currentVersion,
        os: WINDOWS.name,
        os_version: WINDOWS.versions.win8,
        resolution: DEFAULT_RESOLUTION
    },
    {
        browserName: IE.name,
        browser_version: IE.versions.ie8,
        os: WINDOWS.name,
        os_version: WINDOWS.versions.win7,
        resolution: DEFAULT_RESOLUTION
    },
    {
        browserName: IE.name,
        browser_version: IE.versions.ie9,
        os: WINDOWS.name,
        os_version: WINDOWS.versions.win7,
        resolution: DEFAULT_RESOLUTION
    },
    {
        browserName: IE.name,
        browser_version: IE.versions.ie10,
        os: WINDOWS.name,
        os_version: WINDOWS.versions.win7,
        resolution: DEFAULT_RESOLUTION
    },
    {
        browserName: IE.name,
        browser_version: IE.versions.ie11,
        os: WINDOWS.name,
        os_version: WINDOWS.versions.win7,
        resolution: DEFAULT_RESOLUTION
    },
    // APPLE
    {
        browserName: CHROME.name,
        browser_version: CHROME.currentVersion,
        os: OSX.name,
        os_version: OSX.versions.highSierra,
        resolution: DEFAULT_RESOLUTION
    },
    {
        browserName: CHROME.name,
        browser_version: CHROME.currentVersion,
        os: OSX.name,
        os_version: OSX.versions.sierra,
        resolution: DEFAULT_RESOLUTION
    },
    {
        browserName: SAFARI.name,
        browser_version: SAFARI.versions.v11_1,
        os: OSX.name,
        os_version: OSX.versions.highSierra,
        resolution: DEFAULT_RESOLUTION
    },
    {
        browserName: SAFARI.name,
        browser_version: SAFARI.versions.v10_1,
        os: OSX.name,
        os_version: OSX.versions.sierra,
        resolution: DEFAULT_RESOLUTION
    },
    // MOBILE: ANDROID
    {
        os: ANDROID_7_DEVICE.name,
        os_version: ANDROID_7_DEVICE.version,
        real_mobile: 'true'
    },
    {
        os: ANDROID_8_DEVICE.name,
        os_version: ANDROID_8_DEVICE.version,
        real_mobile: 'true'
    },
    {
        os: ANDROID_8_DEVICE.name,
        os_version: ANDROID_8_DEVICE.version,
        real_mobile: 'true',
        deviceOrientation: 'landscape'
    },
    // MOBILE: iOS
    {
        os: IOS_10_DEVICE.name,
        os_version: IOS_10_DEVICE.version,
        real_mobile: 'true'
    },
    {
        os: IOS_11_DEVICE.name,
        os_version: IOS_11_DEVICE.version,
        real_mobile: 'true'
    },
    {
        os: IOS_11_DEVICE.name,
        os_version: IOS_11_DEVICE.version,
        real_mobile: 'true',
        deviceOrientation: 'landscape'
    }
];

/**
 * List of tests to be executed. All tests must be located in `./Tests/Selenium`.
 */
exports.tests = [
    {
        file: 'Payments/DefaultTest',
        timeout: 120000
    },
    {
        file: 'Payments/PaypalTest',
        timeout: 180000
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
        file: 'Payments/SepaTest',
        timeout: 120000
    },
    {
        file: 'Payments/SofortTest',
        timeout: 120000
    }
];
