#!/bin/sh

for i in `find . -follow -type f -name "*.php"`; do
  php -l $i |grep -v "No syntax errors"
done
for i in `find . -follow -type f -name "*.inc"`; do
  php -l $i |grep -v "No syntax errors"
done
