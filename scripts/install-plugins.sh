#!/usr/bin/bash

from=$1
to=$2

[[ -z $from ]] || [[ ! -d $from ]] || [[ -z $to ]] || [[ ! -d $to ]] && exit;

echo $from $to

for plugin in $from/phplist/phplist-plugin-*; do
  [[ ! -z "$(ls -A $plugin/plugins/)" ]] && mv $plugin/plugins/* $to
done
