
# script to replace short php tags <? with long one <?php

echo "replacing <? with <?php"
for i in `fgrep -rilI -e "<?" --exclude-dir .svn public_html*|grep -v short2long`; do
   echo $i;
   sed -i "s/<?/<?php/" $i;
done
# revert doubled ones
echo "replacing <?phpphp with <?php"
for i in `fgrep -rilI -e "<?phpphp" --exclude-dir .svn public_html*|grep -v short2long`; do
   echo $i;
   sed -i "s/<?phpphp/<?php/" $i;
done
echo "replacing <?php php with <?php"
for i in `fgrep -rilI -e "<?php php" --exclude-dir .svn public_html*|grep -v short2long`; do
   echo $i;
   sed -i "s/<?php php/<?php/" $i;
done
echo "replacing <?= with <?php echo "
for i in `fgrep -rilI -e "<?=" --exclude-dir .svn public_html*|grep -v short2long`; do
   echo $i;
   sed -i "s/<?=/<?php echo /" $i;
done
echo "replacing <?php= with <?php echo "
for i in `fgrep -rilI -e "<?php=" --exclude-dir .svn public_html*|grep -v short2long`; do
   echo $i;
   sed -i "s/<?php=/<?php echo /" $i;
done
echo "replacing <?phpxml with <?xml "
for i in `fgrep -rilI -e "<?phpxml" --exclude-dir .svn public_html*|grep -v short2long`; do
   echo $i;
   sed -i "s/<?phpxml/<?xml/" $i;
done


