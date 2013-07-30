<?xml version="1.0" encoding="UTF-8"?>
<!-- revised by Haejoong Lee (LDC, UPenn)
     The main transformation templates have been removed and the functionality
     is replaced by the following stylesheet written by Gary Simons.

       http://www.language-archives.org/metadata_record.xsl

     Last updated 28 July 2008
 -->

<!-- edited with XML Spy v4.4 U (http://www.xmlspy.com) by Gary Simons (SIL International) -->
<!-- http://www.language-archives.org/tools/metadata/metadata.xsl
     A stylesheet for transforming free-standing OLAC metadata
       into a web page following the OLAC Display Format.
     Gary Simons, SIL International (last updated 15 May 2006)

Copyright (c) 2003 Gary Simons (SIL International). This material may be
distributed only subject to the terms and conditions set forth in the
General Public License, version 2 or later (the latest version is presently
available at http://www.gnu.org/licenses/gpl.txt).
 -->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:olac10="http://www.language-archives.org/OLAC/1.0/" xmlns:olac11="http://www.language-archives.org/OLAC/1.1/">
	<xsl:output method="html" version="4.0"/>
	<xsl:template match="/olac10:olac | /olac11:olac">
		<xsl:variable name="title">
			<xsl:choose>
				<xsl:when test="dc:title|dcterms:alternative">
					<xsl:value-of select="(dc:title|dcterms:alternative)[1]"/>
				</xsl:when>
				<xsl:otherwise>No title</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<html>
			<head>
				<title>
					<xsl:value-of select="$title"/>
				</title>
			</head>
			<body>
				<hr style="color: black"/>
				<h1>
					<xsl:value-of select="$title"/>
				</h1>
				<hr style="color: black"/>
				<table cellpadding="1" cellspacing="6">
					<xsl:apply-templates mode="record" select="//olac10:olac | //olac11:olac"/>
				</table>
				<hr style="color: black"/>
				<p>
					<small>
						<xsl:text>This resource description follows the </xsl:text>
						<a href="/OLAC/metadata.html">metadata standard</a>
						<xsl:text> of the </xsl:text>
						<a href="/">Open Language Archives Community</a>
						<xsl:text>.</xsl:text>
					</small>
				</p>
			</body>
		</html>
	</xsl:template>
	<xsl:include href="../../metadata_record.xsl"/>
</xsl:stylesheet>
