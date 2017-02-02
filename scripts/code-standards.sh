#!/bin/bash

[ -d public_html ] && {
  echo Running php-cs-fixer on public_html
  for file in $(find public_html -name *php); do
    if [[ ! $file =~ .*random_compat.* ]]; then
    #    echo $file;
        php-cs-fixer fix $file --level=symfony --fixers=align_double_arrow,newline_after_open_tag,ordered_use,long_array_syntax
     fi
  done

}


