<?php

require_once("lib/php/OLACDB.php");
$DB = new OLACDB();

############################################################
#
# returns a table of the following form:
#
#    | service | institution | contact | description
# ---+---------+-------------+---------+-------------
#  0 |         |             |         |
#  1 |         |             |         |
# ...|         |             |         |
#
############################################################
function get_service_table()
{
  global $DB;

  $tab = $DB->sql("select * from SERVICES
  		   where dateApproved is not NULL
                   order by serviceName");

  if ($DB->saw_error()) {
    echo "<p>" . $DB->get_error_message() . "</p>\n";
    echo "<p>" . $DB->get_error_sql() . "</p>\n";
  }
  if ($tab) foreach ($tab as $row) {
    $x[service] = "<a href=\"$row[serviceURL]\">$row[serviceName]</a>";
    $i = $row[institution];
    $iu = $row[institutionURL];
    if ($i) {
      if ($iu) $x[institution] = "<a href=\"$iu\">$i</a>";
      else     $x[institution] = $i;
    } else if ($iu) {
               $x[institution] = "<a href=\"$iu\">$iu</a>";
    } else     $x[institution] = "";

    $cp = $row[contactPerson];
    $ce = str_replace('', 'mailto:', $row[contactEmail]);
    if ($cp) {
      if ($ce) $x[contact] = "<a href=\"mailto:$ce\">$cp</a>";
      else     $x[contact] = $cp;
    } else if ($ce) {
               $x[contact] = "<a href=\"mailto:$ce\">$ce</a>";
    } else     $x[contact] = "";

    if ($row[description])
      $x[description] = $row[description];
    else
      $x[desctiption] = $row[description];
    $result[] = $x;
  }
  return $result;
}

function get_service_count()
{
  global $DB;

  $tab = $DB->sql("select count(Service_ID) as size
                   from   SERVICES
                   where  dateApproved is not NULL");
  if ($DB->saw_error()) {
    echo "<p>" . $DB->get_error_message() . "</p>\n";
    echo "<p>" . $DB->get_error_sql() . "</p>\n";
  }
  return $tab[0][size];
}
?>
<html>
<head>
<title>Open Language Archives Community</title>
<link rel="stylesheet" type="text/css" href="olac.css">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="description" content="Worldwide network of language archives">
 
<meta name="keywords" content="language resource; language resources;
language archive; linguistic archive; open archives; metadata; dublin core; controlled
vocabulary; linguistics; linguistic data; language data">
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
<td class=sec><a href="archives">ARCHIVES</a></td>
</tr><tr>
<td class=sec><a href="news.html">NEWS</a></td><td>|</td>
<td class=sec><a href="organization.html">ORGANIZATION</a></td><td>|</td>
<td class=sec><a href="tools.html">TOOLS</a></td><td>|</td>
<td class=sec><font color=#ff9090><b>SERVICES</b></font></td>
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


<h2>Registered Services</h2>

<p>The following services permit end-users to access OLAC metadata.
Information about registering a new
service is posted on this site:

<p>
<li> <a href="register/service.html">Register a service</a>

<p>

<?php
$tab = get_service_table();
?>

<?php if ($tab): ?>
	<p>
	<table>
	<tr><th>Service</th>
	    <th>Institution</th>
	    <th>Contact</th>
	    <th>Description</th></tr>

	<?php
	foreach ($tab as $row) {
	  echo "<tr>";
	  echo "<td>$row[service]</td>";
	  echo "<td>$row[institution]</td>";
	  echo "<td>$row[contact]</td>";
	  echo "<td>$row[description]</td>";
	  ECHO "</tr>";
	}
	?>
	</table>
<?php endif; ?>

<hr>

<h2>OLAC Infrastructure Services</h2>

<p>The following services support the operation of OLAC.

<table>
<tr valign=top>
<tr><td><a href="/register/register.php">Archive Registration</a></td>
<td>A service for validating and registering OLAC archives.</td></tr>

<tr><td><a href="/register/service.html">Service Registration</a></td>
<td>A service for registering end-user services based on OLAC metadata.</td></tr>

<tr><td><a href="/tools/metadata/freestanding.html">Freestanding Metadata</a></td>
<td>A service for validating and formatting an OLAC metadata record.</td></tr>

<td><a href="/cgi-bin/olaca3.pl?verb=Document">OLAC Aggregator</a></td>
<td>A service that provides a repository containing the records from all
other registered OLAC repositories, incorporating an OLAC-DC crosswalk
and a query function.</td></tr>

<tr><td><a href="/viser">Viser</a></td>
<td>A virtual service which allows language resource sites to post
services based on OLAC metadata without having to harvest metadata or
develop a user interface.
</td></tr>

</table>

<hr>

<h2>OLAC Utility Services</h2>

<p>The following services support the developers of OLAC archives,
and are provided on the <a href="tools.html">OLAC Tools</a> page.

<ul>
<li>Plain text search on all harvested metadata.
<li>Resolve an identifier to a record.
<li>Survey of metadata element usage.
</ul>

<!-- FOOTER -->
</td></tr></table>

<!-- end of frame around page -->
</td></tr></table></td></tr></table>

<div class=timestamp>
http://www.language-archives.org/services.php<br>
Up-to-date as of: <? echo date("D M j G:i:s T Y"); ?>
</div>

</body>
</html>

