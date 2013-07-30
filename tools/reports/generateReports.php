<?php

####
#
# Synopsis: Generates archive report cards as created by archiveReport.php
#	for all archives. Report cards are viewed using archiveReportCard.php
#
# Written by: Amol Kamat <amol@students.cs.mu.oz.au>
# Minor fixes by: Steven Bird <sb@cs.mu.oz.au>
#
# CVS Info: $Header: /cvsroot/olac/web-20060328/language-archives/tools/reports/generateReports.php,v 1.1 2006/03/28 07:21:43 stevenbird Exp $
####

include "reportInclude.php";
require_once(LIB . "olacdb.php");
$DB=new OLACDB();

global $URLBASE;
$DEBUG=1;

function copyFile($infile, $outfile) {
    while ( $string = fgets($infile) ) {
        fwrite( $outfile, $string );
    }
}

function file_open( $filename, $mode )
{
    if (@fopen($filename, $mode) != FALSE)
    {
	return fopen($filename, $mode);
    }
    else
    {
	return null;
    }

}

/*
function writeStringToFIle( $string, $outfile )
{
	fwrite( $outfile, $string );
	return;
}
*/

  $archivesQuery = "select Archive_ID from OLAC_ARCHIVE";
  $archives = $DB->sql($archivesQuery);

# Form where no archive selected

if ($DEBUG) {
    echo "Form: {$reportdir}{$prefix}.html\n";
}

$filename="{$reportdir}{$prefix}.html";
$infile=file_open("{$URLBASE}archiveReport.php", 'r');
$outfile=file_open($filename, 'w');
if (($outfile!=null)&&($infile!=null)) { copyFile($infile, $outfile); }

/*
$filename="{$reportdir}{$prefix}.html";
$report = archiveReport("", $DB);
$outfile=file_open($filename, 'w');
writeStringToFile($report, $outfile);
*/


# Archive reports for archives
foreach($archives as $archive) {
    if ($DEBUG == 1) {
	echo "Archive ID: {$archive['Archive_ID']} "
		. "- {$reportdir}{$prefix}{$archive['Archive_ID']}.html\n";
	flush();
    }

    $filename="{$reportdir}{$prefix}{$archive['Archive_ID']}.html";
    $infile=file_open(
        "{$URLBASE}archiveReport.php?archive={$archive['Archive_ID']}", 'r');
    $outfile=file_open($filename, 'w');
    if (($outfile!=null)&&($infile!=null)) { copyFile($infile, $outfile); }

/*
    $filename="{$reportdir}{$prefix}{$archive['Archive_ID']}.html";
    $report = archiveReport( $archive['Archive_ID'], $DB );
    $outfile=file_open($filename, 'w');
    writeStringToFile($report, $outfile);
*/
    

}

# Report for all archives

    if ($DEBUG) {
	echo "All archives: {$reportdir}{$prefix}all_with_rank.html\n";
    }

    $filename="{$reportdir}{$prefix}all_with_rank.html";
    $infile=file_open("{$URLBASE}archiveReport.php?archive=all&withranking=1", 
	'r');
    $outfile=file_open($filename, 'w');
    if (($outfile!=null)&&($infile!=null)) {copyFile($infile, $outfile);}

    if ($DEBUG) {
	echo "All archives: {$reportdir}{$prefix}all.html\n";
    }

    $filename="{$reportdir}{$prefix}all.html";
    $infile=file_open("{$URLBASE}archiveReport.php?archive=all", 
	'r');
    $outfile=file_open($filename, 'w');
    if (($outfile!=null)&&($infile!=null)) {copyFile($infile, $outfile);}

/*
    $filename="{$reportdir}{$prefix}all.html";
    $report = archiveReport("all", $DB);
    $outfile=file_open($filename, 'w');
    writeStringToFile( $report, $outfile );
*/

?>
