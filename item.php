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
#   2008-05-16  modify GA script to include tracker for ArchiveURL -SB
#   2008-03-03  obtain item identifier from the path info as well -HL
#   2008-02-06  added GA script; added search terms -HL
#   2008-01-28  fixed an sql query because it didn't pick up all
#               metadata elements -HL
#   2004-11-10  set character encoding to UTF-8 - HL
#   2003-02-28  use olac2 - HL
#   2003-02-26  revised for OLAC 1.0 with "display" format - HL
#   2002-??-??  Haejoong Lee revised it
#   2002-??-??  Steven Bird wrote it originally
######################################################################

require_once("lib/php/OLACDB.php");
$DB = new OLACDB();
$DB->sql("set names 'utf8'");
$BASEURL = olacvar('baseurl');
$OLACA = olacvar('aggregator/url');
$CITE_PROG = olacvar('cite');
$STATIC_ROOT = olacvar('static/root');

if (substr($OLACA, 0, strlen($BASEURL)) == $BASEURL) {
  $OLACA_HREF = substr($OLACA, strlen($BASEURL));
} else {
  $OLACA_HREF = $OLACA;
}

function draw_form() {
  print <<<END
<html>
<head>
<title>OLAC Metadata Lookup</title>
<link rel="stylesheet" type="text/css" href="/olac.css">
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
<link rel="stylesheet" type="text/css" href="/olac.css">
</head>
<body>
<p><b><font color=red>$msg</font></b>
</body>
</html>
END;
    exit;
}

function tokenizeQuery($queryString)
{
    $tokens = array();
    $separators = " \t";

    $booleanKeywords = Array ( "and", "or", "not" );

    if ( (array_key_exists('mode', $_GET)) && ($_GET['mode'] == 'phrase') )
    {
        $tokens[0]= trim($queryString);
        return $tokens;
    }

    $tok = strtok($queryString, $separators);
    $i=0;
    while ($tok)
    {
        if ( in_array( $tok, $booleanKeywords )  )
        {
                # FIXME
                $i--;
        }
        else
        {
        $tokens[$i] = $tok;
        }
        $tok = strtok( $separators );
        $i++;

    }

    return $tokens;

}


function old_meta_tag_formatting($tab)
{
  $meta = "";
  $meta_description = "";
  $meta_keywords = "";
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
      $s = $answer["Content"];
      #$s = preg_replace("/</", "&lt;", $s);
      #$s = preg_replace("/>/", "&gt;", $s);
      $s = preg_replace("/\"/", "&quot;", $s);
      $value .= $padding . $s;
      $padding = ' ';
    } else {
      if ($answer['Type'] == 'language' && $answer['Code']) {
	$value .= $padding . $answer['CodeName'];
      }
    }
    if ($answer[TagLabel] != $answer[DcTag]) {
      $value .= $padding . "[$answer[TagLabel]]";
    }
    
    $meta_content = $value;
    $meta_name = "DC." . ereg_replace(" .*", "", $meta_name);
    if ($meta_name == "DC.Description") $meta_name="Description";
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
  }

  if ($meta_description && $meta_keywords) {
    $meta_description = substr($meta_description, 0, -1);
    $meta_keywords = substr($meta_keywords, 0, -1);
    $meta .= "<meta name=\"Description\" content=\"$meta_description\">\n";
    $meta .= "<meta name=\"Keywords\" content=\"$meta_keywords\">\n";
  }
  return $meta;
}


function same_elements($e1, $e2)
{
  $n = count($e1);
  if ($n != count($e2)) return false;
  for ($i=0; $i < $n; $i++) {
    if ($e1[$i] != $e2[$i])
      return false;
  }
  return true;
}

function found_in($x, $list)
{
  foreach ($list as $element) {
    if (same_elements($x, $element))
      return true;
  }
  return false;
}

function uniq($a) {
  $r = array();
  foreach ($a as $element) {
    if (!found_in($element, $r))
      $r[] = $element;
  }
  return $r;
}


function make_inferred_metadata($tab)
{
  $areas = array();
  $countries = array();
  foreach ($tab as $record) {
    if ($record['TagName'] == 'subject' &&
	$record['Type'] == 'language') {
      if ($record['LangID']) {
	$areas[] = $record['Area'];
	$countries[$record['CountryName']] = $record['CountryCode'];
      }
    }
  }

  $html = "";

  if ($countries) {
    asort($countries);
    $html .= "<tr><td class=\"lookup\"><i>Country:&nbsp;</i></td><td></td><td>";
    foreach ($countries as $country => $cc) {
      $html .=
	"<a href=\"/search?q=country_$cc&a=---+all+archives+\">$country</a>";
    }
    $html .= "</tr>";
  }

  if ($areas) {
    sort($areas);
    $areas = array_unique($areas);
    $html .= "<tr><td class=\"lookup\"><i>Area:&nbsp;</i></td><td></td><td>";
    foreach ($areas as $area) {
      $html .= 
	"<a href=\"/search?q=area_$area&a=---+all+archives+\">$area</a>";
    }
    $html .= "</tr>";
  }

  if ($html) {
    $html = "<tr><td colspan=3><br><p><b>Inferred Metadata</b></td></tr>$html";
  }

  return $html;
}


function olac_display_process($tab, &$search_terms)
{
  $elements = array();
  foreach ($tab as $answer) {

    $tag = $answer['TagName'];
    $tagname = $answer['TagLabel'];
    $content = $answer['Content'];
    $ext = $answer['Type'];
    $code = $answer['Code'];
    $prefix = $answer['NSPrefix'];
    
    if ($ext == 'language' && $answer['LangID']) {
      array_push($search_terms, "iso639_" . $answer['LangID']);
      if ($answer['CountryCode'])
	array_push($search_terms, "country_" . $answer['CountryCode']);
      if ($answer['Area'])
	array_push($search_terms, "area_" . $answer['Area']);
    } elseif (($ext == 'linguistic-type' ||
	       $ext == 'linguistic-field' ||
	       $ext == 'discourse-type') && $code) {
      array_push($search_terms, "olac_" . $code);
    } else if ($ext == 'DCMIType' && $content) {
      array_push($search_terms, "dcmi_" . $content);
    }

    if ($tag == "type" && $ext == "discourse-type") {
      if ($code) {
	$elements[] = array($tag, $code, $ext, '', $tagname, $prefix);
      }
    }
    elseif ($tag == 'type' && $ext == 'linguistic-type') {
      if ($code) {
	$elements[] = array($tag, $code, $ext, '', $tagname, $prefix);
      }
    }
    elseif ($tag == 'subject' && $ext == 'linguistic-field') {
      if ($code) {	
	$elements[] = array($tag, $code, $ext, '', $tagname, $prefix);
      }
      if ($content) {
	$elements[] = array($tag, $content, '', '', $tagname, '');
      }
    }
    elseif ($tag == 'subject' && $ext == 'language') {
      if ($code) {
	$s = "<a href=\"/language/$code\">$code</a>";
	$elements[] = array($tag, $s, $ext, '', $tagname, $prefix);
      }
      if ($answer['CodeName']) {
	$new_content = $answer['CodeName'];
	if (!strstr($new_content, 'language'))
	  $new_content .= " language";
	$elements[] = array($tag, $new_content, '', '', $tagname, '');
      }
      if ($content && $content!=$answer['CodeName']) {
	$elements[] = array($tag, $content, '', '', $tagname, '');
      }
    }
    elseif ($tag == 'language' && $ext == 'language') {
      if ($code) {	
	$s = "<a href=\"/language/$code\">$code</a>";
	$elements[] = array($tag, $s, $ext, '', $tagname, $prefix);
      }
      if ($content) {
	$langname = $answer['CodeName'];
	if ($langname && !strstr($content,$langname))
	  $new_content = "$langname; $content";
	else
	  $new_content = $content;
	$elements[] = array($tag, $new_content, '', '', $tagname, '');
      }
      else {
	$elements[] = array($tag, $answer['CodeName'], '', '', $tagname, '');
      }
    }
    else {
      $elements[] = array($tag, $content, $ext, $code, $tagname, $prefix);
    }
  }

  return uniq($elements);
}


function cmp_html_tab_entries($a, $b)
{
  if ($a[0] == 'Title' && $b[0] != 'Title')
    return -1;
  elseif ($a[0] != 'Title' && $b[0] == 'Title')
    return 1;
  $r = strcmp($a[0], $b[0]);
  if ($r != 0)
    return $r;
  if ($a[2] < $b[2])
    return -1;
  elseif ($a[2] > $b[2])
    return 1;
  else
    return 0;
}

function make_search_link($code, $prefix) {
  if (strpos($code, "<a") === 0)
    return $code;
  else
    return
      "<a href=\"/search?q=$prefix" .
      "_$code&a=---+all+archives+\">$code</a>";
}

function generate_html_format($elements)
{
  global $analytics_link;
  $tab = array();
  foreach ($elements as $record) {

    $tag = $record[0];
    $content = $record[1];
    $ext = $record[2];
    $code = $record[3];
    $tagname = $record[4];
    $prefix = $record[5];

    if (!$content) continue;

    if ($prefix=="olac" && $ext == "language") {
      $newtag = "$tagname (ISO639)";
      $content = make_search_link($content, $prefix);
    }
    else if ($prefix=="olac" && ($ext == "linguistic-field" || $ext == "linguistic-type")) {
      $newtag = "$tagname (OLAC)";
      $content = make_search_link($content, $prefix);
    }
    else if ($prefix=="olac" && $ext == "role" && $code)
      $newtag = "$tagname ($code)";
    else if ($prefix=="olac" && $ext == "discourse-type") {
      $newtag = "$tagname (Discourse)";
      $content = make_search_link($content, $prefix);
    }
    else if ($prefix=="dcterms" && $tag == "type" && $ext == "DCMIType") {
      $newtag = "$tagname (DCMI)";
      $content = make_search_link($content, "dcmi");
    }
    else if ($prefix=="dcterms" && $ext == "ISO3166") {
      $newtag = "$tagname (ISO3166)";
      $content = "<a href=\"/country/$content\">$content</a>";
    }
    else if ($prefix=="dcterms" && $ext)
      $newtag = "$tagname ($ext)";
    else
      $newtag = $tagname;

    $value = $content;
    $value = preg_replace("/(https?:[^ ()\[\]<>{}]+)/", "<a href=\"\\1\" $analytics_link>\\1</a>", $value);
    $value = preg_replace("/(oai:[^: ]+:[^: ]+)/", "<a href=\"/item/\\1\">\\1</a>", $value);

    $tab[] = array($newtag, $value, count($tab));
  }

  usort($tab, "cmp_html_tab_entries");
  return $tab;
}

function get_static_citation($oaiid) {
    global $STATIC_ROOT;
    $citation = file_get_contents($STATIC_ROOT . "/cite/$oaiid.txt");
    return $citation;
}

function get_citation($oaiid) {
    global $CITE_PROG;
    $descriptorspec = array(0 => array("pipe", "r"),
                            1 => array("pipe", "w"),
                            2 => array("file", "/dev/null", "w"));
    $citeproc = proc_open($CITE_PROG, $descriptorspec, $pipes,
                          NULL, NULL, array("binary_pipes"));
    fwrite($pipes[0], $oaiid . "\n");
    fclose($pipes[0]);
    $citation = stream_get_line($pipes[1], 8192);
    fclose($pipes[1]);
    proc_close($citeproc);
    return $citation;
}



$itemid=$_GET['identifier'];
if (!$itemid) {
	$arr = explode('?', $_SERVER["REQUEST_URI"]);
	$arr = explode('/oai:', $arr[0]);
	$itemid = "oai:" . $arr[1];
}
$itemid or draw_form();

$tab = $DB->sql("
    select OA.RepositoryName, OA.BaseURL,       OA.Archive_ID,
           AI.Item_ID,        AI.OaiIdentifier, AI.DateStamp,
	   OA.RepositoryIdentifier
    from   OLAC_ARCHIVE OA, ARCHIVED_ITEM AI
    where  AI.OaiIdentifier = '$itemid'
    and    OA.Archive_ID = AI.Archive_ID");
if ($DB->saw_error()) {
    header("HTTP/1.0 500 Internal Server Error");
    error_page("Query failed");
}
if (!$tab) {
    header("HTTP/1.0 404 Not Found");
    error_page("Record not found");
}
$title = "Untitled";
$body = "";

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
  <td><a href="/archive/$answer[RepositoryIdentifier]">$BASEURL/archive/$answer[RepositoryIdentifier]</a></td>
</tr>
<tr>
  <td class=lookup><i>GetRecord:&nbsp;</i></td>
  <td></td>
  <td><a href="$OLACA_HREF?verb=GetRecord&identifier=$itemid&metadataPrefix=olac">OAI-PMH request for OLAC format</a></td>
</tr>
<tr>
  <td class=lookup><i>GetRecord:&nbsp;</i></td>
  <td></td>
  <td><a href="/static-records/$itemid.xml">Pre-generated XML file</td>
END;

$oai_info = <<<END
<tr>
  <td colspan=3><br><p><b>OAI Info</b></td>
</tr>
<tr>
  <td class=lookup><i>OaiIdentifier:&nbsp;</i></td>
  <td></td>
  <td><a href="/item/$answer[OaiIdentifier]">$answer[OaiIdentifier]</a></td>
</tr>
<tr>
  <td class=lookup><i>DateStamp:&nbsp;</i></td>
  <td></td><td>$answer[DateStamp]</td>
</tr>
<tr>
  <td class=lookup><i>GetRecord:&nbsp;</i></td>
  <td></td>
  <td><a href="$OLACA_HREF?verb=GetRecord&identifier=$itemid&metadataPrefix=oai_dc">OAI-PMH request for simple DC format</a></td>
</tr>
END;

$analytics = <<<END
<script type="text/javascript">
_gaq.push(['_trackPageview', '/item/']);
_gaq.push(['_trackPageview',
           '/archive_item_hits/$answer[RepositoryIdentifier]']);
</script>
END;

$analytics_link = "onClick=\"_gaq.push(['_trackPageview', '/archive_item_clicks/$answer[RepositoryIdentifier]']);\"";

$tab = $DB->sql("
	select ed.TagName, ed.Label as TagLabel, ed2.Label as DcTag, Lang, Content,
          me.Type Type,
# me.Code Code, lc.LangID, lc.Name LangName
cd.Code Code, cd.Label CodeName, ex.NSPrefix, lc.Id LangID,
cc.CountryID CountryCode, cc.Name CountryName, cc.Area
	from METADATA_ELEM me
	left join ELEMENT_DEFN ed on me.Tag_ID=ed.Tag_ID
	left join ELEMENT_DEFN ed2 on ed2.Tag_ID=ed.DcElement
	left join CODE_DEFN cd on cd.Extension_ID=me.Extension_ID and cd.Code=me.Code
        left join EXTENSION ex on ex.Extension_ID=me.Extension_ID
        left join ISO_639_3 lc on me.Code=lc.Id
        left join LanguageCodes eth on lc.Id=eth.LangID
        left join CountryCodes cc on eth.CountryID=cc.CountryID
	where Item_ID=$answer[Item_ID]
	order by ed.Rank
");
$DB->saw_error() and error_page("Query failed");

$prev_tag = "";
$rowcount = 0;
$meta_keywords = "";
$body .= "<p><table class=lookuptable cellspacing=1 cellpadding=2 border=0>\n";
$body .= "<tr><td colspan=3><b>Metadata</b></td></tr>\n";
$search_terms = array();
$queryTokens = tokenizeQuery( $_GET['queryTerms'] );

$elements = olac_display_process($tab, $search_terms);
$html_tab = generate_html_format($elements);
$inferred_metadata_section = make_inferred_metadata($tab);

foreach ($html_tab as $record) {

  $tag = $record[0];
  $value = $record[1];

  if ($tag == 'Title') {
    $title = $value;
  }

  if ($tag != $prev_tag) {
    if ($rowcount > 0) {
      if ($rowcount > 1)
	$rowspan = "rowspan=$rowcount";
      else
	$rowspan = "";
      $body .= "<tr><td class=lookup $rowspan>" . $body1;
    }
    $body1 = "<i>$tag:</i></td>";
    $rowcount = 0;
  }
  else {
    $body1 .= "<tr>";
  }

  # search term highlighting
  foreach( $queryTokens as $tok ) {
    $tok = str_replace("(","\(",$tok);
    # Case insensitive matching of query keywords
    $value = eregi_replace($tok, '<em style="background:yellow">\\0</em>', $value );
  }

  $body1 .= "<td></td><td>$value</td></tr>\n";
  $rowcount += 1;
  $prev_tag = $tag;
}

if ($rowcount > 0) {
  if ($rowcount > 1)
    $rowspan = "rowspan=$rowcount";
  else
    $rowspan = "";
  $body .= "<tr><td class=lookup $rowspan>" . $body1;
}

$citation = @get_static_citation($itemid);
if (!$citation)  # try dynamic version
  $citation = get_citation($itemid);

if ($citation or $search_terms)
  $search_info = "<tr><td colspan=3><br><p><b>Search Info</b></td></tr>";
if ($citation) {
  $search_info .= "<tr><td class=lookup><i>Citation:&nbsp;</i></td><td></td>";
  $search_info .= "<td>$citation</td></tr>";
}
if ($search_terms) {
  sort($search_terms);
  $search_terms = array_unique($search_terms);
  $s = implode(" ", $search_terms);
  $search_info .= "<tr><td class=lookup><i>Terms:&nbsp;</i></td><td></td>";
  $search_info .= "<td>$s</td></tr>";
}


$body .= $olac_info;
$body .= $oai_info;
$body .= $search_info;
$body .= $inferred_metadata_section;
$body .= "</table>\n";

$meta = old_meta_tag_formatting($tab);

?>
<HTML>
<HEAD>
<TITLE>OLAC Record: <?=$title?></TITLE>
<script type="text/javascript" src="/js/gatrack.js"></script>
<LINK REL="stylesheet" TYPE="text/css" HREF="/olac.css">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<?=$meta?>
<style>
.lookup {width: 25%;}
.lookuptable {width: 100%;}
</style>
</HEAD>

<BODY>
<HR>
<TABLE CELLPADDING="10">
<TR>
<TD> <A HREF="/"><IMG SRC="/images/olac100.gif"
BORDER="0"></A></TD>
<TD><span style="color:#00004a; font-size:24pt; font-weight:bold"
>OLAC Record</span><br/><span style="font-size:18pt"
><?=$itemid?></span></FONT></H1></TD>
</TR>
</TABLE>
<HR>

<?php
echo $body;
echo $analytics;
?>

<hr>
<div class="timestamp">
<?=olacvar('baseurl') . $_SERVER['REQUEST_URI']?><br>
Up-to-date as of: <?=date("D M j G:i:s T Y")?>
</div>

</BODY>
</HTML>
