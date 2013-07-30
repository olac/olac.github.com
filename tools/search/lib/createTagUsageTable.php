<?php
include "olacdb.php";

$DB = new OLACDB();
$MAX_RANK = 3;
$DEBUG=0;

$totalRecords = "select count(Item_ID) as num
                        from ARCHIVED_ITEM, OLAC_ARCHIVE
                        where
                        ARCHIVED_ITEM.Archive_ID = OLAC_ARCHIVE.Archive_ID";

$proportionRecsQuery = " select METADATA_ELEM_MYISAM.TagName, ELEMENT_DEFN.Tag_ID,
        count(distinct ARCHIVED_ITEM.Item_ID ) as proportion
        from METADATA_ELEM_MYISAM INNER JOIN ARCHIVED_ITEM
        ON METADATA_ELEM_MYISAM.Item_ID = ARCHIVED_ITEM.Item_ID
        INNER JOIN OLAC_ARCHIVE
        ON ARCHIVED_ITEM.Archive_ID = OLAC_ARCHIVE.Archive_ID
	INNER JOIN ELEMENT_DEFN
	ON ELEMENT_DEFN.Tag_ID = METADATA_ELEM_MYISAM.Tag_ID
        group by TagName
        order by proportion DESC";

$totalRecs = $DB->sql($totalRecords);
$proportionRecs = $DB->sql($proportionRecsQuery);

foreach($proportionRecs as $record)
{
	$percent = ($record['proportion'] / $totalRecs[0]['num'] ) * 100;

	$rank = floor ( 
		($record['proportion'] / $totalRecs[0]['num']) * ($MAX_RANK+1));

	$updateQuery = "REPLACE TAG_USAGE( Tag_ID, Percent, Rank)
			VALUES( $record[Tag_ID], $percent, $rank)";

	/*$updateQuery = "INSERT INTO TAG_USAGE(Tag_ID, Percent, Rank)
			 VALUES($record['Tag_ID'], $percent , $rank)
			 ON DUPLICATE KEY UPDATE Rank=$rank,
				Percent=$percent";
	*/

	if ($DEBUG) {echo "$record[TagName]: " . $updateQuery . "\n";}

	$DB->insertQuery($updateQuery);
		
}

?>
