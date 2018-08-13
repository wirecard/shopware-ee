/**
 * Shop System Plugins:
 * - Terms of Use can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/_TERMS_OF_USE
 * - License can be found under:
 * https://github.com/wirecard/shopware-ee/blob/master/LICENSE
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
                        console.log(test);
                        reject(new Error(`Selenium test (${test.title}) failed.`));
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
