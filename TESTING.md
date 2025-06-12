# Automated testing of phpList 3

## Acceptance tests (UAT)

[Behat](http://behat.org/en/latest/) is used to execute User Acceptance Tests written in [Gherkin](https://github.com/cucumber/cucumber/wiki/Gherkin). These tests are stored in `tests/features/`.

### Developing tests with Docker

To develop acceptance tests locally, install Docker. 
You also need the "make" command in Linux (eg apt install make)

Put @wip in the feature you are working on, so that it doesn't interfere with Github CI.

### Running the tests locally

- cd tests
- cp .env.dist .env
- make test

This will build a Behat container and spin up some docker containers to run the tests against the phpList container.

It will then follow the progress in the Behat container. You can get out of there, by pressing crtl-C

It will run the tests from your local repository, so if you make changes, you can re-run them by running "make" again,
or you can leave the containers running and enter the behat one with

docker exec -it behat bash

and then run the tests with, eg 

vendor/bin/behat --tags="@wip"

### viewing phpList itself

You can load phpList with http://localhost/lists/admin/

Or if you want a different port, change that in the .env file

### viewing the browser

To view the browser container, use a VNC viewer (eg remmina) and go to localhost:5901 password secret for Firefox
Use localhost:5902 password secret for chrome

### Viewing emails

To view email delivery go to http://localhost:8026/ to view the mailpit UI

### Verbose

To increase verbosity on the tests remove "-fprogress" from the command

### Errors

When you run it locally, and a test fails, it will drop a screenshot of the page where it failed in
tests/output/screenshots

That way you can find out why it failed and update your test.

#### TODO

Add writing tests documentation.

