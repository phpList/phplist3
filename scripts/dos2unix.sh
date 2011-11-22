
# script to force Unix line endings

for i in `find public_html -type f|grep -e "\.php\|\.inc"`; do 
  echo $i;
  dos2unix -k -c Mac $i
  dos2unix -k $i
done

