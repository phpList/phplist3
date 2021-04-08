# Automated testing of phpList 3

## Acceptance tests (UAT)

[Behat](http://behat.org/en/latest/) is used to execute User Acceptance Tests written in [Gherkin](https://github.com/cucumber/cucumber/wiki/Gherkin). These tests are stored in `tests/features/`.



### Developing tests with Vagrant

To develop acceptance tests locally, install Vagrant. This allows you to run tests against 
different versions of PHP.
Find the line "PHPVERSION=7.4" and change it to the version of PHP you want to use.
Put @wip in the feature you are working on, so that it doesn't interfere with Github CI.

Then run
```sh
vagant up
```

Once it has run once, you can fire off further runs with

```sh
vagant ssh
cd /vagrant/tests
make test
```

You can also use the following commands:

make verbosetest - show progress during testing, useful to identify issues in your tests
make test-wip - only run the features marked "@wip"
make testall - run all tests



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

### First time

Some tests can only be run once before they change the system state upon which they depend. These tests should only be run once, or only in automated testing environments like Github actions. Execute these and all other tests together by running the following command from within your phpList 3 code root directory:

```sh
$ vendor/bin/behat
```

### Every other time

Usually you will want to avoid running the first time tests and run all the others instead. To do so execute the following command from within your phpList 3 code root directory:

```sh
$ vendor/bin/behat --tags '~@initialise'
```

This will run all tests except for those with the `initialise` tag.

### Errors

When you run it locally or using Vagrant, and a test fails, it will drop a screenshot of the page where it failed in
tests/output/screenshots

That way you can find out why it failed and update your test.



#### TODO

Add writing tests documentation.

