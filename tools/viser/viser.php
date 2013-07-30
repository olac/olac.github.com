<?
# VISER: The Virtual Service Provider
# Gary Simons and Steven Bird
# 28 October 2002
#
# Changes:
# 2006-02-14: Use stream_get_contents() instead of fread()
# 2003-07-29: Updated to call olaca3.pl

# URL: http://www.language-archives.org/tools/viser/viser.php

# Parameters in the calling URL
#    elements -- Number of metadata elements in query
#    sql      -- URL encoded WHERE-clause expression
#    count    -- (Opt) Number of records per batch (default 20)
#    title    -- (Opt) Title for the resulting HTML page
#    xsl      -- (Opt) URL of stylesheet
#    resumptionToken -- Exclusive of first three params
#    start    -- Number of first item in a resumption page

  require_once('olac.php');

# If there is no query, display the documentation

  $sql = $_GET['sql'];
  $resumptionToken = $_GET['resumptionToken'];
  $elements = $_GET['elements'];
  $count = $_GET['count'];
  $title = $_GET['title'];
  $xsl = $_GET['xsl'];

  if (!$sql and !$resumptionToken)
     {
     $documentation = olacvar('baseurl') . "/NOTE/viser.html";
     header("Content-Type: text/html");
     readfile($documentation);
     exit;
     }


# Construct the URL to fetch the query results from OLACA

  $OLACA = olacvar('aggregator/url') . "?verb=Query";
  $sql = ereg_replace("\\\'", "'", $sql);
  $sql = ereg_replace("%", "%25", $sql);
  $sql = ereg_replace("=", "%3D", $sql);
  $sql = ereg_replace(" ", "%20", $sql);

  if ($resumptionToken != '') {
     $query = "$OLACA&resumptionToken=$resumptionToken"; }
  else {
     $query = "$OLACA&elements=$elements&sql=$sql";
     if ($count) {
        $query = "$query&count=$count"; }
     $start = 1; }

# Run query

  $fd = fopen($query, 'r');
  $xmlDecl = fgets($fd);
  #$data = fread($fd, 30000000);
  $data = stream_get_contents($fd, 500000);
  if (!feof($fd)) {
     $xmlDecl = "<html>\n";
     $data = "<p>Response exceeds size limit of 500,000 bytes. Use a smaller value of <i>count.</i></p></html>";
     }
  fclose($fd);

# Use the default stylesheet if no argument was given

  if (!$xsl) {
     $xsl = olacvar('baseurl') . "/tools/viser/basic_service.xsl"; }

# Return the XML page

  header("Content-Type: text/xml");
  print $xmlDecl;
  print "<?xml-stylesheet type=\"text/xsl\" href=\"$xsl\"?>\n"; 
  print "<?title $title?>\n";
  print "<?start $start?>\n";
  print $data;

?>

