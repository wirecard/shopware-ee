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
        creditCardOneClick: {
            label: 'Wirecard Kreditkarte',
            tokenId: '1'
        },
        alipay: {
            label: 'Wirecard Alipay Cross-border',
            fields: {
                email: 'alipaytest20091@gmail.com',
                password: '111111',
                paymentPasswordDigit: '1'
            }
        },
        ratepay: {
            label: 'Wirecard Garantierter Kauf auf Rechnung'
        },
        ideal: {
            label: 'Wirecard iDEAL',
            fields: {
                bank: 'INGBNL2A'
            }
        },
        masterpass: {
            label: 'Wirecard Masterpass',
            fields: {
                email: 'masterpass@mailadresse.net',
                password: 'WirecardPass42'
            }
        },
        paypal: {
            label: 'Wirecard PayPal',
            fields: {
                email: 'paypal.shopware.buyer@wirecard.com',
                password: 'Wirecardbuyer'
            }
        },
        pia: {
            label: 'Wirecard Vorkasse'
        },
        poi: {
            label: 'Wirecard Kauf auf Rechnung'
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
        },
        upi: {
            label: 'Wirecard UnionPay International',
            fields: {
                last_name: 'Lastname',
                account_number: '6210943123456786'
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
//
// const OSX = {
//     name: 'OS X',
//     versions: {
//         highSierra: 'High Sierra', // 10.13
//         sierra: 'Sierra' // 10.12
//     }
// };

const CHROME = {
    name: 'Chrome',
    currentVersion: '68.0'
};

// const FIREFOX = {
//     name: 'Firefox',
//     currentVersion: '62.0'
// };
//
// const OPERA = {
//     name: 'Opera',
//     currentVersion: '12.16'
// };
//
// const IE = {
//     name: 'IE',
//     versions: {
//         ie8: '8.0',
//         ie9: '9.0',
//         ie10: '10.0',
//         ie11: '11.0'
//     }
// };
//
// const SAFARI = {
//     name: 'Safari',
//     versions: {
//         v11_1: '11.1', // Current, only available for High Sierra
//         v10_1: '10.1' // Only available for Sierra
//     }
// };
//
// const ANDROID_7_DEVICE = {
//     name: 'Samsung Galaxy S8',
//     version: '7.0'
// };
//
// const ANDROID_8_DEVICE = {
//     name: 'Samsung Galaxy S9',
//     version: '8.0'
// };
//
// const IOS_10_DEVICE = {
//     name: 'iPhone 7',
//     version: '10.3'
// };
//
// const IOS_11_DEVICE = {
//     name: 'iPhone 8',
//     version: '11.0'
// };

const DEFAULT_RESOLUTION = '1920x1080';

exports.browsers = [
    // WINDOWS
    {
        browserName: CHROME.name,
        browser_version: CHROME.currentVersion,
        os: WINDOWS.name,
        os_version: WINDOWS.versions.win10,
        resolution: DEFAULT_RESOLUTION
    }
    // {
    //     browserName: FIREFOX.name,
    //     browser_version: FIREFOX.currentVersion,
    //     os: WINDOWS.name,
    //     os_version: WINDOWS.versions.win8,
    //     resolution: DEFAULT_RESOLUTION
    // },
    // {
    //     browserName: OPERA.name,
    //     browser_version: OPERA.currentVersion,
    //     os: WINDOWS.name,
    //     os_version: WINDOWS.versions.win8,
    //     resolution: DEFAULT_RESOLUTION
    // },
    // {
    //     browserName: IE.name,
    //     browser_version: IE.versions.ie8,
    //     os: WINDOWS.name,
    //     os_version: WINDOWS.versions.win7,
    //     resolution: DEFAULT_RESOLUTION
    // },
    // {
    //     browserName: IE.name,
    //     browser_version: IE.versions.ie9,
    //     os: WINDOWS.name,
    //     os_version: WINDOWS.versions.win7,
    //     resolution: DEFAULT_RESOLUTION
    // },
    // {
    //     browserName: IE.name,
    //     browser_version: IE.versions.ie10,
    //     os: WINDOWS.name,
    //     os_version: WINDOWS.versions.win7,
    //     resolution: DEFAULT_RESOLUTION
    // },
    // {
    //     browserName: IE.name,
    //     browser_version: IE.versions.ie11,
    //     os: WINDOWS.name,
    //     os_version: WINDOWS.versions.win7,
    //     resolution: DEFAULT_RESOLUTION
    // },
    // // APPLE
    // {
    //     browserName: CHROME.name,
    //     browser_version: CHROME.currentVersion,
    //     os: OSX.name,
    //     os_version: OSX.versions.highSierra,
    //     resolution: DEFAULT_RESOLUTION
    // },
    // {
    //     browserName: CHROME.name,
    //     browser_version: CHROME.currentVersion,
    //     os: OSX.name,
    //     os_version: OSX.versions.sierra,
    //     resolution: DEFAULT_RESOLUTION
    // },
    // {
    //     browserName: SAFARI.name,
    //     browser_version: SAFARI.versions.v11_1,
    //     os: OSX.name,
    //     os_version: OSX.versions.highSierra,
    //     resolution: DEFAULT_RESOLUTION
    // },
    // {
    //     browserName: SAFARI.name,
    //     browser_version: SAFARI.versions.v10_1,
    //     os: OSX.name,
    //     os_version: OSX.versions.sierra,
    //     resolution: DEFAULT_RESOLUTION
    // },
    // // MOBILE: ANDROID
    // {
    //     browserName: CHROME.name,
    //     os: ANDROID_7_DEVICE.name,
    //     os_version: ANDROID_7_DEVICE.version,
    //     real_mobile: 'true'
    // },
    // {
    //     browserName: CHROME.name,
    //     os: ANDROID_8_DEVICE.name,
    //     os_version: ANDROID_8_DEVICE.version,
    //     real_mobile: 'true'
    // },
    // {
    //     browserName: CHROME.name,
    //     device: ANDROID_8_DEVICE.name,
    //     os_version: ANDROID_8_DEVICE.version,
    //     real_mobile: 'true',
    //     deviceOrientation: 'landscape'
    // },
    // // MOBILE: iOS
    // {
    //     browserName: SAFARI.name,
    //     os: IOS_10_DEVICE.name,
    //     os_version: IOS_10_DEVICE.version,
    //     real_mobile: 'true'
    // },
    // {
    //     browserName: SAFARI.name,
    //     os: IOS_11_DEVICE.name,
    //     os_version: IOS_11_DEVICE.version,
    //     real_mobile: 'true'
    // },
    // {
    //     browserName: SAFARI.name,
    //     os: IOS_11_DEVICE.name,
    //     os_version: IOS_11_DEVICE.version,
    //     real_mobile: 'true',
    //     deviceOrientation: 'landscape'
    // }
];

/**
 * List of tests to be executed. All tests must be located in `./Tests/Selenium`.
 */
exports.tests = [
    // {
    //     file: 'Payments/DefaultTest',
    //     timeout: 120000
    // },
    // {
    //     file: 'Payments/CreditCardTest',
    //     timeout: 120000
    // },
    {
        file: 'Payments/CreditCardThreeDTest',
        timeout: 120000
    }
    // {
    //     file: 'Payments/AlipayTest',
    //     timeout: 120000
    // },
    // {
    //     file: 'Payments/RatepayTest',
    //     timeout: 120000
    // },
    // {
    //     file: 'Payments/IdealTest',
    //     timeout: 120000
    // },
    // {
    //     file: 'Payments/MasterpassTest',
    //     timeout: 120000
    // },
    // {
    //     file: 'Payments/PaypalTest',
    //     timeout: 180000
    // },
    // {
    //     file: 'Payments/PiaTest',
    //     timeout: 90000
    // },
    // {
    //     file: 'Payments/PoiTest',
    //     timeout: 90000
    // },
    // {
    //     file: 'Payments/SepaTest',
    //     timeout: 90000
    // },
    // {
    //     file: 'Payments/SofortTest',
    //     timeout: 120000
    // },
    // {
    //     file: 'Payments/UpiTest',
    //     timeout: 120000
    // }
];
