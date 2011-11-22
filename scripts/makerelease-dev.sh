#!/bin/bash

CVSROOT=:ext:mdethmers@phplist.cvs.sourceforge.net:/cvsroot/phplist
export CVSROOT

HOME=/home/michiel/cvs/phplist/versions
cd $HOME
#REVISION="-r pl295"
VERSION=$1
#VERSION=2.10.4-RC2
TAG=HEAD
TAG=phplist-version-2-10-x

DATE=`date "+%d %h %Y %H:%M"`

if [ ! $VERSION ]; then
  echo "No version defined, give on commandline eg 2.10.5";
  exit 0;
fi
if [ ! $TAG ]; then
  echo "No tag set";
  exit 0;
fi
if [ -d phplist-$VERSION ]; then
  echo "phplist-$VERSION already exists";
  exit 0;
fi
echo Preparing for version $VERSION
# export the version into the directory
cvs export -r $TAG -d phplist-$VERSION phplist > /tmp/$$.report
cd phplist-$VERSION/public_html/lists/admin/
cvs export -r $TAG commonlib >> /tmp/$$.report
cvs export -r $TAG -d FCKeditor _FCKeditor/FCKeditor2.3.2 >> /tmp/$$.report
cd $HOME
if [ -d phplist-$VERSION ]; then
  echo export of phplist-$VERSION ok
else 
  echo failed to export $VERSION >> /tmp/$$.report
  mail -s "Error making New PHPlist version $VERSION" michiel@tincan.co.uk < /tmp/$$.report
  rm -f /tmp/$$.report
  exit 0;
fi

# change the versions in the files
sed s/define.*VERSION.*/'define("VERSION","'$VERSION'");'/ phplist-$VERSION/public_html/lists/admin/connect.php > $$
mv -f $$ phplist-$VERSION/public_html/lists/admin/connect.php
sed s/define.*STRUCTUREVERSION.*/'define("STRUCTUREVERSION","'$VERSION'");'/ phplist-$VERSION/public_html/lists/admin/structure.php > $$
mv -f $$ phplist-$VERSION/public_html/lists/admin/structure.php
sed s/VERSIONDATE/'\<meta name="Package" content="PHPList '$VERSION',  '"$DATE"'"\>'/  phplist-$VERSION/public_html/lists/admin/pagetop.php > $$
mv -f $$ phplist-$VERSION/public_html/lists/admin/pagetop.php
# zip it up
tar zcf phplist-$VERSION.tgz phplist-$VERSION/*
zip -rq phplist-$VERSION.zip phplist-$VERSION/*
# create tracking image
ssh oak mkdir /home/phplist/site/stats-images/images/$VERSION
scp phplist-$VERSION/public_html/lists/images/power-phplist.png oak:/home/phplist/site/stats-images/images/$VERSION

# for testing create the uploadimages directory
mkdir phplist-$VERSION/public_html/lists/uploadimages
chmod 777 phplist-$VERSION/public_html/lists/uploadimages
