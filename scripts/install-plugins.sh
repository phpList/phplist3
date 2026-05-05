#!/usr/bin/env bash

from=$1
to=$2

[[ -z $from ]] || [[ ! -d $from ]] || [[ -z $to ]] || [[ ! -d $to ]] && exit;

echo $from $to

[[ -x $(which rsync) ]] || {
  apt install -y rsync
}

while IFS= read -r -d '' plugin; do
  plugin_name="$(basename "$plugin")"

  [[ -d "$plugin/plugins" ]] || continue
  [[ -n "$(find "$plugin/plugins" -mindepth 1 -maxdepth 1 -print -quit)" ]] || continue

  echo "Installing plugin: $plugin_name"

  rsync_args=(-a)

  case "$plugin_name" in
    phplist-plugin-saml2)
      echo "Preserving SAML2 settings.php"

      rsync_args+=(--exclude='simplesaml/settings.php')
      ;;
  esac

  rsync "${rsync_args[@]}" "$plugin/plugins/" "$to/"

done < <(find "$from" -type d -name 'phplist-plugin-*' -print0)
