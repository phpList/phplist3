#!/usr/bin/env bash

from=$1
to=$2

[[ -z $from ]] || [[ ! -d $from ]] || [[ -z $to ]] || [[ ! -d $to ]] && exit;

echo $from $to

for plugin in $(find $from -type d -name phplist-*); do
  [[ ! -z "$(ls -A $plugin/plugins/)" ]] && {
    echo installing $plugin
    rsync -a $plugin/plugins/* $to
  }
done
