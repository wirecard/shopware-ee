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
const { asyncForEach } = require('./common');
const Mocha = require('mocha');

const run = async () => {
    await asyncForEach(browsers, async browser => {
        const bsConfig = Object.assign({
            'browserstack.user': process.env.BROWSERSTACK_USER,
            'browserstack.key': process.env.BROWSERSTACK_KEY,
            'browserstack.local': 'true',
            'browserstack.localIdentifier': process.env.BROWSERSTACK_LOCAL_IDENTIFIER
        }, browser);

        await asyncForEach(tests, async testCase => {
            // Driver used by the Selenium tests.
            global.driver = await new Builder()
                .usingServer('http://hub-cloud.browserstack.com/wd/hub')
                .withCapabilities(Object.assign({
                    name: testCase.file,
                    build: process.env.TRAVIS ? `${process.env.TRAVIS_JOB_NUMBER}` : 'local',
                    project: 'Shopware:WirecardElasticEngine'
                }, bsConfig))
                .build();

            const mocha = new Mocha({
                timeout: testCase.timeout
            });

            return new Promise((resolve, reject) => {
                // `require` (used by Mocha#addFile) caches files by default, making it impossible to run tests
                // multiple times. To fix this we clear the cache on every test.
                mocha.suite.on('require', function (global, file) {
                    delete require.cache[file];
                });

                console.log(`Running ${testCase.file} against ${browser.browserName} (v${browser.browser_version}) on ${browser.os} (${browser.os_version})`);

                mocha.addFile(`./Tests/Selenium/${testCase.file}.js`);

                mocha.run()
                    .on('fail', test => {
                        reject(new Error(`Selenium test (${test.title}) failed.`));
                        process.exit(1);
                    })
                    .on('end', () => {
                        resolve();
                    })
                ;
            });
        });
    });
};

run();
