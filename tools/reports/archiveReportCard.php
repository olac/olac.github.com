<?php
################
# Uses pre-generated html report cards from the $reportdir directory
################

include "reportInclude.php";

if ( array_key_exists('archive', $_GET) )
{ $archiveID = $_GET['archive']; }
else
{ $archiveID = ""; }

$withRanking="";
if ( array_key_exists('withranking', $_GET) && ($_GET['withranking']) )
{
    $withRanking = "_with_rank";
}

readfile("{$reportdir}{$prefix}{$archiveID}{$withRanking}.html");

?>
