#!/bin/bash

#get last 3  releases
curl -H "Authorization: token ${GITHUB_TOKEN}" https://api.github.com/repos/shopware/shopware/releases | jq -r '.[] | .tag_name' | egrep -v [A-Z] | head -3 > tmp.txt
git config --global user.name "Travis CI"
git config --global user.email "wirecard@travis-ci.org"

sort -nr tmp.txt > ${SHOPWARE_RELEASES_FILE}

if [[ $(git diff HEAD ${SHOPWARE_RELEASES_FILE}) != '' ]]; then
    git add  ${SHOPWARE_RELEASES_FILE}
    git commit -m "${SHOP_SYSTEM_UPDATE_COMMIT}"
    git push --quiet https://${GITHUB_TOKEN}@github.com/${TRAVIS_REPO_SLUG} HEAD:master
fi
