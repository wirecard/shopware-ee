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

const { Builder } = require('selenium-webdriver');
const { browsers, tests } = require('./config');
const Mocha = require('mocha');

async function asyncForEach(arr, cb) {
    for (let i = 0; i < arr.length; i++) {
        await cb(arr[i], i, arr);
    }
}

const run = async () => {
    await asyncForEach(browsers, async browser => {
        const bsConfig = Object.assign({
            'browserstack.user': process.env.TEST_BS_USER,
            'browserstack.key': process.env.TEST_BS_KEY,
            'browserstack.local': 'true',
            'browserstack.localIdentifier': process.env.BROWSERSTACK_LOCAL_IDENTIFIER
        }, browser);

        // Driver used by the Selenium tests.
        global.driver = new Builder()
            .usingServer('http://hub-cloud.browserstack.com/wd/hub')
            .withCapabilities(bsConfig)
            .build();

        await asyncForEach(tests, file => {
            const mocha = new Mocha({
                timeout: 120000
            });

            return new Promise((resolve, reject) => {
                mocha.addFile(`./Tests/Selenium/${file}.js`);
                mocha.run()
                    .on('pass', () => resolve())
                    .on('fail', test => reject(`Selenium test (${test.title}) failed.`))
                ;
            });
        });
    });
};

run();

// browsers.forEach(async (browser) => {
//
//     tests.forEach(async (file) => {
//         mocha.addFile(`./Tests/Selenium/${file}.js`);
//
//         await mocha.run()
//             // .on('test', test => console.log(test))
//             // .on('test end', test => console.log(test))
//             // .on('pass', test => console.log(test))
//             .on('fail', test => {
//                 console.log(test);
//                 process.exit(1);
//             })
//             // .on('end', test => console.log(test))
//         ;
//     });
// });
