<?xml version="1.0" encoding="UTF-8"?>
<!-- http://www.language-archives.org/tools/metadata/metadata_sample.xsl
     A stylesheet for transforming an OAI-PMH GetRecord response to 
       a sample record display with raw XML, formatted HTML, and quality analysis.
     Gary Simons, SIL International (last updated 30 Jun 2008)

Copyright (c) 2008 Gary Simons (SIL International). This material may be
distributed only subject to the terms and conditions set forth in the
General Public License, version 2 or later (the latest version is presently
available at http://www.gnu.org/licenses/gpl.txt).
 -->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
   xmlns:oai="http://www.openarchives.org/OAI/2.0/"
   xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
   xmlns:xs="http://www.w3.org/2001/XMLSchema" 
   xmlns:dc="http://purl.org/dc/elements/1.1/" 
   xmlns:dct="http://purl.org/dc/terms/" 
   xmlns:olac10="http://www.language-archives.org/OLAC/1.0/"
   xmlns:olac11="http://www.language-archives.org/OLAC/1.1/">
   <xsl:output method="html" version="4.0"/>
   <xsl:variable name="dq">"</xsl:variable>
   <xsl:template match="/oai:OAI-PMH">
      <xsl:variable name="title" select="//oai:identifier"/>
       <xsl:variable name="xml" select="//olac10:olac | //olac11:olac"/>
      <html>
         <head>
            <title>
               <xsl:value-of select="$title"/>
            </title>
         </head>
         <body>
            <hr style="color: black"/>
            <p><i>Sample Metadata Record</i></p>
            <h1>
               <xsl:value-of select="$title"/>
            </h1>
            <hr style="color: black"/>
            <h3>XML format</h3>
             <pre><xsl:apply-templates select="//olac10:olac | //olac11:olac" mode="raw"/></pre>
            <hr style="color: black"/>
            <h3>Display format</h3>
             <xsl:apply-templates mode="record" select="//olac10:olac | //olac11:olac"></xsl:apply-templates>
            <hr style="color: black"/>
            <h3>Metadata quality analysis</h3>
            <p>OLAC metadata records are scored for metadata quality on a
               10-point scale explained in
               <a href="/NOTE/metrics.html">OLAC
                  Metadata Metrics</a>. The score for the above record (along with
               comments on changes that could improve the score) is as follows:
            </p>
             <xsl:apply-templates mode="advise" select="//olac10:olac | //olac11:olac"></xsl:apply-templates>
         </body>
      </html>
   </xsl:template>
    <xsl:include href="metadata_record.xsl"/>
    <xsl:include href="metadata_advisor10.xsl"/>
    <xsl:include href="metadata_advisor11.xsl"/>
    <xsl:template  match="//olac10:olac | //olac11:olac" mode="raw">
      <xsl:text>&lt;olac:olac></xsl:text><br/>
      <!-- Don't bother since it doesn't pick up xmlns. Use XSLT 2.0?  PHP?
      <xsl:for-each select="@*"> 
         <xsl:value-of select="concat('   ', name(), '=', $dq, ., $dq )"/><br/>
         </xsl:for-each> 
         <xsl:text>   ></xsl:text><br/>  -->
      <xsl:apply-templates select="*" mode="raw"></xsl:apply-templates>
      <xsl:text>&lt;/olac:olac></xsl:text>
   </xsl:template>
   <xsl:template match="*" mode="raw">
      <xsl:value-of select="concat('   &lt;', name() )"/>
      <xsl:if test="@xml:lang"><xsl:value-of select="concat(' xml:lang=', $dq, @xml:lang, $dq )"/></xsl:if>
      <xsl:if test="@xsi:type"><xsl:value-of select="concat(' xsi:type=', $dq, @xsi:type, $dq )"/></xsl:if>
       <xsl:if test="@olac10:code"><xsl:value-of select="concat(' olac:code=', $dq, @olac10:code, $dq )"/></xsl:if>
       <xsl:if test="@olac11:code"><xsl:value-of select="concat(' olac:code=', $dq, @olac11:code, $dq )"/></xsl:if>
      <xsl:choose>
          <xsl:when test="text()"><xsl:value-of select="concat(
              '&gt;', text(), '&lt;/', name(), '>')"/></xsl:when>
         <xsl:otherwise>/></xsl:otherwise>
      </xsl:choose>
      <br/>
   </xsl:template>
</xsl:stylesheet>
