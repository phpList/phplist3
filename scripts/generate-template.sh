#!/bin/bash
# script to generate the language gettext template.po from the source code
# $1: current phplist.pot file
# $2: mail where to report the diff between the new and old template
#
# The new template is created on the current dir and named "phplist.pot"

reportto=$2
current=$1
set -e

if [ -z "$current" -o ! -f "$current" ]; then
  echo "Usage: $0 <currentfile>"
  exit 1;
fi

[ "$reportto" ] || reportto=root@localhost

[ -d public_html ] || exit 1; ## needs to run from phplist root

function mail_template_diff() {

	cp phplist.pot "$current"
	diff=$(git diff $current | grep "^\+" | grep -v "$current" | grep -v "^.#" | grep -v "^.msgid ""$" || true)

	if [ -n "$diff" ]; then
		mail -s "phpList language changes" $reportto << EOF
These are this weeks changes in the language template file
They will show up in https://translate.phplist.com as untranslated
Please update your translations, thanks

$diff
EOF
	fi

	# Revert the cp we just did, so the diff is empty again
	git checkout "$current"
}

## from http://www.lxg.de/code/playing-with-xgettext
echo '' > messages.po # xgettext needs that file, and we need it empty

## the structure.php file has texts that cannot be found this way.
php scripts/structuredump.php > public_html/databasestructure.php

find public_html -type f -iname "*.php" | xgettext --omit-header --keyword=__ --keyword=_e --keyword=s --keyword=get -j -f -
msgmerge -qN $current messages.po > phplist-new.pot

mv -f phplist-new.pot phplist.pot
rm -f messages.po phplist-new.pot public_html/databasestructure.php

mail_template_diff
