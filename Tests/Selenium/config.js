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
            label: 'Wirecard Credit Card',
            fields: {
                last_name: 'Lastname',
                account_number: '4012000300001003',
                card_security_code: '003'
            }
        },
        creditCardThreeD: {
            label: 'Wirecard Credit Card',
            fields: {
                last_name: 'Lastname',
                account_number: '4012000300001003',
                card_security_code: '003'
            },
            password: 'wirecard'
        },
        sepa: {
            label: 'Wirecard SEPA Direct Debit',
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