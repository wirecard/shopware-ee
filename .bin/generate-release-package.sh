#!/bin/bash

TARGET_DIRECTORY="WirecardElasticEngine"

composer install --no-dev
rm -rf $TARGET_DIRECTORY
echo "copying files to target directory ${TARGET_DIRECTORY}"
mkdir $TARGET_DIRECTORY
cp -r Commands Components Controllers Exception Models Resources Subscriber vendor plugin.png plugin.xml WirecardElasticEngine.php ${TARGET_DIRECTORY}/

zip -r WirecardElasticEngine.zip ${TARGET_DIRECTORY}
