<?php
######################################################################
#
# Synopsis: Search metadata elements of OLAC 1.0 database for a given string.
#
# ChangeLog:
#
# 2005-03-20 Baden Hughes <badenh@cs.mu.oz.au>
#	* added supporting documentation
# 2005-01-05 Baden Hughes <badenh@cs.mu.oz.au> 
#	* numerous minor fixes
# 2004-01-23 Amol Kamat <amol@students.cs.mu.oz.au>
# 2003-03-01  Haejoong Lee  <haejoong@ldc.upenn.edu>
#	* revised previous version (/tools/search.php4) to use new database
#	* use /tools/lookup/lookup-OLAC1.0.php4 for record lookup
#
# CVS Info: $Header: /cvsroot/olac/olac_suite/mu_tools/search/search.php,v 1.12 2006/03/28 03:51:22 badenh Exp $
######################################################################

include "searchInclude.php";
include "boolTok.php";
include LIB . "olacdb.php";

$DB = new OLACDB();

$recsPerArchive = 3; 	# The number of records displayed per archive
$maxStars = 5;
$maxTagRank = 3;
$maxQualityRank = 10;

# Find query to display in input box

$query = "";
if ( array_key_exists('query', $_GET) )
{ $query = addslashes($_GET['query']);
  $_GET['query'] = addslashes($_GET['query']);
}

if ( array_key_exists('queryTerms', $_GET) )
{ $query = $_GET['queryTerms']; }

if ( array_key_exists('simSpell', $_GET) )
{ $query = $_GET['simSpell']; }

if ( array_key_exists( 'altNames', $_GET ) )
{ $query = $_GET['altNames']; }

if ( array_key_exists( 'country', $_GET ) )
{ $query = $_GET['country']; }

if ( array_key_exists( 'xml', $_GET ) )
{ $query = $_GET['xml']; }

#######
# Used to provide the archive list in the dropdown box
#######

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
	#if ($_GET['archive'] == $arch[RepositoryIdentifier]) 
	#{ echo " selected";}
	if ( array_key_exists( 'archive', $_GET ) )
	{ 
		if ($_GET['archive'] == $arch['RepositoryIdentifier']) 
		{echo " selected";} 
	}
	print ">$arch[RepositoryIdentifier]\n";
    }
    return;
}

#######
# Used to create the drop down box of languages for Google search terms
#######

function languageNamesDropDown()
{
global $DB;

$formString = "<select name=\"webLangID\">";

$availableLanguagesQuery = "select distinct LangName, LangID from GoogleTerms";

$availableLanguages=$DB->sql($availableLanguagesQuery);

foreach( $availableLanguages as $lang )
{
    $formString .= "<option value='{$lang['LangID']}'";

    if ( array_key_exists('webLangID', $_GET) )
    {
	if ($_GET['webLangID'] == $lang['LangID'])
	{
	    $formString .= " selected ";
	}
    }
    $formString .= ">{$lang['LangName']}";
}

$formString .= "</select><input type='submit' value='Display'>";

return $formString;
}

?>
<?

######
# Returns HTML to create the banner (form)
######
 
function printBanner()
{
global $STYLE, $query;

$banner =
"
<HTML>
<HEAD>
<TITLE>Search OLAC Archives - $query </TITLE>
<script type="text/javascript" src="/js/gatrack.js"></script>
<LINK REL=\"stylesheet\" TYPE=\"text/css\" HREF=\"{$STYLE}olac.css\">
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">
</HEAD>

<BODY>

<table>
<tr>
<td>
	<TABLE CELLPADDING=\"0\">

	<!-- Banner -->
	<TR>
	<TD> <A HREF=\"/\"><IMG
	SRC={$STYLE}olac100.gif
	alt=\"Open Language Archives\"
	BORDER=\"0\"></A></TD>
	<TD> 
	<H3><FONT COLOR=\"0x00004a\">Search<br /> OLAC: </FONT>
	</H3>
	</TD>
	<td>
	<!-- Query form -->
	<form method=\"get\">
	<input type=\"text\" size=\"35\" maxlength=\"50\" name=\"query\" 
		value=\"$query\">
	<input type=\"submit\" value=\"Search\">
	<br />
	<select name=\"archive\">";

echo $banner;
archivesOptions();
$banner =
"
	</select>
	<input type=hidden name=page value=1>
	</td>
	</TR>
	</TABLE>
</td>
<td valign=top>
	<a href=\"searchDoc.html\"><small>User Guide</small></a>
</td>
</tr>
</table>
";

/*
<!-- Checkboxes
<input type="checkbox" value="all" name="allmode" 
	onClick="this.form.phrasemode.checked=false"
	<? 
		if ( array_key_exists( 'allMode', $_GET)
			&& ($_GET['allmode']!= "") )
		{ print "checked"; }
		#if ($_GET['allmode']) print "checked";
	?>
	> Match substrings</input>

<input type="checkbox" value="phrase" name="phrasemode" 
	onClick="this.form.allmode.checked=false"
	<? 
		if ( array_key_exists( 'phrasemode', $_GET )
			&& ( $_GET['phrasemode'] != "") )
		{ print "checked"; }
		#if ($_GET[phrasemode]) print "checked"; 
	?> 
	> Match exact phrase</input>
-->
*/

echo $banner;
return;
}
$output = "";

##########
# Returns the name of the star ratings image
##########

function starImage( $num ) 
{ 
	global $STYLE;
	return $STYLE . "star" . round($num/2.0) . ".gif"; 
}

######
# Shortens a string to a certain length, keeping words intact and appends '...'
######

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
		from METADATA_ELEM_MYISAM me
			where me.Item_ID = '$row[Item_ID]'
			and me.TagName 
			in ('title', 'description', 'subject', 'date', 'identifier')
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
                from METADATA_ELEM_MYISAM me, TAG_USAGE tu
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

$content = $row['Content'];

if ($content=="") { $content=$row['Code']; }

foreach ($queryTokens as $tok)
{
   $tok = str_replace("(", "\(", $tok);
   # Case insensitive matching of query keywords
   $content = eregi_replace($tok, "<em>\\0</em>", $content );
   $moreInfo = eregi_replace($tok, "<em>\\0</em>", $moreInfo );
   # Case insensitive matching of query keywords
   $row['TagName'] = eregi_replace($tok, "<em>\\0</em>", $row['TagName'] );
}

# If the content of the matching tag itself is longer than the maximum
#	synopsis length, shorten to bring the first occurence of the
#	search term toward the front of the content and shorten the
#	string

if (strlen($content) > $maxMoreInfo )
{
    $firstOcc = strpos($content,"<em>");
    $content = substr( $content, max(0, $firstOcc-10) );
	if ($firstOcc != 0) { $content = "..." . $content; }
    $content = shorten($content, $maxMoreInfo);

}

$queryString = str_replace( " ", "+", $_GET['query'] );

			if (array_key_exists('phrasemode', $_GET) )
			{ $phrasemode = $_GET['phrasemode'];}
			else
			{ $phrasemode = ""; }

			if (array_key_exists('allmode', $_GET) )
			{ $allmode = $_GET['allmode'];}
			else
			{ $allmode = ""; }

# Return tabulated result record
return " <table>
		<tr>
		<td>&nbsp;&nbsp;</td>
		<td width=100%>
	$counter."
        . "<img src=\"" . starImage($row['recordScore'])
        . "\"></img>\n"
	#. " \t<a href=\"$_SERVER[PHP_SELF]?item=$row[OaiIdentifier]"
	#. "&queryTerms=$queryString&phrasemode=$phrasemode"
	#. "&allmode=$allmode\">"
	. " \t<a href=\"/item/$row[OaiIdentifier]?queryTerms=$queryString\">"
	. "$row[OaiIdentifier]</a>\n"
	. similarRecords($row['Item_ID'], $queryTokens)
	. "<br />\n\t<b>$row[TagName]:</b> $content<br />"
	. "<small>" . shorten( $moreInfo, $maxMoreInfo ) 
	. "</small></td></tr>"
	. "</table>";

}

function similarRecords($itemID, $queryTokens)
{
global $DB;
$returnString="";
$maxKeyWords=5;

$coreTags = Array("language",
	"type", 
	"subject", 
	#"identifier",
	"date" ); 

$getRecordQuery = 
"
select *
from METADATA_ELEM_MYISAM me
where me.Item_ID = '$itemID' and Content!=''
	and me.Code = ''
group by TagName
";

$record = $DB->sql($getRecordQuery);

$score_field =  "s.title + s.date + s.agent + s.about + s.depth + ";
$score_field .= "s.content_language + s.linguistic_type + ";
$score_field .= "s.subject_language + s.dcmi_type+s.prec";

$getScoreQuery =
"
select $score_field as score
from MetricsQualityScore s
where Item_ID = $itemID
";
$score = $DB->sql($getScoreQuery);

    $returnString .= "<small>Similar records by: ";

	$maxLinks=10;
	$links=0;
    # Similar score
    foreach ($score as $s)
    {
	if ($links > $maxLinks) { break; }
	$val = round($s['score'] / 2.0);
    $returnString .=
	"<a href=\"$_SERVER[PHP_SELF]?query={$_GET['query']}"
	. "&archive={$_GET['archive']}"
	. "&score=$val"
	. "\">score</a>&nbsp;";
	$links++;
    }

    # Similar core elements
    foreach ($record as $elem)
    {
	if ($links > $maxLinks) { break; }
      if ( in_array( $elem['TagName'], $coreTags) )
      {
	$toks = tokenizeQuery($elem['Content'], true , true);
	
	$q = "";
	$i=0;
	foreach($toks as $tok)
	{
	 if ($i>$maxKeyWords) { break; }
	 if ($i!=0) {$q.="+";}
	 $q .= "$tok"; 
	 $i++;
	}

	/*
	$originalQueryTokens = tokenizeQuery($_GET['query'], false, true);

	$originalQueryString="";

	$i=0;
	foreach($originalQueryTokens as $tok)
	{
	    if ($i>$maxKeyWords) { break; }
	    if ($i!=0) { $originalQueryString .="+"; }
	    $originalQueryString .= $tok;
	    $i++;
	}

        $returnString .="<a href=\"$_SERVER[PHP_SELF]?"
	  . "query={$originalQueryString}+{$elem['TagName']}:$q"
	  . "\">{$elem['TagName']}</a>&nbsp;";
	*/
        $returnString .="<a href=\"$_SERVER[PHP_SELF]?"
	  . "query={$elem['TagName']}:$q"
	  . "\">{$elem['TagName']}</a>&nbsp;";
	$links++;
      }
    }

    # Similar archive

    $returnString .= "</small>";
    return $returnString;
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
#    	from ARCHIVED_ITEM as a {, METADATA_ELEM_MYISAM as e1}	
#    	where {a.Item_ID=e1.Item_ID and} ( URL-unencoded-sql-argument )
#    	order by OaiIdentifier	
#					
#  as described at	
# 	http://www.language-archives.org/NOTE/query.html
###


function buildSqlQuery( $tokens, $langCode, $score )
{
    global $DB;
    $FIELD = "";

	# All TagNames for purpose of identifying inline syntax
	#$allTagsQuery = "select TagName from ELEMENT_DEFN";
	#$allTags = $DB->sql($allTagsQuery);

    $score_field =  "s.title + s.date + s.agent + s.about + s.depth + ";
    $score_field .= "s.content_language + s.linguistic_type + ";
    $score_field .= "s.subject_language + s.dcmi_type+s.prec";

    $selectClause =  " ar.Archive_ID, ";
    $selectClause .= " ar.Item_ID, ";
    $selectClause .= " ar.OaiIdentifier, ";
    $selectClause .= " oa.RepositoryIdentifier, ";
    $selectClause .= " round($score_field,1) as recordScore ";

    $fromClause =  " ARCHIVED_ITEM ar, ";
    $fromClause .= " OLAC_ARCHIVE oa, ";
    $fromClause .= " MetricsQualityScore s ";

    $whereClause =  " oa.Archive_ID = ar.Archive_ID ";
    $whereClause .= " and s.Item_ID = ar.Item_ID ";

    if ($score!=null) 
    { 
      $whereClause .= " and ($score_field)/2 < $score + 0.5 ";
      $whereClause .= " and ($score_field)/2 >= $score - 0.5";
    }

    $orderByClause = " oa.RepositoryIdentifier, recordScore DESC ";
	
    		global $query;
    		$ret = boolTok( $query, "Content", $whereClause );

	# For each token in the query string
	# (all this should be in boolTok function)
    $j=0;
    ####while ($j < sizeof($tokens) )
    while ($j <= $ret )
    {
	$tok = $tokens[$j];
	$fieldToken = 0;

	# If find a colon at the end of a token and more tokens follow
	/*
	if ((strpos( $tok, ":") == (strlen($tok) - 1))
		&& ($j < (sizeof($tokens) - 1)  ))
	{
		# FIXME
		foreach ($allTags as $tag)
		{
		    if ( substr($tok, 0, strlen($tok)-1 ) 
			== ( $tag['TagName'] ) )
		    {
			echo $tag['TagName'];
		    }
		}
	}
	*/

	if ($fieldToken == 1) { $j++; continue; }

	$me = "me" . $j;

	if ( ($j == 0) || (($FIELD!="")&&($j==1) ) )		# first token	
	{ 
	    $selectClause .= ", $me.TagName, $me.Content, $me.Code "; 
	}

	$fromClause .= ", METADATA_ELEM_MYISAM $me ";

	# If language code search, only match with language codes
	if ($langCode)
	{
	$whereClause .= " and ar.Item_ID=$me.Item_ID
		and $me.Code LIKE '%$langCode%'
		and ($me.TagName = 'subject'
			or $me.Tagname = 'language') ";
	}

	# If looking for exact word (not substrings) use MATCH AGAINST syntax
	#else if ((!$_GET[phrasemode])&&(!$_GET[allmode]))
	else if ( (!array_key_exists('phrasemode',$_GET)) 
		&& (!array_key_exists('allmode',$_GET)) )
	{
			/* Now in boolTok.php 
		    $whereClause .= " and ar.Item_ID=$me.Item_ID 
		    	and MATCH($me.Content) AGAINST('$tokens[$j]') ";

		    if ($FIELD != "") 
		    {
			$whereClause .= " and $me.TagName = '$FIELD' "; 
		    }
			*/
	}
	$j++;
    }

    if ( array_key_exists('archive', $_GET) && ($_GET['archive'] != "") )
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

	#echo "<!-- $resultsTableQuery -->\n";

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

	#echo "<!-- $resultsTableQuery \n $orderingTableQuery \n -->";

	# Orders the results of the query using the archive aggregate scores
    $SQLQuery = "select  resultsTable.Archive_ID, 
			 resultsTable.RepositoryIdentifier, 
			 resultsTable.Item_ID,
                         resultsTable.TagName, 
			 resultsTable.recordScore,
                         Metrics.metadata_quality as archiveScore,
			 OaiIdentifier,
                         Content,
                         recordScore,
			 resultsTable.Code 	
		from     Metrics, resultsTable
		where    Metrics.archive_id = resultsTable.Archive_ID
		group by resultsTable.OaiIdentifier
		order by archiveScore DESC,
                         resultsTable.RepositoryIdentifier,
			 resultsTable.recordScore DESC, 
			 resultsTable.OaiIdentifier";

    return $SQLQuery;
}

#########
# Returns a query to determine whether a search term is a language name, or
#	whether a token within the query string is a language name
#########

function buildIsLangQuery($query)
{

		# The country information (without the group by) returns
		# all distinct languages-country combinations	
		# 	--> No data to determine which is the primary reference
		#	in Ethnologue

	$returnQuery = "select distinct ls.Name as queryName, 
			ls.LangID, ls2.Name primaryName
			from LanguageSoundex ls
                             INNER JOIN LanguageSoundex ls2
			     ON ls2.LangID = ls.LangID
			where ls2.NameType = 'L'
			and (ls.Name = '$query'";
                        #and ( ( MATCH(ls.Name) AGAINST('$query') ) ";
			# Any record with exact word or token

	/* Or any token is a language name (can result in large list) */
	
	$isLangTokens = tokenizeQuery($query, false, false);
	
	$i=0;
	foreach($isLangTokens as $tok)
	{
	    $returnQuery .= " OR ls.Name = '$tok' ";
	    # Any record with exact word or token
	    #$returnQuery .= " OR ( (MATCH(ls.Name) AGAINST('$tok') )>0.8 ) ";
	    $i++;
	}
	

	$returnQuery .= ") group by LangID
			 order by (ls.Name LIKE '$query') DESC, queryName";

	echo "<!-- isLangQuery: $returnQuery -->";
	return $returnQuery;
}

########
# Returns a query to find alternate names for a language query string 
########

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

	$altTokens = tokenizeQuery($query, false, false);
	
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
	    if ( sizeof($altTokens) == 1 ) { break; }
	    	$returnQuery .= " OR li.Name = '$tok' ";	
	    $i++;
	}

	$returnQuery .= "order by occurs desc, queryName, li2.Name";

	return $returnQuery;
}

#######
# Returns a query to determine whether a query string is a country name, or
#	whether a token within the query string is a country name
#######

function buildIsCountryQuery($query)
{

    $returnQuery = "select distinct CountryCodes.Name as queryName
			from CountryCodes
			where Name = '$query' ";


    $isCountryTokens = tokenizeQuery($query, false, false);

    foreach($isCountryTokens as $tok)
    {
	$returnQuery .= " OR Name = '$tok' ";
    }

    return $returnQuery;
  
}

#######
# Builds a query which will return all languages spoken in a given country
#######

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

#########
# Displays alternate language names and alternate country names using a 
#	definition list
#########

function tabulate($res )
{
	global $output, $DB;
	#if ($_GET['page'] > 1) { return; }

	$numSimilar=0;
		
	$prevItem = "";

	$output .= "<dl>\n";

	foreach ($res as $ar)
	{
		$arlink = str_replace(" ", "+", $ar['Name'] );
		if ($prevItem != $ar['Name'])
		{
		    if ( !$ar['occurs'] )
		    {
			# Suggests that word does not occur in the db
		    $output .= "<dt>$ar[Name]</dt><dd>";
		    }
		    else
		    {
			if (array_key_exists('phrasemode', $_GET) )
			{ $phrasemode = $_GET['phrasemode'];}
			else
			{ $phrasemode = ""; }

			if (array_key_exists('allmode', $_GET) )
			{ $allmode = $_GET['allmode'];}
			else
			{ $allmode = ""; }
			/*
		    $output .= "<dt><a href=$_SERVER[PHP_SELF]?"
			. "query=$arlink&phrasemode=$phrasemode"
			. "&allmode=$allmode&page=1>"
			. "$ar[Name]</a><dd>";
			*/
			# Without using modes
		    $output .= "<dt><a href=$_SERVER[PHP_SELF]?"
			. "query=$arlink"
			. "&page=1>"
			. "$ar[Name]</a><dd>";
			
		    }
		}

		
		if ( array_key_exists('description', $ar) 
		    && $ar['description'] != "L") 
			{ $output .= " ($ar[description])"; }

		$numSimilar++;
		$prevItem = $ar['Name'];
	}

	$output .= "</dl>\n";
		
	return;
}

##########
# Displays languages and words with similar spellings as the query tokens
#
#	Finds languages/words with same soundex value
#	Orders by levenshtein distance from the query term
#	Selects the top n words to display
##########

function similarSpellings($queryTokens)
{
 	global $DB;
	global $output;
	$MaxMatches=10;		# The maximum displayed languages and words

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
		#########$sr['Word'] = trim($sr['Word']);
		$sr['Name'] = trim($sr['Name']);
		$langLevDists[$sr['Name']] = levenshtein( strtolower($qt),
				strtolower( $sr['Name'])  );

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
		/*
    		$output .= "<a href=$_SERVER[PHP_SELF]?"
		. "query=$keyLink&phrasemode=&allmode=$mode&page=1>"
		. "$key</a> ";
		*/
    		$output .= "<a href=$_SERVER[PHP_SELF]?"
		. "query=$keyLink&page=1>"
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
		$sr['Word'] = trim($sr['Word']);
		$wordLevDist = levenshtein( strtolower($qt),
	 			strtolower($sr['Word']) );

	 	$wordLevDists[$sr['Word']] = $wordLevDist;	

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
		. "query=$keyLink&page=1>"
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

	
	$webSearchLang = "ENG";
	if ( array_key_exists( 'webLangID' , $_GET ) )
	{
	    $webSearchLang = $_GET['webLangID'];
	}
	$googleTermsQuery = "select Term from GoogleTerms
				where LangID = '$webSearchLang'";

	$googleTerms = $DB->sql($googleTermsQuery);

	$googleResourceTypes = Array();
	$i = 0;
	foreach( $googleTerms as $term )
	{
	    $googleResourceTypes[$i] = $term['Term'];
	    $i++;
	}
	
				

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
			if (array_key_exists('phrasemode', $_GET) )
			{ $phrasemode = $_GET['phrasemode'];}
			else
			{ $phrasemode = ""; }

			if (array_key_exists('allmode', $_GET) )
			{ $allmode = $_GET['allmode'];}
			else
			{ $allmode = ""; }

                $q = str_replace( " ", "+", $cr['queryName'] );
                $output .= "<a href=\"$_SERVER[PHP_SELF]?country=$q&page=1"
		      . "&phrasemode=$phrasemode"
		      . "&allmode=$allmode\">"
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
                    $q = str_replace( " ", "+", $ar['queryName'] );

			if (array_key_exists('phrasemode', $_GET) )
			{ $phrasemode = $_GET['phrasemode'];}
			else
			{ $phrasemode = ""; }

			if (array_key_exists('allmode', $_GET) )
			{ $allmode = $_GET['allmode'];}
			else
			{ $allmode = ""; }

			#Language page
                        $tab = $DB->sql("
                        select cc.CountryID, cc.Name, cc.Area
                        from CountryCodes cc, LanguageCodes lc
                        where lc.LangID='$ar[LangID]' and lc.CountryID=cc.CountryID");
                        $countryID = $tab[0]['CountryID'];
                        $countryName = $tab[0]['Name'];
                        $a = preg_split('/\s*,\s*/', $countryName);
                        if (count($a) > 0) $countryName = "$a[1] $a[0]";
                        $area = $tab[0]['Area'];
  			$output .= "Visit OLAC report for: "
			  . "<a href=\"/language/$ar[LangID]\">$ar[primaryName]</a>, "
			  . "<a href=\"/country/$countryID\">$countryName</a>, "
			  . "<a href=\"/area/$area\">$area</a>"
			  . "</a><br />\n";

		    $prevLang = $ar['queryName'];

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

main();

function main()
{

if (!( array_key_exists( 'xml', $_GET ) && ($_GET['xml']!=""))) 
{ printBanner(); }

if ( array_key_exists( 'xml', $_GET ) && ($_GET['xml']!="")) 
{ modeXML(); }

else if ( array_key_exists( 'score', $_GET ) && ($_GET['score']!=""))
{ modeScore($_GET['score']); }

else if ( array_key_exists( 'query', $_GET ) && ($_GET['query']!="")) 
{ modeQuery(); }

else if ( array_key_exists('item', $_GET)) 
{ modeItem(); }

else if ( array_key_exists('altNames', $_GET) ) 
{ modeAltNames(); }

else if (array_key_exists('country', $_GET) ) 
{ modeCountry(); }

else if ( array_key_exists('simSpell', $_GET) ) 
{ modeSimSpell(); }

return;
}

/*********************/


/**********************/

#if ( array_key_exists( 'xml', $_GET ) && ($_GET['xml']!=""))
function modeXML()
{
    global $DB, $output;
    print "<?xml version=\"1.0\"?>\n";
    $tokens = tokenizeQuery( $_GET['xml'], false, false );
    $query = buildSqlQuery( $tokens, "", null);

    $results = $DB->sql( $query );

    foreach($results as $row)
    {
	$item = $row['Item_ID'];

	$fullRecQuery = "select *
			from METADATA_ELEM_MYISAM me
			where me.Item_ID = $item";

	$fullRecord = $DB->sql( $fullRecQuery );

	echo "<olac:olac
     xmlns:olac=\"http://www.language-archives.org/OLAC/1.0/\"
     xmlns:dc=\"http://purl.org/dc/elements/1.1/\"
     xmlns:dcterms=\"http://purl.org/dc/terms/\"
     xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\">\n";

	foreach( $fullRecord as $element )
	{
	echo "\t<oai:{$element['TagName']}";
		if ( $element['Type'] != NULL )
		{
		echo " {$element['Type']}=\"{$element['Code']}\"";
		}
	echo ">{$element['Content']}</oai:{$element['TagName']}>\n";
	}
	echo "</olac:olac>\n";
			
    }
}

/*********************/

#else if ( array_key_exists( 'query', $_GET ) && ($_GET['query']!=""))
function modeQuery()
{
  global $DB, $output;
  if ( strlen($_GET['query']) < 2 ) { die("<h3>Query string too short</h3>"); }

  # Build and submit query to find OLAC records matching the search
  #  tokens
  $queryTokens = tokenizeQuery($_GET['query'], false, false);

  if (array_key_exists( 'langCode', $_GET )) { $langCode = $_GET['langCode']; }
  else { $langCode = ""; }


  # Display header links relevant to the query string.
  headerLinks($_GET['query']);
		
  $sqlQuery = buildSqlQuery($queryTokens, $langCode, null );
  #print_r($queryTokens); return;

  print "<!-- $sqlQuery -->\n";
  $tab = $DB->sql($sqlQuery);
  ($DB->saw_error()) and die("Query failed");

  $count = 1;
  $archive_ids = array();

  # Display record matches

  $prevArchive = "";

  $counter=1;

  $arrSize = sizeof($tab);

  if ($tab) 
  {

	# DISPLAY MATCHING RECORDS

	$numArchives = 0;
	$numResults = 0;
        $queryTokens = tokenizeQuery($_GET['query'], true, false);
	$output .= displayResults($tab, $queryTokens, $numArchives , 
		$numResults );

  	#
  	# Search header info where there is a successful query 
  	#

  	echo "<table width=\"100%\" bgcolor=darkblue border=0 cellspacing=0><tr>
  	    <td><font color=white>&nbsp;&nbsp;Search results for ";
  
	$i=0;
  
  	$queryTokensWithBools = tokenizeQuery( $_GET['query'], false, true );
  	foreach ($queryTokensWithBools as $token)
  	{
	  $booleans = Array ( "and", "or", "not" );

	  # If token is a boolean operator
	  if ( in_array( strtolower($token), $booleans) )
	  {
	    print " " . strtolower($token) . " "; 
	  }
	  else if ( (strpos( $token, ":" )) !== FALSE )
	  {
	    print " $token ";
	  }
	  else
	  {
	    $tokenLink = str_replace( " ", "+", $token );

    	    echo "\"<a href=$_SERVER[PHP_SELF]?"
		. "query=$tokenLink&phrasemode=$phrasemode"
		. "&allmode=$allmode"
		. "&page=1>"
		. "<font color=white><b>$token</b></font></a>\" ";
	  }
          $i++;
  	}

  	# Where search is within single archive 
  
  	if ( array_key_exists('archive', $_GET) &&  $_GET['archive'] != "") 
  	{ 
	  echo "in \"<b>$_GET[archive]</b>\"</font></td>"; 
  	}
  	else 
	{ 
	  echo "in all OLAC archives</font></td>"; 
	}

  	echo "<td align=right><font color=white>" . $numResults
  	  . " results from $numArchives archive(s)</font>&nbsp;&nbsp;</td>";
  	echo "</tr></table>\n";
	
  }
  else
  {
	# THE DISPLAY IF THERE ARE NO MATCHES

    $isLangQuery = buildIsLangQuery($_GET['query']);
    $isLangResults = $DB->sql($isLangQuery);

    # If the query string is a language name, display header and alternate
    #	names for the language
    if ($isLangResults)
    {
	# Search header info

	if ( array_key_exists( 'altNames', $_GET ) )	
	{ $altNames = $_GET['altNames']; }
	else
	{ $altNames = ""; }
	
        $qString = str_replace( " ", "+", $altNames );

	$isLangLink = 
		str_replace(" ", "+", $isLangResults[0]['queryName']);

    echo
	"<table width=\"100%\" border=0 bgcolor=darkblue cellspacing=0><tr>
        <td><font color=white>&nbsp;&nbsp;Alternate names for the language \"";
    echo "<a href=$_SERVER[PHP_SELF]?query=$isLangLink"
	. "&page=1&phrasemode=$phrasemode&allmode=$allmode>";
    echo "<font color=white><b>{$isLangResults[0]['queryName']}</b>"
		. "</font></a>\"";

    echo "<td align=right><font color=white>&nbsp;</font></td>";

    echo "</tr></table>\n";

    $langResQuery = buildAltQuery($_GET['query']);
    $langResults = $DB->sql($langResQuery);

	# Displays the alternate language names
  	tabulate($langResults);
    }
    # If not a language name, display words which are similar in spelling
    else
    {
    #Search header info
    echo  
	"<table width=\"100%\" border=0 bgcolor=darkblue cellspacing=0 ><tr>
        <td><font color=white>Words similar in spelling to ";

        foreach ($queryTokens as $qt)
        {
            echo "'<a href=$_SERVER[PHP_SELF]?query=$qt>"
		. "<font color=white><b>$qt</b></font></a>'&nbsp;";
        }

    echo "</td><td align=right><font color=white>&nbsp;</font></td>";
    echo "</tr></table>\n";

    similarSpellings($queryTokens);
    }

  }


return;

}


##########
# Mode 'score' when searching for records of same score
##########

function modeScore($score)
{
  global $DB, $output;
  if ( strlen($_GET['query']) < 2 ) { die("<h3>Query string too short</h3>"); }

  $queryTokens = tokenizeQuery($_GET['query'], false, false);

  # Display header links relevant to the query string.
  headerLinks($_GET['query']);
		
  $sqlQuery = buildSqlQuery($queryTokens, $langCode, $score );
  $tab = $DB->sql($sqlQuery);
  ($DB->saw_error()) and die("Query failed");

  if ($tab) 
  {

	# DISPLAY MATCHING RECORDS

	$numArchives = 0;
	$numResults = 0;
	$output .= displayResults($tab, $queryTokens, $numArchives , 
		$numResults );

  	#
  	# Search header info where there is a successful query 
  	#
  	echo "<table width=\"100%\" bgcolor=darkblue border=0 cellspacing=0><tr>
  	    <td><font color=white>&nbsp;&nbsp;Search results for ";
  
	$i=0;
  
  	$queryTokensWithBools = tokenizeQuery( $_GET['query'], false, true );
  	foreach ($queryTokensWithBools as $token)
  	{
	  $booleans = Array ( "and", "or", "not" );

	  # If token is a boolean operator
	  if ( in_array( strtolower($token), $booleans) )
	  {
	    print " " . strtolower($token) . " "; 
	  }
	  else if ( (strpos( $token, ":" )) !== FALSE )
	  {
	    print " $token ";
	  }
	  else
	  {
	    $tokenLink = str_replace( " ", "+", $token );
    	    echo "\"<a href=$_SERVER[PHP_SELF]?"
		. "query=$tokenLink&phrasemode=$phrasemode"
		. "&allmode=$allmode"
		. "&page=1>"
		. "<font color=white><b>$token</b></font></a>\" ";
	  }
          $i++;
  	}

  	# Where search is within single archive 
  
  	if ( array_key_exists('archive', $_GET) &&  $_GET['archive'] != "") 
  	{ 
	  echo "in \"<b>$_GET[archive]</b>\""; 
  	}
  	else { echo "in all OLAC archives"; }

	echo " with score:{$score}/5 ";

  	echo "<td align=right><font color=white>" . $numResults
  	  . " results from $numArchives archive(s)</font>&nbsp;&nbsp;</td>";
  	echo "</tr></table>\n";
  }

  return;
}


#########
# Mode 'item' used to display the full record information for an item
#########

#else if ( array_key_exists('item', $_GET))
function modeItem()
{
  global $DB, $output;
  $stringQuery = str_replace( " " , "+", $_GET['queryTerms'] );

  $url = "/item?"
                        . "identifier=" . urlencode($_GET["item"])
                        . "&queryTerms=" . urlencode($stringQuery)
                        . "&phrasemode=" . urlencode($_GET["phrasemode"])
                        . "&allmode=" . urlencode($_GET["allmode"]);
  $result = file_get_contents($url);
  if (!$result) header("HTTP/1.0 404 Not Found");
  #$result = preg_replace('{<script [^>]*urchin\.js.*?</script>}', '', $result);
  #$result = preg_replace('{_uacct[^;]*?;}', '', $result);
  #$result = preg_replace('{urchinTracker[^;]*?;}', '', $result);

  $search = array('/<\/?(BODY|HTML)>/i',
                  '/<HEAD>.*<\/HEAD>/si');
  $replace = array('','');

  print "<hr>". preg_replace($search, $replace, $result);

}

##########
# AltNames mode used to find alternate names for a searched language
##########

#if ( array_key_exists('altNames', $_GET) )
function modeAltNames()
{
    global $DB, $output;
	$alternateQuery = buildAltQuery($_GET['altNames']);
	$altRes = $DB->sql($alternateQuery);

# Search header info

	$qString = str_replace( " ", "+", $_GET['altNames'] );

  echo "<table width=\"100%\" border=0 bgcolor=darkblue cellspacing=0><tr>
	<td><font color=white>&nbsp;&nbsp;Alternate names for the language \"
	<a href=$_SERVER[PHP_SELF]?query=$qString&page=1><font color=white><b>	
	$_GET[altNames]</b></font></a>\"";

  echo "<td align=right><font color=white>&nbsp;</font></td>";
  echo  "</tr></table>\n";

	headerLinks($_GET['altNames']);
	if ($altRes) {
		tabulate($altRes);
	}
}

########
# Mode 'country' used to display all languages spoken in a given country
########

#else if (array_key_exists('country', $_GET) )
function modeCountry()
{
    global $DB, $output;
	$countryQuery = buildCountryQuery($_GET['country']);
	$countryRes = $DB->sql($countryQuery);

	if ($countryRes) {

# Search header info

  echo "<table width=\"100%\" border=0 bgcolor=darkblue cellspacing=0 ><tr>
	<td><font color=white>Languages spoken in \"$_GET[country]\"";
  echo "<td align=right><font color=white>&nbsp;</font></td>";
  echo "</tr></table>\n";

	headerLinks($_GET['country']);
	tabulate($countryRes);

	}

}

########
# Mode 'simSpell' used to display a list of words which are spelt similarly
#	to the search term
########

#else if ( array_key_exists('simSpell', $_GET) )
function modeSimSpell()
{
    global $DB, $output;
    $queryTokens = tokenizeQuery($_GET['simSpell'], false, false);

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
  headerLinks($_GET['simSpell']);

	# Display words with similar spelling
  similarSpellings($queryTokens);
}

function displayResults($tab, $queryTokens, &$numArchives, &$numResults )
{
global $output;
$results = "";

      $prevOAIIdentifier = "";

      $prevArchiveHeader = "";
      $prevArchiveRecords= "";
      $loopOut = "";

	$prevArchive="";
	$prevArchiveID="";
	$counter=0;

      foreach ($tab as $row)
      {
	$numResults++;
	if ($prevArchive=="")
	{
	    $prevArchive = $row['RepositoryIdentifier'];
	    $prevArchiveID=$row['Archive_ID'];
	    $numArchives++;
	}

	if ($prevArchive != $row['RepositoryIdentifier'])
	{
	    $results .= displayArchiveResults ($prevArchive, $archiveRecords, 
				$queryTokens, $counter, $prevArchiveID );
	    clearArray($archiveRecords);
	    $numArchives++;
	    $counter=0;
	}

	$archiveRecords[$counter] = $row;

	$prevArchive = $row['RepositoryIdentifier'];
	$prevArchiveID=$row['Archive_ID'];
	$counter++;
      }

      $results .= displayArchiveResults( $prevArchive, $archiveRecords, 
		$queryTokens, $counter, $prevArchiveID );


return $results;
}

function clearArray( &$array )
{

    while (!empty($array)) { array_pop($array); }

return;
}

function displayArchiveResults( $repositoryID, $archiveRecords, $queryTokens, 
	$number, $archiveID )
{
    global $recsPerArchive;
    $specArchive=0;

    $records = "<table><tr>"
    	   . "\n<th align=left><big>Results from "
	   . "\"<a href=\"/archive/"
	   . "$repositoryID\">"
	   . "$repositoryID</a>\"</big></th>\n";

    if ( array_key_exists("archive", $_GET) && ($_GET['archive'] != ""))
    {
	$specArchive=1;	
    }

    if (($number > $recsPerArchive)&&($specArchive==0))
    {
      $q = str_replace( " ", "+", $_GET['query']);
      $records .= 
	"<td align=left><a href=\"$_SERVER[PHP_SELF]?query=$q"
	. "&score={$_GET['score']}"
	. "&archive=$repositoryID&page=1"
	. "\">"
	. "List all results from this archive ($number matches)</a>"
	. "</td>";

    }
	$records .= "</tr></table>";

    $counter=0;
    foreach($archiveRecords as $rec)
    {
	if (($counter >= $recsPerArchive)&&($specArchive==0)) { break; }

	$n = $counter + 1;

	#$toks = tokenizeQuery( $_GET['query'], true, false);
        $records .= formatResult( $rec, $n, $queryTokens );
	$counter++;
    }

	#$records .= "</td></tr></table>";

return $records;
}

function printResultsBar($leftString, $rightString)
{

}

$DB->disconnect();

print $output;

?>

</BODY>
</HTML>

