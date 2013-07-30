<?php
# freestanding_validate.php
#
# Validate a free-standing OLAC metadata document
# Gary Simons and Haejoong Lee
# 5 July 2003
#
# See: http://www.language-archives.org/tools/metadata/freestanding.html
#
# Changes:
# 2008-07-30: updates for OLAC 1.1. Rewrote namespace processing part -HL
# 2006-02-14: paths to the external tools have been updated -HL
#
#

require_once("olac.php");
$BASEURL = olacvar('baseurl');

# Parameters in the calling POST request
#    metadata -- the submitted free-standing metadata

# If there is no metadata, go back to main page

  if (!$HTTP_POST_VARS['metadata'])
  {
    $mainpage = "$BASEURL/tools/metadata/freestanding.html";
    header("Content-Type: text/html");
    readfile($mainpage);
    exit;
  }

# PHP changed " to \"; change it back

  $metadata = ereg_replace('\\\"', '"', $HTTP_POST_VARS['metadata']);

# $nssymbols : table of (tag name, namespace symbol)
# This table tells to which namespace a tag belongs.
$nssymbols = array(
  "accrualMethod" => "dc",
  "accrualPeriodicity" => "dc",
  "accrualPolicy" => "dc",
  "audience" => "dc",
  "contributor" => "dc",
  "coverage" => "dc",
  "creator" => "dc",
  "date" => "dc",
  "description" => "dc",
  "format" => "dc",
  "identifier" => "dc",
  "instructionalMethod" => "dc",
  "language" => "dc",
  "provenance" => "dc",
  "publisher" => "dc",
  "relation" => "dc",
  "rights" => "dc",
  "rightsHolder" => "dc",
  "source" => "dc",
  "subject" => "dc",
  "title" => "dc",
  "type" => "dc",
  "abstract" => "dcterms",
  "accessRights" => "dcterms",
  "alternative" => "dcterms",
  "available" => "dcterms",
  "bibliographicCitation" => "dcterms",
  "conformsTo" => "dcterms",
  "created" => "dcterms",
  "dateAccepted" => "dcterms",
  "dateCopyrighted" => "dcterms",
  "dateSubmitted" => "dcterms",
  "educationLevel" => "dcterms",
  "extent" => "dcterms",
  "hasFormat" => "dcterms",
  "hasPart" => "dcterms",
  "hasVersion" => "dcterms",
  "isFormatOf" => "dcterms",
  "isPartOf" => "dcterms",
  "isReferencedBy" => "dcterms",
  "isReplacedBy" => "dcterms",
  "isRequiredBy" => "dcterms",
  "issued" => "dcterms",
  "isVersionOf" => "dcterms",
  "license" => "dcterms",
  "mediator" => "dcterms",
  "medium" => "dcterms",
  "modified" => "dcterms",
  "references" => "dcterms",
  "replaces" => "dcterms",
  "requires" => "dcterms",
  "spatial" => "dcterms",
  "tableOfContents" => "dcterms",
  "temporal" => "dcterms",
  "valid" => "dcterms",
  "olac" => "olac"
);

# $namespaces: table of (namespace symbol, namespace)
$namespaces = array(
  "dc" => "http://purl.org/dc/elements/1.1/",
  "dcterms" => "http://purl.org/dc/terms/",
  "olac" => "http://www.language-archives.org/OLAC/1.1/",
  "xsi" => "http://www.w3.org/2001/XMLSchema-instance"
);

# schemas: table of (namespace, schema)
$schemas = array(
  "http://purl.org/dc/elements/1.1/" => "$BASEURL/OLAC/1.1/dc.xsd",
  "http://purl.org/dc/terms/" => "$BASEURL/OLAC/1.1/dcterms.xsd",
  "http://www.language-archives.org/OLAC/1.1/" => "$BASEURL/OLAC/1.1/olac.xsd",
  "http://www.language-archives.org/OLAC/1.0/" => "$BASEURL/OLAC/1.0/olac.xsd"
);

##
## Check consistency of namespace prefixes.
## Figure out what prefix is used for each namespace of dc, dcterms, olac
##   and xsi.
##

# $pfxmap : table of (namespace symbol, namespace prefix)
$matches = array();
$pfxmap = array();
# matches all qualified tag names
preg_match_all("{<(?:([^/?]+?):)(\w+?)\b}", $metadata, &$matches);
for ($i=0; $i<count($matches[0]); $i++) {
  $prefix = $matches[1][$i];
  $tag = $matches[2][$i];
  $nssym = $nssymbols[$tag];
  if (array_key_exists($prefix1, $pfxmap)) {
    if ($prefix != $pfxmap[$nssym]) {
      echo "<p>Inconsistent use of namespace prefix: '$prefix:$tag'</p>";
      return;
    }
  } else {
    # found a new namespace prefix
    $pfxmap[$nssym] = $prefix;
  }
}

# matches all xsi:type attributes
# NOTE: this pattern may fail on unanticipated inputs
preg_match_all("{<[^>]*?(\S+?):type\s*=\s*}s", $metadata, &$matches);
for ($i=0; $i<count($matches[0]); $i++) {
  $prefix = $matches[1][$i];
  if (array_key_exists("xsi", $pfxmap)) {
    if ($prefix != $pfxmap["xsi"]) {
      echo "<p>Inconsistent use of xsi namespace prefix.</p>";
      return;
    }
  } else {
    # by looking the qualified attribute name of "xsi:type"
    # attribute, we know what prefix is used for xsi namespace
    $pfxmap["xsi"] = $prefix;
  }
}

##
## Extract olac tag. Extract attributes string and schemaLocation string it.
##

# obtain a string defining attributes of olac element
# $olacprefix : namespace prefix of OLAC 1.1
# $olactag    : attributes string of olac tag
# $schemaloc  : schemaLocation string of olac tag
if (array_key_exists("olac", $pfxmap)) {
  $olacprefix = $pfxmap["olac"];
  $pat = "{<";
  if ($olacprefix) $pat .= "$olacprefix:";
  $pat .= "olac\b(.*?)>}s";
  preg_match($pat, $metadata, $group);
  $olactag = $group[1];
  $pat = "{(?:\S+:)schemaLocation\s*=\s*[\"'](.*?)[\"']}s";
  if (preg_match($pat, $olactag, $group)) {
    $schemaLoc = $group[1];
    $olactag = preg_replace($pat, "", $olactag);
  } else {
    $schemaLoc = "";
  }
} else {
  echo "<p>No olac element found.</p>";
  return;
}

##
## Figure out olac version used.
## If namespace is declared, it contains the version number.
## Otherwise, assume it's 1.1.
##

$pat = "{xmlns";
if ($olacprefix) $pat .= ":$olacprefix";
$pat .= "\s*=\s*[\"'](http://www\.language-archives\.org/OLAC/(.*?)/)[\"']}s";
if (preg_match($pat, $olactag, $group)) {
  $olacver = $group[2];
  $namespaces["olac"] = $group[1];
} else {
  $olacver = "1.1";
}


##
## Check xsi namespace declaration. If it is not declared, add one.
## If declared prefix is incorrect, correct it.
##

# matches an attribute that declares the xsi namespace and identify
# the namespace prefix
$pat = "{([^: \t\n]+)\s*=\s*[\"']http://www.w3.org/2001/XMLSchema-instance[\"']}";
if (preg_match($pat, $olactag, $group)) {
  if ($group[1] != $pfxmap["xsi"]) {
    # the declared prefix is different from what's used for "xsi:type" attributes
    # --> correct it
    $pat = "{[^: \t\n]+(\s*=\s*[\"']http://www.w3.org/2001/XMLSchema-instance[\"'])}";
    $olactag = preg_replace($pat, $pfxmap["xsi"] . "$1", $olactag);
  }
} else {
  # xsi namespace and prefix are not declared --> add it
  $ns = $namespaces["xsi"];
  $olactag .= " xmlns:xsi=\"$ns\"";
}

##
## Add namespace declarations and schema locations if missing.
##

#foreach ($pfxmap as $default => $used) {
foreach ($namespaces as $default => $ns) {
  if (array_key_exists($default, $pfxmap)) {
    $used = $pfxmap[$default];
    if (!preg_match("{xmlns:$used\s*=}", $olactag)) {
      $olactag .= " xmlns:$used=\"$ns\"";
    }
  } else {
    $olactag .= " xmlns:$default=\"$ns\"";
  }

  if ($default != 'xsi') {
    $schema = $schemas[$ns];
    if (!preg_match("@$ns\s+@", $schemaLoc)) {
      $schemaLoc .= " $ns $schema";
    }
  }
}

$xsiprefix = $pfxmap["xsi"];
if ($xsiprefix) $xsiprefix .= ":";
$olactag = "<$olacprefix:olac $olactag ${xsiprefix}schemaLocation=\"$schemaLoc\">";

## the document has been fixed
$metadata = preg_replace("{<(?:[^/>]+?:)olac\b.*?>}s", $olactag, $metadata);


# Write the submitted metadata to a temporary file

  $tmpfile = tempnam("tmp", '');
  $fd = fopen($tmpfile, 'w');
  fputs($fd, $metadata);
  fclose($fd);
  chmod($tmpfile, 0644);
# readfile($tmpfile);     # For debug purposes


# Validate the tempfile

  $command = olacvar('xml_validator') . " $tmpfile";
  exec($command, $result);

#    $command = "/mnt/unagi/speechd8/ldc/wwwhome/olac/bin/xsv_html $tmpfile";
#    exec($command, $result, $errcode);
#    errcode: 0 = success; 1 = fail
#    also, xsv_xml, which is faster

  if (!$result) { # The record is valid

	if ($_POST["action"] == "Analyze") {
		$getrecord_header = <<<EOT
<OAI-PMH
  xmlns="http://www.openarchives.org/OAI/2.0/"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/
  http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd">
  <responseDate>2007-08-08T00:00:00Z</responseDate>
  <request></request>
  <GetRecord>
    <record>
      <header>
        <identifier></identifier>
        <datestamp>2007-08-08</datestamp>
      </header>
      <metadata>
EOT;
		$getrecord_footer = <<<EOT
      </metadata>
    </record>
  </GetRecord>
</OAI-PMH>
EOT;

		$metadata = preg_replace('/<olac/', $getrecord_header."<olac", $metadata);
		$metadata .= $getrecord_footer;
		$fd = fopen($tmpfile, 'w');
		fputs($fd, $metadata);
		fclose($fd);
		chmod($tmpfile, 0644);

		$xsl = "$BASEURL/metadata_sample.xsl";
	} else {
		$xsl = "$BASEURL/tools/metadata/metadata.xsl";
	}

# Set up the XSLT processor

  #$java    = "/pkg/j/j2sdk1.4.0/bin/java";
  #$xalan   = "/mnt/unagi/ldc/wwwhome/jakarta-tomcat-3.2.3-sb/lib/xalan.jar";
  #$xerces  = "/pkg/x/xerces-2_0_1/xercesImpl.jar";
  #$xslt    = "$java -classpath $xalan:$xerces org.apache.xalan.xslt.Process -xsl $xsl";

# Transform output

  #$command = "$xslt -IN $tmpfile";
  #$command = "/mnt/unagi/speechd8/ldc/wwwhome/olac/bin/xalan $tmpfile $xsl";
  $command = "xsltproc $xsl $tmpfile";
  exec($command, $result);
  unlink($tmpfile);
  
# Return the HTML page

  header("Content-Type: text/html"); 
  print join("\n", $result);
?>
<?php

  } else {

# Return the HTML page reporting validation errors

  header("Content-Type: text/html"); 
?>
<HTML>
<HEAD>
<TITLE>Validate Free-standing Metadata</TITLE>
<script type="text/javascript" src="/js/gatrack.js"></script>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=windows-1252"> 
<LINK HREF="olac.css" TYPE="text/css" REL="stylesheet"> 

</HEAD>
<BODY>
<HR>
<TABLE CELLPADDING="10">
<TBODY>
<TR>
<TD><A HREF="/"><IMG
SRC="/images/olac100.gif" BORDER="0"
ALT="OLAC Logo"> </A> </TD>
<TD> <H1>Free-standing OLAC Metadata:<BR>Validation Service</H1>
</TD>
</TR>
</TBODY>
</TABLE>
<HR>
<P>The following validation errors were detected in the document you uploaded:</P>
<BLOCKQUOTE><PRE>
<?=join("\n", $result);?>
</PRE></BLOCKQUOTE>
<P>The document is not a valid OLAC metadata record. Go back to the previous page and correct this before proceeding to the step of display formatting.</P>
</BODY>
</HTML>
<?php
    }
?>
