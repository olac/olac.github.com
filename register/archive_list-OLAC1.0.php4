<?php
require_once("../lib/php/OLACDB.php");
$DB = new OLACDB("olac2");
$DB->saw_error() and die("system error");

$tab = $DB->sql("select count(*) as num from ARCHIVES
                 where dateApproved is not NULL");
$DB->saw_error() and die("Query failed");
$archives = $tab[0][num];

$tab = $DB->sql("select * from ARCHIVES
                 where dateApproved is not NULL
                 order by ID");
$DB->saw_error() and die("Query failed");
		
header ("Content-type: text/xml");
print '<?xml version="1.0" encoding="UTF-8" ?>';
print "\n";
print "<archives number=\"$archives\">\n";
foreach ($tab as $row) {
  print '<archive id="';
  print $row['ID'];
  print '" baseURL="';
  print $row['BASEURL'];
  print '" dateApproved="';
  print $row['dateApproved'];
  print "\" />\n";
}
print '</archives>';

?>
