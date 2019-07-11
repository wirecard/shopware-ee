#!/bin/bash

#get last 3  releases
curl -H "Authorization: token ${GITHUB_TOKEN}" https://api.github.com/repos/shopware/shopware/releases | jq -r '.[] | .tag_name' | egrep -v [A-Z] | head -3 > ${SHOPWARE_RELEASES_FILE}
git config --global user.name "Travis CI"
git config --global user.email "wirecard@travis-ci.org"

git add  ${SHOPWARE_RELEASES_FILE}
git commit -m "[skip ci] Update latest shop releases"
git push --quiet https://${GITHUB_TOKEN}@github.com/${TRAVIS_REPO_SLUG} HEAD:master
