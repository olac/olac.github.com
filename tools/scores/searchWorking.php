<?php
######################################################################
#
# Synopsis: Search metadata elements of OLAC 1.0 database for the given string.
#
# ChangeLog:
#
# 2004-01-23 Amol Kamat <amol@students.cs.mu.oz.au>
# 2003-03-01  Haejoong Lee  <haejoong@ldc.upenn.edu>
#	* revised previous version (/tools/search.php4) to use new database
#	* use /tools/lookup/lookup-OLAC1.0.php4 for record lookup
#
# CVS Info: $Header: /cvsroot/olac/web-20060328/language-archives/tools/scores/searchWorking.php,v 1.1 2006/03/28 07:21:56 stevenbird Exp $
######################################################################

#require_once("OLACDB.php");
include "olacdb.php";
$DB = new OLACDB();

$scriptName = "searchWorking.php";

$query = $_GET[query];
if ($_GET[queryTerms]) { $query = $_GET[queryTerms]; }
if ($_GET[simSpell]) { $query = $_GET[simSpell]; }
if ($_GET[altNames]) { $query = $_GET[altNames]; }
if ($_GET[country]) { $query = $_GET[country]; }

$maxStars = 5;
$maxTagRank = 3;
$maxQualityRank = 10;

# Used to provide the archive list in the dropdown box

function archivesOptions()
{
    global $DB;
    $query = "select RepositoryIdentifier from OLAC_ARCHIVE
		ORDER BY RepositoryIdentifier";

    ($result = $DB->sql($query)) or die ("Error creating archive option box");

    echo "<option value=\"\">-- All archives --";
    foreach($result as $arch)
    {
	echo "<option value='$arch[RepositoryIdentifier]'";
	if ($_GET[archive] == $arch[RepositoryIdentifier]) { echo " selected";}
	print ">$arch[RepositoryIdentifier]\n";
    }
    return;
}
?>

<HTML>
<HEAD>
<TITLE>Search OLAC Archives</TITLE>
<LINK REL="stylesheet" TYPE="text/css" HREF="olac.css">
</HEAD>

<BODY>
<TABLE CELLPADDING="0">

<!-- Banner -->
<TR>
<!--SRC="http://www.language-archives.org/images/olac100.gif" -->
<TD> <A HREF="http://www.language-archives.org/"><IMG
SRC="olac100.gif"
BORDER="0"></A></TD>
<TD> <H3><FONT COLOR="0x00004a">Search<br /> OLAC:
</FONT>
</H3>
</TD>
<td>

<!-- Query form -->
<form method="get">
<input type="text" size="30" maxlength="30" name="query" value="<? print $query; ?>">
<input type="submit" value="Find">
<input type="checkbox" value="all" name="allmode" 
	onClick="this.form.phrasemode.checked=false"

	<? if ($_GET[allmode]) print "checked"; ?> 
	> Match substrings</input>

<input type="checkbox" value="phrase" name="phrasemode" 
	onClick="this.form.allmode.checked=false"

	<? if ($_GET[phrasemode]) print "checked"; ?> 
	> Match exact phrase</input>
<br />
<select name="archive">
<? archivesOptions(); ?>
</select>
<input type=hidden name=page value=1>
</form>
</td>
</TR>
</TABLE>

<?
$output = "";

# If allmode, tokenizes the query using spaces as separators
# If phrasemode, does no tokenizing

function tokenizeQuery($queryString)
{
    $tokens = array();
    $separators = " \t";

    if ($_GET[phrasemode])
    {
	$tokens[0]= trim($queryString);
	return $tokens;
    }

    $tok = strtok($queryString, $separators);
    $i=0;
    while ($tok)
    {
	#$tokens[$i] = str_replace( "*", "%", $tok ); #Replace wildcard
	$tokens[$i] = $tok;
	$tok = strtok( $separators );
	$i++;
    }

    return $tokens;
}

function starImage( $num ) { return "star" . $num . ".gif"; }

# Shortens a string to a certain length, keeping words intact and appends '...'
function shorten ($var, $len)
{
   if (empty ($var)) { return ""; }
   if (strlen ($var) < $len) { return $var; }
   if (preg_match ("/(.{1,$len})\s/", $var, $match)) {
           return $match [1] . "...";
   }
   else {
     return substr ($var, 0, $len) . "...";
   }
}

####
# Display the information about a single matching record.
#
# Uses a predefined set of tags to display:
#	title, description, subject, date, identifier
#
####

function formatResult( $row, $counter, $queryTokens )
{
    global $DB;
    $maxMoreInfo=250; # Maximum length of synopsis information
		      # in addition to the content of the matching tag
		      # ie, 2x is maximum summary length

    $moreInfoQuery = "select TagName, Content 
		from METADATA_ELEM me
			where me.Item_ID = '$row[Item_ID]'
			and me.TagName 
			in ('title', 'description', 'subject', 'date',
				'identifier')
			and NOT (me.Content = '')
			and NOT (me.TagName = '$row[TagName]')
			order by me.TagName = 'title' DESC,
				me.TagName = 'description' DESC,
				me.TagName = 'subject' DESC,
				me.TagName = 'date' DESC,
				me.TagName = 'identifier' DESC ";

/* # Displays the most popular tags based upon the tag_usage table
   # rather than the hard coded set above
$moreInfoQuery = "select TagName, Content
                from METADATA_ELEM me, TAG_USAGE tu
                        where me.Item_ID = '$row[Item_ID]'
                        and tu.Tag_ID = me.Tag_ID
                        and tu.Rank > 1
                        and NOT (me.Content = '')
                        and NOT (me.TagName = '$row[TagName]')
                        order by tu.Rank";
*/

$moreInfoRes = $DB->sql($moreInfoQuery);

$moreInfo="";
foreach ($moreInfoRes as $tag)
{
    $moreInfo .= "<b>$tag[TagName]:</b> $tag[Content]<br />";
    if (strlen($moreInfo) > $maxMoreInfo) { break; }
}

$content = $row[Content];

if ($content=="") { $content=$row[Code]; }

foreach ($queryTokens as $tok)
{
   # Case insensitive matching of query keywords
   $content = ereg_replace( sql_regcase($tok), 
	"<em>\\0</em>", $content );
   $moreInfo = ereg_replace( sql_regcase($tok), 
	"<em>\\0</em>", $moreInfo );
}

# If the content of the matching tag itself is longer than the maximum
#	synopsis length, shorten to bring the first occurance of the
#	search term toward the front of the content and shorten the
#	string

if (strlen($content) > $maxMoreInfo )
{
    $firstOcc = strpos($content,"<em>");
    $content = substr( $content, max(0, $firstOcc-10) );
	if ($firstOcc != 0) { $content = "..." . $content; }
    $content = shorten($content, $maxMoreInfo);

}

$queryString = str_replace( " ", "+", $_GET[query] );

# Return tabulated result record
return " <table><tr><td>&nbsp;&nbsp;</td>
	<td>
	$counter."
	. " <img src=" . starImage($row[recordScore]) . "></img>"
	. " <a href=\"$_SERVER[PHP_SELF]?item=$row[OaiIdentifier]"
	. "&queryTerms=$queryString&phrasemode=$_GET[phrasemode]"
	. "&allmode=$_GET[allmode]\">"
	. "$row[OaiIdentifier]</a>"
	. "<br /><b>$row[TagName]:</b> $content<br />"
	. "<small>" . shorten( $moreInfo, $maxMoreInfo ) . "</small>"
	. "</td></tr></table>";

}

#####
# Should return an SQL field as a suitable rank out of 5 for any record.
#	Uses:
#		tu.Rank as the TAG_USAGE ( integer between 0 and 3 )
#		its.Item_Score as quality rank (integer between 0 and 10)
#####

function getRecordScore()
{
	global $maxStars;
	global $maxTagRank;
	global $maxQualityRank;

    #return "round( ( (tu.Rank + its.Item_Score)*$maxStars)/
    #		($maxTagRank+$maxQualityRank) )";

    return " round( (its.Item_Score*$maxStars)/($maxQualityRank)  ) ";

}

###
# Builds SQL query of the format:
#	
#	select OaiIdentifier, DateStamp, a.Item_ID {, en.*}
#    	from ARCHIVED_ITEM as a {, METADATA_ELEM as e1}	
#    	where {a.Item_ID=e1.Item_ID and} ( URL-unencoded-sql-argument )
#    	order by OaiIdentifier	
#					
#  as described at	
# 	http://www.language-archives.org/NOTE/query.html
###


function buildSqlQuery( $tokens, $langCode )
{
    global $DB;

    $selectClause = " ar.Archive_ID, ar.Item_ID, ar.OaiIdentifier,
			oa.RepositoryIdentifier ";

    $selectClause .= 
		", " . getRecordScore() . " as recordScore ";

    $fromClause = " ARCHIVED_ITEM ar, OLAC_ARCHIVE oa ";
	$fromClause .= ", TAG_USAGE tu, ITEM_SCORES its ";


    $whereClause = " oa.Archive_ID = ar.Archive_ID ";
	$whereClause .= " and its.Item_ID = ar.Item_ID ";

    $orderByClause = " oa.RepositoryIdentifier, recordScore DESC ";
    $j=0;
    while ($j < sizeof($tokens) )
    {
	$me = "me" . $j;

	if ($j == 0)			# first token	
	{ 
	    $selectClause .= ", $me.TagName, $me.Content, $me.Code "; 
	    $whereClause .= " and tu.Tag_ID = $me.Tag_ID ";
	}

	$fromClause .= ", METADATA_ELEM $me ";

	# If language code search, only match with language codes
	if ($langCode)
	{
	$whereClause .= " and ar.Item_ID=$me.Item_ID
		and $me.Code LIKE '%$langCode%'
		and ($me.TagName = 'subject'
			or $me.Tagname = 'language') ";
	}
	# If only using substring matching in phrase or all mode
	else if (($_GET[phrasemode])||($_GET[allmode]))
	{
	$whereClause .= " and ar.Item_ID=$me.Item_ID 
		and $me.Content LIKE '%$tokens[$j]%' ";
	}
	# If looking for exact word (not substrings) use MATCH AGAINST syntax
	else if ((!$_GET[phrasemode])&&(!$_GET[allmode]))
	{
		# Full text index does not index words shorter than 4 chars
		# If a word is less than 4 chars and there is at least another
		#	word in the query string, use substring matching
		#	for the word < 4 chars
		#if ((strlen($tokens[$j]) < 4 )&&(sizeof($tokens)>1))
		#{
		#    $whereClause .= " and ar.Item_ID=$me.Item_ID 
		#	and $me.Content LIKE '%$tokens[$j]%' ";
		#}
		#else
		#{
		    $whereClause .= " and ar.Item_ID=$me.Item_ID 
			and MATCH($me.Content) AGAINST('$tokens[$j]') ";
		#}
	}
	$j++;
    }
    
    if ($_GET[archive] != "")
    {
	$whereClause .= " and oa.RepositoryIdentifier = '$_GET[archive]' ";
    }
  
 
/*# 	Returns the matching records, but does no quality ranking
    $SQLQuery = "select distinct $selectClause
		from $fromClause 
		where $whereClause
		ORDER BY $orderByClause";
*/

/* Quality ranking */

	# Temporary table: Finds all records which match search terms
    $resultsTableQuery = "create temporary table resultsTable
    		select distinct $selectClause
		from $fromClause 
		where $whereClause
		ORDER BY $orderByClause";

	($res = mysql_query($resultsTableQuery))
		or die("Error executing resultsTableQuery:$resultsTableQuery");

	# Temporary table: Orders archives by their aggregate record score
	#	for each of the matching records
    $orderingTableQuery = "create temporary table orderingTable
		select sum(recordScore) as archiveScore, Archive_ID
		from resultsTable
		group by Archive_ID
		order by archiveScore DESC";

	($res = mysql_query($orderingTableQuery)) 
		or die("Error executing orderingTableQuery");

	echo "<!-- $resultsTableQuery \n $orderingTableQuery \n -->";

	# Orders the results of the query using the archive aggregate scores
    $SQLQuery = "select resultsTable.Archive_ID, 
			resultsTable.RepositoryIdentifier, 
			resultsTable.Item_ID, resultsTable.TagName, 
			resultsTable.recordScore, orderingTable.archiveScore,
			OaiIdentifier, Content, recordScore,
			resultsTable.Code 	
		from orderingTable, resultsTable
		where orderingTable.Archive_ID = resultsTable.Archive_ID
		group by resultsTable.OaiIdentifier
		order by archiveScore DESC, resultsTable.RepositoryIdentifier,
			resultsTable.recordScore DESC, 
			resultsTable.OaiIdentifier";

    return $SQLQuery;
}

# Returns a query to determine whether a search term is a language name, or
#	whether a token within the query string is a language name
function buildIsLangQuery($query)
{

		# The country information (without the group by) returns
		# all distinct languages-country combinations	
		# 	--> No data to determine which is the primary reference
		#	in Ethnologue
	$returnQuery = "select distinct ls.Name as queryName, 
			ls.LangID, ls2.Name as primaryName  #,cc.Name as country
			from LanguageSoundex ls INNER JOIN LanguageSoundex ls2
				ON ls2.LangID = ls.LangID
			#INNER JOIN CountryCodes cc
			#ON (cc.CountryID = ls2.CountryID)
			where ls2.NameType = 'L'
			and (ls.Name = '$query'";
			#and ( ( MATCH(ls.Name) AGAINST('$query') ) ";
			# Any record with exact word or token

	/*
	$isLangTokens = tokenizeQuery($query);
	
	$i=0;
	foreach($isLangTokens as $tok)
	{
	    $returnQuery .= " OR ls.Name = '$tok' ";
	    # Any record with exact word or token
	    #$returnQuery .= " OR ( (MATCH(ls.Name) AGAINST('$tok') )>0.8 ) ";
	    $i++;
	}
	*/

	$returnQuery .= ") group by LangID
			 order by (ls.Name LIKE '$query') DESC, queryName";

	return $returnQuery;
}

# Returns a query to find alternate names for a language query string 
function buildAltQuery($query)
{
/* Returns all alternate names (even those not in db)
	return "select distinct li2.Name, st.Word is not null as occurs,
				cc.Name as CountryName
			from LanguageSoundex li
				INNER JOIN LanguageSoundex li2
				ON li.LangID = li2.LangID
				LEFT OUTER JOIN SOUNDEX_TABLE st
				ON st.SoundexValue = li2.SoundexValue
				and st.Word = li2.Name 
				INNER JOIN CountryCodes cc
				ON cc.CountryID = li2.CountryID
			where li.Name = '$query'
			order by li2.Name";
*/

	$altTokens = tokenizeQuery($query);
	
	$returnQuery = "select distinct li2.Name, li.LangID, 
			li.Name as queryName, cc.Name as description,
			st.Word is not null as occurs
			from LanguageSoundex li
				INNER JOIN LanguageSoundex li2
				ON li.LangID = li2.LangID
				LEFT OUTER JOIN SOUNDEX_TABLE st
				ON st.SoundexValue = li2.SoundexValue
				and st.Word = li2.Name
				INNER JOIN CountryCodes cc
				ON cc.CountryID = li2.CountryID
				where li.Name = '$query' 
				";

	$i=0;
	foreach ($altTokens as $tok)
	{
	    $returnQuery .= " OR li.Name = '$tok' ";	
	    $i++;
	}

	$returnQuery .= "order by occurs desc, queryName, li2.Name";

	return $returnQuery;
}

# Returns a query to determine whether a query string is a country name, or
#	whether a token within the query string is a country name

function buildIsCountryQuery($query)
{

    $returnQuery = "select distinct CountryCodes.Name as queryName
			from CountryCodes
			where Name = '$query' ";


    $isCountryTokens = tokenizeQuery($query);

    foreach($isCountryTokens as $tok)
    {
	$returnQuery .= " OR Name = '$tok' ";
    }

    return $returnQuery;
  
}

# Builds a query which will return all languages spoken in a given country
function buildCountryQuery($query)
{

/* Returns all languages (even those not in db) */
	return "select distinct ls.Name, st.Word is not null as occurs
		from LanguageSoundex ls
				LEFT OUTER JOIN SOUNDEX_TABLE st
				ON st.SoundexValue = ls.SoundexValue
				and st.Word = ls.Name
				INNER JOIN CountryCodes cc 
				ON cc.Name = '$query'
				and ls.CountryID = cc.CountryID
		order by occurs desc, ls.Name";

}

# Displays alternate language names and alternate country names using a 
#	definition list
function tabulate($res )
{
	global $output, $DB;
	if ($_GET[page] > 1) { return; }

	$numSimilar=0;
		
	$prevItem = "";

	$output .= "<dl>\n";

	foreach ($res as $ar)
	{
		$arlink = str_replace(" ", "+", $ar[Name] );
		if ($prevItem != $ar[Name])
		{
		    if ( !$ar[occurs] )
		    {
			# Suggests that word does not occur in the db
		    $output .= "<dt>$ar[Name]</dt><dd>";
		    }
		    else
		    {
		    $output .= "<dt><a href=$_SERVER[PHP_SELF]?"
			. "query=$arlink&phrasemode=$_GET[phrasemode]"
			. "&allmode=$_GET[allmode]&page=1>"
			. "$ar[Name]</a><dd>";
		    }
		}

		
		if ($ar[description] && $ar[description] != "L") 
			{ $output .= " ($ar[description])"; }

		$numSimilar++;
		$prevItem = $ar[Name];
	}

	$output .= "</dl>\n";
		
	return;
}

# Displays languages and words with similar spellings as the query tokens
#
#	Finds languages/words with same soundex value
#	Orders by levenshtein distance from the query term
#	Selects the top n words to display

function similarSpellings($queryTokens)
{
 	global $DB;
	global $output;
	$MaxMatches=15;		# The maximum displayed languages and words

	foreach ($queryTokens as $qt)
	{

	/* Only returns languages which appear in the db */
	$similarLangQuery = "
			select distinct li.Name
			from LanguageSoundex li, SOUNDEX_TABLE st
			where st.SoundexValue = li.SoundexValue
			and st.Word = li.Name
			and li.SoundexValue = SUBSTRING(SOUNDEX('$qt'),1,4)";
	

	/* Returns all languages similar in spelling
	$similarLangQuery = "
			select distinct li.Name
			from LanguageSoundex li
			where li.SoundexValue = SUBSTRING(SOUNDEX('$qt'),1,4)";
	*/

	($simLangRes = $DB->sql($similarLangQuery));

	$numSimilar=1;
	$langLevDists = array();
	
	foreach ($simLangRes as $sr)
	{
		$sr[Word] = trim($sr[Word]);
		$langLevDists[$sr[Name]] = levenshtein( strtolower($qt),
				strtolower( $sr[Name])  );

		$numSimilar++;
	}

	# Outputs similar language names

	$j=0;
	asort($langLevDists);
	while (($j<$MaxMatches) && (list($key,$value) = each($langLevDists)))
	{
		if ($value == 0 ) # Word occurs in database
		{
		}

		# Fulltext index does not index words shorter than 4 characters
		if (strlen($key)<4) { $mode = "all"; }
		else { $mode=""; }

		$keyLink = str_replace(" ", "+", $key);
    		$output .= "<a href=$_SERVER[PHP_SELF]?"
		. "query=$keyLink&phrasemode=&allmode=$mode&page=1>"
		. "$key</a> ";

		$j++;
	}

	$output .="<br />";

	# Finds all words similar in spelling to search term
	$similarWordQuery = "select st.Word
			from SOUNDEX_TABLE st
			where SoundexValue = SUBSTRING(SOUNDEX('$qt'),1,4)";

	($simWordRes = $DB->sql($similarWordQuery));

	if (! $simWordRes ) { $output .= "No similar words<br />"; }

	$numSimilar=1;
	$wordLevDists = array();
	foreach ($simWordRes as $sr)
	{
		$sr[Word] = trim($sr[Word]);
		$wordLevDist = levenshtein( strtolower($qt),
	 			strtolower($sr[Word]) );

	 	$wordLevDists[$sr[Word]] = $wordLevDist;	

		$numSimilar++;
	}
	
	# Outputs similar words to query string
	$j=0;
	asort($wordLevDists);
	while (($j < $MaxMatches)&& (list($key,$value) = each($wordLevDists)))
	{
		if ($value==0) # The word exists in the database
		{ 
		}

		# Fulltext index does not index words shorter than 4 characters
		if (strlen($key)<4) { $mode = "all"; }
		else { $mode=""; }

		$keyLink = str_replace(" ", "+", $key);
    		$output .= "<a href=$_SERVER[PHP_SELF]?"
		. "query=$keyLink&page=1&phrasemode=&allmode=$mode&page=1>"
		. "$key</a> ";
		$j++;
	}

	$output .="<br /><br />";
	}
}

##########
# Displays the relevant header links after a query is submitted
##########

function headerLinks($queryString)
{
	global $output;
	global $DB;

	# Terms to combine language name with for Google web search
	$googleResourceTypes = Array(
				"language",
				"grammar",
				"alphabet",
				"phonology",
				"text",
				"dictionary"
				);

        $alternateQuery = buildIsLangQuery($queryString);
        $altRes = $DB->sql($alternateQuery);

        $countryQuery = buildIsCountryQuery($queryString);
        $countryRes = $DB->sql($countryQuery);

	# If query string is a country name, display links:
	#	Visit Ethnologue entry for country
	#	Find resources for languages spoken in country

        $qString = str_replace(" ", "+", $queryString );
        if ($countryRes)
        {
            foreach ($countryRes as $cr)
            {
                $q = str_replace( " ", "+", $cr[queryName] );
                $output .= "<a href=\"$_SERVER[PHP_SELF]?country=$q&page=1"
		      . "&phrasemode=$_GET[phrasemode]"
		      . "&allmode=$_GET[allmode]\">"
                      . "Find resources for languages spoken in "
		      . " '$cr[queryName]'</a><br />\n";
                $output .= "<a href=http://www.ethnologue.com/show_country.asp?"
			. "name=$q> Visit Ethnologue entry for country"	
			. " '$cr[queryName]'</a><br />\n";
            }

        }

	# If the query string is a language name, display links:	
	#	List alternate names for language
	#	Look up resources for LangID
	#	Search the web for language dictionary, grammar etc. 
	#	Visit Ethnologue entry for language
	
        if ($altRes)
        {
	 	$prevLang = "";
                foreach($altRes as $ar)
                {
                    $q = str_replace( " ", "+", $ar[queryName] );

			#Alternate names link
		    if ($prevLang != $ar[queryName])
		    {
                    $output .= "<a href=\"$_SERVER[PHP_SELF]?"
			. "altNames=$q&page=1&phrasemode=$_GET[phrasemode]"
			. "&allmode=$_GET[allmode]\">"
                        . "List alternate names for "
			. "'$ar[queryName]'</a><br />\n";

		    }

			#Language ID search link
  			$output .= "<a href=$_SERVER[PHP_SELF]?"
				. "langCode=$ar[LangID]"
				. "&phrasemode=$_GET[phasemode]"
				. "&allmode=$_GET[allmode]"
				. "&query=$q>"
				. "Look up resources for '$ar[LangID]'"
				. "</a><br />";


			#Visit Ethnologue link
                    $output .= "<a href=http://www.ethnologue.com/"
			. "show_language.asp?code="
                        . $ar[LangID]
                        . ">Visit Ethnologue entry for language "
			. "'$ar[queryName]'</a>";
		
			if ($ar[primaryName] != $ar[queryName] ) 
			{ 
			    $output .= " ($ar[primaryName])"; 
			}
			$output .= "<br />";

			#Google search link

		    if ( $prevLang != $ar[queryName] )
		    {
		    $output .= "Search the web for '$ar[queryName]': ";
		    foreach($googleResourceTypes as $type)
		    {
			$ar[queryName] = str_replace(" ", "+", $ar[queryName]);
			$output .=" <a href=\"http://www.google.com/search?"
				. "q={$ar[queryName]}+$type\">$type</a>";
		    }
			$output .= "<br />";
		    }

		    $prevLang = $ar[queryName];

		    # If there is a matching language for the entire query
		    # string, do not display matches for tokens in the string
		    # Only relevant where MATCH AGAINST used in language query
		    #
		    #if (strtoupper($ar[queryName]) == strtoupper($queryString))
		    #	  { break; }

                }
        }

	# Always display link to view similar spellings

	$qString = str_replace(" ", "+", $queryString );
  	$output .= "<a href=$_SERVER[PHP_SELF]?simSpell=$qString>"
		. "Search similar spellings</a><br />";

	$output .= "<hr>";

    return;
}


if ($_GET[query]) {

  #$_GET[query] = addslashes( $_GET[query] );

  if ( strlen($_GET[query]) < 2 ) { die("<h3>Query string too short</h3>"); }


	# Build and submit query to find OLAC records matching the search
	#  tokens
  $queryTokens = tokenizeQuery($_GET[query]);
  $sqlQuery = buildSqlQuery($queryTokens, $_GET[langCode]);

  print "<!-- $sqlQuery -->\n";

	# Display header links relevant to the query string.
  headerLinks($_GET[query]);
		
  $tab = $DB->sql($sqlQuery);
  ($DB->saw_error()) and die("Query failed");

  $count = 1;
  $archive_ids = array();

  # Display record matches

  $prevArchive = "";

  $counter=1;
  $maxPerArchive = 3;

  $arrSize = sizeof($tab);
  if ($tab) 
  {

$prevOAIIdentifier = "";

$prevArchiveHeader = "";
$prevArchiveRecords= "";
$loopOut = "";

foreach ($tab as $row)
{
$content = $row[Content];

if ($row[RepositoryIdentifier] != $prevArchive)
{

    $archive = "Results from "
	. "\"<a href=\"http://www.language-archives.org/archive.php4?"
	. "id=$row[Archive_ID]\">"
	. "$row[RepositoryIdentifier]</a>\"";
	# For each archive, print a heading and list the n highest ranking
	#	record summaries. 
  if ($_GET[archive] == "")
  {
    /*$archive = "Results from "
	. "\"<a href=\"http://www.language-archives.org/archive.php4?"
	. "id=$row[Archive_ID]\">"
	. "$row[RepositoryIdentifier]</a>\"";*/

    if (($prevArchive != "")||($count == $arrSize-1))
    {
	$numMore = $counter - $maxPerArchive;
	$q = str_replace( " ", "+", $_GET[query]);
	if ($numMore >0)
	{
	$prevArchiveHeader .= 
	"<td align=right><a href=\"$_SERVER[PHP_SELF]?query=$q"
	. "&archive=$prevArchive&page=1&phrasemode=$_GET[phrasemode]"
	. "&allmode=$_GET[allmode]&langCode=$_GET[langCode]\">"
	. "List all results from this archive ($counter matches)</a></td>";

	}
        $loopOut .= "<table></td>$prevArchiveHeader</tr></table>" 
		. $prevArchiveRecords;
        $prevArchiveHeader = "";
	$prevArchiveRecords = "";
    }
  }

$counter=1;
}
else 
{ 
  if ($_GET[archive] == "")
  {
    if ($count == $arrSize)	# The last archive
    {
	$numMore = $counter - $maxPerArchive + 1;
	$counterIncr = $counter + 1;
	$q = str_replace( " ", "+", $_GET[query] );
	if ($numMore >0)
	{
	$prevArchiveHeader .= 
	"<td align=right><a href=\"$_SERVER[PHP_SELF]?query=$q"
	. "&archive=$prevArchive&page=1&phrasemode=$_GET[phrasemode]"
	. "&allmode=$_GET[allmode]&langCode=$_GET[langCode]\">"
	. "List all results from this archive ($counterIncr matches)</a></td>";

	}
	$loopOut .= "<table></td>$prevArchiveHeader</tr></table>" 	
		. $prevArchiveRecords . "<br>";
	$prevArchiveHeader = "";
	$prevArchiveRecords = "";
    }
  }
$archive = "";
$counter++;
}

$prevArchive = $row[RepositoryIdentifier];

# Only disply $maxPerArchive results for each archive, unless searching
#	for results in only one archive

if (($counter <= $maxPerArchive)||($_GET[archive]!=""))
{
    if ($archive != "") 
    { 
	$prevArchiveHeader = $prevArchiveHeader 
		. "<th><big>$archive</big></th>";
    }
   $prevArchiveRecords .= formatResult( $row, $counter, $queryTokens );
}

    $archive_ids[$row[Archive_ID]] = 1;
    $count++;
}

# Outputs the information for all archives
$output .= $loopOut . "<table></td>$prevArchiveHeader</tr></table>" 
		. $prevArchiveRecords ;

}
else
{
	# THE DISPLAY IF THERE ARE NO MATCHES

$isLangQuery = buildIsLangQuery($_GET[query]);
$isLangResults = $DB->sql($isLangQuery);

# If the query string is a language name, display header and alternate
#	names for the language
if ($isLangResults)
{
	# Search header info
  $qString = str_replace( " ", "+", $_GET[altNames] );

	$isLangLink = 
		str_replace(" ", "+", $isLangResults[0][queryName]);

  $output .="<table width=\"100%\" border=0 bgcolor=darkblue cellspacing=0><tr>
        <td><font color=white>&nbsp;&nbsp;Alternate names for the language \"";
  $output .="<a href=$_SERVER[PHP_SELF]?query=$isLangLink"
	. "&page=1&phrasemode=$_GET[phrasemode]&allmode=$_GET[allmode]>";
  $output .= "<font color=white><b>{$isLangResults[0][queryName]}</b>"
		. "</font></a>\"";

  $output .="<td align=right><font color=white>&nbsp;</font></td>";

  $output .="</tr></table>\n";

  $langResQuery = buildAltQuery($_GET[query]);
  $langResults = $DB->sql($langResQuery);
	# Displays the alternate language names
  tabulate($langResults);
}
# If not a language name, display words which are similar in spelling
else
{
#Search header info
  $output .= 
	"<table width=\"100%\" border=0 bgcolor=darkblue cellspacing=0 ><tr>
        <td><font color=white>Words similar in spelling to ";

        foreach ($queryTokens as $qt)
        {
            $output .= "'<a href=$_SERVER[PHP_SELF]?query=$qt>"
		. "<font color=white><b>$qt</b></font></a>'&nbsp;";
        }

  $output .= "</td><td align=right><font color=white>&nbsp;</font></td>";
  $output .= "</tr></table>\n";

similarSpellings($queryTokens);
}

}

$archives = sizeof($archive_ids);

#
# Search header info where there is a successful query 
#
  echo "<table width=\"100%\" bgcolor=darkblue border=0 cellspacing=0><tr>
	<td><font color=white>&nbsp;&nbsp;Search results for ";
  $i=0;
  foreach ($queryTokens as $token)
  {
    if ($i > 0) { print " AND "; }

	$tokenLink = str_replace( " ", "+", $token );
    echo "\"<a href=$_SERVER[PHP_SELF]?"
	. "query=$tokenLink&phrasemode=$_GET[phrasemode]&allmode=$_GET[allmode]"
	. "&page=1>"
	. "<font color=white><b>$token</b></font></a>\" ";
    $i++;
  }

    if ($_GET[archive] != "") 
    { 
	echo "in \"<b>$_GET[archive]</b>\"</font></td>"; 
    }
    else { echo "in all OLAC archives</font></td>"; }

  echo "<td align=right><font color=white>" . ($count-1)
	. " results from $archives archive(s)</font>&nbsp;&nbsp;</td>";

  echo "</tr></table>\n";

}

# Mode 'item' used to display the full record information for an item

if ($_GET[item])
{
  $stringQuery = str_replace( " " , "+", $_GET[queryTerms] );
  $base = str_replace( "$scriptName", "", $_SERVER[PHP_SELF]);

  $result = join( file("http://$_SERVER[HTTP_HOST]$base/lookup.php?"
			. "identifier=$_GET[item]"
			. "&queryTerms=$stringQuery"
			. "&phrasemode=$_GET[phrasemode]"
			. "&allmode=$_GET[allmode]") );

  $search = array('/<\/?(BODY|HTML)>/i',
                  '/<HEAD>.*<\/HEAD>/si');
  $replace = array('','');

  print "<hr>". preg_replace($search, $replace, $result);

}

# AltNames mode used to find alternate names for a searched language
if ($_GET[altNames])
{
	$alternateQuery = buildAltQuery($_GET[altNames]);
	$altRes = $DB->sql($alternateQuery);

# Search header info

	$qString = str_replace( " ", "+", $_GET[altNames] );

  echo "<table width=\"100%\" border=0 bgcolor=darkblue cellspacing=0><tr>
	<td><font color=white>&nbsp;&nbsp;Alternate names for the language \"
	<a href=$_SERVER[PHP_SELF]?query=$qString&page=1><font color=white><b>	
	$_GET[altNames]</b></font></a>\"";

  echo "<td align=right><font color=white>&nbsp;</font></td>";
  echo "</tr></table>\n";

	headerLinks($_GET[altNames]);
	if ($altRes) {
		tabulate($altRes);
	}
}

# Mode 'country' used to display all languages spoken in a given country
if ($_GET[country])
{

	$countryQuery = buildCountryQuery($_GET[country]);
	$countryRes = $DB->sql($countryQuery);

	if ($countryRes) {

# Search header info

  echo "<table width=\"100%\" border=0 bgcolor=darkblue cellspacing=0 ><tr>
	<td><font color=white>Languages spoken in \"$_GET[country]\"";
  echo "<td align=right><font color=white>&nbsp;</font></td>";
  echo "</tr></table>\n";

	headerLinks($_GET[country]);
	tabulate($countryRes);

	}

}

# Mode 'simSpell' used to display a list of words which are spelt similarly
#	to the search term

if ($_GET[simSpell])
{
    $queryTokens = tokenizeQuery($_GET[simSpell]);

	#Search header info
  	$output .= "<table width=\"100%\" border=0 bgcolor=darkblue "
		. "cellspacing=0 >
	<tr><td><font color=white>Words similar in spelling to ";

	foreach ($queryTokens as $qt)
	{
	    $output .= "'<a href=$_SERVER[PHP_SELF]?query=$qt>"
		. "<font color=white><b>$qt</b></font></a>'&nbsp;";
	}

  $output .= "</td><td align=right><font color=white>&nbsp;</font></td>";
  $output .= "</tr></table>\n";
  headerLinks($_GET[simSpell]);

	# Display words with similar spelling
  similarSpellings($queryTokens);
}

$DB->disconnect();

    print $output;

?>

</BODY>
</HTML>

