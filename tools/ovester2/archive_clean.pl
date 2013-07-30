#! /usr/bin/env perl -w
use DBI;

if ($#ARGV != 1) {
    print "usage: perl archive_clean.pl <dbinfo> <url>\n";
    exit(0);
}

open (PASS, <$ARGV[0]>);
$dbname = <PASS>;
$user = <PASS>;
$pass = <PASS>;
chomp $dbname;
chomp $user;
chomp $pass;

$dbh = DBI->connect("dbi:mysql:$dbname",$user,$pass);
die(DBI->errstr) if DBI->errstr;

$aids = $dbh->selectcol_arrayref("select Archive_ID from OLAC_ARCHIVE ".
                                 "where BaseURL='$ARGV[1]'");
foreach $aid (@$aids) {
    my $iids = $dbh->selectcol_arrayref
        ("select Item_ID from ARCHIVED_ITEM where Archive_ID='$aid'");
    $dbh->do("delete from OLAC_ARCHIVE where Archive_ID='$aid'");
    $dbh->do("delete from ARCHIVED_ITEM where Archive_ID='$aid'");
    foreach my $iid (@$iids) {
        $dbh->do("delete from METADATA_ELEM where Item_ID='$iid'");
    }
}

