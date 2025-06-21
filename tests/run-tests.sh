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

firefoxready=$(curl -s http://firefox:4444/wd/hub/status | jq '.state' | sed s/\"//g)
C=0

while [[ "$firefoxready" != "success" ]]; do
  echo $C: Waiting for firefox to be ready: $firefoxready
  sleep 10
  C=$(( $C + 1 ));
  firefoxready=$(curl -s http://firefox:4444/wd/hub/status | jq '.state' | sed s/\"//g)
  [[ $C -gt 5 ]] && break
done

chromeready=$(curl -s http://chrome:4444/wd/hub/status | jq '.state' | sed s/\"//g)
C=0

while [[ "$chromeready" != "success" ]]; do
  echo $C: Waiting for chrome to be ready: $chromeready
  sleep 10
  C=$(( $C + 1 ));
  chromeready=$(curl -s http://chrome:4444/wd/hub/status | jq '.state' | sed s/\"//g)
  [[ $C -gt 5 ]] && break
done

phplistready=$(curl -s --head http://phplist/lists/admin/ | grep OK | cut -d ' ' -f 2)
C=0

while [[ "$phplistready" != "200" ]]; do
  C=$(( $C + 1 ));
  echo $C: Waiting for phpList to be ready: $phplistready
  sleep 10
  phplistready=$(curl -s --head http://phplist/lists/admin/ | grep OK | cut -d ' ' -f 2)
  [[ $C -gt 5 ]] && break
done

echo READY
vendor/bin/behat --tags="@behattest"
vendor/bin/behat --tags="@initialise"
vendor/bin/behat -n -fprogress -p firefox --tags="~@initialise && ~@wip && ~@behattest"
vendor/bin/behat -n -fprogress -p chrome --strict --tags="~@initialise && ~@wip && ~@behattest"

echo ======================================================================================
echo ============================ EXPERIMENTAL ============================================
echo ======================================================================================

vendor/bin/behat -n -fprogress -p chrome --strict --tags="~@initialise && @wip && ~@behattest"

## keep container alive for debugging
while (( 1 )); do
  sleep 3600;
done