<?php

require_once('lib/php/OLACDB.php');
$DB = new OLACDB();

class CitationGenerator {

  private $proc = null;
  private $pipes = null;
  private $open = FALSE;

  function __construct() {
    $descriptorspec = array(0 => array("pipe", "r"),
			    1 => array("pipe", "w"),
			    2 => array("file", "/dev/null", "w"));
    $this->proc = proc_open(olacvar('cite'), $descriptorspec, $this->pipes,
			    NULL, NULL, array("binary_pipes"));
    $this->open = TRUE;
  }

  function __destruct() {
    if ($this->open) {
      $this->close();
    }
  }

  function cite($oaiid) {
    fwrite($this->pipes[0], $oaiid . "\n");
    return stream_get_line($this->pipes[1], 8192, "\n");
  }

  function close() {
    fclose($this->pipes[0]);
    fclose($this->pipes[1]);
    proc_close($this->proc);
    $this->open = FALSE;
  }
}

function error($msg) {
  echo "<p>ERROR: $msg</p>";
  exit(0);
}

$CITE = new CitationGenerator();

function shutdown() {
  if (isset($GLOBALS['CITE']))
    unset($GLOBALS['CITE']);
}

register_shutdown_function('shutdown');

# get language code
$path = preg_split('#/#', $_SERVER['PATH_INFO']);
$langcode = $path[1];

# get language name
$s = mysql_real_escape_string($langcode);
$tab = $DB->sql("select Ref_Name from ISO_639_3 where Id='$s'");
if ($DB->saw_error())
  error("internal server error");
elseif (count($tab) == 0)
  error("no language found for language code '$langcode'");
$langname = $tab[0]['Ref_Name'];
$langname = preg_replace('/\s*\(.*\)\s*$/', '', $langname);
$langname = trim($langname);
if (preg_match('/\s+language\s*$/i', $langname))
  $langname2 = $langname;
else
  $langname2 = "$langname language";
$title = "OLAC resources in and about the $langname2";

# get other names and dialects
$escaped_langname = $DB->escape($langname);
$sql = "select distinct Name from LanguageIndex
        where LangID='$langcode' and Name!='$escaped_langname'
        order by Name";
$tab = $DB->sql($sql);
if ($DB->saw_error()) error("internal server error");
$arr = array();
if ($tab) {
  foreach ($tab as $row) {
    $a = preg_split('#\s*,\s*#', $row['Name']);
    $arr[] = rtrim("$a[1] $a[0]");
  }
}
$langnames = implode(', ', $arr);

# get primary texts
$sql = "
select distinct ai.OaiIdentifier
from ARCHIVED_ITEM ai, METADATA_ELEM me1, METADATA_ELEM me2
where ai.Item_ID=me1.Item_ID
  and me1.Item_ID=me2.Item_ID
  and me1.TagName='subject'
  and me1.Code='$langcode'
  and me2.Type='linguistic-type'
  and me2.Code='primary_text'
order by ai.OaiIdentifier
";
$tab = $DB->sql($sql);
if ($tab) {
  $primary_texts = array();
  foreach ($tab as $row) $primary_texts[] = $row['OaiIdentifier'];
}

# get lexical resources
$sql = "
select distinct ai.OaiIdentifier
from ARCHIVED_ITEM ai, METADATA_ELEM me1, METADATA_ELEM me2
where ai.Item_ID=me1.Item_ID
  and me1.Item_ID=me2.Item_ID
  and me1.TagName='subject'
  and me1.Code='$langcode'
  and me2.Type='linguistic-type'
  and me2.Code='lexicon'
order by ai.OaiIdentifier
";
$tab = $DB->sql($sql);
if ($tab) {
  $lexical_resources = array();
  foreach ($tab as $row) $lexical_resources[] = $row['OaiIdentifier'];
}

# get language descriptions
$sql = "
select distinct ai.OaiIdentifier
from ARCHIVED_ITEM ai, METADATA_ELEM me1, METADATA_ELEM me2
where ai.Item_ID=me1.Item_ID
  and me1.Item_ID=me2.Item_ID
  and me1.TagName='subject'
  and me1.Code='$langcode'
  and me2.Type='linguistic-type'
  and me2.Code='language_description'
order by ai.OaiIdentifier
";
$tab = $DB->sql($sql);
if ($tab) {
  $language_descriptions = array();
  foreach ($tab as $row) $language_descriptions[] = $row['OaiIdentifier'];
}

# get other resources about the language
$sql = "
select distinct ai.OaiIdentifier
from ARCHIVED_ITEM ai, METADATA_ELEM me
where ai.Item_ID=me.Item_ID
  and me.TagName='subject'
  and me.Code='$langcode'
  and not exists (
    select 1 from METADATA_ELEM me2
    where me2.Item_ID=me.Item_ID
      and me2.Type='linguistic-type')
";
$tab = $DB->sql($sql);
if ($tab) {
  $other_resources1 = array();
  foreach ($tab as $row) $other_resources1[] = $row['OaiIdentifier'];
}

# get other resources in the language
$sql = "
select distinct ai.OaiIdentifier
from ARCHIVED_ITEM ai, METADATA_ELEM me
where ai.Item_ID=me.Item_ID
  and me.TagName = 'language'
  and me.Code='$langcode'
  and not exists (
    select 1 from METADATA_ELEM me2
    where me2.Item_ID=me.Item_ID
      and me2.TagName='subject'
      and me2.Code='$langcode')
";
$tab = $DB->sql($sql);
if ($tab) {
  $other_resources2 = array();
  foreach ($tab as $row) $other_resources2[] = $row['OaiIdentifier'];
}

# compute search terms
$arr = array("dialect", "vernacular");
if (count($primary_texts) > 0) {
  $arr[] = "discourse";
  $arr[] = "stories";
  $arr[] = "conversation";
  $arr[] = "dialogue";
  $arr[] = "documentation";
}
if (count($lexical_resources) > 0) {
  $arr[] = "lexicon";
  $arr[] = "dictionary";
  $arr[] = "vocabulary";
  $arr[] = "wordlist";
  $arr[] = "phrase book";
}
if (count($language_descriptions) > 0) {
  $arr[] = "grammar";
  $arr[] = "syntax";
  $arr[] = "morphology";
  $arr[] = "phonology";
  $arr[] = "orthography";
}
$search_terms = implode(", ", $arr);

?>
<html>
<head>
<title><?=$title?></title>
<meta http-equiv="Content-type" content="text/html;charset=UTF-8" />
<script type="text/javascript" src="/js/gatrack.js"></script>
<link rel="stylesheet" type="text/css" href="/olac.css">
<style>
.online_indicator {
  font-size: 7pt;
  font-weight: bold;
  color: #005500;
  background-color: #DDFFDD;
  border: solid 1pt #AAEEAA;
  margin: 3pt;
  padding: 0pt;
 }
</style>
</head>
<body>

<table class="doc_header">
    <tr>
        <td class="doc_header_logobox">
            <a href="/">
                <img alt="OLAC Logo" src="/images/olac100.gif"/>
            </a>
        </td>
        <td><?=$title?></td>
    </tr>
</table>

<p>ISO 639-3:
<a href="http://www.sil.org/iso639-3/documentation.asp?id=<?=$langcode?>">
<?=$langcode?></a>
</p>

<p>
<?php
if (count($primaty_text) > 0 or
    count($lexical_resources) > 0 or
    count($other_resources1) > 0 or
    count($other_resources2) > 0) {
  echo "The combined catalog of all OLAC participants contains ";
  echo "the following resources that are relevant to this language:";
} else {
  echo "The combined catalog of all OLAC participants does not yet ";
  echo "contain any resources that are relevant to this language.";
}
?>
</p>
<ul>
<?php

if (count($primary_texts) > 0)
  echo '<li><a href="#primary_text">Primary texts</a></li>';

if (count($lexical_resources) > 0)
  echo '<li><a href="#lexical_resources">Lexical resources</a></li>';

if (count($language_descriptions) > 0)
  echo '<li><a href="#language_descriptions">Language descriptions</a></li>';

$n = count($primary_texts) + count($lexical_resources) + count($language_descriptions);

if (count($other_resources1) > 0) {
  echo '<li><a href="#other_resources1">';
  if ($n > 0)
    echo "Other resources about the language</a></li>";
  else
    echo "Resources about the language</a></li>";
}

if (count($other_resources2) > 0) {
  echo '<li><a href="#other_resources2">';
  if ($n > 0)
    echo "Other resources in the language</a></li>";
  else
    echo "Resources in the language</a></li>";
}

?>
</ul>

<?php
function print_list_item($oaiid) {
  global $DB;
  $citation = $GLOBALS['CITE']->cite($oaiid);
  $sql  = "select Content from ARCHIVED_ITEM ai, METADATA_ELEM me ";
  $sql .= "where ai.OaiIdentifier='$oaiid' and ai.Item_ID=me.Item_ID ";
  $sql .= "and me.TagName='title'";
  $tab = $DB->sql($sql);
  $title = $tab[0]['Content'];
  $sql  = "select count(*) c from ARCHIVED_ITEM ai, METADATA_ELEM me ";
  $sql .= "where ai.OaiIdentifier='$oaiid' ";
  $sql .= "and ai.Item_ID=me.Item_ID ";
  $sql .= "and me.TagName='identifier' ";
  $sql .= "and me.Content regexp '^[ \\t\\n]*(http|https|ftp):.*'";
  $tab = $DB->sql($sql);
  $count = $tab[0]['c'];

  echo "<li>";
  if ($count > 0) echo "<span class=\"online_indicator\">ONLINE</span>";
  if ($title) echo "<i>" . $title . ".</i> ";
  echo "$citation <a href=\"/item/$oaiid\">$oaiid</a>";
  echo "</li>";
}

if ($langnames)
  echo "<p>Other known names and dialect names: $langnames</p>";

$link = "http://search.language-archives.org/search.html?q=";
$link .= urlencode($langname);
echo "<p>Use faceted search to <a href=\"$link\">";
echo "explore resources for $langname2</a>.</p>";

if (count($primary_texts) > 0) {
  echo '<a name="primary_text"></a>';
  echo "<h2>Primary texts</h2><ol>";
  foreach ($primary_texts as $oaiid) {
    print_list_item($oaiid);
  }
  echo "</ol>";
}

if (count($lexical_resources) > 0) {
  echo '<a name="lexical_resources"></a>';
  echo "<h2>Lexical resources</h2><ol>";
  foreach ($lexical_resources as $oaiid) {
    print_list_item($oaiid);
  }
  echo "</ol>";
}

if (count($language_descriptions) > 0) {
  echo '<a name="language_descriptions"></a>';
  echo "<h2>Language descriptions</h2><ol>";
  foreach ($language_descriptions as $oaiid) {
    print_list_item($oaiid);
  }
  echo "</ol>";
}

$n = count($primary_texts) + count($lexical_resources) + count($language_descriptions);

if (count($other_resources1) > 0) {
  echo '<a name="other_resources1"></a>';
  if ($n > 0)
    echo "<h2>Other resources about the language</h2>";
  else
    echo "<h2>Resources about the language</h2>";
  echo "<ol>";
  foreach ($other_resources1 as $oaiid) {
    print_list_item($oaiid);
  }
  echo "</ol>";
}

if (count($other_resources2) > 0) {
  echo '<a name="other_resources2"></a>';
  if ($n > 0)
    echo "<h2>Other resources in the language</h2>";
  else
    echo "<h2>Resources in the language</h2>";
  echo "<ol>";
  foreach ($other_resources2 as $oaiid) {
    print_list_item($oaiid);
  }
  echo "</ol>";
}

if ($langnames)
  echo "<p>Other known names and dialect names: $langnames</p>";

if ($search_terms)
  echo "<p>Other search terms: $search_terms</p>";

$CITE->close();
?>

<hr>
<div class="timestamp">
<?=olacvar('baseurl') . $_SERVER['REQUEST_URI']?><br>
Up-to-date as of: <?=date("D M j G:i:s T Y")?>
</div>

</body>
</html>
