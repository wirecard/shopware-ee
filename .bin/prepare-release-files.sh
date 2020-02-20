#!/bin/bash

TARGET_DIRECTORY="WirecardElasticEngine"

mkdir ${TARGET_DIRECTORY}
shopt -s extglob
mv !(WirecardElasticEngine) WirecardElasticEngine
