<?xml version="1.0"?>
<!-- edited with XML Spy v4.3 U (http://www.xmlspy.com) by Gary Simons (SIL International) -->
<!--document_headers.xsl
     Stylesheet for extracting the current document headers from the document list

     G. Simons, 2 Aug 2003
     Last updated: 4 April 2006
-->
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
  <xsl:output method="xml" version="1.0" encoding="ISO-8859-1"/>
  <xsl:strip-space elements="*"/>
  <xsl:template match="/documents">
    <xsl:element name="document-headers">
      <xsl:for-each select="//document">
        <xsl:sort select="document(@href)/OLAC_doc/header/issued" order="descending"/>
        <xsl:element name="header">
          <xsl:copy-of select="@href"/>
          <xsl:apply-templates select="document(@href)"/>
        </xsl:element>
      </xsl:for-each>
    </xsl:element>
  </xsl:template>
  <xsl:template match="OLAC_doc">
    <xsl:copy-of select="header/status"/>
    <xsl:copy-of select="header/issued"/>
    <xsl:copy-of select="header/title"/>
  </xsl:template>
</xsl:stylesheet>
