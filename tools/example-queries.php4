<HTML>
<HEAD>
<TITLE>Search OLAC Archives</TITLE>
<script type="text/javascript" src="/js/gatrack.js"></script>
<LINK REL="stylesheet" TYPE="text/css" HREF="../olac.css">
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

<form method="get">
<input type="submit">
</form>

<?
$script = "http://www.language-archives.org/tools/search.php4";
$re = "http://oai.dlib.vt.edu/~oai/cgi-bin/Explorer/oai1.1/testoai";

function connect() {
  $connect = mysql_connect("", "olac", "SECRET") or die ("Could not connect");
  mysql_select_db ("olac") or die ("Could not select database");
  return $connect;
}

function get_one($sql) {
  $result = mysql_query($sql) or die("Query failed");
  $answer = mysql_fetch_row($result);
  return $answer[0];
}

if ($submit) {
  $connect = connect();
  $sql = "select B.Content
          from METADATA_ELEM AS A, METADATA_ELEM AS B
          where A.TagName = 'type' and A.Code = 'Sound'
          and B.TagName = 'subject.language'
          and A.Item_ID = B.Item_ID";
  $sql = "select B.Content
          from METADATA_ELEM AS A, METADATA_ELEM AS B
          where A.TagName = 'type' and A.Code = 'Sound'";
  $result = mysql_query($sql) or die("Query failed");
  $answer = mysql_fetch_row($result);
  $count = 1;
  while ($answer) {
    print "<p>$count. <a href=\"$script?item=$answer[1]\">$answer[0]</a>: ";
    print "$answer[2]: <i>$answer[3]</i>";
    $count++;
    $answer = mysql_fetch_row($result);
  }
  mysql_close($connect);
}

if ($item) {
  $connect = connect();

  $sql = "select OLAC_ARCHIVE.RepositoryName,
                 OLAC_ARCHIVE.BaseURL,
                 ARCHIVED_ITEM.OaiIdentifier
          from OLAC_ARCHIVE,ARCHIVED_ITEM
          where ARCHIVED_ITEM.Item_ID = '$item'
          and OLAC_ARCHIVE.Archive_ID = ARCHIVED_ITEM.Archive_ID";
  $result = mysql_query($sql) or die("Query failed");
  $answer = mysql_fetch_row($result);
  print "<hr><h2>$answer[0]: $answer[2]</h2>";
  print "<p><a href=\"$answer[1]?verb=GetRecord&identifier=$answer[2]&metadataPrefix=olac\">
    $answer[1]?verb=GetRecord&identifier=$answer[2]&metadataPrefix=olac</a>";

  $sql = "select TagName, Content
          from METADATA_ELEM
          where METADATA_ELEM.Item_ID = '$item'";
  $result = mysql_query($sql) or die("Query failed");
  $answer = mysql_fetch_row($result);
  print "<table>\n";
  while ($answer) {
    if ($answer[1] != '') {
      $value = $answer[1];
      $value = ereg_replace("(http:[^ ]+)", "<a href=\"\\1\">\\1</a>", $value);
      print "<tr><td align=right><b>$answer[0]</b></td><td>$value</td></tr>\n";
    }
    $answer = mysql_fetch_row($result);
  }
  print "</table>\n";
  mysql_close($connect);
}

?>

</BODY>
</HTML>
