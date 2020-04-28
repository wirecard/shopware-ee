#!/bin/bash

set -e
set -x

PREVIEW_LINK='https://raw.githack.com/wirecard/reports'
REPORT_FILE='report.html'

if [[ ${GATEWAY} = "NOVA" ]]; then
  CHANNEL='shs-ui-nova'
elif [[  ${GATEWAY} = "API-TEST" ]]; then
   CHANNEL='shs-ui-api-test'
fi

if [[ ${COMPATIBILITY_CHECK}  == "0" ]]; then
  # send information about the build
  curl -X POST -H 'Content-type: application/json' \
      --data "{'text': 'Build Failed. Shopware version: ${SHOPWARE_VERSION}\n
      Build URL : ${TRAVIS_JOB_WEB_URL}\n
      Build Number: ${TRAVIS_BUILD_NUMBER}\n
      Branch: ${TRAVIS_BRANCH}\n
      Report link: ${PREVIEW_LINK}/${SCREENSHOT_COMMIT_HASH}/${RELATIVE_REPORTS_LOCATION}/${REPORT_FILE}',
      'channel': '${CHANNEL}'}\n
      " ${SLACK_ROOMS}
else
  # send information about compatiblity
  curl -X POST -H 'Content-type: application/json' \
      --data "{'text': 'Compatibility Failed. Shopware version: ${SHOPWARE_VERSION}\n
      Build URL : ${TRAVIS_JOB_WEB_URL}\n
      Build Number: ${TRAVIS_BUILD_NUMBER}\n
      Branch: ${TRAVIS_BRANCH}\n
      Report link: ${PREVIEW_LINK}/${SCREENSHOT_COMMIT_HASH}/${RELATIVE_REPORTS_LOCATION}/${REPORT_FILE}',
      'channel': '${CHANNEL}'}\n
      " ${SLACK_ROOMS}
fi
