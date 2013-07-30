<html>
<head>
    <title>Quantitative Analysis of Usage of Metadata Element Set</title>
    <link rel=stylesheet type="text/css" href="olac.css">
</head>
<body>

<?php

include "form.php";
include "olacdb.php";

$DB = new OLACDB();

?>

<h1>Metadata usage information</h1>
<form action="quantAnalysis.php" method="get">
<select name="archive">
<? archivesOptions($DB); ?>
</select>
<input type="submit">
</form>

<hr>

<?php

if ($_GET[archive])
{
    print "<h2>Statistics for \"$_GET[archive]\"</h2>";
    archiveInfo( $_GET[archive] );
}
else
{
    print "<h2>Statistics for all archives</h2>";
    archiveInfo( "" );	# for all archives
}


function archiveInfo( $archive )
{
	global $DB;

# Build the SQL Queries

if ( $archive != "" )
{
	$specArchive = "      and RepositoryIdentifier = '$archive'";
}
else { $specArchive = ""; }

$query = "select count(*) as num, me.TagName
from METADATA_ELEM me NATURAL JOIN ARCHIVED_ITEM ai, OLAC_ARCHIVE oa
where oa.Archive_ID = ai.Archive_ID
	$specArchive
group by TagName
order by num DESC
";

$totalElements = "select count(*) as total
		from METADATA_ELEM me, ARCHIVED_ITEM ai, OLAC_ARCHIVE oa
		where me.Item_ID = ai.Item_ID
			and ai.Archive_ID = oa.Archive_ID
			$specArchive ";

$avgTagsPerRecord1 = "select count(Item_ID) as num 
			from ARCHIVED_ITEM, OLAC_ARCHIVE 
			where 
			ARCHIVED_ITEM.Archive_ID = OLAC_ARCHIVE.Archive_ID
			$specArchive ";

if ($_GET[archive] != "")
{
$proportionRecsQuery = "select TagName,  
	count(distinct ARCHIVED_ITEM.Item_ID ) as proportion 
	from METADATA_ELEM INNER JOIN ARCHIVED_ITEM 
	ON METADATA_ELEM.Item_ID = ARCHIVED_ITEM.Item_ID 
	INNER JOIN OLAC_ARCHIVE 
	ON ARCHIVED_ITEM.Archive_ID = OLAC_ARCHIVE.Archive_ID 
	$specArchive
	group by TagName
	order by proportion DESC";
}
else
{
$proportionRecsQuery = "select ed.TagName, tu.Percent as proportion
			from ELEMENT_DEFN ed, TAG_USAGE tu
			where ed.Tag_ID = tu.Tag_ID";
}
			

$result = $DB->sql($query);
$totalResult = $DB->sql($totalElements);
$avgTagsRes1 = $DB->sql($avgTagsPerRecord1);
$proportionRecs = $DB->sql($proportionRecsQuery);

if ( $avgTagsRes1[0][num] == 0 ) { $avElems = 0; }
else 
{
  $avElems= round ($totalResult[0][total]/ $avgTagsRes1[0][num], 2);
}


print "<p>";
print "<table>\n";

print "<tr><th>Total records: </td><td> " 
	. $avgTagsRes1[0][num] . "</td></tr>";
print "<tr><th>Average elements per record: </th> ";

print  "<td>" . $avElems
	. "</td></tr>";
print "</table>\n";

print "</p><p>";
echo "<table border=0>\n";
echo "<th>Tag Name</th><th>Number of times used</th>
	<th>Percent of total metaelements</th>
	<th>Average times used per record</th>\n";
foreach( $result as $row )
{
	$percent = round (100 * ($row[num] / $totalResult[0][total]), 2);
	$perItem = round ($row[num] / $avgTagsRes1[0][num] , 2 );
    print "<tr><td>$row[TagName]</td><td>$row[num]</td>
		<td>$percent%</td>";

    print "<td>$perItem</td></tr>" ;
    
}


echo "<th>Total</th><td>" . $totalResult[0][total] . "</td>";
echo "</table>\n";
print "</p>";

    print "<table>";
    print "<th>Tag Name</th><th>Proportion of records using this tag</th>\n";
foreach($proportionRecs as $pr)
{
    if ($_GET[archive]=="") { $percent = $pr[proportion]; }
    else {
    $percent = round ( 100*( $pr[proportion] / $avgTagsRes1[0][num]    ) , 2);
    }
    print "<tr><td>$pr[TagName]</td><td>$percent%</td></tr>";
}
    print "</table>";

}
?>



</body>
</html>
