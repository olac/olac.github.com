<?header ("Content-type: text/xml");
$link = mysql_connect("", "olac", "SECRET")  or die ("Could not connect");
mysql_select_db ("olac_registry")  or die ("Could not select database");

$sql = " select ID, BASEURL
         from ARCHIVES
         where dateApproved is not NULL
         order by ID";
$result = mysql_query($sql) or die("Query failed");
		
print '<?xml version="1.0" encoding="UTF-8" ?> ';
print "\n";
print "<harvestconfig>\n";
while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
  print "  <archive>\n";
  printf("    <identifier>%s</identifier>\n", $line['ID']);
  printf("    <url>%s</url>\n", $line['BASEURL']);
  print "    <metadataPrefix>olac</metadataPrefix>\n";
  print "    <interval>1</interval>\n";
  print "    <interrequestgap>10</interrequestgap>\n";
  print "  </archive>\n\n";
}
print '</harvestconfig>';
mysql_close($link);
?>
