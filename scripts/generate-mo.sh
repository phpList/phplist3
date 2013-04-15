#!/bin/bash

## generate mo files from po files

[ -d public_html/lists/admin/locale ] || {
  echo Run from phpList coderoot
  exit;
}
here=$(pwd)
localedir="public_html/lists/admin/locale/"
for i in `ls ${localedir}`; do
  [ -d ${localedir}/$i ] && [ "$i" != "templates" ] && {
    cd ${localedir}/$i
    msgfmt -cv -o phplist.mo phplist.po
    rm -f LC_MESSAGES
    ln -s . LC_MESSAGES
    cd $here
    echo $i;
  }
done
