<?xml version="1.0" encoding="UTF-8"?>
<!-- edited with XML Spy v4.3 (http://www.xmlspy.com) by Gary Simons (SIL International) -->
<!-- basic_service.xsl

     Stylesheet for a Basic Metadata Display Service for use
     with Viser (OLAC's Virtual Service Provider)
        http://www.language-archives.org/viser

     by Gary Simons, SIL International
        Last revised: 29 July 2003

     The stylesheet has two parameters which are passed in as 
     processing instructions:

        title   The title of the rendered page
        start   The sequence number of the first hit displayed on the page

-->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:oai="http://www.openarchives.org/OAI/2.0/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:olac="http://www.language-archives.org/OLAC/1.0/">
   <xsl:output method="html"/>
   <xsl:variable name="title">
      <xsl:choose>
         <xsl:when test="/processing-instruction('title') != ''">
            <xsl:value-of select="/processing-instruction('title')"/>
         </xsl:when>
         <xsl:otherwise>Untitled Query Results</xsl:otherwise>
      </xsl:choose>
   </xsl:variable>
   <xsl:variable name="start">
      <xsl:value-of select="/processing-instruction('start')"/>
   </xsl:variable>
   <xsl:variable name="Viser">http://www.language-archives.org/viser</xsl:variable>
   <xsl:template match="/">
      <html>
         <head>
            <title>
               <xsl:value-of select="$title"/>
            </title>
            <style type="text/css">.footer {color: white}
			body {font-family: sans-serif }</style>
         </head>
         <body>
            <table width="100%" border="1" borderColor="black" cellspacing="0" cellpadding="10">
               <tr>
                  <td colspan="2" bgcolor="silver">
                     <p style="margin-top: 8pt; margin-bottom: 8pt">
                        <b>
                           <big>
                              <big>
                                 <big>
                                    <xsl:value-of select="$title"/>
                                    <xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text>
                                 </big>
                              </big>
                           </big>
                        </b>
                     </p>
                  </td>
               </tr>
               <tr>
                  <td colspan="2">
                     <xsl:if test="//oai:error">
                        <!--It is an error report from OLACA-->
                        <xsl:copy-of select="//oai:error"/>
                     </xsl:if>
                     <br/>
                     <ol start="{$start}">
                        <xsl:apply-templates select="//oai:record">
                           <!--This only sorts this page; note that OLACA returns records in identifier order.-->
                           <xsl:sort select=".//dc:title"/>
                        </xsl:apply-templates>
                     </ol>
                     <xsl:if test="//oai:resumptionToken">
                        <blockquote>
                           <p>
                              <a>
                                 <xsl:attribute name="href"><xsl:value-of select="$Viser"/><xsl:text>?resumptionToken=</xsl:text><xsl:value-of select="//oai:resumptionToken"/><xsl:text>&amp;title=</xsl:text><xsl:value-of select="translate($title, ' ', '+')"/><xsl:text>&amp;start=</xsl:text><xsl:value-of select="$start+count(//oai:record)"/></xsl:attribute>More resources ...</a>
                           </p>
                        </blockquote>
                        <br/>
                     </xsl:if>
                  </td>
               </tr>
               <xsl:call-template name="footer"/>
            </table>
         </body>
      </html>
   </xsl:template>
   <xsl:template match="oai:record">
      <li>
         <p>
            <xsl:value-of select=".//dc:title[1]"/>
            <xsl:if test=".//dc:creator">
               <xsl:text>, by </xsl:text>
               <xsl:for-each select=".//dc:creator">
                  <xsl:value-of select="."/>
                  <xsl:if test="following-sibling::dc:creator">
                     <xsl:text>; </xsl:text>
                  </xsl:if>
               </xsl:for-each>
            </xsl:if>
            <xsl:if test=" .//dc:date | .//dcterms:issued | .//dcterms:modified">
               <xsl:text>. </xsl:text>
               <xsl:value-of select="(.//dcterms:modified | .//dcterms:issued | .//dc:date )[1]"/>
            </xsl:if>
            <xsl:text>. [</xsl:text>
            <a href="/tools/lookup.php4?identifier={.//oai:identifier}">
               <xsl:value-of select=".//oai:identifier"/>
            </a>
            <xsl:text>]</xsl:text>
         </p>
      </li>
   </xsl:template>
   <xsl:template name="footer">
      <tr bgcolor="#000000">
         <td width="120">
            <img src="http://www.language-archives.org/images/olac100.gif" alt=""/>
         </td>
         <td align="left" style="color: white" width="100%">
            <b>Powered by:</b>
            <br/>
            <a href="/" class="footer">
               <b>Open Language Archives Community</b>
            </a>
            <xsl:text> and </xsl:text>
            <a href="/viser" class="footer">
               <b>Viser</b>
            </a>
         </td>
      </tr>
   </xsl:template>
</xsl:stylesheet>
