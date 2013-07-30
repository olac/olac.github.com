<?xml version="1.0"?>
<!-- edited with XML Spy v4.3 U (http://www.xmlspy.com) by Gary Simons (SIL International) -->
<!--documents_by_date.xsl
     Stylesheet for generating list of OLAC documents by date

     G. Simons, 2 Aug 2003
     Last updated: 16 July 2008
-->
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
  <xsl:output method="html" version="4.0" doctype-public="-//W3C//DTD HTML 4.0 Transitional//EN" doctype-system="http://www.w3.org/TR/REC-html40/loose.dtd" encoding="ISO-8859-1"/>
    <xsl:include href="OLAC_doc_functions.xsl"/>
    <xsl:include href="Google_Analytics.xsl"/>
  <xsl:strip-space elements="*"/>
  <xsl:variable name="doc-headers" select="document('document_headers.xml')"/>
  <xsl:template match="/documents">
    <HTML>
      <HEAD>
        <TITLE>OLAC Documents by Date</TITLE>
        <link type="text/css" rel="stylesheet" href="olac.css"/>
        <meta name="Title">
          <xsl:attribute name="content">OLAC Documents by Date</xsl:attribute>
        </meta>
        <meta name="Description">
          <xsl:attribute name="content">Lists all of the OLAC documents by latest date of revision</xsl:attribute>
        </meta>
        <meta name="Publisher" content="OLAC (Open Language Archives Community)"/>
      </HEAD>
      <BODY>
        <table border="0" cellspacing="1" cellpadding="0" width="100%">
          <tr>
            <td class="frame">
              <table border="0" cellspacing="1" cellpadding="0" width="100%">
                <tr>
                  <td>
                    <xsl:copy-of select="pageBanner/*"/>
                  </td>
                </tr>
                <tr>
                  <td style="padding: 10pt">
                    <br/>
                    <h2>OLAC Documents by Date</h2>
                    <table width="90%" cellpadding="5" align="center">
                      <tr>
                        <td>
                          <xsl:copy-of select="textBlocks/dateIntro/*"/>
                          <xsl:copy-of select="textBlocks/processRef/*"/>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
                <tr>
                  <td style="padding: 10pt">
                    <table>
                      <xsl:for-each select="$doc-headers//header">
                        <xsl:sort select="issued" order="descending"/>
                        <xsl:sort select="title"/>
                        <xsl:apply-templates select="document(@href)"/>
                      </xsl:for-each>
                    </table>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        </table>
        <div class="timestamp">
http://www.language-archives.org/documents_by_date.html<br/>Latest update: <xsl:for-each select="$doc-headers//header[1]">
            <xsl:call-template name="format-date"/>
          </xsl:for-each>
        </div>
        <xsl:call-template name="GA-script"/>
      </BODY>
    </HTML>
  </xsl:template>
  <xsl:template match="OLAC_doc">
    <xsl:for-each select="header">
      <tr valign="top">
        <td nowrap="nowrap">
          <xsl:call-template name="format-date"/>
          <xsl:text disable-output-escaping="yes">&amp;nbsp;&amp;nbsp;&amp;nbsp;</xsl:text>
        </td>
        <td>
          <p>
              <a>
                  <xsl:attribute name="href">
                      <xsl:choose>
                          <xsl:when test="status[@type='standard']"><xsl:text>OLAC/</xsl:text></xsl:when>
                          <xsl:when test="status[@type='recommendation']"><xsl:text>REC/</xsl:text></xsl:when>
                          <xsl:otherwise><xsl:text>NOTE/</xsl:text></xsl:otherwise>
                      </xsl:choose>
                      <xsl:value-of select="baseName"/>
                      <xsl:if test="status/@supersededBy = baseName">
                          <xsl:value-of select="concat( '-', issued)"/>
                      </xsl:if>
                      <xsl:text>.html</xsl:text>
                  </xsl:attribute>
              <xsl:value-of select="title"/>
            </a>
          </p>
          <blockquote style="margin-left: 18pt">
            <xsl:for-each select="status">
              <xsl:call-template name="format-status-and-type"/>
            </xsl:for-each>
            <xsl:value-of select="abstract"/>
          </blockquote>
        </td>
      </tr>
    </xsl:for-each>
  </xsl:template>
</xsl:stylesheet>
