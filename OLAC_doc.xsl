<?xml version="1.0"?>
<!-- edited with XML Spy v4.3 U (http://www.xmlspy.com) by Gary Simons (SIL International) -->
<!--Stylesheet for OLAC  documents

     G. Simons, 21 Feb 2001
     Last modified: 17 Dec 2008
-->
<xsl:stylesheet version="1.0" xmlns:dc="http://purl.org/dc/elements/1.1/"
   xmlns:olac="http://www.language-archives.org/OLAC/1.1/"
   xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
   <xsl:output doctype-public="-//W3C//DTD HTML 4.0 Transitional//EN"
      doctype-system="http://www.w3.org/TR/REC-html40/loose.dtd" encoding="ISO-8859-1" method="html"
      version="4.0"/>
    <xsl:include href="Google_Analytics.xsl"/>
   <xsl:strip-space elements="*"/>
   <xsl:preserve-space elements="eg"/>
   <xsl:key name="Ref" match="ref" use="@abbrev"/>
   <xsl:variable name="baseURL">
      <xsl:text>http://www.language-archives.org/</xsl:text>
      <xsl:choose>
         <xsl:when test="//status[@type='standard']">
            <xsl:text>OLAC/</xsl:text>
         </xsl:when>
         <xsl:when test="//status[@type='recommendation']">
            <xsl:text>REC/</xsl:text>
         </xsl:when>
         <xsl:when
            test="//status[@type='experimental' or @type='informational' or @type='implementation']">
            <xsl:text>NOTE/</xsl:text>
         </xsl:when>
      </xsl:choose>
   </xsl:variable>
   <xsl:variable name="previous">
      <xsl:value-of select="/OLAC_doc/header/previousIssued"/>
   </xsl:variable>
   <xsl:template match="/OLAC_doc">
      <HTML>
         <HEAD>
            <TITLE>
               <xsl:value-of select="header/title"/>
            </TITLE>
            <xsl:call-template name="GA-script"/>
            <meta name="Title">
               <xsl:attribute name="content">
                  <xsl:value-of select="header/title"/>
               </xsl:attribute>
            </meta>
            <meta name="Creator">
               <xsl:attribute name="content">
                  <xsl:value-of select="header/editors"/>
               </xsl:attribute>
            </meta>
            <meta name="Description">
               <xsl:attribute name="content">
                  <xsl:value-of select="header/abstract"/>
               </xsl:attribute>
            </meta>
            <meta content="OLAC (Open Language Archives Community)" name="Publisher"/>
            <meta name="Date">
               <xsl:attribute name="content">
                  <xsl:value-of select="header/issued"/>
               </xsl:attribute>
            </meta>
            <STYLE> BODY { MARGIN:10px; BACKGROUND: white; COLOR:
               navy; FONT-FAMILY: sans-serif; }
            </STYLE>
         </HEAD>
         <BODY>
            <hr/>
            <xsl:apply-templates select="header"/>
            <hr/>
            <h3>Table of contents</h3>
            <ol>
               <xsl:for-each select="body/section">
                  <LI>
                     <A>
                        <xsl:attribute name="href">#<xsl:value-of select="normalize-space(heading)"
                           /></xsl:attribute>
                        <xsl:value-of select="heading"/>
                     </A>
                     <xsl:if
                        test="subsection|element[@name!='All elements']|term[@status!='retired']|extensions">
                        <UL>
                           <xsl:for-each select="subsection">
                              <LI>
                                 <A>
                                    <xsl:attribute name="href">#<xsl:value-of
                                          select="normalize-space(subheading)"/></xsl:attribute>
                                    <xsl:value-of select="subheading"/>
                                 </A>
                              </LI>
                           </xsl:for-each>
                           <xsl:for-each select="element">
                              <LI>
                                 <A>
                                    <xsl:attribute name="href">#<xsl:value-of select="@name"/></xsl:attribute>
                                    <xsl:value-of select="@name"/>
                                 </A>
                              </LI>
                           </xsl:for-each>
                           <xsl:for-each select="term[@status!='retired']">
                              <LI>
                                 <A>
                                    <xsl:attribute name="href">#<xsl:value-of select="code"/></xsl:attribute>
                                    <xsl:value-of select="code"/>
                                 </A>
                                 <xsl:if test="term[@status!='retired']">
                                    <UL>
                                       <xsl:for-each select="term[@status!='retired']">
                                          <LI>
                                             <A>
                                                <xsl:attribute name="href">#<xsl:value-of
                                                  select="code"/></xsl:attribute>
                                                <xsl:value-of select="code"/>
                                             </A>
                                          </LI>
                                       </xsl:for-each>
                                    </UL>
                                 </xsl:if>
                              </LI>
                           </xsl:for-each>
                           <xsl:for-each select="extensions/extension">
                              <xsl:variable name="extName">
                                 <xsl:value-of select="@name"/>
                              </xsl:variable>
                              <LI>
                                 <A>
                                    <xsl:attribute name="href">#<xsl:value-of select="@name"/></xsl:attribute>
                                    <xsl:value-of
                                       select="document(@href)//olac:olac-extension[olac:shortName=$extName]/olac:longName"
                                    />
                                 </A>
                                 <xsl:value-of select="concat(' [', @ns, ':', @name, ']')"/>
                              </LI>
                           </xsl:for-each>
                        </UL>
                     </xsl:if>
                     <xsl:if test="recommendations">
                        <UL>
                           <xsl:for-each select="document(recommendations/@href)//element[bp]">
                              <LI>
                                 <A>
                                    <xsl:attribute name="href">
                                       <xsl:value-of select="concat('#',@name)"/>
                                    </xsl:attribute>
                                    <xsl:value-of select="@name"/>
                                 </A>
                              </LI>
                           </xsl:for-each>
                        </UL>
                     </xsl:if>
                  </LI>
               </xsl:for-each>
            </ol>
            <xsl:if test="references">
               <blockquote>
                  <a href="#References">References</a>
                  <br/>
               </blockquote>
            </xsl:if>
            <xsl:if test="//term/history/event[date &gt; $previous]">
               <a href="#vocabchanges">
                  <xsl:text>Changes to vocabulary since previous version</xsl:text>
               </a>
               <p/>
            </xsl:if>
            <hr/>
            <xsl:apply-templates select="body"/>
            <xsl:if test="//term/history/event[date &gt; $previous]">
               <h2>
                  <a name="vocabchanges"/>Changes to vocabulary since previous version</h2>
               <table cellspacing="10">
                  <tr valign="top">
                     <td>
                        <i>Code</i>
                     </td>
                     <td>
                        <i>Action</i>
                     </td>
                     <td>
                        <i>Remedy for existing data</i>
                     </td>
                  </tr>
                  <xsl:for-each select="//term/history/event[date &gt; $previous]">
                     <tr valign="top">
                        <td>
                           <b>
                              <xsl:value-of select="../../code"/>
                           </b>
                        </td>
                        <td>
                           <xsl:value-of select="@type"/>
                        </td>
                        <td>
                           <xsl:value-of select="remedy"/>
                        </td>
                     </tr>
                  </xsl:for-each>
               </table>
               <br/>
            </xsl:if>
             <xsl:apply-templates select="todo|references"/>
         </BODY>
      </HTML>
   </xsl:template>
   <xsl:template match="header">
      <H1>
         <xsl:value-of select="title"/>
      </H1>
      <table cellspacing="10">
         <tr valign="top">
            <th align="left" width="100">
               <xsl:text disable-output-escaping="yes">Date&amp;nbsp;issued:</xsl:text>
            </th>
            <td>
               <xsl:call-template name="format-date">
                  <xsl:with-param name="date">
                     <xsl:value-of select="issued"/>
                  </xsl:with-param>
               </xsl:call-template>
            </td>
         </tr>
         <tr valign="top">
            <th align="left">Status of document:</th>
            <td>
               <xsl:apply-templates select="status"/>
            </td>
         </tr>
         <xsl:if test="status/@endDate  and not(status/@supersededBy = baseName)">
            <tr valign="top">
               <th align="left" width="100">
                  <xsl:choose>
                     <xsl:when test="status/@supersededBy">
                        <xsl:text disable-output-escaping="yes">Superseded&amp;nbsp;by:</xsl:text>
                     </xsl:when>
                     <xsl:when test="status[@code = 'adopted']">Date retired:</xsl:when>
                     <xsl:when test="status[@code != 'adopted']">
                        <xsl:text disable-output-escaping="yes">Date&amp;nbsp;withdrawn:</xsl:text>
                     </xsl:when>
                  </xsl:choose>
               </th>
               <td>
                  <xsl:choose>
                     <xsl:when test="status/@supersededBy">
                        <a>
                           <xsl:attribute name="href">
                              <xsl:value-of select="$baseURL"/>
                              <xsl:value-of select="status/@supersededBy"/>
                              <xsl:text>-</xsl:text>
                              <xsl:value-of select="status/@endDate"/>
                              <xsl:text>.html</xsl:text>
                           </xsl:attribute>
                           <xsl:value-of select="$baseURL"/>
                           <xsl:value-of select="status/@supersededBy"/>
                           <xsl:text>-</xsl:text>
                           <xsl:value-of select="status/@endDate"/>
                           <xsl:text>.html</xsl:text>
                        </a>
                     </xsl:when>
                     <xsl:otherwise>
                        <xsl:call-template name="format-date">
                           <xsl:with-param name="date">
                              <xsl:value-of select="status/@endDate"/>
                           </xsl:with-param>
                        </xsl:call-template>
                     </xsl:otherwise>
                  </xsl:choose>
               </td>
            </tr>
         </xsl:if>
         <tr valign="top">
            <th align="left">This version:</th>
            <td>
               <a>
                  <xsl:attribute name="href">
                     <xsl:value-of select="$baseURL"/>
                     <xsl:value-of select="baseName"/>
                     <xsl:text>-</xsl:text>
                     <xsl:value-of select="issued"/>
                     <xsl:text>.html</xsl:text>
                  </xsl:attribute>
                  <xsl:value-of select="$baseURL"/>
                  <xsl:value-of select="baseName"/>
                  <xsl:text>-</xsl:text>
                  <xsl:value-of select="issued"/>
                  <xsl:text>.html</xsl:text>
               </a>
            </td>
         </tr>
         <xsl:if test="supersedes">
            <tr valign="top">
               <th align="left">
                  <xsl:choose>
                     <xsl:when test="status/@code='adopted'">Supersedes:</xsl:when>
                     <xsl:otherwise>Will supersede:</xsl:otherwise>
                  </xsl:choose>
               </th>
               <td>
                  <a>
                     <xsl:attribute name="href">
                        <xsl:value-of select="$baseURL"/>
                        <xsl:value-of select="baseName"/>
                        <xsl:text>-</xsl:text>
                        <xsl:value-of select="supersedes"/>
                        <xsl:text>.html</xsl:text>
                     </xsl:attribute>
                     <xsl:value-of select="$baseURL"/>
                     <xsl:value-of select="baseName"/>
                     <xsl:text>-</xsl:text>
                     <xsl:value-of select="supersedes"/>
                     <xsl:text>.html</xsl:text>
                  </a>
               </td>
            </tr>
         </xsl:if>
         <xsl:if test="not(status[@endDate]) or status/@supersededBy = baseName">
            <tr valign="top">
               <th align="left">Latest version:</th>
               <td>
                  <a>
                     <xsl:attribute name="href">
                        <xsl:value-of select="$baseURL"/>
                        <xsl:value-of select="baseName"/>
                        <xsl:text>.html</xsl:text>
                     </xsl:attribute>
                     <xsl:value-of select="$baseURL"/>
                     <xsl:value-of select="baseName"/>
                     <xsl:text>.html</xsl:text>
                  </a>
               </td>
            </tr>
         </xsl:if>
         <tr valign="top">
            <th align="left">
               <xsl:text disable-output-escaping="yes">Previous&amp;nbsp;version:</xsl:text>
            </th>
            <td>
               <xsl:choose>
                  <xsl:when test="previousIssued=''">
                     <xsl:text>None.</xsl:text>
                  </xsl:when>
                  <xsl:otherwise>
                     <a>
                        <xsl:attribute name="href">
                           <xsl:value-of select="$baseURL"/>
                           <xsl:value-of select="baseName"/>
                           <xsl:text>-</xsl:text>
                           <xsl:value-of select="previousIssued"/>
                           <xsl:text>.html</xsl:text>
                        </xsl:attribute>
                        <xsl:value-of select="$baseURL"/>
                        <xsl:value-of select="baseName"/>
                        <xsl:text>-</xsl:text>
                        <xsl:value-of select="previousIssued"/>
                        <xsl:text>.html</xsl:text>
                     </a>
                  </xsl:otherwise>
               </xsl:choose>
            </td>
         </tr>
         <tr valign="top">
            <th align="left">Abstract:</th>
            <td>
               <xsl:apply-templates select="abstract/*"/>
            </td>
         </tr>
         <tr valign="top">
            <th align="left">Editors:</th>
            <td>
               <xsl:apply-templates select="editors"/>
            </td>
         </tr>
         <xsl:if test="changes != ''">
            <tr valign="top">
               <th align="left">Changes since previous version:</th>
               <td>
                  <xsl:apply-templates select="changes/*"/>
               </td>
            </tr>
         </xsl:if>
      </table>
      <blockquote>
         <small>
            <xsl:text disable-output-escaping="yes">Copyright &amp;copy; </xsl:text>
            <xsl:value-of select="copyright"/>
            <xsl:text>. This material may be distributed and repurposed subject to the terms and conditions set forth in the </xsl:text>
            <a href="http://creativecommons.org/licenses/by-sa/2.5/" rel="license">Creative Commons
               Attribution-ShareAlike 2.5 License</a>
            <xsl:text>.</xsl:text>
         </small>
      </blockquote>
   </xsl:template>
   <xsl:template match="status">
      <i>
         <xsl:if test="@endDate and @code='adopted'">
            <xsl:text>Retired </xsl:text>
         </xsl:if>
         <xsl:if test="@endDate and @code!='adopted'">
            <xsl:text>Former </xsl:text>
         </xsl:if>
         <xsl:choose>
            <xsl:when test="@code='draft'">
               <xsl:text>Draft </xsl:text>
            </xsl:when>
            <xsl:when test="@code='proposed'">
               <xsl:text>Proposed </xsl:text>
            </xsl:when>
            <xsl:when test="@code='candidate'">
               <xsl:text>Candidate </xsl:text>
            </xsl:when>
            <xsl:when test="@code='adopted'"/>
            <xsl:when test="@code='superseded'">
               <xsl:text>Superseded </xsl:text>
            </xsl:when>
            <xsl:when test="@code='withdrawn'">
               <xsl:text>Withdrawn</xsl:text>
            </xsl:when>
            <xsl:otherwise>
               <xsl:text>Bad status code. </xsl:text>
            </xsl:otherwise>
         </xsl:choose>
         <xsl:choose>
            <xsl:when test="@type='standard'">
               <xsl:text>Standard. </xsl:text>
            </xsl:when>
            <xsl:when test="@type='recommendation'">
               <xsl:text>Recommendation. </xsl:text>
            </xsl:when>
            <xsl:when test="@type='experimental'">
               <xsl:text>Experimental Note. </xsl:text>
            </xsl:when>
            <xsl:when test="@type='informational'">
               <xsl:text>Informational Note. </xsl:text>
            </xsl:when>
            <xsl:when test="@type='implementation'">
               <xsl:text>Implementation Note. </xsl:text>
            </xsl:when>
            <xsl:otherwise>
               <xsl:text>Bad document type. </xsl:text>
            </xsl:otherwise>
         </xsl:choose>
      </i>
      <xsl:choose>
         <xsl:when test="@endDate">
            <xsl:if test="@code != 'adopted'">
               <xsl:text>This document was withdrawn from the OLAC document process.</xsl:text>
            </xsl:if>
            <xsl:if test="@code = 'adopted' and @supersededBy">
               <xsl:text>This document was once adopted by the community, but has now been superseded</xsl:text>
               <xsl:if test="@supersededBy=../baseName"> by a revised version.</xsl:if>
               <xsl:if test="@supersededBy!=../baseName"> by another document.</xsl:if>
            </xsl:if>
            <xsl:if test="@code = 'adopted' and not(@supersededBy)">
               <xsl:text>This document was once adopted by the community, but is now obsolete.</xsl:text>
            </xsl:if>
         </xsl:when>
         <xsl:otherwise>
            <xsl:if test="@code='draft'">
               <xsl:text>This is only a preliminary draft that is still under development; it has not yet been presented to the whole community for review.</xsl:text>
            </xsl:if>
            <xsl:if test="@code='proposed'">
               <xsl:text>This document is in the midst of open review by the community.</xsl:text>
            </xsl:if>
            <xsl:if test="@code='candidate'">
               <xsl:text>This document is on track to be accepted by the community as a  </xsl:text>
               <xsl:if test="@type='standard'">
                  <xsl:text>standard; </xsl:text>
               </xsl:if>
               <xsl:if test="@type='recommendation'">
                  <xsl:text>best practice recommendation; </xsl:text>
               </xsl:if>
               <xsl:if test="@type='informational' or @type='experimental'">
                  <xsl:text>note; </xsl:text>
               </xsl:if>
               <xsl:text>full adoption is awaiting successful
                   implementation.  The version that is ultimately
                   adopted may incorporate changes based on feedback
                   from implementers. </xsl:text>
                <xsl:if test="../supersedes">
                    <xsl:text>Anyone working on a new implementation 
                        should follow this version of the document
                        rather than the one it will supersede when
                        adopted. </xsl:text>
                </xsl:if>
            </xsl:if>
            <xsl:if test="@code='adopted'">
               <xsl:if test="@type='standard'">
                  <xsl:text>This document describes a standard that is currently followed by OLAC archives and services.</xsl:text>
               </xsl:if>
               <xsl:if test="@type='recommendation'">
                  <xsl:text>This document embodies an OLAC consensus concerning best current practice.</xsl:text>
               </xsl:if>
               <xsl:if test="@type='informational'">
                  <xsl:text>This document provides background
                     information related to an OLAC standard,
                     recommendation,
                     or service.</xsl:text>
               </xsl:if>
               <xsl:if test="@type='implementational'">
                  <xsl:text>This document describes
                  a particular approach to implementing an OLAC
                  standard or recommendation.
                  </xsl:text>
               </xsl:if>
               <xsl:if test="@supersededBy=../baseName">
                   <xsl:text> However, a new version of the document
                       has reached Candidate status and is being
                       implemented by early adopters. Please click on
                       the "Latest version" link to see the new version.</xsl:text>
               </xsl:if>
                
            </xsl:if>
         </xsl:otherwise>
      </xsl:choose>
   </xsl:template>
   <xsl:template match="section|subsection">
      <xsl:apply-templates/>
   </xsl:template>
   <xsl:template match="heading">
      <h2>
         <A>
            <xsl:attribute name="name">
               <xsl:value-of select="normalize-space(.)"/>
            </xsl:attribute>
         </A>
         <xsl:number count="section"/>
         <xsl:text>. </xsl:text>
         <xsl:value-of select="."/>
      </h2>
   </xsl:template>
   <xsl:template match="subheading">
      <h3>
         <A>
            <xsl:attribute name="name">
               <xsl:value-of select="normalize-space(.)"/>
            </xsl:attribute>
         </A>
         <xsl:value-of select="."/>
      </h3>
   </xsl:template>
   <xsl:template match="references">
      <a name="References"/>
      <hr/>
      <h2>References</h2>
      <table cellspacing="10">
         <xsl:for-each select="ref">
            <xsl:sort select="@abbrev"/>
            <tr valign="top">
               <td width="150">
                  <xsl:text>[</xsl:text>
                  <a>
                     <xsl:attribute name="name">
                        <xsl:value-of select="@abbrev"/>
                     </xsl:attribute>
                     <xsl:value-of select="@abbrev"/>
                  </a>
                  <xsl:text>]</xsl:text>
               </td>
               <td>
                  <xsl:apply-templates/>
               </td>
            </tr>
         </xsl:for-each>
      </table>
   </xsl:template>
   <!-- The following is a special case just for supporting extraction of the best
      practices in the bpr.xml document -->
   <xsl:template match="element[@name='All elements']">

      <table align="center" cellspacing="12" width="95%">
         <tr valign="top">
            <td width="100">
               <i>Best&#160;practice&#160;</i>
            </td>
            <td>
               <xsl:apply-templates select="bp/*"/>
            </td>
         </tr>
         <xsl:if test="examples/*">
            <tr valign="top">
               <td width="100">
                  <i>Examples</i>
               </td>
               <td>
                  <xsl:apply-templates select="examples/*"/>
               </td>
            </tr>
         </xsl:if>
      </table>
   </xsl:template>
   <xsl:template match="element">
      <h3>
         <A>
            <xsl:attribute name="name">
               <xsl:value-of select="@name"/>
            </xsl:attribute>
         </A>
         <xsl:value-of select="@name"/>
      </h3>
      <table align="center" cellspacing="12" width="95%">
         <tr valign="top">
            <td width="100">
               <i>Definition</i>
            </td>
            <td>
               <xsl:value-of select="definition"/>
            </td>
         </tr>
         <tr valign="top">
            <td width="100">
               <i>Refinements</i>
            </td>
            <td>
               <xsl:choose>
                  <xsl:when test="refinements">
                     <xsl:apply-templates select="refinements/*"/>
                  </xsl:when>
                  <xsl:otherwise>
                     <p>There are no refinements for this element.</p>
                  </xsl:otherwise>
               </xsl:choose>
            </td>
         </tr>
         <tr valign="top">
            <td width="100">
               <i>Schemes</i>
            </td>
            <td>
               <xsl:choose>
                  <xsl:when test="schemes">
                     <xsl:apply-templates select="schemes/*"/>
                  </xsl:when>
                  <xsl:otherwise>
                     <p>There are no encoding schemes for this element.</p>
                  </xsl:otherwise>
               </xsl:choose>
            </td>
         </tr>
         <xsl:if test="bp">
            <tr valign="top">
               <td width="100">
                  <i>Best&#160;practice&#160;</i>
               </td>
               <td>
                  <xsl:apply-templates select="bp/*"/>
               </td>
            </tr>
         </xsl:if>
         <tr valign="top">
            <td width="100">
               <i>Usage&#160;notes&#160;</i>
            </td>
            <td>
               <xsl:apply-templates select="comment/*"/>
            </td>
         </tr>
         <tr valign="top">
            <td width="100">
               <i>Examples</i>
            </td>
            <td>
               <xsl:apply-templates select="examples/*"/>
            </td>
         </tr>
         <xsl:if test="todo">
            <tr valign="top">
               <td width="100">
                  <b>
                     <font color="red">
                        <p>
                           <i>To do</i>
                        </p>
                     </font>
                  </b>
               </td>
               <td>
                  <font color="red">
                     <xsl:apply-templates select="todo/*"/>
                  </font>
               </td>
            </tr>
         </xsl:if>
      </table>
   </xsl:template>
   <xsl:template match="recommendations">
      <xsl:variable name="href" select="concat(substring-before(@href,'.xml'),'.html')"/>
      <xsl:for-each select="document(@href)//element[bp]">
         <h3>
            <A>
               <xsl:attribute name="name">
                  <xsl:value-of select="@name"/>
               </xsl:attribute>
            </A>
            <xsl:value-of select="@name"/>
            <small> (<a href="{$href}#{@name}">more</a>)</small>
         </h3>
         <xsl:for-each select="bp/p">
            <OL>
               <xsl:attribute name="START">
                  <xsl:value-of select="count(preceding::bp/p)+count(preceding-sibling::p)+1"/>
               </xsl:attribute>
               <LI>
                  <xsl:apply-templates select="self::*"/>
               </LI>
            </OL>
         </xsl:for-each>
      </xsl:for-each>
   </xsl:template>
   <xsl:template match="extensions">
       <xsl:for-each select="extension">
         <xsl:variable name="extName">
            <xsl:value-of select="@name"/>
         </xsl:variable>
         <xsl:variable name="schema">
            <xsl:choose>
               <xsl:when test="starts-with(@href, '..')">
                  <xsl:value-of
                     select="concat('http://www.language-archives.org',
                  substring-after(@href, '..'))"
                  />
               </xsl:when>
               <xsl:otherwise>
                  <xsl:value-of select="@href"/>
               </xsl:otherwise>
            </xsl:choose>
         </xsl:variable>
         <xsl:variable name="ns">
            <xsl:value-of select="concat(@ns, ':')"/>
         </xsl:variable>
         <!--	<xsl:for-each select="document(@href)//*[@name=$extName]//olac:extension">  -->
         <xsl:for-each select="document(@href)//olac:olac-extension[olac:shortName=$extName]">
            <h3>
               <A>
                  <xsl:attribute name="name">
                     <xsl:value-of select="$extName"/>
                  </xsl:attribute>
               </A>
               <xsl:value-of select="olac:longName"/>
            </h3>
            <table align="center" cellspacing="12" width="95%">
               <tr valign="top">
                  <td width="100">
                     <i>Name:</i>
                  </td>
                  <td>
                     <xsl:value-of select="concat($ns, $extName)"/>
                  </td>
               </tr>
               <tr valign="top">
                  <td width="100">
                     <i>Date:</i>
                  </td>
                  <td>
                     <xsl:value-of select="olac:versionDate"/>
                  </td>
               </tr>
               <tr valign="top">
                  <td width="100">
                     <i>Applies to:</i>
                  </td>
                  <td>
                     <xsl:for-each select="olac:appliesTo">
                        <xsl:if test="preceding::olac:appliesTo">
                           <xsl:text>, </xsl:text>
                        </xsl:if>
                        <xsl:value-of select="."/>
                     </xsl:for-each>
                  </td>
               </tr>
               <tr valign="top">
                  <td width="100">
                     <i>Description:</i>
                  </td>
                  <td>
                     <xsl:value-of select="olac:description"/>
                  </td>
               </tr>
               <tr valign="top">
                  <td width="100">
                     <i>Documentation:</i>
                  </td>
                  <td>
                     <a href="{olac:documentation}">
                        <xsl:value-of select="olac:documentation"/>
                     </a>
                  </td>
               </tr>
               <tr valign="top">
                  <td width="100">
                     <i>Schema:</i>
                  </td>
                  <td>
                     <a href="{$schema}">
                        <xsl:value-of select="$schema"/>
                     </a>
                  </td>
               </tr>
            </table>
         </xsl:for-each>
      </xsl:for-each>
   </xsl:template>
   <xsl:template match="term[@status!='retired']">
      <h3>
         <A>
            <xsl:attribute name="name">
               <xsl:value-of select="code"/>
            </xsl:attribute>
         </A>
         <xsl:value-of select="code"/>
      </h3>
      <table align="center" cellspacing="12" width="95%">
         <tr valign="top">
            <td width="100">
               <i>Name</i>
            </td>
            <td>
               <xsl:value-of select="name"/>
            </td>
         </tr>
         <tr valign="top">
            <td width="100">
               <i>Definition</i>
            </td>
            <td>
               <xsl:value-of select="definition"/>
            </td>
         </tr>
         <xsl:if test="comment">
            <tr valign="top">
               <td width="100">
                  <i>Comments</i>
               </td>
               <td>
                  <xsl:apply-templates select="comment/*"/>
               </td>
            </tr>
         </xsl:if>
         <xsl:if test="examples">
            <tr valign="top">
               <td width="100">
                  <i>Examples</i>
               </td>
               <td>
                  <xsl:apply-templates select="examples/*"/>
               </td>
            </tr>
         </xsl:if>
         <xsl:if test="todo">
            <tr valign="top">
               <td width="100">
                  <b>
                     <font color="red">
                        <i>To do</i>
                     </font>
                  </b>
               </td>
               <td>
                  <font color="red">
                     <xsl:apply-templates select="todo/*"/>
                  </font>
               </td>
            </tr>
         </xsl:if>
         <xsl:if test="term">
            <tr valign="top">
               <td width="100">
                  <i>Subterms</i>
               </td>
               <td>
                  <xsl:apply-templates mode="subterm" select="term"/>
               </td>
            </tr>
         </xsl:if>
      </table>
   </xsl:template>
   <xsl:template match="term[@status='retired']"/>
   <xsl:template match="term[@status!='retired']" mode="subterm">
      <h4>
         <A>
            <xsl:attribute name="name">
               <xsl:value-of select="code"/>
            </xsl:attribute>
         </A>
         <xsl:value-of select="code"/>
      </h4>
      <table cellspacing="12">
         <tr valign="top">
            <td width="100">
               <i>Name</i>
            </td>
            <td>
               <xsl:value-of select="name"/>
            </td>
         </tr>
         <tr valign="top">
            <td width="100">
               <i>Definition</i>
            </td>
            <td>
               <xsl:value-of select="definition"/>
            </td>
         </tr>
         <xsl:if test="comment">
            <tr valign="top">
               <td width="100">
                  <i>Comments</i>
               </td>
               <td>
                  <xsl:apply-templates select="comment/*"/>
               </td>
            </tr>
         </xsl:if>
         <xsl:if test="examples">
            <tr valign="top">
               <td width="100">
                  <i>Examples</i>
               </td>
               <td>
                  <xsl:apply-templates select="examples/*"/>
               </td>
            </tr>
         </xsl:if>
         <xsl:if test="todo">
            <tr valign="top">
               <td width="100">
                  <b>
                     <font color="red">
                        <i>To do</i>
                     </font>
                  </b>
               </td>
               <td>
                  <font color="red">
                     <xsl:apply-templates select="todo/*"/>
                  </font>
               </td>
            </tr>
         </xsl:if>
      </table>
   </xsl:template>
   <xsl:template match="term[@status='retired']" mode="subterm"/>
   <xsl:template match="event" mode="history"/>
   <xsl:template match="todo">
      <hr/>
      <h2>
         <font color="red">To do</font>
      </h2>
      <font color="red">
         <xsl:apply-templates/>
      </font>
   </xsl:template>
   <xsl:template match="url">
      <a>
         <xsl:attribute name="href">
            <xsl:apply-templates/>
         </xsl:attribute>
         <xsl:apply-templates/>
      </a>
   </xsl:template>
   <xsl:template match="cit">
      <xsl:text>[</xsl:text>
      <a>
         <xsl:choose>
            <xsl:when test="key('Ref',.)/url">
               <xsl:attribute name="href">
                  <xsl:value-of select="key('Ref',.)/url"/>
               </xsl:attribute>
            </xsl:when>
            <xsl:otherwise>
               <xsl:attribute name="href">
                  <xsl:text>#</xsl:text>
                  <xsl:value-of select="."/>
               </xsl:attribute>
            </xsl:otherwise>
         </xsl:choose>
         <xsl:value-of select="."/>
      </a>
      <xsl:text>]</xsl:text>
   </xsl:template>
   <xsl:template match="xref">
      <a>
         <xsl:attribute name="href">
            <xsl:text>#</xsl:text>
            <xsl:value-of select="."/>
         </xsl:attribute>
         <xsl:value-of select="."/>
      </a>
   </xsl:template>
   <xsl:template match="eg">
      <pre style="margin-left: 18pt">
      <xsl:apply-templates/>
    </pre>
   </xsl:template>
   <xsl:template match="refinements/dl | schemes/dl">
      <xsl:apply-templates mode="table" select="self::dl"/>
   </xsl:template>
   <xsl:template match="dl">
      <blockquote>
         <dl>
            <xsl:apply-templates/>
         </dl>
      </blockquote>
   </xsl:template>
   <xsl:template match="dt">
      <dt>
         <b>
            <xsl:apply-templates/>
         </b>
      </dt>
   </xsl:template>
   <xsl:template match="dl[@style='table']">
      <blockquote>
         <xsl:apply-templates mode="table" select="self::dl"/>
      </blockquote>
   </xsl:template>
   <xsl:template match="dl" mode="table">
      <table cellpadding="4">
         <xsl:for-each select="dt">
            <tr valign="top">
               <td>
                  <xsl:value-of select="."/>
                  <xsl:text disable-output-escaping="yes">&amp;nbsp;&amp;nbsp;</xsl:text>
               </td>
               <td>
                  <xsl:apply-templates select="./following::dd[1]/*"/>
               </td>
            </tr>
         </xsl:for-each>
      </table>
   </xsl:template>
   <xsl:template match="*">
      <xsl:copy>
         <xsl:copy-of select="@*"/>
         <xsl:apply-templates/>
      </xsl:copy>
   </xsl:template>
   <xsl:template name="format-date">
      <xsl:param name="date"/>
      <xsl:value-of select="substring($date,1,4)"/>
      <xsl:text>-</xsl:text>
      <xsl:value-of select="substring($date,5,2)"/>
      <xsl:text>-</xsl:text>
      <xsl:value-of select="substring($date,7,2)"/>
   </xsl:template>
</xsl:stylesheet>
