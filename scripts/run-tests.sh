#!/bin/bash

## run behat tests 
## assumes to be run from the project root
## tests are in "/tests"

rm -rf vendor
composer install

cp tests/default.behat.yml behat.yml
cp -fv tests/ci/config.php public_html/lists/config/config.php
php -S 127.0.0.1:80 -t public_html > /dev/null 2>&1 &

sudo service mysql start

# start selenium and php server
./bin/start-selenium > /dev/null 2>&1 &
sleep 5

# setup database and phplist
mkdir -p build/screenshot

sudo service postfix stop
mkdir -p build/mails
cd build/mails
smtp-sink -u phplist -d "%d.%H.%M.%S" localhost:2500 1000 &

cd ../../tests

make test

