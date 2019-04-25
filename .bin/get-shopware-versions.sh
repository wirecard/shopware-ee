#!/bin/bash

#get last 3  releases
curl --silent "https://api.github.com/repos/shopware/shopware/releases" | jq -r '.[] | .tag_name' | head -3 > ${SHOPWARE_RELEASES_FILE}
git config --global user.name "Travis CI"
git config --global user.email "wirecard@travis-ci.org"

git add .bin/shop-releases.txt
git commit -m "[skip ci] Update latest shop releases"
git push --quiet https://${GITHUB_TOKEN}@github.com/${TRAVIS_REPO_SLUG} HEAD:TPWDCEE-3662
