<?php
#
# Synopsis: Display a summary of OLAC database.
#

require_once("lib/php/OLACDB.php");
$DB = new OLACDB();

############################################################
#
# returns a table of the following form:
#
#    | archive | institution | location | detail
# ---+---------+-------------+----------+--------
#  0 |         |             |          |
#  1 |         |             |          |
# ...|         |             |          |
#
############################################################
function get_archive_count()
{
  global $DB;

  $tab = $DB->sql("select count(Archive_ID) as size
                   from   OLAC_ARCHIVE");
  if ($DB->saw_error()) {
    echo "<p>" . $DB->get_error_message() . "</p>\n";
    echo "<p>" . $DB->get_error_sql() . "</p>\n";
  }
  return $tab[0][size];
}

function table_sort($row1, $row2)
{
  $name1 = $row1['RepositoryName'];
  $name2 = $row2['RepositoryName'];
  $pat = '/^[Tt]he\s+|[Aa]n?\s+/';
  $name1 = preg_replace($pat,'', $name1);
  $name2 = preg_replace($pat,'', $name2);
  return strcasecmp($name1, $name2);
}

function get_archive_table()
{
  global $DB;

  $tab = $DB->sql("select oa.Archive_ID,
			  oa.RepositoryName,
			  oa.RepositoryIdentifier,
			  ai.Item_ID Sample,
			  oa.ArchiveURL,
                          oa.Institution,
			  oa.InstitutionURL,
			  oa.ShortLocation,
                          oa.BaseURL,
                          oa.LastHarvested
                   from   OLAC_ARCHIVE oa
			  left join ARCHIVED_ITEM ai on
                            oa.SampleIdentifier=ai.OaiIdentifier
		   order by RepositoryName");

  if ($DB->saw_error()) {
    echo "<p>" . $DB->get_error_message() . "</p>\n";
    echo "<p>" . $DB->get_error_sql() . "</p>\n";
  }

  usort($tab, "table_sort");
  if ($tab) foreach ($tab as $row) {
    $x[archive] = $row[RepositoryName];
    $x[institution] = $row[Institution];
    $x[location] = $row[ShortLocation];
    $x[detail] = "<a class=arch href=\"/archive/$row[RepositoryIdentifier]\">MORE<br>DETAILS</a>";
    #$x[report] = "<a class=arch href=\"tools/reports/archiveReportCard.php?archive=$row[Archive_ID]\">REPORT<br>CARD</a>";
    $x[report] = "<a class=arch href=\"/metrics/$row[RepositoryIdentifier]\">METRICS</a>";

    $s = preg_replace('@^http://@', '', $row[BaseURL]);
    #$s = preg_replace('@/@', '_', $s) . ".html";

    #if (preg_match('<td>', @join('',file('/ldc/web/language-archives/tools/eth15filter/'.$s))))
    #    $x[iso639] = "<a class=arch href=\"tools/eth15filter/$s\"><font color=red><b>ISO639<br>REPORT</b></font></a>";
    #else
    #    $x[iso639] = "ISO639<br>REPORT";

    if ($row["Sample"])
        $x["sample"] = "<a class=arch href=\"/sample/$row[RepositoryIdentifier]\">SAMPLE RECORD</a>";
    else
        $x["sample"] = "<span class=arch>N/A</span>";
    $x["last_harvested"] = $row[LastHarvested];
    $result[] = $x;
  }
  return $result;
}

?>
<html>
<head>
<title>Open Language Archives Community</title>
<link rel="stylesheet" type="text/css" href="olac.css">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="description" content="Worldwide network of language archives">
 
<meta name="keywords" content="language resource; language resources;
language archive; linguistic archive; open archives; metadata; dublin core; controlled
vocabulary; linguistics; linguistic data; language data">
<style>
.row1 {background-color: white;}
.row2 {background-color: #DDDDEE;}
.name {font-weight: bold;}
.loc {padding-left: 2ex;}
</style>
<script type="text/javascript" src="/js/gatrack.js"></script>
</head>
<body>

<!-- get a frame around entire page -->
<table border=0 cellspacing=1 cellpadding=0 width="100%"><tr><td class=frame>
<table border=0 cellspacing=1 cellpadding=0 width="100%"><tr><td>

<table border=0 cellpadding=5 cellspacing=0 width=100% class=banner><tr>

<!-- main olac icon -->
<td align=left><img src="images/olac100.gif"></td>

<!-- rest of banner -->
<td><table class=banner cellspacing=0 width=100%>

<!-- title: spans banner -->
<tr><td class=title colspan=2>
<b><big><big>OLAC: Open Language Archives Community</big></big></b>
</td></tr>

<!-- underneath: the navigation block and search function -->
<tr><td></td></tr><tr>

<!-- navigation block -->
<td align=center>
<table class=banner><tr>
<td class=sec><a href="index.html">HOME</a></td><td>|</td>
<td class=sec><a href="documents.html">DOCUMENTS</a></td><td>|</td>
<td class=sec><a href="about.html">ABOUT</a></td><td>|</td>
<td class=sec><font color=#ff9090><b>ARCHIVES</b></font></td>
</tr><tr>
<td class=sec><a href="news.html">NEWS</a></td><td>|</td>
<td class=sec><a href="organization.html">ORGANIZATION</a></td><td>|</td>
<td class=sec><a href="tools.html">TOOLS</a></td><td>|</td>
<td class=sec><a href="services.php">SERVICES</a></td>
</tr></table>
</td>

<!-- search function -->
<td class=sec align=right valign=top>

<form method="get" action="http://www.google.com/search">
  <table class=banner>
  <tr><td class=sec>SEARCH THIS SITE:<br>
  <font size=-1>
  <input type="text" size="20" maxlength="40"  name="q" value=""></font>
  <input type="hidden" name="sitesearch" value="www.language-archives.org">
  </td></tr></table>
</form>

</td></tr></table>
</td></tr></table>

</td></tr><tr><td>

<!-- END OF BANNER -->

<br>
<table width=100% cellspacing=20>
<tr><td width=100%>

<h2>Participating Archives</h2>

<p>OLAC has
<?php
$count = get_archive_count();
echo $count." ";
?>
participating archives.  This page provides a summary;
for full details on any archive follow the link on the right.

<p>
<li> <a href="/register/register.php">Register an archive</a>
<li> <a href="/register/archive_list.php">Machine readable list of registered archives</a>
<li> <a href="/metrics/">Metrics report on all archives</a>
<li> <a href="/metrics/compare">Comparative archive metrics</a>

<p>
<hr>

<p>
<table cellspacing=0>
<?php
$tab = get_archive_table();
if ($tab) {
    $rowclass = "row1";
    foreach ($tab as $row) {
      echo "<tr>";
      echo "<td class=$rowclass><div class=name>$row[archive]</div>";
      echo "<div class=loc>";
      if ($row[institution]) {
        echo "$row[institution], ";
      }
      echo "$row[location]</div></td>";
      echo "<td class=\"arch $rowclass\">$row[detail]</td>";
      echo "<td class=$rowclass>&nbsp;</td>";
      echo "<td class=\"arch $rowclass\">$row[report]</td>";
      echo "<td class=$rowclass>&nbsp;</td>";
      echo "<td class=\"arch $rowclass\">$row[sample]</td>";
      echo "<td class=\"arch $rowclass\" align=center>";
      if (strtotime("now") - strtotime($row["last_harvested"]) > 259200) {
          echo "<img src=\"/images/16-em-cross.png\"/><br>";
          echo "<span style=\"font-size:7pt\">$row[last_harvested]</span>";
      }
      else
          echo "<img src=\"/images/16-em-check.png\"/>";
      echo "</td>";
      echo "</tr><tr><td></td></tr>";

      if ($rowclass == "row1")
          $rowclass = "row2";
      else
          $rowclass = "row1";
    }
}
?>
</table>

<!-- FOOTER -->
</td></tr></table>

<!-- end of frame around page -->
</td></tr></table></td></tr></table>

<div class=timestamp>
<?=olacvar('baseurl') . $_SERVER['REQUEST_URI']?><br>
Up-to-date as of: <?=date("D M j G:i:s T Y")?>
</div>

</body>
</html>
