#!/usr/bin/env bash
#Fake sendmail script, adapted from:
#https://github.com/mrded/MNPP/blob/ee64fb2a88efc70ba523b78e9ce61f9f1ed3b4a9/init/fake-sendmail.sh

#Create a temp folder to put messages in

SCRIPT_DIR=$(cd `dirname $0` && pwd)
numPath=$(cd `dirname ${SCRIPT_DIR}` && pwd)/build/mails

mkdir -p ${numPath}

DATE=`date '+%d.%H.%M.%S'`
name="${numPath}/message_${DATE}.eml"
while read line
do
  echo ${line} >> ${name}
done
exit 0
