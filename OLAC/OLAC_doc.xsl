<?xml version="1.0"?>
<!-- edited with XML Spy v4.3 (http://www.xmlspy.com) by Gary Simons (SIL International) -->
<!--Stylesheet for OLAC  documents

     G. Simons, 21 Feb 2001
     Last modified: 3 Dec 2002
-->
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:olac="http://www.language-archives.org/OLAC/1.0/olac-extension.xsd" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:xs="http://www.w3.org/2001/XMLSchema" version="1.0">
	<xsl:output method="html" version="4.0" doctype-public="-//W3C//DTD HTML 4.0 Transitional//EN" doctype-system="http://www.w3.org/TR/REC-html40/loose.dtd" encoding="ISO-8859-1"/>
	<xsl:strip-space elements="*"/>
	<xsl:preserve-space elements="eg"/>
	<xsl:variable name="baseURL">
		<xsl:text>http://www.language-archives.org/</xsl:text>
		<xsl:choose>
			<xsl:when test="//status[@type='standard']">
				<xsl:text>OLAC/</xsl:text>
			</xsl:when>
			<xsl:when test="//status[@type='recommendation']">
				<xsl:text>REC/</xsl:text>
			</xsl:when>
			<xsl:when test="//status[@type='experimental' or @type='informational' or @type='implementation']">
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
				<meta name="Title">
					<xsl:attribute name="content"><xsl:value-of select="header/title"/></xsl:attribute>
				</meta>
				<meta name="Creator">
					<xsl:attribute name="content"><xsl:value-of select="header/editors"/></xsl:attribute>
				</meta>
				<meta name="Description">
					<xsl:attribute name="content"><xsl:value-of select="header/abstract"/></xsl:attribute>
				</meta>
				<meta name="Publisher" content="OLAC (Open Language Archives Community)"/>
				<meta name="Date">
					<xsl:attribute name="content"><xsl:value-of select="header/issued"/></xsl:attribute>
				</meta>
				<STYLE>
          BODY       { MARGIN:10px; BACKGROUND: white; COLOR: navy; 
                              FONT-FAMILY: sans-serif; FONT-SIZE: 12pt }
          H1    {FONT-SIZE: 24pt }
          H2    {FONT-SIZE: 18pt }
          H3    {FONT-SIZE: 16pt }

        </STYLE>
			</HEAD>
			<BODY>
				<hr/>
				<H1>
					<xsl:value-of select="header/title"/>
				</H1>
				<table cellspacing="10">
					<tr valign="top">
						<th width="100" align="left">Date issued:</th>
						<td>
							<xsl:value-of select="substring(header/issued,1,4)"/>
							<xsl:text>-</xsl:text>
							<xsl:value-of select="substring(header/issued,5,2)"/>
							<xsl:text>-</xsl:text>
							<xsl:value-of select="substring(header/issued,7,2)"/>
						</td>
					</tr>
					<tr valign="top">
						<th align="left">Status of document:</th>
						<td>
							<i>
								<xsl:choose>
									<xsl:when test="header/status[@code='draft']">
										<xsl:text>Draft </xsl:text>
									</xsl:when>
									<xsl:when test="header/status[@code='proposed']">
										<xsl:text>Proposed </xsl:text>
									</xsl:when>
									<xsl:when test="header/status[@code='candidate']">
										<xsl:text>Candidate </xsl:text>
									</xsl:when>
									<xsl:when test="header/status[@code='adopted']"/>
									<xsl:when test="header/status[@code='superseded']">
										<xsl:text>Superseded </xsl:text>
									</xsl:when>
									<xsl:when test="header/status[@code='withdrawn']">
										<xsl:text>Withdrawn</xsl:text>
									</xsl:when>
									<xsl:otherwise>
										<xsl:text>Bad status code. </xsl:text>
									</xsl:otherwise>
								</xsl:choose>
								<xsl:choose>
									<xsl:when test="header/status[@type='standard']">
										<xsl:text>Standard. </xsl:text>
									</xsl:when>
									<xsl:when test="header/status[@type='recommendation']">
										<xsl:text>Recommendation. </xsl:text>
									</xsl:when>
									<xsl:when test="header/status[@type='experimental']">
										<xsl:text>Experimental Note. </xsl:text>
									</xsl:when>
									<xsl:when test="header/status[@type='informational']">
										<xsl:text>Informational Note. </xsl:text>
									</xsl:when>
									<xsl:when test="header/status[@type='implementation']">
										<xsl:text>Implementation Note.</xsl:text>
									</xsl:when>
									<xsl:otherwise>
										<xsl:text>Bad document type. </xsl:text>
									</xsl:otherwise>
								</xsl:choose>
							</i>
							<xsl:choose>
								<xsl:when test="header/status[@code='draft']">
									<xsl:text>This is only a preliminary draft that is still under development; it has not yet been presented to the whole community for review.</xsl:text>
								</xsl:when>
								<xsl:when test="header/status[@code='proposed']">
									<xsl:text>This document is in the midst of open review by the community.</xsl:text>
								</xsl:when>
								<xsl:when test="header/status[@code='candidate']">
									<xsl:text>This document is on track to be accepted by the community as a  </xsl:text>
									<xsl:if test="header/status[@type='standard']">
										<xsl:text>standard;</xsl:text>
									</xsl:if>
									<xsl:if test="header/status[@type='recommendation']">
										<xsl:text>best practice recommendation;</xsl:text>
									</xsl:if>
									<xsl:if test="header/status[@type='informational' or @type='experimental']">
										<xsl:text>note;</xsl:text>
									</xsl:if>
									<xsl:text>full adoption is awaiting successful implementation.  The version that is submitted for final review and adoption may incorporate changes based on experience gained in implementation.</xsl:text>
								</xsl:when>
								<xsl:when test="header/status[@code='adopted']">
									<xsl:if test="header/status[@type='standard']">
										<xsl:text>This document describes a standard that is currently followed by OLAC data providers and service providers.</xsl:text>
									</xsl:if>
									<xsl:if test="header/status[@type='recommendation']">
										<xsl:text>This document embodies an OLAC consensus concerning best current practice.</xsl:text>
									</xsl:if>
								</xsl:when>
								<xsl:when test="header/status[@code='superseded']">
									<xsl:text>This document has now been superseded by another.</xsl:text>
								</xsl:when>
								<xsl:when test="header/status[@code='withdrawn']">
									<xsl:text>This document was withdrawn from the OLAC document process without being adopted.</xsl:text>
								</xsl:when>
							</xsl:choose>
						</td>
					</tr>
					<tr valign="top">
						<th align="left">This version:</th>
						<td>
							<a>
								<xsl:attribute name="href"><xsl:value-of select="$baseURL"/><xsl:value-of select="header/baseName"/><xsl:text>-</xsl:text><xsl:value-of select="header/issued"/><xsl:text>.html</xsl:text></xsl:attribute>
								<xsl:value-of select="$baseURL"/>
								<xsl:value-of select="header/baseName"/>
								<xsl:text>-</xsl:text>
								<xsl:value-of select="header/issued"/>
								<xsl:text>.html</xsl:text>
							</a>
						</td>
					</tr>
					<tr valign="top">
						<th align="left">Latest version:</th>
						<td>
							<a>
								<xsl:attribute name="href"><xsl:value-of select="$baseURL"/><xsl:value-of select="header/baseName"/><xsl:text>.html</xsl:text></xsl:attribute>
								<xsl:value-of select="$baseURL"/>
								<xsl:value-of select="header/baseName"/>
								<xsl:text>.html</xsl:text>
							</a>
						</td>
					</tr>
					<tr valign="top">
						<th align="left">Previous version:</th>
						<td>
							<xsl:choose>
								<xsl:when test='header[previousIssued=""]'>
									<xsl:text>None.</xsl:text>
								</xsl:when>
								<xsl:otherwise>
									<a>
										<xsl:attribute name="href"><xsl:value-of select="$baseURL"/><xsl:value-of select="header/baseName"/><xsl:text>-</xsl:text><xsl:value-of select="header/previousIssued"/><xsl:text>.html</xsl:text></xsl:attribute>
										<xsl:value-of select="$baseURL"/>
										<xsl:value-of select="header/baseName"/>
										<xsl:text>-</xsl:text>
										<xsl:value-of select="header/previousIssued"/>
										<xsl:text>.html</xsl:text>
									</a>
								</xsl:otherwise>
							</xsl:choose>
						</td>
					</tr>
					<tr valign="top">
						<th align="left">Abstract:</th>
						<td>
							<xsl:apply-templates select="header/abstract/*"/>
						</td>
					</tr>
					<tr valign="top">
						<th align="left">Editors:</th>
						<td>
							<xsl:apply-templates select="header/editors"/>
						</td>
					</tr>
					<xsl:if test='header[changes!=""]'>
						<tr valign="top">
							<th align="left">Changes since previous version:</th>
							<td>
								<xsl:apply-templates select="header/changes/*"/>
							</td>
						</tr>
					</xsl:if>
				</table>
				<blockquote>
					<small>
						<xsl:text>Copyright &#169; </xsl:text>
						<xsl:value-of select="header/copyright"/>
						<xsl:text>. This material may be distributed only subject to the terms and conditions set forth in the Open Publication License, v1.0 or later (the latest version is presently available at </xsl:text>
						<a href="http://www.opencontent.org/openpub/">
							<xsl:text>http://www.opencontent.org/openpub/</xsl:text>
						</a>
						<xsl:text>).</xsl:text>
					</small>
				</blockquote>
				<hr/>
				<h3>Table of contents</h3>
				<ol>
					<xsl:for-each select="body/section">
						<LI>
							<A>
								<xsl:attribute name="href">#<xsl:value-of select="normalize-space(heading)"/></xsl:attribute>
								<xsl:value-of select="heading"/>
							</A>
							<xsl:if test='subsection|element|term[@status!="retired"]|extensions'>
								<UL>
									<xsl:for-each select="subsection">
										<LI>
											<A>
												<xsl:attribute name="href">#<xsl:value-of select="normalize-space(subheading)"/></xsl:attribute>
												<xsl:value-of select="subheading"/>
											</A>
										</LI>
									</xsl:for-each>
									<xsl:for-each select="element">
										<LI>
											<A>
												<xsl:attribute name="href">#<xsl:value-of select="tag"/></xsl:attribute>
												<xsl:value-of select="tag"/>
											</A>
										</LI>
									</xsl:for-each>
									<xsl:for-each select='term[@status!="retired"]'>
										<LI>
											<A>
												<xsl:attribute name="href">#<xsl:value-of select="code"/></xsl:attribute>
												<xsl:value-of select="code"/>
											</A>
											<xsl:if test='term[@status!="retired"]'>
												<UL>
													<xsl:for-each select='term[@status!="retired"]'>
														<LI>
															<A>
																<xsl:attribute name="href">#<xsl:value-of select="code"/></xsl:attribute>
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
												<xsl:value-of select="document(@href)//olac:olac-extension[olac:shortName=$extName]/olac:longName"/>
											</A>
											<xsl:value-of select="concat(' [', @ns, ':', @name, ']')"/>
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
	<xsl:template match="header"/>
	<xsl:template match="section|subsection">
		<xsl:apply-templates/>
	</xsl:template>
	<xsl:template match="heading">
		<h2>
			<A>
				<xsl:attribute name="name"><xsl:value-of select="normalize-space(.)"/></xsl:attribute>
			</A>
			<xsl:number count="section"/>
			<xsl:text>. </xsl:text>
			<xsl:value-of select="."/>
		</h2>
	</xsl:template>
	<xsl:template match="subheading">
		<h3>
			<A>
				<xsl:attribute name="name"><xsl:value-of select="normalize-space(.)"/></xsl:attribute>
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
							<xsl:attribute name="name"><xsl:value-of select="@abbrev"/></xsl:attribute>
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
	<xsl:template match="element">
		<h3>
			<A>
				<xsl:attribute name="name"><xsl:value-of select="tag"/></xsl:attribute>
			</A>
			<xsl:value-of select="tag"/>
		</h3>
		<table width="95%" align="center" cellspacing="12">
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
			<tr valign="top">
				<td width="100">
					<i>Comments</i>
				</td>
				<td>
					<xsl:apply-templates select="comment/*"/>
				</td>
			</tr>
			<tr valign="top">
				<td width="100">
					<i>Attributes</i>
				</td>
				<td>
					<xsl:choose>
						<xsl:when test="attributes">
							<xsl:apply-templates select="attributes/*"/>
							<p>
								<xsl:text>Also  </xsl:text>
								<i>lang</i>
								<xsl:text> and  </xsl:text>
								<i>scheme</i>
								<xsl:text> (see section 2).  </xsl:text>
							</p>
						</xsl:when>
						<xsl:otherwise>
							<xsl:text>Only  </xsl:text>
							<i>lang</i>
							<xsl:text> and  </xsl:text>
							<i>scheme</i>
							<xsl:text> (see section 2).  </xsl:text>
						</xsl:otherwise>
					</xsl:choose>
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
	<xsl:template match="extensions">
		<xsl:for-each select="extension">
			<xsl:variable name="extName">
				<xsl:value-of select="@name"/>
			</xsl:variable>
			<xsl:variable name="schema">
				<xsl:value-of select="@href"/>
			</xsl:variable>
			<xsl:variable name="ns">
				<xsl:value-of select="concat(@ns, ':')"/>
			</xsl:variable>
			<!--	<xsl:for-each select="document(@href)//*[@name=$extName]//olac:extension">  -->
			<xsl:for-each select="document(@href)//olac:olac-extension[olac:shortName=$extName]">
				<h3>
					<A>
						<xsl:attribute name="name"><xsl:value-of select="$extName"/></xsl:attribute>
					</A>
					<xsl:value-of select="olac:longName"/>
				</h3>
				<table width="95%" align="center" cellspacing="12">
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
	<xsl:template match='term[@status!="retired"]'>
		<h3>
			<A>
				<xsl:attribute name="name"><xsl:value-of select="code"/></xsl:attribute>
			</A>
			<xsl:value-of select="code"/>
		</h3>
		<table width="95%" align="center" cellspacing="12">
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
	<xsl:template match='term[@status="retired"]'/>
	<xsl:template match='term[@status!="retired"]' mode="subterm">
		<h4>
			<A>
				<xsl:attribute name="name"><xsl:value-of select="code"/></xsl:attribute>
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
	<xsl:template match='term[@status="retired"]' mode="subterm"/>
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
			<xsl:attribute name="href"><xsl:apply-templates/></xsl:attribute>
			<xsl:apply-templates/>
		</a>
	</xsl:template>
	<xsl:template match="cit">
		<xsl:text>[</xsl:text>
		<a>
			<xsl:attribute name="href"><xsl:text>#</xsl:text><xsl:value-of select="."/></xsl:attribute>
			<xsl:value-of select="."/>
		</a>
		<xsl:text>]</xsl:text>
	</xsl:template>
	<xsl:template match="xref">
		<a>
			<xsl:attribute name="href"><xsl:text>#</xsl:text><xsl:value-of select="."/></xsl:attribute>
			<xsl:value-of select="."/>
		</a>
	</xsl:template>
	<xsl:template match="eg">
		<pre style="margin-left: 12pt">
			<xsl:apply-templates/>
		</pre>
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
		</blockquote>
	</xsl:template>
	<xsl:template match="*">
		<xsl:copy>
			<xsl:copy-of select="@*"/>
			<xsl:apply-templates/>
		</xsl:copy>
	</xsl:template>
</xsl:stylesheet>
