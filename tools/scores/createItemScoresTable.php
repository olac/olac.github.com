<?

include "metadataScoring.php";
require_once "olacdb.php";

$DB = new OLACDB();

$itemsQuery = "select Item_ID from ARCHIVED_ITEM";

$items = $DB->sql($itemsQuery);

foreach($items as $item)
{

$itemReport = new ItemReport( $item[Item_ID], $DB );

$score = $itemReport->getScore();

$updateQuery = "REPLACE ITEM_SCORES( Item_ID, Item_Score )
		VALUES ( $item[Item_ID], $score )";
/*
$updateQuery = "INSERT INTO ITEM_SCORES( Item_ID, Item_Score )
		VALUES ( $item[Item_ID] , $score )
		ON DUPLICATE KEY UPDATE Item_Score=$score";
*/

#echo " $updateQuery\n";
$res = $DB->insertQuery($updateQuery);


}



?>
