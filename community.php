<?php

  // prepare community table from db
  // default community table (error message)
  $community_table = '<table border="3" width="100%" height="50"><td align="center"><font color="red">system error</font></td></table>';

  // olaca_entry (note: olaca is not in OLAC_ARCHIVE) 
  $olaca_entry = '<tr><td><a href="/">OLACA: OLAC-Aggregator</a></td><td class=repo><b><a href="http://oai.dlib.vt.edu/~oai/cgi-bin/Explorer/oai1.1/testoai?archive=http://www.language-archives.org/cgi-bin/olaca.pl">&nbsp;REPOSITORY&nbsp;</a></b></td></tr>';
  $olaca = "OLACA: OLAC-Aggregator";

  $connect = mysql_connect("localhost", "olac", "SECRET");
  if ($connect != FALSE) {
    mysql_select_db("olac");
    $sql = "select RepositoryName, archiveURL, institutionURL, BaseURL
            from OLAC_ARCHIVE order by RepositoryName;";
    $result = @mysql_query($sql);
    $expurl = "http://rocky.dlib.vt.edu/~oai/cgi-bin/Explorer/oai1.1/testoai?archive=";
    if ($result != FALSE) {
      while ($row = mysql_fetch_row($result)) {
        $name = $row[0];
        $home = $row[1];
        if ($home == "") {  // archiveURL is empty
	  $home = $row[2];  // use institutionURL instead
	}
	$repo = $row[3];

	if (strcasecmp($name,$olaca) > 0) {
	  $t .= $olaca_entry;
	  $olaca = "zzzzzzzzzzzzzzzzz"; // very large? 
	}

	if ($home == "") {  // no website, no href
          $t .= "<tr><td>$name</td><td class=repo><b><a href='$expurl$repo'>&nbsp;REPOSITORY&nbsp;</a></b></td></tr>";
        } else {
	  $t .= "<tr><td><a href='$home'>$name</a></td><td class=repo><b><a href='$expurl$repo'>&nbsp;REPOSITORY&nbsp;</a></b></td></tr>";
        }
      }
      $community_table = "<table>$t</table>";
      mysql_free_result($result);
    }
    mysql_close($connect);
  }

?>


<html>
<head>
<title>Open Language Archives Community</title>
<link rel="stylesheet" type="text/css" href="olac.css">
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
<tr>

<!-- navigation block -->
<td align=center valign=top>
<table class=banner><tr>
<td class=sec><a href="index.html">HOME</a></td><td>|</td>
<td class=sec><a href="news.html">NEWS</a></td><td class=div>|</td>
<td class=sec><a href="organization.html">ORGANIZATION</a></td>
</tr><tr>
<td class=sec><a href="docs.html">DOCS</a></td><td>|</td>
<td class=sec><a href="tools.html">TOOLS</a></td><td>|</td>
<td class=sec><font color=ff9090><b>COMMUNITY</b></font></td>
</tr></table>
</td>

<!-- search function -->
<td class=sec align=right valign=top>

<form action="http://www.language-archives.org/tools/search.php"
      method="get">
  <table class=banner>
  <tr><td class=sec><b>QUERY OLAC ARCHIVES:</b>
<br><a href="tools/archive_summary.php">[HARVESTED ARCHIVES]</a>
<br><a href="http://www.linguistlist.org/olac/">[OLAC@LINGUISTLIST]</a>
  </td>
  <td><font size=-1>
  <input type="text" size="16" maxlength="24"  name="query"
	 value=""></font>
  <input type="hidden" name="submit" value="1">
  </td></tr></table>
</form>

</td></tr></table>
</td></tr></table>

</td></tr><tr><td>

<!-- END OF BANNER -->

<br>
<table width=100% cellspacing=20>
<tr><td width=50%>

<h2>Participating Archives</h2>

<p>Access OLAC Archives using the OAI Repository Explorer
(NB this requires JavaScript to be enabled in the web-browser).

<?=$community_table?>

<h2>Participating Services</h2>

<table width=100%>

<tr><td>
  <a href="http://www.linguistlist.org/">The LINGUIST List</a>
</td>
  <td class=repo><b>
  <a href="http://saussure.linguistlist.org/olac/">&nbsp;SEARCH&nbsp;</a>
</td></tr>

<tr><td>
  OLAC Prototype
</td>
  <td class=repo><b>
  <a href="/tools/search.php">&nbsp;SEARCH&nbsp;</a>
</td></tr>

</table>

<h2>Register an Archive or a Service</h2>

<p>
Information about registering a new archive or service
is posted on this site:

<p>
<li> <a href="register/archive.html">Register an archive</a>
<li> <a href="register/service.html">Register a service</a>

<p>
A machine-readable list of registered archives is posted at:
<a href="register/archive_list.php">
http://www.language-archives.org/register/archive_list.php</a>

</td><td width=50%>

<h2>OLAC-General Mailing List</h2>

<p>OLAC-General is a low-volume, moderated list for OLAC announcements
(<a href="http://lists.linguistlist.org/archives/olac-general.html">LIST ARCHIVES</a>).

<p>
<form action="http://lists.linguistlist.org/cgi-bin/wa">
<font size=-1>
<input type=hidden name=SUBED2 value="OLAC-GENERAL">
<input type=hidden name="A" value=1>
NAME:<br>
<input name=p size=30 maxlength=60 value=""><input type=hidden name=q value=""><br>
EMAIL:<br>
<input name=s size=30 maxlength=60 value=""><input type=hidden name=t value=""><br>
<input type=hidden name=0 value="">
<input type=hidden name=1 value="">
<input type=hidden name=2 value="">
<input type=submit name="b" value="Subscribe to OLAC-General">
</font>
</form>

<h2>OLAC-Implementers Mailing List</h2>

<p>OLAC-Implementers is an unmoderated list for technical discussions
concerning the implementation of OLAC repositories
(<a href="http://lists.linguistlist.org/archives/olac-implementers.html">LIST ARCHIVES</a>).

<p>
<form action="http://lists.linguistlist.org/cgi-bin/wa">
<font size=-1>
<input type=hidden name=SUBED2 value="OLAC-IMPLEMENTERS">
<input type=hidden name="A" value=1>
NAME:<br>
<input name=p size=30 maxlength=60 value=""><input type=hidden name=q value=""><br>
EMAIL:<br>
<input name=s size=30 maxlength=60 value=""><input type=hidden name=t value=""><br>
<input type=hidden name=0 value="">
<input type=hidden name=1 value="">
<input type=hidden name=2 value="">
<input type=submit name="b" value="Subscribe to OLAC-Implementers">
</font>
</form>

<h2>The Open Archives Initiative</h2>

<p>
OLAC is part of a larger community known as the
<a href="http://www.openarchives.org/">Open Archives Initiative</a>.
The OAI develops and promotes interoperability standards
for digital archives, and currently spans dozens of archives and
a total of over a million records.  The OAI community page lists
OAI mailing lists, archives, and websites.

<p>
<li><a href="http://www.openarchives.org/community/">The OAI Community</a>



<!-- FOOTER -->
</td></tr></table>

<!-- end of frame around page -->
</td></tr></table></td></tr></table>

</body>
</html>

