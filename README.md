# phpList 3

[![Build Status](https://travis-ci.org/phpList/phplist3.svg?branch=master)](https://travis-ci.org/phpList/phplist3)
[![Stable release](https://img.shields.io/badge/stable-3.4.0-blue.svg)](https://sourceforge.net/projects/phplist/files/phplist/)
[![License](https://poser.pugx.org/phplist/phplist4-core/license.svg)](https://www.gnu.org/licenses/agpl-3.0.en.html)

Fully functional Open Source email marketing manager for creating, sending, integrating, and analysing email campaigns and newsletters: https://www.phplist.org

phpList includes analytics, segmentation, content personalisation, bounce processing, plugin-based architecture, and multiple APIs. Used in 95 countries, available in 20 languages, and used to send more than 25 billion campaign messages in 2015.

Deploy it on your own server, or get a hosted account at http://phplist.com.

---

### Features

* Responsive web-based, and command-line interfaces
* Real-time analytics: track message responses and subscriber behaviour
* Message-queue management: load-balances and throttles multiple accounts and campaigns; tracks every delivery outcome
* Content personalisation: every message customised to individual subscriber attributes and preferences
* Automated bounce management and processing: policy and regex-based handling with every bounce accessibly archived
* Schedule, pause, resume, repeat, and requeue campaigns
* Amazon SES: built-in optimised support
* CSV and Excel based subscriber import and export, including attributes and preferences
* Send a Webpage: automatic remote html polling, conversion, and dispatch
* Email attachments support
* Domain-based throttling: comply with host-specific policies by defining custom rules
* RSS Feeds: phpList can be set up to read a range of RSS sources and send the contents on a regular basis to users. The user can identify how often they want to receive the feeds.

---

## Trying out phpList

If you'd like to use phpList for your own campaigns, or you just want to try phpList out, there is no need to do all the work of installing it yourself. [phpList Hosted](https://phplist.com) is free to use, just [sign up](https://phplist.com/register) to get started.

If at a later time you do want to migrate from your own installation to phpList Hosted, or vice versa, your data can be migrated.

Alternatively, you can try out the latest phpList release at [phplist.org](https://demo.phplist.org/lists/admin/). This installation is wiped and refreshed every hour.

## Requirements
See [System requirements](https://resources.phplist.com/system/start)

## Development
See [phpList development](https://resources.phplist.com/develop/start)

## Installation
See the [Installation guide](https://www.phplist.org/manual/ch028_installation.xhtml)

## Upgrade

### For users

See [Upgrading a manual installation](https://www.phplist.org/manual/ch031_upgrading.xhtml)

### For developers

How to upgrade from any previous version to the latest version

Step 1. BACKUP your database
(e.g. # mysqldump -u[user] -p[password] [database] > phplist-backup.sql)

Step 2. Copy your old configured files to some safe place

These files are:
	lists/config/config.php
        possibly lists/texts/english.inc or any other language.inc if you have edited it

Step 3. Copy the files from the tar file to your webroot.

You can copy everything in the "lists" directory in the tar file to your website.
To facilitate future upgrades, ie to make it easier for you to simply copy
everything I have now put the "configurable" files in their own directory. They
reside in "lists/config". This is hopefully going to be the directory that you can
keep between upgrades, and that will contain the only information that you want to be changed in order to make it work for your own site.

Step 4. Copy your configuration files to lists/config or re-edit the new config file
sometimes new features are added to the config file, so it's better to use
the new config file and re-adapt it to your situation.

An example .htaccess exists file in this directory. You should not allow
access to this directory from the webserver at all. The example will work with
apache.

You can overwrite the files that are there. They are example files.

Step 5. Go to http://yourdomain/lists/admin/ and choose the Upgrade link

Step 6. Click the link on this page.

This process may take quite a while if your database is large. Don't interrupt it.

## Issues

Report issues to [Mantis issue tracker](https://mantis.phplist.org/bug_report_page.php) (select project *phpList*)

## Languages
In the directory `phplists/lists/texts` you will find existing translations of the public
pages of phpList. You can use them in your config file to make the frontend of the system
appear in the language of your choice.

In the config file there are a lot of choices to make about your particular
installation. Make sure to read it carefully and step by step work your way through
it. A lot of information is provided with each step.

## Code of Conduct
This project adheres to a [Contributor Code of Conduct](CODE_OF_CONDUCT.md).
By participating in this project and its community, you are expected to uphold
this code.

## License
phpList 3 is licensed under the terms of the AGPLv3 Open Source license and is available free of charge.
