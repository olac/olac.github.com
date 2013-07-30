use OLAC::DB;

print "Cleaning up...\n";
$db = new OLAC::DB("/home/olac/.dbinfo_olac2");
$dbh = $db->{dbh};
$dbh->{'AutoCommit'} = 0;

$sth = $dbh->prepare("select Element_ID from METADATA_ELEM where Item_ID=?");
$sth2 = $dbh->prepare("delete from METADATA_ELEM where Element_ID=?");
$sth3 = $dbh->prepare("delete from ARCHIVED_ITEM where Item_ID=?");
$sth4 = $dbh->prepare("delete from OLAC_ARCHIVE where Archive_ID=?");


print "  - Removing Archives with no BaseURL...";
$t1 = time;
$res = $dbh->selectcol_arrayref("select Archive_ID from OLAC_ARCHIVE where BaseURL=''");
foreach $archiveid (@$res) {
    $sth4->bind_param(1,$archiveid);
    $sth4->execute;
}
$dbh->commit();
$t2 = time;
($sec, $min, $hour) = gmtime($t2 - $t1);
printf "(elapsed time: %02d:%02d:%02d)\n", $hour, $min, $sec;


print "  - Removing records with no Archive ID...";
$t1 = time;
$res = $dbh->selectcol_arrayref("select Item_ID from ARCHIVED_ITEM where Archive_ID=NULL");
foreach $itemid (@$res) {
    $sth->bind_param(1,$itemid);
    $sth->execute;
    $res1 = $sth->fetchall_arrayref;
    foreach $tup (@$res1) {
	#print "$tup->[0]\n";
	$sth2->bind_param(1,$tup->[0]);
	$sth2->execute;
    }
    #print "*** $itemid\n";
    $sth3->bind_param(1,$itemid);
    $sth3->execute;
}
$dbh->commit();
$t2 = time;
($sec, $min, $hour) = gmtime($t2 - $t1);
printf "(elapsed time: %02d:%02d:%02d)\n", $hour, $min, $sec;


print "  - Removing redundant records...";
$t1 = time;
$res = $dbh->selectall_arrayref("select OaiIdentifier, Item_ID " .
                                "from ARCHIVED_ITEM order by OaiIdentifier, DateStamp desc");
$firstid = "";
foreach $tup (@$res) {
    # discard first record
    if ($firstid ne $tup->[0]) {
	$firstid = $tup->[0];
    }
    else {
	$itemid = $tup->[1];
	#print "$firstid $tup->[0] $itemid\n";
	$sth->bind_param(1,$itemid);
	$sth->execute;
	$res1 = $sth->fetchall_arrayref;
	foreach $tup1 (@$res1) {
	    #print "$tup1->[0]\n";
	    $sth2->bind_param(1,$tup1->[0]);
	    $sth2->execute;
	}
	#print "*** $itemid\n";
	$sth3->bind_param(1,$itemid);
	$sth3->execute;
    }
}
$dbh->commit();
$t2 = time;
($sec, $min, $hour) = gmtime($t2 - $t1);
printf "(elapsed time: %02d:%02d:%02d)\n", $hour, $min, $sec;

