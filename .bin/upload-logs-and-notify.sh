#!/bin/bash
# This script will send the notification if 'fail' parameter is passed it will
set -e # Exit with nonzero exit code if anything fails
export REPO_NAME='reports'
export REPO_LINK="https://github.com/wirecard/${REPO_NAME}"
export REPO_ADDRESS="${REPO_LINK}.git"

# add random sleep time to minimize conflict possibility
echo "Timestamp : $(date)"
RANDOM_VALUE=$[ ( RANDOM % 30 ) * $SHOP_RELEASE + 1 ]
echo "Sleeping for: ${RANDOM_VALUE}"
sleep ${RANDOM_VALUE}s

#clone the repository where the screenshot should be uploaded
git clone ${REPO_ADDRESS}

# get current date to create a folder
export TODAY=$(date +%Y-%m-%d)

export SHOPWARE_CURRENT_VERSION=$(sed 's/[A-Za-z]*//g' <<<"$SHOPWARE_VERSION")
export PROJECT_FOLDER="shopware-ee-${SHOPWARE_CURRENT_VERSION}"

if [ ! -d "${REPO_NAME}/${PROJECT_FOLDER}/${GATEWAY}" ]; then
mkdir -p ${REPO_NAME}/${PROJECT_FOLDER}/${GATEWAY}
fi

if [ ! -d "${REPO_NAME}/${PROJECT_FOLDER}/${GATEWAY}/${TODAY}" ]; then
mkdir ${REPO_NAME}/${PROJECT_FOLDER}/${GATEWAY}/${TODAY}
fi

export BRANCH_FOLDER=${TRAVIS_BRANCH}

# if tests triggered by PR, use different Travis variable to get branch name
if [ ${TRAVIS_PULL_REQUEST} != "false" ]; then
    export BRANCH_FOLDER="${TRAVIS_PULL_REQUEST_BRANCH}"
# if we were testing latest released extension version
elif [ "${LATEST_EXTENSION_RELEASE}" == "1" ]; then
    export BRANCH_FOLDER="Release-${LATEST_RELEASED_SHOP_EXTENSION_VERSION}"
fi

export RELATIVE_REPORTS_LOCATION=${PROJECT_FOLDER}/${GATEWAY}/${TODAY}/${BRANCH_FOLDER}

if [ ! -d "${REPO_NAME}/${RELATIVE_REPORTS_LOCATION}" ]; then
    mkdir ${REPO_NAME}/${RELATIVE_REPORTS_LOCATION}
fi

# copy report files
cp -r ./report.xml ./mochawesome-report/report.html ./mochawesome-report/assets/ ${REPO_NAME}/${RELATIVE_REPORTS_LOCATION}
if [[ $1 == 'fail' ]]; then
    cp -r ./report.xml ./mochawesome-report/report.html ./mochawesome-report/assets/ ${REPO_NAME}/${RELATIVE_REPORTS_LOCATION}
fi

cd ${REPO_NAME}
# push report files to the repository
git add ${PROJECT_FOLDER}/${GATEWAY}/${TODAY}/*
git commit -m "Add failed test screenshots from ${TRAVIS_BUILD_WEB_URL}"
git push -q https://${GITHUB_TOKEN}@github.com/wirecard/${REPO_NAME}.git master

# save commit hash
export SCREENSHOT_COMMIT_HASH=$(git rev-parse --verify HEAD)
if [[ $1 == 'fail' ]]; then
    cd ..
    # send slack notification
    bash .bin/send-notify.sh
fi
