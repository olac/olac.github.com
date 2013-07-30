<?php
######################################################################
#
# Synopsis: Search metadata elements of OLAC 1.0 database for the given string.
#
# ChangeLog:
#
# 2004-11-10  Haejoong Lee  <haejoong@ldc.upenn.edu>
#	* set character encoding to UTF-8
#
# 2003-03-01  Haejoong Lee  <haejoong@ldc.upenn.edu>
#	* revised previous version (/tools/search.php4) to use new database
#	* use /tools/lookup/lookup-OLAC1.0.php4 for record lookup
#
######################################################################

require_once("../lib/php/OLACDB.php");
$DB = new OLACDB("olac2");
?>

<HTML>
<HEAD>
<TITLE>Search OLAC Archives</TITLE>
<script type="text/javascript" src="/js/gatrack.js"></script>
<LINK REL="stylesheet" TYPE="text/css" HREF="/olac.css">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</HEAD>

<BODY>
<HR>
<TABLE CELLPADDING="10">
<TR>
<TD> <A HREF="http://www.language-archives.org/"><IMG
SRC="http://www.language-archives.org/images/olac100.gif"
BORDER="0"></A></TD>
<TD> <H1><FONT COLOR="0x00004a">Search OLAC Archives
</FONT>
</H1>
</TD>
</TR>
</TABLE>
<HR>

<p>This page provides single keyword search over
<a href="/archives.php4">harvested archives</a>.
For full-featured search, please see the
<a href="http://saussure.linguistlist.org/olac/">
LINGUIST List search interface</a>.

<form method="get">
<b>Simple Query:</b>
<input type="text" size="20" maxlength="20" name="query" value="<? print $query; ?>">
<input type="submit">
</form>

<?
if ($_GET[query]) {
  $_GET[query] = sprintf("%%%s%%", $_GET[query]);
  $tab = $DB->sql("
    select distinct
           ar.Archive_ID, ar.Item_ID, ar.OaiIdentifier,
           me.TagName,    me.Content
    from   ARCHIVED_ITEM ar, METADATA_ELEM me
    where  me.Content LIKE '$_GET[query]'
    and    ar.Item_ID = me.Item_ID
    ORDER BY ar.OaiIdentifier");
  $DB->saw_error() and die("Query failed");

  $count = 1;
  $archive_ids = array();
  $record_ids = array();
  $output = "";

  if ($tab) foreach ($tab as $row) {
    $output .= <<<END
<p>$count.
<a href="$_SERVER[PHP_SELF]?item=$row[OaiIdentifier]">$row[OaiIdentifier]</a>:
$row[TagName]: <i>$row[Content]</i>
END;

    $archive_ids[$row[Archive_ID]] = 1;
    $record_ids[$row[Item_ID]] = 1;
    $count++;
  }

  $archives = sizeof($archive_ids);
  $records = sizeof($record_ids);
  print "<hr><h2>$records record(s) from $archives archive(s)</h2>";
  print $output;
}

if ($_GET[item]) {
  $result = join(file("http://www.language-archives.org/tools/lookup/index.php?identifier=$_GET[item]"),'');
  $search = array('/<\/?(BODY|HTML)>/i',
                  '/<HEAD>.*<\/HEAD>/si');
  $replace = array('','');
  print "<hr>". preg_replace($search, $replace, $result);
}

?>

</BODY>
</HTML>
