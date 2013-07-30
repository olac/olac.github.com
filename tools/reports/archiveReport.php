<?php
##########
# Synopsis: Generates Archive Report Cards depending upon record scores
#
# Maintained by: Baden Hughes <badenh@cs.mu.oz.au>
# Written by: Amol Kamat <amol@students.cs.mu.oz.au>
#
# CVS Info: $Header: /cvsroot/olac/web-20060328/language-archives/tools/reports/archiveReport.php,v 1.1 2006/03/28 07:21:43 stevenbird Exp $
##########

include "reportInclude.php";

/* Use gd library if installed */
if (extension_loaded('gd')) { $withGD=true; }

require_once LIB . "olacdb.php";
$DB = new OLACDB();
$maxItemScore = 10;
$decPlaces=0;

?>

<html>
<head>
    <link rel="stylesheet" type="text/css" href="<? print $STYLE; ?>olac.css">
    <title>OLAC Archive Report Card</title>
    <script type="text/javascript" src="/js/gatrack.js"></script>
</head>
<body>


<TABLE CELLPADDING=0>
<tr>
<td> <a HREF="http://www.language-archives.org/"><IMG
#SRC="http://www.language-archives.org/images/olac100.gif"
SRC=<? print $STYLE; ?>olac100.gif
alt="Open Language Archives"
BORDER="0"></a></td>
<td> <h2> OLAC Archive Report Card </h2> </td>

<td> 
<?php 
    if ( array_key_exists('archive', $_GET) )
    { printForm( $_GET['archive'] ); }
    else { printForm("all"); }
?> 
</td>

</tr>
</table>
<hr>

<?php

if (array_key_exists('archive', $_GET) )
{
main($_GET['archive']);
}

function main( $archiveID )
{
    global $DB, $maxItemScore;

    archiveInfo( $archiveID );
    print "<hr>\n";

    print "<h2>Archive Diversity
	<sub><a href='ExplainReport.html#Archive_Diversity'>*</a></sub></h2>\n";
    archiveDiversity( $archiveID, $DB );

    print "<h2>Metadata Quality
	<sub><a href='ExplainReport.html#Metadata_Quality'>*</a></sub></h2>\n";
    archiveItemRanks( $archiveID, $DB, $maxItemScore );

    print "<h2>Core Elements Per Record
	<sub><a href='ExplainReport.html#Core_Tags_Per_Record'>*</a>
	</sub></h2>\n";
    archiveCoreTagsPerRecord( $archiveID, $DB );

    print "<h2>Core Element Usage
	<sub><a href='ExplainReport.html#Core_Tag_Usage'>*</a></sub></h2>\n";
    archiveCoreTagUsage( $archiveID, $DB );

    print "<h2>Code Usage
	<sub><a href='ExplainReport.html#Code_Usage'>*</a></sub></h2>\n";
    archiveCodeUsage( $archiveID , $DB );

    print "<h2>Element and Code Usage
	<sub><a href='ExplainReport.html#Tag_And_Code_Usage'>*</a></sub>
	</h2>\n";
    archiveTagCodeUsage( $archiveID , $DB, false );

    if ($archiveID=="all" && array_key_exists("withranking", $_GET)
	&& ($_GET['withranking']))
    {
        print "<h2>Archive Ranking</h2>";
	rankArchives($DB);
    }

    return;
}

######
# Function prints:
#
#	Archive details, archive size, average fields per record
######

function archiveInfo( $archiveID )
{
global $DB;
if ($archiveID!="all")
{
	$specArchive = " and ai.Archive_ID = $archiveID ";
}
else
{
	$specArchive = "";
}

if ($archiveID!="all")
{
$archiveInfo = "select *
		from OLAC_ARCHIVE oa
		where Archive_ID = $archiveID";

$archiveInfoRes = $DB->sql( $archiveInfo );
}

$totalRecordsQuery = "select count(*) as total
                from ARCHIVED_ITEM ai ";

if ($archiveID!="all") 
{ $totalRecordsQuery .= " where ai.Archive_ID = $archiveID"; }


$totalRecs = $DB->sql( $totalRecordsQuery );

$avgItemScoreQuery = "select avg(Item_Score) as avgScore
                        from ITEM_SCORES its, ARCHIVED_ITEM ai
                        where its.Item_ID = ai.Item_ID";

if ($archiveID!="all")
{
$avgItemScoreQuery .= " and ai.Archive_ID = $archiveID "; }

$avgItemScore = $DB->sql( $avgItemScoreQuery );

    print "<table width=100%><tr><td>";

if ($archiveID!="all")
{
print "<h2>{$archiveInfoRes[0]['RepositoryName']}";
print " <img src=" . getStarImage($avgItemScore[0]['avgScore']) 
	. "></img></h2>";
}
else
{
print "<h2>All archives</h2>";
}

print "</td><td align=right>
		<a href='ExplainReport.html'>Report Card Explanation</a>
	</td></tr></table>";

$fieldsByRecQuery = "create temporary table fieldsByRec
			select me.Item_ID, count(*) as num
			from METADATA_ELEM me, ARCHIVED_ITEM ai
       			where ai.Item_ID = me.Item_ID
			$specArchive
			group by Item_ID";

	($res = mysql_query($fieldsByRecQuery))
		or die("Error creating temporary table");

$avgFieldsPerRecQuery = "select round(avg(num), 2) as avgNum
			from fieldsByRec";

$avgFieldsPerRec = $DB->sql( $avgFieldsPerRecQuery );

$avgItemScore[0]['avgScore']=round($avgItemScore[0]['avgScore'],2);
print "<table>";

if ($archiveID!="all")
{
print "	<tr>
		<th valign=top align=right>Repository Identifier:</th>
		<td>{$archiveInfoRes[0]['RepositoryIdentifier']}</td>
	</tr>
	<tr>
		<th valign=top align=right>Archive URL:</th>
		<td><a href=\"{$archiveInfoRes[0]['ArchiveURL']}\">
			{$archiveInfoRes[0]['ArchiveURL']}</a></td>
	</tr>
	<tr>
		<th valign=top align=right>Archive Details:</th>
		<td>
		<a href=
		'http://www.language-archives.org/archive.php4?id={$archiveID}'
		>http://www.language-archives.org/archive.php4?id={$archiveID}
			</a>
		</td>
	</tr>";

}
print "
	<tr>
		<th valign=top align=right>Size:</th>
		<td>{$totalRecs[0]['total']} record(s)</td>
	</tr>
	<tr>
		<th valign=top align=right>Average elements per record:</th>
		<td>{$avgFieldsPerRec[0]['avgNum']}</td>
	</tr>\n";

print "</table>\n";

return;
}

###########
# Creates a table of all archives and their star ratings
##########

function rankArchives($DB)
{

$rankArchivesQuery =
"select oa.Archive_ID, oa.RepositoryIdentifier, avg(Item_Score) as avgScore
from ITEM_SCORES its, ARCHIVED_ITEM ai, OLAC_ARCHIVE oa
where its.Item_ID = ai.Item_ID
        and ai.Archive_ID = oa.Archive_ID
group by ai.Archive_ID
order by avgScore desc
";

($result = $DB->sql($rankArchivesQuery)) or die("Ranking query failed");


print "<table>";
print "
<tr>
	<th>Archive</th><th>Star rating</th>
<tr>";
foreach($result as $row)
{
print "<tr><td>";

print "{$row['RepositoryIdentifier']}</td>";
print "<td><img src=" . getStarImage($row['avgScore']) . ">"
	. "</td></tr>";

}
print "</table>";

return;
}

########
# Prints a histogram of record scores
#
#######

function archiveItemRanks( $archiveID, $DB, $maxItemScore )
{

global $withGD;

if ($archiveID!="all")
{
	$specArchive = " and ai.Archive_ID = $archiveID ";
}
else
{
	$specArchive = "";
}

$itemScoreTableQuery = "create temporary table itemScoreTable
		select its.Item_Score, count(its.Item_ID) as num
		from ITEM_SCORES its, ARCHIVED_ITEM ai
		where its.Item_ID = ai.Item_ID
		$specArchive
		group by its.Item_Score
		order by its.Item_Score DESC";

	($itemScoreTable = mysql_query($itemScoreTableQuery))
		or die("Error creating temporary table");
$query = "select * from itemScoreTable";
$maxNumQuery = "select max(num) as maximum from itemScoreTable";

$result = $DB->sql( $query );
$maxNum = $DB->sql( $maxNumQuery );

$SCORES = Array();

foreach($result as $row)
{
$SCORES[$row['Item_Score']] = $row['num'];
}


$avgItemScoreQuery = "select avg(Item_Score) as avgScore
                        from ITEM_SCORES its, ARCHIVED_ITEM ai
                        where its.Item_ID = ai.Item_ID ";
                        #where ai.Archive_ID = $archiveID

		if ($specArchive != "" ) { $avgItemScoreQuery .=$specArchive;}


$avgItemScore = $DB->sql( $avgItemScoreQuery );

$avgScore = round( $avgItemScore[0]['avgScore'], 2 );
echo "<h4>Average record score: $avgScore / 10</h4>";

# Enter zeros for keys which do not exist
$j=0;
while($j<=10)
{
    if ( !array_key_exists($j , $SCORES ) )
    {
	$SCORES[$j] = 0;
    }
    $j++;
}

if ($withGD)
{
    $graph = getGraphURL( "Number of records (% of records)",  700, false , $SCORES, 
	"Number of records (% of records)", "Record Score", "", "bar", 20 );
    print "<img width=700 src=$graph>";
}
else
{
# Display histogram of metadata quality scores
drawHistogram("Number of records", "Record Score", $maxItemScore, 
		$SCORES, $maxNum[0]['maximum'], "", "darkblue", true );
}

return;
}

####
# Draws a horizontal bar graph of SCORES array. If bars are to be labeled as
#	percentages, $percent="%".
####

function drawHistogram($xLabel, $yLabel, $maxItemScore, $SCORES, $maxNum,
		$percent, $color, $showPercent )
{

    ksort($SCORES);
    $scoreColWidth=10;
    $i=$maxItemScore;

/*****

		$max=0;
		foreach($SCORES as $val)
		{
		if ($val > $max) { $max=$val; }
		}

    print "<table border=1 width=\"100%\">"
	. "<tr><td width=5%>$yLabel</td>\n";

    # GRAPH

    print "<td>\n";

    print "<table width=100% border=1>\n";
	print "<tr>\n";

	# Y labels
	print "<td width=5%>\n";
	print "<table>\n";

	foreach($SCORES as $key=>$value)
	{
	   print "<tr><td>$key</td></tr>\n";
	}

	print "</table>\n";
	print "</td>\n";
	##

	# GRAPHS
	print "<td>\n";
	print "<table width=100%>\n";

	foreach($SCORES as $key=>$value)
	{
	print "<tr>\n";
	print "</tr>\n";
	}

	print "</table>\n";
	print "</td>\n";
	##

	print "</tr>\n";
    print "</table>\n";

    print "</td>\n";
    ##

    print "</tr>\n";
    print "</table>\n";

******/



    print "<table border=0 width=\"100%\">"
	. "<tr><td width=5%>$yLabel</td>";
    print "<td width=95%>";

    print "<table border=0 cellpadding=0 cellspacing=1 width=\"100%\">\n";

    foreach($SCORES as $key=>$value)
    {
	print "<tr>\n<td>";

	if ( !array_key_exists( $i,$SCORES) ) {$SCORES[$i]=0;}

	if ($SCORES[$i] == "") { $SCORES[$i]=0; }
	if ($value == "") { $value=0; }

	if ($maxNum == 0) { $barWidth=0; }
	else 
	{
	    #$barWidth = round( ($SCORES[$i] / $maxNum) 
	    $barWidth = round( ($value / $maxNum) 
		* (100-$scoreColWidth) );
	}

	print "<table border=0 cellpadding=0 cellspacing=0 width='100%'>\n";
	print "<tr>\n";

	# Item score
	print "<th width=\"$scoreColWidth%\" align=right>$key</th>\n";
	
	# Graph
	#$percentage = round( 100 * $SCORES[$i] / (array_sum($SCORES)), 0);
	$percentage = round( 100 * $value / (array_sum($SCORES)), 0);
	print "<td align=right bgcolor=$color width=\"$barWidth%\">
		<font color=white>{$value}$percent
		</font></td>\n";

	# Leftover whitespace
	$leftOver = 100 - $scoreColWidth - $barWidth;
	
	if (!$percent && $showPercent)
	{
	#$percentage = round( 100 * $SCORES[$i] / (array_sum($SCORES)), 0);
	$percentage = round( 100 * $value / (array_sum($SCORES)), 0);
	print "<td width=\"$leftOver%\">&nbsp;({$percentage}%)</td>";
	}
	else
	{
	print "<td width=\"$leftOver%\">&nbsp;</td>";
	}

	print "</tr>";
	print "</table>";

	print "</tr></td>\n";
	$i--;
    }
    print "</table>"
	. "</td></tr>"
	. "<tr><td></td><td><center>$xLabel</center></td>"
	. "</tr></table>\n";
}

#########
# Returns the file name of the star image
#########

function getStarImage( $outOfTen )
{
    global $STYLE;
    return $STYLE . "star" . round($outOfTen/2) . ".gif";
}

#####
# Displays a table containing the percentage of elements which use code 
#	attributes. if $onlyCoreTags is true, table only contains numbers
#	for the core tags.
#####

function archiveTagCodeUsage( $archiveID, $DB, $onlyCoreTags )
{
global $withGD;

if ($archiveID!="all")
{
	$specArchive = " and ai.Archive_ID = $archiveID ";
}
else
{
	$specArchive = "";
}

	$archiveCodeUsageQuery = "create temporary table 
					archiveCodeUsage$onlyCoreTags
				select me.TagName, count(*) as timesUsed, 
					sum(me.Code !='') as codeExists
				from    METADATA_ELEM me, ARCHIVED_ITEM ai
        			where me.Item_ID = ai.Item_ID
				$specArchive
				group by me.TagName";

	($archiveCodeUsage = mysql_query($archiveCodeUsageQuery))
		or die("Error creating archiveCodeUsage temporary table");

	$allTagsQuery = "create temporary table allTags$onlyCoreTags
				select TagName
				from ELEMENT_DEFN ed";

	($allTags = mysql_query($allTagsQuery))
		or die("Error creating allTags temporary table");

	$tagCodeUsageQuery = "select allTags.TagName, 
			archiveCodeUsage.timesUsed, codeExists, ex.AppliesTo
			from allTags$onlyCoreTags as allTags
			LEFT OUTER JOIN archiveCodeUsage$onlyCoreTags
				as archiveCodeUsage
			on (allTags.TagName = archiveCodeUsage.TagName)
			LEFT OUTER JOIN EXTENSION ex
			on (ex.AppliesTo LIKE CONCAT('%', allTags.TagName, '%'))
			group by TagName
			order by timesUsed desc";

	/*
	$tagCodeUsageQuery = "select allTags.TagName, 
				archiveCodeUsage.timesUsed, codeUsed
				from allTags LEFT OUTER JOIN archiveCodeUsage
			on (allTags.TagName = archiveCodeUsage.TagName)";
	*/

	$tagBestPractice = $DB->sql($tagCodeUsageQuery);

		$SCORES = Array();
		foreach( $tagBestPractice as $tag )
		{
			if ($tag['timesUsed'] == NULL) {$tag['timesUsed'] = 0;}
			if ($tag['codeExists']==NULL) { $tag['codeExists']=0;}
			$SCORES[$tag['TagName']] = $tag['timesUsed'];
		}
	
	if (!$withGD)
	{
		$max=0;
		foreach($SCORES as $val)
		{
		if ($val > $max) { $max=$val; }
		}

		drawHistogram("Times used", 
			"Element", 4 , 
			$SCORES, $max, "", "darkblue", false );
		/*
		echo "<table>\n";
		echo "<th>Element name</th>
			<th>Times used</th>
			<th>Code used</th>\n";

		foreach( $tagBestPractice as $tag )
		{
			$color = "white";
			if ( $tag['AppliesTo'] == NULL )
			{
			if ($onlyCoreTags) { continue; }
			$codeUsage = "-";
			}
			else
			{
			    if ($tag['codeExists'] < $tag['timesUsed'])
				{ $color = "pink"; }
			    else { $color = "lightgreen"; }

			    if ($tag['timesUsed']!=0)
			    {
			    $codeUsage = round(
				100*($tag['codeExists'] / $tag['timesUsed']),
				2 ) . "%";
			    }
			    else
			    {
			    $codeUsage = "0%";
			    }
			}

			if ($tag['timesUsed'] == NULL) {$tag['timesUsed'] = 0;}
			if ($tag['codeExists']==NULL) { $tag['codeExists']=0;}

		    echo "\n<tr bgcolor=$color><td>$tag[TagName]</td>
			<td>$tag[timesUsed]</td>
			<td>$codeUsage</td>
			</tr>";
			#<td>$tag[bestPractice]</td>
		}

		echo "</table>\n";
		*/
	}
	else
	{
		$graph = getGraphURL( "Times used",  700, 
			false, $SCORES,
			"Times used", "Element", "", 
			"maximum", 120 );
		print "<img width=700 src=$graph>";
	}
}

#####
# Drop-down box of archives
#####

function printForm( $selectedID )
{
global $DB;

$query = "select Archive_ID, RepositoryIdentifier
		from OLAC_ARCHIVE
		order by RepositoryIdentifier";

$res = $DB->sql($query);


print "<form method=get>\n<select name=archive>";
print "<option value=\"all\">-- All archives --";
foreach($res as $row)
{
    print "<option value=\"$row[Archive_ID]\"";
    if ($selectedID == $row['Archive_ID']) { print " selected "; }
    print ">$row[RepositoryIdentifier]";
}
print "</select>\n";
print "<input type=\"submit\">\n</form>";


}

#######
# Finds the percentage of distinct code attributes used in the subject and
#	type fields
######

function archiveDiversity( $archiveID, $DB )
{

if ($archiveID!="all")
{
	$specArchive = " and ai.Archive_ID = $archiveID ";
}
else
{
	$specArchive = "";
}

$subjectDiversityQuery =
	"select count(distinct me.Code) as codes, 
		count( me.Item_ID ) as numFields
	from ARCHIVED_ITEM ai, METADATA_ELEM me
        where ai.Item_ID = me.Item_ID
		$specArchive
        	and TagName = 'subject'	
		and me.Code != ''";

$subjectDiversity = $DB->sql( $subjectDiversityQuery );

echo "<!-- subjectDiversity\n$subjectDiversityQuery\n-->";

if ( $subjectDiversity[0]['numFields'] == 0 )
{ $subjDiversity = 0; }
else
{
$subjDiversity = round (
	100 * ($subjectDiversity[0]['codes'] / $subjectDiversity[0]['numFields']),
	2 );
}


$typeDiversityQuery =
	"select count(distinct me.Code) as codes, 
		count( me.Item_ID ) as numFields
	from ARCHIVED_ITEM ai, METADATA_ELEM me
        where ai.Item_ID = me.Item_ID
		$specArchive
        	and TagName = 'type'
		and me.Code != ''";

$typeDiversity = $DB->sql( $typeDiversityQuery );

if ( $typeDiversity[0]['numFields'] == 0 )
{ $typeDiversity = 0; }
else
{
$typeDiversity = round (
	100 * ($typeDiversity[0]['codes'] / $typeDiversity[0]['numFields']),
	2 );
}

print "<table border=0>";
print "<tr><th align=left>Diversity by Subject:</th>\n";
print "<td align=left>$subjDiversity%</td></tr>\n";
print "<tr><th align=left>Diversity by Type:</th>\n";
print "<td>$typeDiversity%</td></tr>\n";
print "</table>\n";

return;
}

######
# A table of the percentage of records which contain a given element at least
#	once. Uses a set of core tags.
#####

function archiveCoreTagUsage( $archiveID, $DB )
{
global $decPlaces, $withGD;

if ($archiveID!="all")
{
	$specArchive = " ARCHIVED_ITEM.Archive_ID = $archiveID and ";
}
else
{
	$specArchive = "";
}

$coreTags = Array(
		"title" => 0,
		"description" => 0,
		"subject" => 0,
		"date" => 0,
		"identifier" => 0
		);

$numQuery = "select TagName,
        count(distinct ARCHIVED_ITEM.Item_ID ) as num
        from METADATA_ELEM INNER JOIN ARCHIVED_ITEM
        ON METADATA_ELEM.Item_ID = ARCHIVED_ITEM.Item_ID
        INNER JOIN OLAC_ARCHIVE
        ON ARCHIVED_ITEM.Archive_ID = OLAC_ARCHIVE.Archive_ID
        where $specArchive (";

	#ARCHIVED_ITEM.Archive_ID = $archiveID and (";

$i=0;
foreach( $coreTags as $key => $value )
{
	if ($i!=0) { $numQuery .= " or "; }
	$numQuery .= " METADATA_ELEM.TagName = '$key' ";
	$i++;
}

$numQuery .= ") group by TagName
        	order by num DESC";

$numResult = $DB->sql( $numQuery );

$totalRecordsQuery = "select count(*) as total
                from ARCHIVED_ITEM ai ";

	if ($specArchive != "") 
	{ 
	    $totalRecordsQuery .=  " where ai.Archive_ID = $archiveID";
	}

$totalRecs = $DB->sql( $totalRecordsQuery );

$SCORES = Array();
foreach( $numResult as $row )
{
    $proportion = round (100 * ($row['num'] / $totalRecs[0]['total']), 
	$decPlaces);
    $SCORES[$row['TagName']] = $proportion;
    $coreTags[$row['TagName']] = 1;
}
foreach( $coreTags as $key => $value )
{
		# Prints zero for any tag names not returned by the query
    if ( $value == 0 )
    {
	$SCORES[$key]=0;
    }
}
if (!$withGD)
{

drawHistogram("Percentage of records which contain element", 
	"Element", 4 , 
		$SCORES, 100, "%", "darkblue", true );

/*
  echo "<table>";
  foreach( $numResult as $row )
  {
    $proportion = round (100 * ($row['num'] / $totalRecs[0]['total']), 
	$decPlaces );

    if ($proportion < 100 ) { $color = "pink"; }
    else { $color = "lightgreen"; }

    echo "<tr bgcolor=$color><td>$row[TagName]:</td><td align=right>"
		. "$proportion%</td></tr>\n";
    $coreTags[$row['TagName']] = 1;
  }
  foreach( $coreTags as $key => $value )
  {
		# Prints zero for any tag names not returned by the query
    if ( $value == 0 )
    {
	echo "<tr bgcolor=pink><td>$key:</td><td align=right>0%</td>"
		. "</tr>\n";
    }
  }
  echo "</table>";
*/
}
else
{
$graph = getGraphURL( "Percentage of records which contain element",  700, 
	true, $SCORES,
	"Percentage of records which contain element", "Element", "", 
	"proportion", 120 );
print "<img width=700 src=$graph>";
}

return;
}

#####
# Draws a histogram of the percentage of records which contain a specific
#	number of the core fields
#####

function archiveCoreTagsPerRecord( $archiveID, $DB )
{

global $decPlaces, $withGD;
if ($archiveID!="all")
{
	$specArchive = " and ai.Archive_ID = $archiveID ";
}
else
{
	$specArchive = "";
}

$coreTags = Array(
		"title",
		"description",
		"subject",
		"date",
		"identifier"
		);

$itemTagsTempQuery = "
create temporary table itemTags
select ai.Item_ID, count(distinct me.TagName) as num
                        from METADATA_ELEM me, ARCHIVED_ITEM ai
                        where ai.Item_ID = me.Item_ID
			$specArchive
                        and me.TagName in ('subject','date','identifier',
                                'title','description')
                        group by me.Item_ID";

	($itemTags = mysql_query($itemTagsTempQuery))
		or die("Error creating temporary table");

$coreTagFrequencyQuery = "
select num, count(num) as frequency from itemTags 
	group by num 
	order by frequency
";

$coreTagFrequency = $DB->sql($coreTagFrequencyQuery);

$totalRecordsQuery = "select count(*) as total
                from ARCHIVED_ITEM ai";

if ($archiveID != "all") 
{
	$totalRecordsQuery .= " where ai.Archive_ID = $archiveID";
}

$totalRecs = $DB->sql( $totalRecordsQuery );


$SCORES = Array();
$sum=0;
foreach( $coreTagFrequency as $tagFreq )
{
	$SCORES[$tagFreq['num']] =round (
		($tagFreq['frequency'] / $totalRecs[0]['total']) * 100, 
		$decPlaces );
	$sum += round (
		($tagFreq['frequency'] / $totalRecs[0]['total']) * 100,
		$decPlaces );
}
$SCORES[0] = max(0, round(100 - $sum, 1));

# Enter zeros for keys which do not exist
$j=0;
while($j<=5)
{
    if ( !array_key_exists($j , $SCORES ) )
    {
	$SCORES[$j] = 0;
    }
    $j++;
}

if ($withGD)
{
    $graph = getGraphURL( "Percentage of records",  700, true, $SCORES,
	"Percentage of records", "Number of core elements", "", "bar", 20);
    print "<img width=700 src=$graph>";
}
else
{
    drawHistogram("Percentage of records", "Number of core elements", 5, $SCORES
			, 100, "%", "darkblue", true);
}

}

########
# Returns the URL to the barChart.php graph script 
########

function getGraphURL( $title, $width, $percent, $values, 
	$xAxisLabel, $yAxisLabel, $barLabel, $type , $labelWidth)
{

global $URLBASE;
ksort($values);

$title = str_replace( " ", "%20", $title );
$xAxisLabel = str_replace( " " , "%20", $xAxisLabel );
$yAxisLabel = str_replace( " " , "%20", $yAxisLabel );
$barLabel = str_replace( " " , "%20", $barLabel );

if ($type == "bar")
{
$graph = "{$URLBASE}barChart.php?mode=aggregate&labelWidth=$labelWidth&"
    . "title=$title&width=$width&percent=$percent&ylabel=$yAxisLabel&data=";
}
else if ($type == "proportion")
{
$graph = "{$URLBASE}barChart.php?mode=percentage&labelWidth=$labelWidth&"
    . "title=$title&width=$width&percent=$percent&ylabel=$yAxisLabel&data=";
}
else if ($type == "maximum")
{
$graph = "{$URLBASE}barChart.php?mode=maximum&labelWidth=$labelWidth&"
    . "title=$title&width=$width&percent=$percent&ylabel=$yAxisLabel&data=";
}

$i=0;
foreach( $values as $key=>$value )
{
     $graph .= "{$key}%20{$barLabel}^{$value}^^";
     $i++;
}

return $graph;

}

function archiveCodeUsage( $archiveID, $DB )
{
global $decPlaces, $withGD;

if ($archiveID!="all")
{
	$specArchive = " and ai.Archive_ID = $archiveID ";
}
else
{
	$specArchive = "";
}

$codeTags = Array(
		"contributor" => 0,
		"subject" => 0,
		"language" => 0,
		"type" => 0
		);

$query = "select count(me.Item_ID) as timesUsed, sum(me.Code!='') as codeUsed,
		me.TagName
	from ARCHIVED_ITEM ai, METADATA_ELEM me
	where ai.Item_ID = me.Item_ID
	$specArchive
	and TagName in (";

$i=0;
foreach($codeTags as $key=>$value)
{
    if ($i!=0) {$query .= ","; }
    $query .= " '$key' ";
    $i++;
}

$query .= ") group by me.TagName order by timesUsed desc";

$result = $DB->sql($query);

$SCORES = Array();
foreach($result as $row)
{
    $percent = 0;
    if ($row['timesUsed'] != 0 )
    {
	$percent = round (100* ($row['codeUsed'] / $row['timesUsed']), 
		$decPlaces);
    }

    $SCORES[$row['TagName']]=$percent;
    $codeTags[$row['TagName']] = 1;
}
foreach($codeTags as $key=>$value)
{
    if (!$value)
    {
	$SCORES[$key] = 0;
    }
}

if (!$withGD)
{
drawHistogram("Percentage of records which use code", 
	"Element", 5, 
		$SCORES, 100, "%", "darkblue", true );
/*
echo "<table><tr><th>Element Name</th>
	<th>Times used</th>
	<th>Code used</th></tr>";

foreach($result as $row)
{
    $percent = 0;
    if ($row['timesUsed'] != 0 )
    {
	$percent = round (100* ($row['codeUsed'] / $row['timesUsed']), 
		$decPlaces);
    }

    $color="lightgreen";
    if ($row['timesUsed']>$row['codeUsed']) { $color = "pink"; }

    echo "<tr bgcolor=$color><td>$row[TagName]</td>
	<td>$row[timesUsed]</td>
	<td>$percent%</td></tr>";

    $codeTags[$row['TagName']] = 1;
}
foreach($codeTags as $key=>$value)
{
    if (!$value)
    {
    echo "<tr bgcolor=lightgreen><td>$key</td>
	<td>0</td>
	<td>0%</td></tr>";
    }
}
echo "</table>\n";


*/

}
else
{

$graph = getGraphURL( "Percentage of records which use code",  700, 
	true, $SCORES,
	"Percentage of records which contain element", "Element", "", 
	"proportion", 120 );
print "<img width=700 src=$graph>";
}

}

?>

</body>
</html>
