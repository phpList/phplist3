#!/bin/bash

# create a language pack for translation
DIR=`pwd`
[ -d "$DIR/public_html/lists/admin" ] || { echo "Run from code root as ./scripts/langpack.sh"; exit; }
. VERSION
cd $DIR/public_html/lists/admin
mkdir -p ${DIR}/public_html/langpack/
rm -f `find . -name ".#*"`
tar zcf ${DIR}/public_html/langpack/phplist-langpack-$DEVVERSION.tgz --exclude .svn lan/en info/en help/en

for languagedir in lan/*; do
  language=$(basename $languagedir)
  if [ $language != "en" ]; then
    #echo $language;
    tar zcf ${DIR}/public_html/langpack/phplist-langpack-${DEVVERSION}-${language}.tgz --exclude .svn lan/${language} info/${language} help/${language} 2>/dev/null
  fi
done

cd $DIR
