<?xml version="1.0" encoding="UTF-8"?>
<!--
    http://www.language-archives.org/tools/metadata/metadata_advisor11.xsl
     A stylesheet for transforming an OLAC metadata record (version 1.1)
       into an HTML table giving an analysis of the metadata quality.
       (Use <xsl:include> to include it in a stylesheet that produces a whole page.)
       Gary Simons, SIL International (last updated 30 June 2008)

Copyright (c) 2008 Gary Simons (SIL International). This material may be
distributed only subject to the terms and conditions set forth in the
General Public License, version 2 or later (the latest version is presently
available at http://www.gnu.org/licenses/gpl.txt).
-->
<!-- N.B. When using dct as the namespace prefix, matches like @xsi:type='dct:DCMIType'
   did not work -->
<xsl:stylesheet version="1.0" xmlns:dc="http://purl.org/dc/elements/1.1/"
   xmlns:dcterms="http://purl.org/dc/terms/" 
   xmlns:olac="http://www.language-archives.org/OLAC/1.1/"
   xmlns:xs="http://www.w3.org/2001/XMLSchema" 
   xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
   xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
   <xsl:output method="html" version="4.0"/>
   <xsl:template match="olac:olac" mode="advise">
      <xsl:variable name="title">
         <xsl:choose>
            <xsl:when test="dc:title[.!=''] ">1</xsl:when>
            <xsl:otherwise>0</xsl:otherwise>
         </xsl:choose>
      </xsl:variable>
      <xsl:variable name="date">
         <xsl:choose>
            <xsl:when
               test="dc:date[.!=''] | dcterms:created[.!='']  | dcterms:available[.!='']  |
               dcterms:dateAccepted[.!='']  | dcterms:dateCopyrighted[.!='']  |
               dcterms:dateSubmitted[.!=''] | dcterms:issued[.!=''] | dcterms:modified[.!=''] |
               dcterms:valid[.!=''] "
               >1</xsl:when>
            <xsl:otherwise>0</xsl:otherwise>
         </xsl:choose>
      </xsl:variable>
      <xsl:variable name="agent">
         <xsl:choose>
            <xsl:when test="dc:creator[.!='']  | dc:contributor[.!='']  | dc:publisher[.!=''] ">1</xsl:when>
            <xsl:otherwise>0</xsl:otherwise>
         </xsl:choose>
      </xsl:variable>
      <xsl:variable name="about">
         <xsl:choose>
            <xsl:when
               test="dc:description[.!='']  | dc:subject[.!='' or @olac:code!=''] | dcterms:abstract[.!='']  |
               dcterms:tableOfContents[.!=''] | dc:coverage[.!=''] | dcterms:spatial[.!=''] |
               dcterms:temporal[.!='']"
               >1</xsl:when>
            <xsl:otherwise>0</xsl:otherwise>
         </xsl:choose>
      </xsl:variable>
      <xsl:variable name="lang">
         <xsl:choose>
            <xsl:when test="dc:language[@xsi:type='olac:language' and @olac:code!='']">1</xsl:when>
            <xsl:otherwise>0</xsl:otherwise>
         </xsl:choose>
      </xsl:variable>
      <xsl:variable name="OLACtype">
         <xsl:choose>
            <xsl:when test="dc:type[@xsi:type='olac:linguistic-type' and @olac:code!='']">1</xsl:when>
            <xsl:otherwise>0</xsl:otherwise>
         </xsl:choose>
      </xsl:variable>
      <xsl:variable name="subjLang">
         <xsl:choose>
             <xsl:when
                 test="dc:subject[@xsi:type='olac:language' and
                 not(@olac:code) ]  "
                 >0</xsl:when>
             <xsl:when
                test="dc:subject[@xsi:type='olac:language' and
                @olac:code!=''] |
               dc:type[@olac:code='primary_text' ]  "
               >1</xsl:when>
            <!-- The following is termporary until linguistic-type becomes
               language-resource-type -->
               <xsl:when test="not(dc:type[@xsi:type='olac:linguistic-type' ])">1</xsl:when>
            <xsl:otherwise>0</xsl:otherwise>
         </xsl:choose>
      </xsl:variable>
      <xsl:variable name="DCtype">
         <xsl:choose>
            <xsl:when test="dc:type[@xsi:type='dcterms:DCMIType'  and .!='']">1</xsl:when>
            <xsl:otherwise>0</xsl:otherwise>
         </xsl:choose>
      </xsl:variable>
      

      <xsl:variable name="main"
         select="$about+$agent+$date+$title+$lang+$subjLang+$DCtype+$OLACtype"/>

      <xsl:variable name="depth">
         <xsl:choose>
            <xsl:when test="count(*[.!='' or @olac:code!=''])-8 > 6">6</xsl:when>
            <xsl:when test="count(*[.!='' or @olac:code!=''])-8 &lt; 0">0</xsl:when>
            <xsl:otherwise>
               <xsl:value-of select="count(*[.!='' or @olac:code!=''])-8"/>
            </xsl:otherwise>
         </xsl:choose>
      </xsl:variable>

      <xsl:variable name="W3CDTF">
         <xsl:choose>
            <xsl:when test="*[@xsi:type='dcterms:W3CDTF' and .!=''] ">1</xsl:when>
            <xsl:otherwise>0</xsl:otherwise>
         </xsl:choose>
      </xsl:variable>
      <xsl:variable name="role">
         <xsl:choose>
            <xsl:when test="*[@xsi:type='olac:role' and @olac:code!='' and .!=''] ">1</xsl:when>
            <xsl:otherwise>0</xsl:otherwise>
         </xsl:choose>
      </xsl:variable>
      <xsl:variable name="IMT">
         <xsl:choose>
            <xsl:when test="*[@xsi:type='dcterms:IMT' and .!=''] ">1</xsl:when>
            <xsl:otherwise>0</xsl:otherwise>
         </xsl:choose>
      </xsl:variable>
      <xsl:variable name="URI">
         <xsl:choose>
            <xsl:when test="*[@xsi:type='dcterms:URI' and .!=''] ">1</xsl:when>
            <xsl:otherwise>0</xsl:otherwise>
         </xsl:choose>
      </xsl:variable>
      <xsl:variable name="Coverage">
         <xsl:choose>
            <xsl:when
               test="dc:coverage[@xsi:type] | dcterms:spatial[@xsi:type] |
               dcterms:temporal[@xsi:type] "
               >1</xsl:when>
            <xsl:otherwise>0</xsl:otherwise>
         </xsl:choose>
      </xsl:variable>

      <xsl:variable name="schemes">
         <xsl:choose>
            <xsl:when test="$W3CDTF + $IMT + $URI + $role + $Coverage > 3">3</xsl:when>
            <xsl:otherwise>
               <xsl:value-of select="$W3CDTF + $IMT + $URI + $role + $Coverage"/>
            </xsl:otherwise>
         </xsl:choose>
      </xsl:variable>

      <table cellpadding="2" cellspacing="6">
         <tr>
            <th bgcolor="silver">Component</th>
            <th bgcolor="#e0e0e0">+</th>
            <th bgcolor="#e0e0e0">-</th>
            <th bgcolor="#f8f8f8">Comments</th>
         </tr>
         <xsl:call-template name="component11">
            <xsl:with-param name="label">Title</xsl:with-param>
            <xsl:with-param name="plus" select="$title"/>
         </xsl:call-template>
         <xsl:call-template name="component11">
            <xsl:with-param name="label">Date</xsl:with-param>
            <xsl:with-param name="plus" select="$date"/>
         </xsl:call-template>
         <xsl:call-template name="component11">
            <xsl:with-param name="label">Agent</xsl:with-param>
            <xsl:with-param name="plus" select="$agent"/>
         </xsl:call-template>
         <xsl:call-template name="component11">
            <xsl:with-param name="label">About</xsl:with-param>
            <xsl:with-param name="plus" select="$about"/>
         </xsl:call-template>
         <xsl:call-template name="component11">
            <xsl:with-param name="label">Depth</xsl:with-param>
            <xsl:with-param name="plus" select="$depth div 6"/>
         </xsl:call-template>
         <xsl:call-template name="component11">
            <xsl:with-param name="label">Content&#160;Language</xsl:with-param>
            <xsl:with-param name="plus" select="$lang"/>
         </xsl:call-template>
         <xsl:call-template name="component11">
            <xsl:with-param name="label">Subject&#160;Language</xsl:with-param>
            <xsl:with-param name="plus" select="$subjLang"/>
         </xsl:call-template>
         <xsl:call-template name="component11">
            <xsl:with-param name="label">OLAC Type</xsl:with-param>
            <xsl:with-param name="plus" select="$OLACtype"/>
         </xsl:call-template>
         <xsl:call-template name="component11">
            <xsl:with-param name="label">DCMI Type</xsl:with-param>
            <xsl:with-param name="plus" select="$DCtype"/>
         </xsl:call-template>
         <xsl:call-template name="component11">
            <xsl:with-param name="label">Precision</xsl:with-param>
            <xsl:with-param name="plus" select="$schemes div 3"/>
         </xsl:call-template>

         <tr>
            <td bgcolor="silver">
               <i>Quality&#160;score&#160;</i>
            </td>
            <td align="center" bgcolor="#e0e0e0" colspan="2">
               <xsl:value-of
                  select="format-number($main + $depth div 6 + $schemes div 3,
                  '0.##')"
               />
            </td>
            <td bgcolor="#f8f8f8">&#160;</td>
         </tr>
      </table>
   </xsl:template>
   <xsl:template name="component11">
      <xsl:param name="label"/>
      <xsl:param name="plus"/>
      <xsl:param name="max">1</xsl:param>
      <xsl:variable name="minus">
         <xsl:choose>
            <xsl:when test="$plus > $max">0</xsl:when>
            <xsl:otherwise>
               <xsl:value-of select="$max - $plus"/>
            </xsl:otherwise>
         </xsl:choose>
      </xsl:variable>
      <tr>
         <td bgcolor="silver">
            <xsl:value-of select="$label"/>&#160; </td>
         <td align="center" bgcolor="#e0e0e0">
            <xsl:choose>
               <xsl:when test="$plus > $max">Error</xsl:when>
               <xsl:otherwise> &#160;<xsl:value-of select="format-number($plus, '0.##')"
                  />&#160; </xsl:otherwise>
            </xsl:choose>
         </td>
         <td align="center" bgcolor="#e0e0e0"> &#160;<xsl:value-of
               select="format-number($minus, '0.##')"/>&#160; </td>
         <td bgcolor="#f8f8f8">
            <xsl:if test="$minus > 0">
               <xsl:choose>
                  <xsl:when test="$label='About'">Add a dc:description or dc:subject or dcterms:abstract
                     or dcterms:tableOfContents element.</xsl:when>
                  <xsl:when test="$label='Agent'">Add a dc:creator or dc:contributor or dc:publisher
                     element.</xsl:when>
                  <xsl:when test="$label='Date'">Add a dc:date element (or one of its refinements,
                     like dcterms:created or dcterms:issued).</xsl:when>
                  <xsl:when test="$label='Depth'">For the full score, add at least <xsl:choose>
                        <xsl:when test="round($minus * 6) = 1">one more element</xsl:when>
                        <xsl:otherwise><xsl:value-of select="format-number($minus * 6, '0.##')"/>
                           more elements</xsl:otherwise>
                     </xsl:choose> in addition to the ones counted explicitly in other components of
                     the score.</xsl:when>
                  <xsl:when test="$label='Content&#160;Language'"> Add a dc:language element
                     with an ISO 639-3 code to identify the language in which the resource is
                     written or spoken. </xsl:when>
                  <xsl:when test="$label='Subject&#160;Language'"> Add a dc:subject element with
                     an ISO 639-3 code to identify the language which the resource is about. </xsl:when>
                  <xsl:when test="$label='Precision'">For the full score, make use of at least <xsl:choose>
                        <xsl:when test="round($minus * 3) = 1">one more encoding scheme</xsl:when>
                        <xsl:otherwise><xsl:value-of select="format-number($minus * 3, '0.##')"/>
                           more encoding schemes</xsl:otherwise>
                     </xsl:choose> in addition to the ones counted explicitly in other components of
                     the score. For instance, <ul>
                        <xsl:if test="not(*[@xsi:type='olac:role'])">
                           <li>olac:role on dc:creator or dc:contributor</li>
                        </xsl:if>
                        <xsl:if test="not(*[@xsi:type='dcterms:W3CDTF'])">
                           <li>use dcterms:W3CDTF on dc:date (or its refinements)</li>
                        </xsl:if>
                        <xsl:if
                           test="*[starts-with(., 'http:') and
                           not(@xsi:type='dcterms:URI')]">
                           <li>use dcterms:URI when the value of an element is a URL</li>
                        </xsl:if>
                        <xsl:if test="not(*[@xsi:type='dcterms:IMT'])">
                           <li>use dcterms:IMT on dc:format</li>
                        </xsl:if>
                        <xsl:if
                           test="dc:coverage[not(@xsi:type)] |
                           dcterms:spatial[not(@xsi:type)]">
                           <li>use dcterms:Box or dcterms:Point or dcterms:TGN on dcterms:spatial</li>
                        </xsl:if>
                     </ul></xsl:when>
                  <xsl:when test="$label='Title'">Add a dc:title element.</xsl:when>
                  <xsl:when test="$label='DCMI Type'">Add a dc:type element that uses the DCMIType
                     encoding scheme to identify the generic type of the resource.</xsl:when>
                  <xsl:when test="$label='OLAC Type'">Add a dc:type element that uses the OLAC
                     linguistic-type encoding scheme to identify the type of the resource from a
                     linguistic point of view.</xsl:when>
               </xsl:choose>
            </xsl:if>
         </td>
      </tr>

   </xsl:template>
</xsl:stylesheet>
