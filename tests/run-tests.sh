#!/bin/bash

# hubready=$(curl -s http://selenium-hub:4444/wd/hub/status | jq '.value.ready')
# C=0

# while [[ "$hubready" != "true" ]]; do
#   C=$(( $C + 1 ));
#   echo $C: Waiting for the HUB to be ready: $hubready
#   hubready=$(curl -s http://selenium-hub:4444/wd/hub/status | jq '.value.ready')
#   sleep 30
#   [[ $C -gt 5 ]] && break;
# done

seleniumready=$(curl -s http://firefox:4444/wd/hub/status | jq '.state' | sed s/\"//g)
C=0

while [[ "$seleniumready" != "success" ]]; do
  echo $C: Waiting for selenium to be ready: $seleniumready
  sleep 30
  C=$(( $C + 1 ));
  seleniumready=$(curl -s http://firefox:4444/wd/hub/status | jq '.state' | sed s/\"//g)
  [[ $C -gt 5 ]] && break
done

phplistready=$(curl -s --head http://phplist/lists/admin/ | grep OK | cut -d ' ' -f 2)
C=0

while [[ "$phplistready" != "200" ]]; do
  C=$(( $C + 1 ));
  echo $C: Waiting for phpList to be ready: $phplistready
  sleep 30
  phplistready=$(curl -s --head http://phplist/lists/admin/ | grep OK | cut -d ' ' -f 2)
  [[ $C -gt 5 ]] && break
done


echo READY
vendor/bin/behat --tags="@behattest"
vendor/bin/behat --tags="@initialise"
vendor/bin/behat --tags="~@initialise && ~@wip && ~@behattest"

## keep container alive for debugging
while (( 1 )); do
  sleep 3600;
done