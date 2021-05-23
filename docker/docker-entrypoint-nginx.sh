#!/bin/bash

## entry point file, needed to be able to pass ENV vars from docker-compose.yml to the containers.

echo Initialising phpList, Please wait

exec 6>&1
exec > /usr/bin/phplist

echo '#!/bin/bash'
echo -n
echo 'exec 6>&1'
echo 'exec > /dev/null 2>&1'
printenv | sed 's/^\(.*\)$/export \1/g'
echo -n
echo 'exec 1>&6 6>&-'
echo /usr/bin/php /var/www/phpList3/public_html/lists/admin/index.php -c /var/www/phpList3/config.php \$\*

exec 1>&6 6>&-
chmod 755 /usr/bin/phplist

## wait for the DB container, but not forever
UNCONNECTED=$(phplist | grep "Cannot connect")
COUNT=1
while [[ "$UNCONNECTED" ]] && [[ $COUNT -lt 11 ]] ; do
    echo Waiting for the Database to be available - $COUNT/10
    sleep 10;
    UNCONNECTED=$(phplist | grep "Cannot connect")
    COUNT=$(( $COUNT + 1 ))
done

if [[ "$UNCONNECTED" ]]; then
    echo Failed to find a Database to connect to
    exit;
fi

/usr/bin/phplist -pinitialise
/usr/bin/phplist -pinitlanguages

echo READY
echo $(phplist --version)
service cron start
/usr/bin/nginx -g 'daemon off;'
