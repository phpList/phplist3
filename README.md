# phpList

[![Build Status](https://travis-ci.org/phpList/phplist3.svg?branch=master)](https://travis-ci.org/phpList/phplist3)

[![StyleCI](https://styleci.io/repos/32042787/shield)](https://styleci.io/repos/32042787)

Open source newsletter and email marketing manager https://www.phplist.org

---

Copyright (C) 2000-2015 Michiel Dethmers, phpList Ltd.

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

phpList delivers Open Source email marketing, including analytics, list segmentation, content personalisaton and bounce processing. Used in 95 countries, available in 20 languages, and used to send more than 25 billion campaign messages in 2015.

Deploy it on your own server, or get a hosted account at http://phplist.com. 

### Features

* Web Based Interface: Lets you write and send messages, and manage your email campaigns over the internet
* Message Queuing: No duplicate messages. No 'forgotten' messages. phpList manages message delivery with a message queue, ensuring that every subscriber gets the email message, and that no subscribers receive two copies, even if they're subscribed to more than one list!
* Personalisation: You can use the attributes you define in the emails you send, to make every email personal to the user who receives them.
* Amazon SES support
* Tracking: You can see how many users opened + clicked your email
* Bounce Handling: Bounces can be processed and users can be automatically unsubscribed when too many emails to them bounced.
* CSV User Import and Export
* Send a Webpage: Tell phpList the URL of a webpage you want to send to your users and it will fetch it and send it out.
* Embargoed Sending: You can create a message and tell the system to only start sending it at a certain date and time in the future.
* Attachments: You can add attachments to your message.
* Load Throttling: You can limit the load on your server so it doesn't overload.
* Domain Throttling: You can limit the number of emails to specific domains to keep on the friendly side of their system administrators.
* RSS Feeds: phpList can be set up to read a range of RSS sources and send the contents on a regular basis to users. The user can identify how often they want to receive the feeds.

---

## Requirements
See [System requirements](https://resources.phplist.com/system)

## Demo

See [Public demo](http://dev.phplist.com/lists/admin/)

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

## Issues

Report issues to [Mantis issue tracker](https://mantis.phplist.org/bug_report_page.php) (select project *phpList*)

## Languages
In the directory `phplists/lists/texts` you will find existing translations of the public
pages of phpList. You can use them in your config file to make the frontend of the system
appear in the language of your choice.

In the config file there are a lot of choices to make about your particular
installation. Make sure to read it carefully and step by step work your way through
it. A lot of information is provided with each step.
