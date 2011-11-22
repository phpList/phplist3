#!/bin/bash

# identify the changes in language files
. VERSION
cvs diff -r $TAG -r $PREVTAG public_html/lists/admin/info/en/* >> language$PREVTAG-$TAG.diff
cvs diff -r $TAG -r $PREVTAG public_html/lists/admin/help/en/* >> language$PREVTAG-$TAG.diff
cvs diff -r $TAG -r $PREVTAG public_html/lists/admin/lan/en/* >> language$PREVTAG-$TAG.diff
cvs diff -r $TAG -r $PREVTAG public_html/lists/texts/english.inc >> language$PREVTAG-$TAG.diff
#[ -s "language$PREVTAG-$TAG.diff" ] || rm -f "language$PREVTAG-$TAG.diff"
