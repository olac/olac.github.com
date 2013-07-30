<?xml version="1.0"?>
<!-- edited with XML Spy v4.3 U (http://www.xmlspy.com) by Gary Simons (SIL International) -->
<!--document_index.xsl
     Stylesheet for generating the main OLAC document index (by type)

     G. Simons, 10 Sept 2002
     Last revised: 17 Dec 2008
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
        <TITLE>OLAC Document Index</TITLE>
        <link type="text/css" rel="stylesheet" href="olac.css"/>
        <meta name="Title">
          <xsl:attribute name="content">OLAC Document Index</xsl:attribute>
        </meta>
        <meta name="Keywords">
          <xsl:attribute name="content">OLAC, Open Language Archives Community, language resources, archiving standards, best practice recommendations</xsl:attribute>
        </meta>
        <meta name="Description">
          <xsl:attribute name="content">Lists all documents that are current in the OLAC document process by category (Standard, Recommendation, or Note).</xsl:attribute>
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
                    <h2>OLAC Documents</h2>
                    <table width="90%" cellpadding="5" align="center">
                      <tr>
                        <td colspan="2">
                          <xsl:copy-of select="textBlocks/typeIntro/*"/>
                        </td>
                      </tr>
                      <xsl:for-each select="byType/section">
                        <tr valign="top">
                          <td>
                            <h4 style="margin-left: 18pt">
                              <a href="#{heading}">
                                <xsl:value-of select="heading"/>
                              </a>
                            </h4>
                          </td>
                          <td>
                            <xsl:value-of select="intro"/>
                          </td>
                        </tr>
                      </xsl:for-each>
                      <tr>
                        <td colspan="2">
                          <xsl:copy-of select="textBlocks/processRef/*"/>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
                <tr>
                  <td style="padding: 10pt">
                    <xsl:apply-templates select="byType/section"/>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        </table>
        <div class="timestamp">
http://www.language-archives.org/documents.html<br/>Latest update: <xsl:for-each select="$doc-headers//header[1]">
            <xsl:call-template name="format-date"/>
          </xsl:for-each>
        </div>
        <xsl:call-template name="GA-script"/>
      </BODY>
    </HTML>
  </xsl:template>
  <xsl:template match="section">
    <h2 align="center">
      <a name="{heading}"/>
      <xsl:value-of select="heading"/>
    </h2>
    <xsl:choose>
      <xsl:when test="heading='Standards'">
        <xsl:for-each select="$doc-headers//header[status[not(@endDate) and @type = 'standard']]">
          <xsl:sort select="title"/>
          <xsl:apply-templates select="document(@href)"/>
        </xsl:for-each>
      </xsl:when>
      <xsl:when test="heading='Recommendations'">
        <xsl:for-each select="$doc-headers//header[status[not(@endDate) and @type = 'recommendation']]">
          <xsl:sort select="title"/>
          <xsl:apply-templates select="document(@href)"/>
        </xsl:for-each>
      </xsl:when>
      <xsl:otherwise>
        <xsl:for-each select="$doc-headers//header[status[not(@endDate) and not(@type = 'standard') and not(@type = 'recommendation') ]]">
          <xsl:sort select="title"/>
          <xsl:apply-templates select="document(@href)"/>
        </xsl:for-each>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  <xsl:template match="OLAC_doc">
    <xsl:for-each select="header">
      <p style="margin-left: 15pt">
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
        <xsl:text disable-output-escaping="yes">&amp;nbsp;&amp;nbsp;[</xsl:text>
        <xsl:call-template name="format-date"/>
        <xsl:text>]</xsl:text>
      </p>
      <blockquote>
        <xsl:for-each select="status">
          <xsl:call-template name="format-status-and-type"/>
        </xsl:for-each>
        <xsl:value-of select="abstract"/>
      </blockquote>
    </xsl:for-each>
  </xsl:template>
</xsl:stylesheet>
