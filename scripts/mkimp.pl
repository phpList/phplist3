#!/usr/bin/perl
# generate files to test import

## CSV import
open F,">testimport-csv.txt";
print F "email\tName\tAddr1\tAddr2\tTown\tPostcode\tforeign key\tsend this user HTML emails\n";
$start = 20;
$num = 50000;
for ($i=$start;$i<$start+$num;$i++) {
  print F "$i\@mailinator.com\tTestUser $i\tAddr line 1\tAddr line2\tTown\tPostcode $i\tABC $i\t1\n";
}
close(F);

# Plain list of emails import
open F,">testimport-plain.txt";
$start = 0;
for ($i=$start;$i<$start+$num;$i++) {
  print F "importtest-$i\@mailinator.com\n";
}
close(F);

open F,">testimport-admin.txt";
print F "email\tloginname\tpassword\n";
$start = 20;
$num = 10000;
for ($i=$start;$i<$start+$num;$i++) {
  $pass = `pwgen -1`;
  chomp($pass);
  print F "$i\@reallynonexistentdomain.com\tTest Admin $i\t$pass\n";
}
close(F);

