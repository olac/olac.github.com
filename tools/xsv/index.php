<?php $MTIME =  date("Y-m-d\TH:i:s\Z",getlastmod()-date(Z));

# synopsis: Web interface to schema validators. Uses cgi scrips which
#   in turn use local shell scripts interfacing XSV and Xerces-J.
#
# changelog:
#   2008-07-08  upgraded to xerces-j 2.9.1  -HL
#   2004-11-17  XSV (2.6-2->2.8-1) version up
#               Xerces-J (2.5.0->2.6.2) version up  -HL
#   2004-02-17  XSV (2.5-2->2.6-2) version ups  -HL
#   2003-09-18  Xerces-J (2.3->2.5) and XSV (2.3-1->2.5-2) version ups  -HL
#   2003-03-10  XSV version up (2.2 --> 2.3-1)  - HL
#   2003-02-17  Xerers-J has been added in the validator list. HL
#   2003-02-12  created by HL
#

require_once('olac.php');

function draw_form()
{
  global $MTIME;

  print <<<END
<html>
<head>
<title>OLAC tools: XML Schema Validator</title>
<script type="text/javascript" src="/js/gatrack.js"></script>
</head>
<body>

<h1>XML Schema Validator</h1>

<form action="index.php" method="get">
URL of the file to validate:<br>
<p><input type="text" name="url" value="http://" size="80"/></p>
<p>
<input type="radio" name="validator" value="xsv" checked> XSV (hosted at <a href="http://www.w3.org/2001/03/webdata/xsv">http://www.w3.org/2001/03/webdata/xsv</a>)<br>
<input type="radio" name="validator" value="xercesj"> Xerces-J 2.9<br>
<input type="radio" name="validator" value="strons"> Schematron 1.5
[<a href="/register/scripts/olac-static-repository.xml">static repository schema</a>]<br>
<input type="radio" name="validator" value="strond"> Schematron 1.5
[<a href="/register/scripts/olac-dynamic-repository.xml">dynamic repository schema</a>]
</p>
<input type="submit" value="Go!"/>
</form>

<hr>
<p>Last updated $MTIME</p>
</body>
</html>
END;

  exit;
}

function validate_and_print($url)
{
  switch ($_GET[validator]) {
    case "xsv":
      header("Location: http://www.w3.org/2001/03/webdata/xsv?docAddrs=$url&style=xsl#");
      break;
    case "xercesj":
      header("Location: ".olacvar('baseurl')."/cgi-bin/xercesj.cgi?url=$url");
      break;
    case "strons":
      header("Location: ".olacvar('baseurl')."/cgi-bin/schematron.cgi?url=$url&type=static");
      break;
    case "strond":
      header("Location: ".olacvar('baseurl')."/cgi-bin/schematron.cgi?url=$url&type=dynamic");
      break;
  }
  exit;
}

if ($_GET[url]) {
  validate_and_print($_GET[url]);
} else {
  draw_form();
}

?>
