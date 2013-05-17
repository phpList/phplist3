#!/bin/bash

## script to generate the language gettext template.po from the source code

svnup=$3
reportto=$1
current=$2

[ "$reportto" ] || reportto=root@localhost 

if [ "$svnup" ]; then
  svn up
fi

[ -d public_html ] || exit; ## needs to run from phplist root

now=$(date +%Y%m%d%H%M)

## from http://www.lxg.de/code/playing-with-xgettext
echo '' > messages.po # xgettext needs that file, and we need it empty

## the structure.php file has texts that cannot be found this way.
php scripts/structuredump.php > public_html/databasestructure.php

find public_html -type f -iname "*.php" | xgettext --omit-header --keyword=__ --keyword=_e --keyword=s --keyword=get -j -f -
msgmerge -N $current messages.po > phplist-new.pot 2>/dev/null

diff phplist-new.pot $current > diff${now}
if [ -s "diff${now}" ]; then
  exec > /tmp/message$$
  echo Language text updates 
  echo
  fgrep '< msgid' diff${now} | sed s/'< msgid'//

  mail -s "phpList language template update" $reportto < /tmp/message$$ 
  rm -f diff${now} /tmp/message$$
  mv -f phplist-new.pot phplist.pot
fi
rm -f messages.po phplist-new.pot diff${now} public_html/databasestructure.php

