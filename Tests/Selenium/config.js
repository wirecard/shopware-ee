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
        // masterpass: {
        //     label: 'Wirecard Masterpass',
        //     fields: {
        //         email: 'masterpass@mailadresse.net',
        //         password: 'WirecardPass42'
        //     }
        // },
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
