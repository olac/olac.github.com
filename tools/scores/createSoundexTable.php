
<?

include "olacdb.php";
$DB = new OLACDB();

$allMetaQuery = "select	distinct Content from METADATA_ELEM";

echo "$allMetaQuery\n";

$allMetaResults = $DB->sql($allMetaQuery);	

$errors=0;

$punct = array("-", ",", ".", ":", ";", "]", "[","\"", "\'", "?", ")", "(",
		"/", "\\" );

foreach ($allMetaResults as $mr)
{
    $mr[Content] = addslashes($mr[Content]);
    $mr[Content] = str_replace( $punct, " ", $mr[Content]);
    $words = explode(" ", $mr[Content] );

    foreach( $words as $word )
    {

	$word = str_replace( $punct, $replace, $word );
	$insertQuery = "INSERT into SOUNDEX_TABLE(SoundexValue, Word)
			VALUES ( SUBSTRING(SOUNDEX('$word'),1,4) , '$word' )";

	#echo "$insertQuery\n";
	$result = $DB->insertQuery($insertQuery);
	if ($result != "") { $errors++; }
    }
}

echo "DONE. $errors duplicates.";

$DB->disconnect();

?>
