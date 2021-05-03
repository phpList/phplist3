#!/bin/bash

## run behat tests 
## assumes to be run from the project root

cp tests/default.behat.yml behat.yml
cp -fv tests/ci/config.php public_html/lists/config/config.php
php -S localhost:8000 -t public_html > /dev/null 2>&1 &

# start selenium and php server
sh -e /etc/init.d/xvfb start
export DISPLAY=:99.0
./bin/start-selenium > /dev/null 2>&1 &
sleep 5

# setup database and phplist
mkdir -p build/screenshot
mysql -e 'SET GLOBAL wait_timeout = 5400;'
mysql -e 'create database phplistdb;'
mysql -u root -e "CREATE USER 'phplist'@'localhost' IDENTIFIED BY 'phplist';"
mysql -u root -e "GRANT ALL ON phplistdb.* TO 'phplist'@'localhost'; FLUSH PRIVILEGES;"
sudo service mysql restart

sudo service postfix stop
mkdir -p build/mails
cd build/mails
smtp-sink -d "%d.%H.%M.%S" localhost:2500 1000 &

./vendor/bin/phpLint ./public_html

# run setup feature first to create database
./vendor/bin/behat -n -fprogress -p $BROWSER --strict --tags=@first-run

# run all feature except @first-run
./vendor/bin/behat -n -fprogress -p $BROWSER --strict --tags="~@initialise && ~@wip"

ls -l build/mails
ls -l
