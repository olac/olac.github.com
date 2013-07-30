<?php
######################################################################
# synopsis:
#   Metadata lookup program for OLAC 1.0 MySQL database
#
# usage:
#   http://www.language-archives.org/tools/lookup  or
#   http://www.language-archives.org/tools/lookup/index.php?identifier=<oai-id>
#
#   <oai-id>  OAI record identifier
#
# changes:
#   2003-02-28  use olac2 - HL
#   2003-02-26  revised for OLAC 1.0 with "display" format - HL
#   2002-??-??  Haejoong Lee revised it
#   2002-??-??  Steven Bird wrote it originally
######################################################################

define(OLAC_DB_NAME, 'olac');
#require_once("/mnt/unagi/speechd8/ldc/wwwhome/htdocs/language-archives/lib/php/OLACDB.php");

include "olacdb.php";
#$DB = new OLACDB(OLAC_DB_NAME);
$DB = new OLACDB();


function draw_form() {
  print <<<END
<html>
<head>
<title>OLAC Metadata Lookup</title>
<link rel="stylesheet" type="text/css" href="olac.css">
</head>
<body>

<form action="$_SERVER[PHP_SELF]" method="get">
OAI identifier:
<input type="text" name="identifier" size="40"/>
<input type="submit" value="Lookup!"/>
</form>

</body>
</html>
END;
    exit;
}

function error_page($msg) {
    print <<<END
<html>
<head>
<title>OLAC Metadata Lookup</title>
<link rel="stylesheet" type="text/css" href="olac.css">
</head>
<body>
<p><b><font color=red>$msg</font></b>
</body>
</html>
END;
    exit;
}



$_GET[identifier] or draw_form();

$_GET[identifier] = trim( $_GET[identifier] );

$tab = $DB->sql("
    select OA.RepositoryName, OA.BaseURL,       OA.Archive_ID,
           AI.Item_ID,        AI.OaiIdentifier, AI.DateStamp
    from   OLAC_ARCHIVE OA, ARCHIVED_ITEM AI
    where  AI.OaiIdentifier = '$_GET[identifier]'
    and    OA.Archive_ID = AI.Archive_ID");
$DB->saw_error() and error_page("Query failed");
$tab or error_page("Record not found");

$body = "<h2>OLAC Record: $_GET[identifier]</h2>\n";

$answer = $tab[0];

$olac_info = <<<END
<tr>
  <td colspan=3><br><p><b>OLAC Info</b></td>
</tr>
<tr>
  <td class=lookup><i>Archive:&nbsp;</i></td>
  <td></td>
  <td>$answer[RepositoryName]</td>
</tr>
<tr>
  <td class=lookup><i>Description:&nbsp;</i></td>
  <td></td>
  <td><a href="/archive.php4?id=$answer[Archive_ID]">http://www.language-archives.org/archive.php4?id=$answer[Archive_ID]</a></td>
</tr>
END;

$oai_info = <<<END
<tr>
  <td colspan=3><br><p><b>OAI Info</b></td>
</tr>
<tr>
  <td class=lookup><i>OaiIdentifier:&nbsp;</i></td>
  <td></td>
  <td>$answer[OaiIdentifier]</td>
</tr>
<tr>
  <td class=lookup><i>DateStamp:&nbsp;</i></td>
  <td></td><td>$answer[DateStamp]</td>
</tr>
<tr>
  <td class=lookup><i>GetRecord:&nbsp;</i></td>
  <td></td>
  <td><a href="$answer[BaseURL]?verb=GetRecord&identifier=$identifier&metadataPrefix=olac">$answer[BaseURL]?verb=GetRecord&identifier=$identifier&metadataPrefix=olac</a></td>
</tr>
<tr>
  <td class=lookup><i>Lookup Arc:&nbsp;</i></td>
  <td></td>
  <td><a href="http://arc.cs.odu.edu:8080/oai/servlet/search?formname=detail&id=$_GET[identifier]">http://arc.cs.odu.edu:8080/oai/servlet/search?formname=detail&id=$_GET[identifier]</a>
</tr>
END;

$queryString = "
    select ed.Label as TagLabel, ed2.Label as DcTag,
           Lang, Content, ex.Label as Type, cd.Label as Code
    from   ELEMENT_DEFN ed, ELEMENT_DEFN ed2,
           METADATA_ELEM me, EXTENSION ex, CODE_DEFN cd 
    where  Item_ID=$answer[Item_ID]
    and    ed2.Tag_ID = ed.DcElement
    and    me.Extension_ID=ex.Extension_ID
    and    me.Extension_ID=cd.Extension_ID
    and    (cd.Code='' or me.Code=cd.Code)
    and    me.Tag_ID = ed.Tag_ID
    order by ed.Rank";

$queryString = "
select 	me.Content, ed2.Label as DcTag, cd.Label as Code, ex.Label as Type,
		ed.Label as DcTag, ed.Label as TagLabel
from 	ELEMENT_DEFN ed, CODE_DEFN cd, ELEMENT_DEFN ed2, EXTENSION ex 
		RIGHT OUTER JOIN 
		METADATA_ELEM me
		ON ex.Extension_ID = me.Extension_ID
where 	ed.Tag_ID = me.Tag_ID
and	ed2.Tag_ID = ed.DcElement
and	cd.Extension_ID = me.Extension_ID
and	(cd.Code='' or me.Code=cd.Code)
and	me.Item_ID=$answer[Item_ID]
order by ed.Rank
";

echo "<!-- $queryString -->";
$tab = $DB->sql($queryString);
$DB->saw_error() and error_page("Query failed");

$prev_field = "";
$meta = "";
$meta_description = "";
$meta_keywords = "";
$body .= "<p><table cellspacing=0 cellpadding=2 border=0>\n";
$body .= "<tr><td colspan=3><b>Metadata</b></td></tr>\n";

foreach ($tab as $answer) {
    $tag = $meta_name = $answer[DcTag];
    $padding = '';
    $value = '';
    if ($answer[Type]) {
	if ($answer[Code]) {
	    $value = "[$answer[Type] = $answer[Code]]";
        } else {
            $value = "[$answer[Type]]";
        }
	$padding = ' ';
    }
    if ($answer[Content]) {
        $value .= $padding . $answer[Content];
        $padding = ' ';
    }
    if ($answer[TagLabel] != $answer[DcTag]) {
        $value .= $padding . "[$answer[TagLabel]]";
    }
	
    $meta_content = $value;
    $value = ereg_replace("(http:[^ ]+)", "<a href=\"\\1\">\\1</a>", $value);
    $body .= "<tr><td class=lookup><i>";
    if ($tag != $prev_field) {
        $body .= "$tag:";
    }
    $body .= "&nbsp;</i></td><td></td><td>$value</td></tr>\n";

    $meta_name = "DC." . ereg_replace(" .*", "", $meta_name);
    $meta .= "<meta name=\"$meta_name\" content=\"$meta_content\">\n";

    switch ($tag) {
	case "Title":
	    $title = $meta_content;
	    break;
	case "Description":
	    $meta_description .= "$meta_content ";
	    break;
	case "Subject":
	case "Subject language":
	case "Coverage":
	case "Type.linguistic":
	    $meta_keywords .= "$meta_content;";
	    break;
    }

    $prev_field = $tag;
}

$body .= $olac_info;
$body .= $oai_info;
$body .= "</table>\n";

if ($meta_description && $meta_keywords) {
    $meta_description = substr($meta_description, 0, -1);
    $meta_keywords = substr($meta_keywords, 0, -1);
    $meta .= "<meta name=\"Description\" content=\"$meta_description\">\n";
    $meta .= "<meta name=\"Keywords\" content=\"$meta_keywords\">\n";
}

?>

<!--
<HTML>
<HEAD>
<TITLE><?=$title?></TITLE>
<LINK REL="stylesheet" TYPE="text/css" HREF="olac.css">
<?=$meta?>
</HEAD>

<BODY>
-->

<? 

function tokenizeQuery($queryString)
{
    $tokens = array();
    $separators = " \t";

    if ($_GET[mode] == 'phrase')
    {
        $tokens[0]= trim($queryString);
        return $tokens;
    }

    $tok = strtok($queryString, $separators);
    $i=0;
    while ($tok)
    {
        $tokens[$i] = $tok;
        $tok = strtok( $separators );
        $i++;
    }

    return $tokens;

}

        $queryTokens = tokenizeQuery( $_GET[queryTerms] );
        foreach( $queryTokens as $tok )
        {
                # Case insensitive matching of query keywords
                $body = ereg_replace( sql_regcase($tok),
                "<em>\\0</em>", $body );
        }


echo $body; 


?>
</BODY>
</HTML>
