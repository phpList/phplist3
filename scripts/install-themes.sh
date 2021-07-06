#!/usr/bin/env bash

from=$1
to=$2

[[ -z $from ]] || [[ ! -d $from ]] || [[ -z $to ]] || [[ ! -d $to ]] && exit;

echo $from $to

for theme in $(ls -d $from/*/phplist-ui-*); do
  [[ ! -z "$(ls -A $theme)" ]] && {
    echo installing $theme
    mv $theme $to
  }
done
