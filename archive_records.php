<?php
######################################################################
#
# Synopsis: List all records of the specified archive
#
# ChangeLog:
#
# 2003-09-03 Steven Bird
#
######################################################################

require_once("lib/php/OLACDB.php");
$DB = new OLACDB();
?>
<HTML>
<HEAD>
<TITLE>List Records from an OLAC Archive</TITLE>
<LINK REL="stylesheet" TYPE="text/css" HREF="/olac.css">
<script type="text/javascript" src="/js/gatrack.js"></script>
</HEAD>

<BODY>
<HR>
<TABLE CELLPADDING="10">
<TR>
<TD> <A HREF="/"><IMG
SRC="/images/olac100.gif"
BORDER="0"></A></TD>
<TD> <H1><FONT COLOR="0x00004a">List Records from an OLAC Archive
</FONT>
</H1>
</TD>
</TR>
</TABLE>
<HR>

<p>This page permits the records of a particular
<a href="/archives.php">harvested archive</a>
to be displayed.  It may be slow for large archives.
To find particular records in OLAC archives, please use
the <a href="/search/">OLAC search interface</a>.

<?
function table_sort($row1, $row2)
{
  $name1 = $row1['RepositoryName'];
  $name2 = $row2['RepositoryName'];
  $pat = '/^[Tt]he\s+|[Aa]n?\s+/';
  $name1 = preg_replace($pat,'', $name1);
  $name2 = preg_replace($pat,'', $name2);
  return strcasecmp($name1, $name2);
}

function archives($archiveid)
{
    global $DB;

    $tab = $DB->sql("select RepositoryName, RepositoryIdentifier from OLAC_ARCHIVE");
    if (! $tab) {
      print "No repositories!";
      exit;
    }
    usort($tab, "table_sort");

    $output = "<select name='archive'>";
    foreach ($tab as $archive) {
      $id = $archive[RepositoryIdentifier];
      $name = $archive[RepositoryName];
      if ($id == $archiveid) {
        $output .= "<option value=\"$id\" selected=\"selected\">$name</option>";
      } else {
        $output .= "<option value=\"$id\">$name</option>";
      }
    }
    $output .= "</select>";
    return $output;
}

$archiveid = $_GET[archive];
if (!$archiveid) {
	$arr = explode('?', $_SERVER["REQUEST_URI"]);
	$archiveid = basename($arr[0]);
}
?>

<script>
function select_new_archive() {
    var i = document.form1.archive.selectedIndex;
    var repoid = document.form1.archive.options[i].value;
    window.location = "/archive_records/" + repoid;
}
</script>

<form name="form1">
<b>Archive:</b>
<? print archives($archiveid); ?>
<input type="button" value="Submit" onClick="select_new_archive();">
</form>

<?php
if ($archiveid) {
  $tab = $DB->sql("
    select Item_ID, OaiIdentifier
    from   ARCHIVED_ITEM, OLAC_ARCHIVE
    where  ARCHIVED_ITEM.Archive_ID = OLAC_ARCHIVE.Archive_ID
    and    OLAC_ARCHIVE.RepositoryIdentifier = '$archiveid'
    ORDER BY OaiIdentifier");
  $DB->saw_error() and die("Query failed");

  $count = 1;
  $archive_ids = array();
  $record_ids = array();
  $output = "";

  if ($tab) foreach ($tab as $row) {
    $output .= <<<END
<p>$count.
<a href="/item/$row[OaiIdentifier]">$row[OaiIdentifier]</a>
END;

    $record_ids[$row[Item_ID]] = 1;
    $count++;
  }

  $records = sizeof($record_ids);
  print "<hr><h2>$records record(s) from $archiveid</h2>";
  print $output;
}

?>

<br><hr>
<div class=timestamp>
<?=olacvar('baseurl') . $_SERVER['REQUEST_URI']?><br>
Up-to-date as of: <?=date("D M j G:i:s T Y")?>
</div>

</BODY>
</HTML>
