# phpList

[![Build Status](https://travis-ci.org/phpList/phplist3.svg?branch=master)](https://travis-ci.org/phpList/phplist3)

Open source newsletter and email marketing manager https://www.phplist.com

---

Copyright (C) 2000-2015 Michiel Dethmers, phpList ltd

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU Affero General Public License
as published by the Free Software Foundation; either version 3
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

---

## What is phpList

phpList is a mailinglist system. It uses MySQL for storing the information.

Particular aspects of the system are:

* Posting to the mailinglist is via a webpage. It is therefore more a kind of "announcements" list system than an actual email mailinglist.
* It is designed to be able to deal with a very large amount of email addresses.
* Subscribers can sign up to multiple lists. If a campaign is sent to multiple lists, they will only receive one copy of the campaign and not as many as the number of lists they are subscribed to.
* Geographical information. If people sign up, they can identify the geographical location they're in and when sending an email you can determine which locations need to receive the message.
* Personalised emails. You can specify "variables" in your emails, which will at send time be replaced by the appropriate values for the person who receives the email.

---

## Requirements
To use phpList you need a webserver which supports PHP version 5, and a MySQL database.

## Installation
Read the installation instructions with abundant help at: 

https://www.phplist.org/manual/ch028_installation.xhtml

## Upgrade

phpList upgrade process.

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
reside in "lists/config". This is hopefully going to be the directory thay you can
keep between upgrades, and that will contain the only information that you want
changed in order to make it work for your own site.

Step 4. Copy your configuration files to lists/config or re-edit the new config file
sometimes new features are added to the config file, so it's better to use
the new config file and re-adapt it to your situation.

I have put an example .htaccess file in this directory. You should not allow
access to this directory from the webserver at all. The example will work with
apache.

You can overwrite the files that are there. They are example files.

Step 5. Go to http://yourdomain/lists/admin/ and choose the Upgrade link

Step 6. Click the link in this page.

This process may take quite a while if your database is large. Don't interrupt it.

## Languages
In the directory `phplists/lists/texts` you will find existing translations of the public
pages of phpList. You can use them in your config file to make the frontend of the system
appear in the language of your choice.

In the config file there are a lot of choices to make about your particular
installation. Make sure to read it carefully and step by step work your way through
it. A lot of information is provided with each step.
