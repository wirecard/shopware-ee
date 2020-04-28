#!/bin/bash

set -e
set -x

# update compatible-shop-releases.txt if there was a release and we are compatible
if [[ ${COMPATIBILITY_CHECK}  == "1" ]]; then
    cp ${SHOPWARE_COMPATIBILITY_FILE} ${SHOPWARE_RELEASES_FILE}
    git config --global user.name "Travis CI"
    git config --global user.email "wirecard@travis-ci.org"
    git add  ${SHOPWARE_RELEASES_FILE}
    git commit -m "${SHOP_SYSTEM_UPDATE_COMMIT}"
    git push --quiet https://${GITHUB_TOKEN}@github.com/${TRAVIS_REPO_SLUG} HEAD:master
fi
