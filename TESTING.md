# Automated testing of phpList 3

## Acceptance tests (UAT)

[Behat](http://behat.org/en/latest/) is used to execute User Acceptance Tests written in [Gherkin](https://github.com/cucumber/cucumber/wiki/Gherkin). These tests are stored in `tests/features/`.

### Execute Acceptance tests

#### Install composer globally
```sh
$ curl -sS https://getcomposer.org/installer | php
$ mv composer.phar /usr/local/bin/composer
```
You can read the official documentation [here](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx)
#### Install Behat
To install behat and other dependencies run the following command on the project root: 
```sh
$ composer install
```
#### Configure Behat
run the following command:
```sh
$ cp default.behat.yml behat.yml
```
Edit _behat.yml_ file and customize your `base_url`. The `base_url` determines which phpList server you wish to test. This could be a local development copy running on your machine, or a remotely hosted server. 

#### Database setup
Create a database, but keep it empty

```sh
$ mysqladmin -uroot -p create phplisttestdb
$ mysqladmin -uroot -p -e "grant all on phplisttestdb.* to phplist@localhost identified by 'testpassword'"
```

Edit your phplist `config.php` [file](https://www.phplist.org/manual/ch028_installation.xhtml#edit-the-phplist-config-php-file) to use these details

#### Run The tests
Execute the following command from within your phpList 3 code root directory:
```sh
$ vendor/bin/behat
```
#### TODO
Add writing tests documentation.
