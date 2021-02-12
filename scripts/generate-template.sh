#!/bin/bash

# script to generate the language gettext template.po from the source code
# $1: current phplist.pot file
# $2: mail where to report the diff between the new and old template
#
# The new template is created on the current dir and named "phplist.pot"

current=$1
reportto=$2
from_envelope=$3
from_realn=$4
set -e

if [ -z "$current" -o ! -f "$current" ]; then
  echo "Usage: $0 <currentfile>"
  exit 1;
fi

[[ -d public_html ]] || exit 1; ## needs to run from phplist root

## from http://www.lxg.de/code/playing-with-xgettext
echo '' > messages.po # xgettext needs that file, and we need it empty

## the structure.php file has texts that cannot be found this way.
php scripts/structuredump.php > public_html/databasestructure.php

find public_html -type f -iname "*.php" | xgettext --omit-header --keyword=__ --keyword=_e --keyword=s --keyword=snbr --keyword=sjs --keyword=get -j -f -
msgmerge -qN $current messages.po > phplist-new.pot

mv -f phplist-new.pot phplist.pot
rm -f messages.po phplist-new.pot public_html/databasestructure.php

cp phplist.pot "$current"
diff=$(git diff $current | grep "^\+" | grep -v "$current" | grep -v "^.#" | grep -v '^.msgid ""$' | grep -v '^.msgstr ""$' || true)

# Revert the cp we just did, so the diff is empty again
git checkout "$current"
echo $diff

