#!/bin/sh

#Script for create the plugin artifact
echo "Travis tag: $TRAVIS_TAG"

if [ "$TRAVIS_TAG" = "" ]
then
   TRAVIS_TAG='1.0.0'
fi

composer install --no-dev
composer update --no-dev

SRC_DIR="webpay"
FILE1="webpay.php"
FILE2="config.xml"
FILE3="config_es.xml"

sed -i.bkp "s/$this->version = '3.0.6'/$this->version = '${TRAVIS_TAG#"v"}'/g" "$SRC_DIR/$FILE1"
sed -i.bkp "s/\[3.0.6\]/\[${TRAVIS_TAG#"v"}\]/g" "$SRC_DIR/$FILE2"
sed -i.bkp "s/\[3.0.6\]/\[${TRAVIS_TAG#"v"}\]/g" "$SRC_DIR/$FILE3"

PLUGIN_FILE="plugin-prestashop-webpay-$TRAVIS_TAG.zip"

zip -r9 $PLUGIN_FILE $SRC_DIR -x "$SRC_DIR/$FILE1.bkp" "$SRC_DIR/$FILE2.bkp" "$SRC_DIR/$FILE3.bkp"

cp "$SRC_DIR/$FILE1.bkp" "$SRC_DIR/$FILE1"
cp "$SRC_DIR/$FILE2.bkp" "$SRC_DIR/$FILE2"
cp "$SRC_DIR/$FILE3.bkp" "$SRC_DIR/$FILE3"
rm "$SRC_DIR/$FILE1.bkp"
rm "$SRC_DIR/$FILE2.bkp"
rm "$SRC_DIR/$FILE3.bkp"

echo "Plugin version: $TRAVIS_TAG"
echo "Plugin file: $PLUGIN_FILE"
