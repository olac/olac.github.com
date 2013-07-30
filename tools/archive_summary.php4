<HTML>
<HEAD>
<TITLE>Harvested OLAC Archives</TITLE>
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
<TD> <H1><FONT COLOR="0x00004a">Harvested OLAC Archives
</FONT>
</H1>
</TD>
</TR>
</TABLE>
<HR>

<p>This page gives statistics for
<a href="/register/archive_list.php4">
registered OLAC archives</a>
that have been harvested on the OLAC site.
This service is provided for archive developers.
Others are encouraged to consult the
<a href="http://saussure.linguistlist.org/olac/">LINGUIST List Search Engine</a>
for full-featured cross-archive searching.

<?
### GLOBALS

$size = array();
$version = array();
$r1 = "<tr><td align=right>";
$r2 = "</td><td><font color=blue><i>";
$r3 = "</i></font></td></tr>";

### QUERIES

function connect() {
  $connect = mysql_connect("", "olac", "SECRET")
    or die ("Could not connect");
  mysql_select_db ("olac")
    or die ("Could not select database");
  return $connect;
}

function get_num_archives() {
  $sql = "select count(*) from OLAC_ARCHIVE";
  $result = mysql_query($sql) or die("Query failed");
  $answer = mysql_fetch_row($result);
  return $answer[0];
}

function get_summary() {
  $sql = "select OLAC_ARCHIVE.Archive_ID,
               OLAC_ARCHIVE.RepositoryName,
               count(ARCHIVED_ITEM.Item_ID)
        from OLAC_ARCHIVE natural join ARCHIVED_ITEM
        group by OLAC_ARCHIVE.Archive_ID
        order by OLAC_ARCHIVE.RepositoryName";
  $result = mysql_query($sql) or die("Query failed");
  return $result;
}

function get_details() {
  $sql = "select * from OLAC_ARCHIVE order by RepositoryName";
  $result = mysql_query($sql) or die("Query failed");
  return $result;
}

function get_olac_version() {
  $sql = "select distinct Archive_ID, SchemaName
        from ARCHIVED_ITEM natural join SCHEMA_VERSION";
  $result = mysql_query($sql) or die("Query failed");
  return $result;
}

### DISPLAY

function put_line($tag, $contents) {
  global $r1, $r2, $r3;
  print "$r1 $tag $r2 $contents $r3";
}

function put_details($count, $a, $s, $version) {
  # anchor for the hyperlink
  printf("\n<a name=\"%s\"></a>\n", $a['Archive_ID']);

  # section heading
  printf("<h3>%d. %s</h3>\n", $count, $a['RepositoryName']);
  print "<table>\n";
  put_line("Size:", "$s record(s)");
  put_line("Institution:",
    sprintf("<a href=\"%s\">%s</a>\n", $a['institutionURL'], $a['institution']));
  put_line("Archive URL:",
    sprintf("<a href=\"%s\">%s</a>\n", $a['archiveURL'], $a['archiveURL']));
  put_line("Curator:",
    sprintf("<a href=\"%s\">%s</a> (%s)\n",
      $a['curatorEmail'], $a['curator'], $a['curatorTitle']));
  put_line("Location:", $a['location']);
  put_line("Synopsis:", $a['synopsis']);
  put_line("Access:", $a['access']);
  put_line("Admin:",
    sprintf("<a href=\"mailto:%s\">%s</a>", $a['adminEmail'], $a['adminEmail']));
  put_line("BaseURL:", $a['BaseURL']);
  put_line("Repo ID:", $a['RepositoryIdentifier']);
  put_line("OaiVersion:", $a['OaiVersion']);
  put_line("OLAC MS Version(s):", $version[$a['Archive_ID']]);
  put_line("Explore:",
    sprintf("<a href=\"http://oai.dlib.vt.edu/~oai/cgi-bin/Explorer/oai1.1/testoai?archive=%s\">
    Visit archive with the Repository Explorer 1.1</a>",
    $a['BaseURL']));
  print "</table>";
}

### MAIN PROGRAM

$connect = connect();

# Heading

$num_archives = get_num_archives();
print "<h2>$num_archives Harvested Archives</h2>";

# Index

print "<hr><table><tr><th>Size</th><th>Archive</th></tr>\n";
$result = get_summary();
while ($a = mysql_fetch_array($result, MYSQL_NUM)) {
  print "$r1 $a[2] $r2 <a href=\"#$a[0]\">$a[1]</a> $r3\n";
  $size[$a[0]] = $a[2];
}
print "</table><hr>\n";

# Details for each archive

$result = get_olac_version();
while ($a = mysql_fetch_array($result, MYSQL_NUM)) {
  $version[$a[0]] = sprintf("%s%s; ", $version[$a[0]], $a[1]);
}  

$result = get_details();
$count = 1;
while ($a = mysql_fetch_array($result, MYSQL_ASSOC)) {
  $s = $size[$a[0]];
  put_details($count, $a, $s, $version);
  $count++;
}
mysql_close($connect);

?>

</BODY>
</HTML>
