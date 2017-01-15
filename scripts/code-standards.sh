#!/bin/bash

[ -d public_html ] && {
  echo Running php-cs-fixer on public_html
  php-cs-fixer fix public_html --level=symfony --fixers=align_double_arrow,newline_after_open_tag,ordered_use,long_array_syntax
}


