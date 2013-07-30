
<?php

include "olacdb.php";
$DB = new OLACDB();
$DEBUG=0;

$allMetaQuery = "select	distinct Content from METADATA_ELEM_MYISAM";

if ($DEBUG) { echo "$allMetaQuery\n"; }

$allMetaResults = $DB->sql($allMetaQuery);	

$errors=0;

$punct = array("-", ",", ".", ":", ";", "]", "[","\"", "\'", "?", ")", "(",
		"/", "\\" );

foreach ($allMetaResults as $mr)
{
    $mr['Content'] = addslashes($mr['Content']);
    $mr['Content'] = str_replace( $punct, " ", $mr['Content']);
    $words = explode(" ", $mr['Content'] );
    $replace = Array();

    foreach( $words as $word )
    {
	set_time_limit(0);

	$word = str_replace( $punct, $replace, $word );
	$insertQuery = "INSERT into SOUNDEX_TABLE(SoundexValue, Word)
			VALUES ( SUBSTRING(SOUNDEX('$word'),1,4) , '$word' )";

	if ($DEBUG) { echo "$insertQuery\n"; }
	$result = $DB->insertQuery($insertQuery);
	if ($result != "") { $errors++; }
    }
}

echo "DONE. $errors duplicates.";

$DB->disconnect();

?>
