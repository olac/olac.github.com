<?
##########
# Synopsis: Generates Archive Report Cards depending upon record scores
#
# Written by: Amol Kamat <amol@students.cs.mu.oz.au>
#
# CVS Info: $Header: /cvsroot/olac/web-20060328/language-archives/tools/scores/archiveReport.php,v 1.1 2006/03/28 07:21:56 stevenbird Exp $
##########

require_once "olacdb.php";
$DB = new OLACDB();
$maxItemScore = 10;
?>

<html>
<head>
    <link rel="stylesheet" type="text/css" href="olac.css">
    <title>Archive Report Card</title>
</head>
<body>

<TABLE CELLPADDING=0>
<tr>
<!-- SRC="http://www.language-archives.org/images/olac100.gif" -->
<td> <a HREF="http://www.language-archives.org/"><IMG
SRC="olac100.gif"
BORDER="0"></a></td>
<td> <h2> Archive Report Card </h2> </td>

<td> <? printForm( $_GET[archive] ); ?> </td>

</tr>
</table>
<hr>

<p><i>Note: This is an experimental service intended to help 
  repository maintainers improve the quality of their metadata.
  Please direct any comments to the
  <a href="http://lists.linguistlist.org/archives/olac-implementers.html">OLAC-Implementers</a>
  mailing list.
</i></p>

<?


if ($argv[1])
{
main($argv[1]);
}
else if ($_GET[archive])
{
main($_GET[archive]);
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


	if ($archiveID != "all")
   	{
    print "<h2>Core Tags Per Record
	<sub><a href='ExplainReport.html#Core_Tags_Per_Record'>*</a>
	</sub></h2>\n";
    archiveCoreTagsPerRecord( $archiveID, $DB );
	}

    print "<h2>Core Tag Usage
	<sub><a href='ExplainReport.html#Core_Tag_Usage'>*</a></sub></h2>\n";
    archiveCoreTagUsage( $archiveID, $DB );

    print "<h2>Code Usage
	<sub><a href='ExplainReport.html#Code_Usage'>*</a></sub></h2>\n";
    archiveCodeUsage( $archiveID , $DB, true );

    print "<h2>Tag and Code Usage
	<sub><a href='ExplainReport.html#Tag_And_Code_Usage'>*</a></sub>
	</h2>\n";
    archiveCodeUsage( $archiveID , $DB, false );

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

if ($archiveID=="all") 
{ 
echo "<h2>All Archives</h2>";
return; 
}

$archiveInfo = "select *
		from OLAC_ARCHIVE oa
		where Archive_ID = $archiveID";

$archiveInfoRes = $DB->sql( $archiveInfo );

$totalRecordsQuery = "select count(*) as total
                from ARCHIVED_ITEM ai
		where ai.Archive_ID = $archiveID";

$totalRecs = $DB->sql( $totalRecordsQuery );

$avgItemScoreQuery = "select avg(Item_Score) as avgScore
                        from ITEM_SCORES its, ARCHIVED_ITEM ai
                        where ai.Archive_ID = $archiveID
                        and its.Item_ID = ai.Item_ID";

$avgItemScore = $DB->sql( $avgItemScoreQuery );
print "<h2>{$archiveInfoRes[0][RepositoryName]}";
print " <img src=" . getStarImage($avgItemScore[0][avgScore]) . "></img></h2>";


$fieldsByRecQuery = "create temporary table fieldsByRec
			select me.Item_ID, count(*) as num
			from METADATA_ELEM me, ARCHIVED_ITEM ai
			where ai.Archive_ID = $archiveID
       			and ai.Item_ID = me.Item_ID
			group by Item_ID";

	($res = mysql_query($fieldsByRecQuery))
		or die("Error creating temporary table");

$avgFieldsPerRecQuery = "select round(avg(num), 2) as avgNum
			from fieldsByRec";

$avgFieldsPerRec = $DB->sql( $avgFieldsPerRecQuery );

$avgItemScore[0][avgScore]=round($avgItemScore[0][avgScore],2);
print "<table>";
print "	<tr>
		<th valign=top align=right>Repository Identifier:</th>
		<td>{$archiveInfoRes[0][RepositoryIdentifier]}</td>
	</tr>
	<tr>
		<th valign=top align=right>Archive URL:</th>
		<td><a href=\"{$archiveInfoRes[0][ArchiveURL]}\">
			{$archiveInfoRes[0][ArchiveURL]}</a></td>
	</tr>
	<!--
        <tr>
                <th valign=top align=right>Synopsis:</th>
                <td>{$archiveInfoRes[0][Synopsis]}</td>
	</tr>
	-->
	<tr>
		<th valign=top align=right>Archive Size:</th>
		<td>{$totalRecs[0][total]} record(s)</td>
	</tr>
	<tr>
		<th valign=top align=right>Average fields per record:</th>
		<td>{$avgFieldsPerRec[0][avgNum]}</td>
	</tr>\n";

print "</table>\n";

return;
}

########
# Prints a histogram of record scores
#
#######

function archiveItemRanks( $archiveID, $DB, $maxItemScore )
{
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

$SCORES;

foreach($result as $row)
{
$SCORES[$row[Item_Score]] = $row[num];
}


$avgItemScoreQuery = "select avg(Item_Score) as avgScore
                        from ITEM_SCORES its, ARCHIVED_ITEM ai
                        where its.Item_ID = ai.Item_ID ";
                        #where ai.Archive_ID = $archiveID

		if ($specArchive != "" ) { $avgItemScoreQuery .=$specArchive;}


$avgItemScore = $DB->sql( $avgItemScoreQuery );

$avgScore = round( $avgItemScore[0][avgScore], 2 );
echo "<h4>Average item score: $avgScore / 10</h4>";

# Display histogram of metadata quality scores
drawHistogram("Number of records", "Item Score", $maxItemScore, 
		$SCORES, $maxNum[0][maximum], "", "darkblue" );

return;
}

####
# Draws a horizontal bar graph of SCORES array. If bars are to be labeled as
#	percentages, $percent="%".
####

function drawHistogram($xLabel, $yLabel, $maxItemScore, $SCORES, $maxNum,
		$percent, $color )
{
    $scoreColWidth=5;
    $i=$maxItemScore;

    print "<table border=0 width=\"100%\">"
	. "<tr><td width=5%>$yLabel</td>";
    print "<td width=95%>";
    print "<table border=0 cellpadding=0 cellspacing=1 width=\"100%\">\n";
    while ($i >= 0)
    {
	print "<tr>\n<td>";
	if ($SCORES[$i] == "") { $SCORES[$i]=0; }

	if ($maxNum == 0) { $barWidth=0; }
	else 
	{
	    $barWidth = round( ($SCORES[$i] / $maxNum) 
		* (100-$scoreColWidth) );
	}

	print "<table border=0 cellpadding=0 cellspacing=0 width='100%'>\n";
	print "<tr>\n";

	# Item score
	print "<th width=\"$scoreColWidth%\">$i</th>\n";

	# Graph
	print "<td align=right bgcolor=$color width=\"$barWidth%\">
		<font color=white>$SCORES[$i]$percent</font></td>\n";

	# Leftover whitespace
	$leftOver = 100 - $scoreColWidth - $barWidth;
	print "<td width=\"$leftOver%\"></td>";

	print "</tr>";
	print "</table>";

	print "</tr></td>\n";
	$i--;
    }
    print "</table></td></tr>"
	. "<tr><td></td><td><center>$xLabel</center></td></tr></table>\n";
}


function getStarImage( $outOfTen )
{
return "star" . round($outOfTen/2) . ".gif";
}

#####
# Displays a table containing the percentage of elements which use code 
#	attributes. if $onlyCoreTags is true, table only contains numbers
#	for the core tags.
#####

function archiveCodeUsage( $archiveID, $DB, $onlyCoreTags )
{
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
			order by codeExists desc";

	/*
	$tagCodeUsageQuery = "select allTags.TagName, 
				archiveCodeUsage.timesUsed, codeUsed
				from allTags LEFT OUTER JOIN archiveCodeUsage
			on (allTags.TagName = archiveCodeUsage.TagName)";
	*/

	$tagBestPractice = $DB->sql($tagCodeUsageQuery);

		echo "<table>\n";
		echo "<th>Tag name</th>
			<th>Times used</th>
			<th>Code used</th>\n";

		foreach( $tagBestPractice as $tag )
		{
			$color = "white";
			if ( $tag[AppliesTo] == NULL )
			{
			if ($onlyCoreTags) { continue; }
			$codeUsage = "-";
			}
			else
			{
			    if ($tag[codeExists] < $tag[timesUsed])
				{ $color = "pink"; }
			    else { $color = "lightgreen"; }

			    if ($tag[timesUsed]!=0)
			    {
			    $codeUsage = round(
				100*($tag[codeExists] / $tag[timesUsed]),
				2 ) . "%";
			    }
			    else
			    {
			    $codeUsage = "0%";
			    }
			}

			if ($tag[timesUsed] == NULL) {$tag[timesUsed] = 0;}
			if ($tag[codeExists]==NULL) { $tag[codeExists]=0;}

		    echo "\n<tr bgcolor=$color><td>$tag[TagName]</td>
			<td>$tag[timesUsed]</td>
			<td>$codeUsage</td>
			</tr>";
			#<td>$tag[bestPractice]</td>
		}

		echo "</table>\n";

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
    if ($selectedID == $row[Archive_ID]) { print " selected "; }
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

if ( $subjectDiversity[0][numFields] == 0 )
{ $subjDiversity = 0; }
else
{
$subjDiversity = round (
	100 * ($subjectDiversity[0][codes] / $subjectDiversity[0][numFields]),
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

if ( $typeDiversity[0][numFields] == 0 )
{ $typeDiversity = 0; }
else
{
$typeDiversity = round (
	100 * ($typeDiversity[0][codes] / $typeDiversity[0][numFields]),
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

echo "<table>";
foreach( $numResult as $row )
{
    $proportion = round (100 * ($row[num] / $totalRecs[0][total]), 2 );

    if ($proportion < 100 ) { $color = "pink"; }
    else { $color = "lightgreen"; }

    echo "<tr bgcolor=$color><td>$row[TagName]:</td><td align=right>"
		. "$proportion%</td></tr>\n";
    $coreTags[$row[TagName]] = 1;
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

return;
}

#####
# Draws a histogram of the percentage of records which contain a specific
#	number of the core fields
#####

function archiveCoreTagsPerRecord( $archiveID, $DB )
{
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

$tagsPerRecQuery = "create temporary table coreRecordTags
			select ai.Item_ID, me.TagName
			from METADATA_ELEM me, ARCHIVED_ITEM ai
			where ai.Item_ID = me.Item_ID
			$specArchive
        		and me.TagName in (";

$i=0;
foreach($coreTags as $ct)
{
    if ($i != 0 ) { $tagsPerRecQuery .= ","; }
    $tagsPerRecQuery .= " '$ct' ";
    $i++;
}
/*
'title', 'description', 'subject',
                                'date','identifier')
*/
$tagsPerRecQuery .= ") group by me.Item_ID, me.TagName";

	($tagsPerRec = mysql_query($tagsPerRecQuery))
		or die("Error creating tagsPertemporary table");

	#echo "$tagsPerRecQuery<br />";
$coreTagsTempQuery = "create temporary table t
			select count(*) as num
			from coreRecordTags
			group by Item_ID";
	#echo "$coreTagsTempQuery<br />";

	($coreTags = mysql_query($coreTagsTempQuery))
		or die("Error creating temporary table");

$coreTagFrequencyQuery = "select num, count(*) as frequency
			from t
			group by num";

$coreTagFrequency = $DB->sql($coreTagFrequencyQuery);

$totalRecordsQuery = "select count(*) as total
                from ARCHIVED_ITEM ai
		where ai.Archive_ID = $archiveID";

$totalRecs = $DB->sql( $totalRecordsQuery );

$SCORES;
$sum=0;
foreach( $coreTagFrequency as $tagFreq )
{
	$SCORES[$tagFreq[num]] =round (
		($tagFreq[frequency] / $totalRecs[0][total]) * 100, 1 );
	$sum += round (
		($tagFreq[frequency] / $totalRecs[0][total]) * 100, 1 );
}

$SCORES[0] = round(100 - $sum, 1);

drawHistogram("Percentage of records", "Number of core tags", 5, $SCORES
			, 100, "%", "darkgreen");
}

?>

</body>
</html>
