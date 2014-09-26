# phpList

Open source newsletter manager http://www.phplist.com

---

Copyright (C) 2000-2013 Michiel Dethmers, phpList ltd

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
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

phpList is a set of PHP pages that implement a simple mailinglist system. It uses MySQL for storing the information.

Particular aspects of the system are:

* Posting to the mailinglist is via a webpage. It is therefore more a kind of "announcements" list system than an actual email mailinglist.
* It is designed to be able to deal with a very large amount of email addresses.
* Users can sign up to multiple lists. If a message is sent to multiple lists, they will only receive one copy of the email and not as many as the number of lists they are subscribed to.
* Geographical information. If people sign up, they can identify the geographical location they're in and when sending an email you can determine which locations need to receive the message.
* Personalised emails. You can specify "variables" in your emails, which will at send time be replaced by the appropriate values for the person who receives the email.

---

## Requirements
To use phpList you need a webserver which supports PHP version 5, and a MySQL database.

## Installation
See file: INSTALLATION

## Upgrade
See file: UPGRADE

## Security
See file: README.security

## Languages
In the directory `phplists/lists/texts` you will find existing translations of the public
pages of PHPlist. You can use them in your config file to make the frontend of the system
appear in the language of your choice.

In the config file there are a lot of choices to make about your particular
installation. Make sure to read it carefully and step by step work your way through
it. A lot of information is provided with each step.
