<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<axsl:stylesheet xmlns:axsl="http://www.w3.org/1999/XSL/Transform" xmlns:sch="http://www.ascc.net/xml/schematron" version="1.0" xmlns:xs="http://www.w3.org/2001/XMLSchema-instance" xs:dummy-for-xmlns="" xmlns:oai="http://www.openarchives.org/OAI/2.0/" oai:dummy-for-xmlns="" xmlns:olac="http://www.language-archives.org/OLAC/1.0/" olac:dummy-for-xmlns="" xmlns:olac-archive="http://www.language-archives.org/OLAC/1.0/olac-archive" olac-archive:dummy-for-xmlns="" xmlns:dc="http://purl.org/dc/elements/1.1/" dc:dummy-for-xmlns="" xmlns:dcterms="http://purl.org/dc/terms/" dcterms:dummy-for-xmlns="">
<axsl:output method="html"/>
<axsl:template mode="schematron-get-full-path" match="*|@*">
<axsl:apply-templates mode="schematron-get-full-path" select="parent::*"/>
<axsl:text>/</axsl:text>
<axsl:if test="count(. | ../@*) = count(../@*)">@</axsl:if>
<axsl:value-of select="name()"/>
<axsl:text>[</axsl:text>
<axsl:value-of select="1+count(preceding-sibling::*[name()=name(current())])"/>
<axsl:text>]</axsl:text>
</axsl:template>
<axsl:template match="/">
<html>
<style>
         a:link    { color: black}
         a:visited { color: gray}
         a:active  { color: #FF0088}
         h3        { background-color:black; color:white;
                     font-family:Arial Black; font-size:12pt; }
         h3.linked { background-color:black; color:white;
                     font-family:Arial Black; font-size:12pt; }
      </style>
<h2 title="Schematron contact-information is at the end of                   this page">
<font color="#FF0080">Schematron</font> Report
      </h2>
<h1 title=" "/>
<div class="errors">
<ul>
<a title="Link to User Documentation:" target="SRDOCO" href="http://www.language-archives.org/OLAC/repositories.html#Identify">
<h3 class="linked">OLAC-PMH Identify requirement</h3>
</a>
<axsl:apply-templates mode="M6" select="/"/>
<a title="Link to User Documentation:" target="SRDOCO" href="http://www.language-archives.org/OLAC/repositories.html#ListMetadataFormats">
<h3 class="linked">OLAC-PMH ListMetadataFormats requirement</h3>
</a>
<axsl:apply-templates mode="M7" select="/"/>
<a title="Link to User Documentation:" target="SRDOCO" href="http://www.language-archives.org/OLAC/repositories.html#ListRecords">
<h3 class="linked">OLAC-PMH ListRecords requirement</h3>
</a>
<axsl:apply-templates mode="M8" select="/"/>
</ul>
</div>
<hr color="#FF0080"/>
<p>
<font size="2">Schematron Report by David Carlisle.
      <a title="Link to the home page of the Schematron,                  a tree-pattern schema language" href="http://www.ascc.net/xml/resource/schematron/schematron.html">
<font color="#FF0080">The Schematron</font>
</a> by
      <a title="Email to Rick Jelliffe (pronounced RIK JELIF)" href="mailto:ricko@gate.sinica.edu.tw">Rick Jelliffe</a>,
      <a title="Link to home page of Academia Sinica" href="http://www.sinica.edu.tw">Academia Sinica Computing Centre</a>.
      </font>
</p>
</html>
</axsl:template>
<axsl:template mode="M6" priority="4000" match="oai:Identify">
<axsl:choose>
<axsl:when test="//oai:description/olac-archive:olac-archive"/>
<axsl:otherwise>
<li>
<a title="Link to where this pattern was expected" target="out" href="schematron-out.html#{generate-id(.)}">
<i/>ERROR: Identify: description element has no olac-archive element<b/>
</a>
</li>
</axsl:otherwise>
</axsl:choose>
<axsl:if test="//oai:description/olac-archive:olac-archive">
<li>
<a title="Link to where this pattern was found" target="out" href="schematron-out.html#{generate-id(.)}">
<i/>Identify: description element has an olac-archive element - OK<b/>
</a>
</li>
</axsl:if>
<axsl:apply-templates mode="M6"/>
</axsl:template>
<axsl:template mode="M6" priority="-1" match="text()"/>
<axsl:template mode="M7" priority="4000" match="oai:ListMetadataFormats">
<axsl:choose>
<axsl:when test="//oai:metadataFormat/oai:metadataPrefix[normalize-space(.) ='olac']"/>
<axsl:otherwise>
<li>
<a title="Link to where this pattern was expected" target="out" href="schematron-out.html#{generate-id(.)}">
<i/>ERROR: ListMetadataFormats: olac missing<b/>
</a>
</li>
</axsl:otherwise>
</axsl:choose>
<axsl:if test="//oai:metadataFormat/oai:metadataPrefix[normalize-space(.) = 'olac']">
<li>
<a title="Link to where this pattern was found" target="out" href="schematron-out.html#{generate-id(.)}">
<i/>ListMetadataFormats: olac present - OK<b/>
</a>
</li>
</axsl:if>
<axsl:apply-templates mode="M7"/>
</axsl:template>
<axsl:template mode="M7" priority="3999" match="oai:ListMetadataFormats/oai:metadataFormat">
<axsl:choose>
<axsl:when test="count(oai:metadataPrefix) = 1"/>
<axsl:otherwise>
<li>
<a title="Link to where this pattern was expected" target="out" href="schematron-out.html#{generate-id(.)}">
<i/>ERROR: ListMetadataFormats: incorrect number of metadataPrefix elements<b/>
</a>
</li>
</axsl:otherwise>
</axsl:choose>
<axsl:if test="count(oai:metadataPrefix) = 1">
<li>
<a title="Link to where this pattern was found" target="out" href="schematron-out.html#{generate-id(.)}">
<i/>ListMetadataFormats: correct number of metadataPrefix elements - OK<b/>
</a>
</li>
</axsl:if>
<axsl:apply-templates mode="M7"/>
</axsl:template>
<axsl:template mode="M7" priority="-1" match="text()"/>
<axsl:template mode="M8" priority="4000" match="oai:ListRecords">
<axsl:choose>
<axsl:when test="//oai:record"/>
<axsl:otherwise>
<li>
<a title="Link to where this pattern was expected" target="out" href="schematron-out.html#{generate-id(.)}">
<i/>ERROR: ListRecords: no record element was found<b/>
</a>
</li>
</axsl:otherwise>
</axsl:choose>
<axsl:if test="//oai:record">
<li>
<a title="Link to where this pattern was found" target="out" href="schematron-out.html#{generate-id(.)}">
<i/>ListRecords: at least one record was found - OK<b/>
</a>
</li>
</axsl:if>
<axsl:apply-templates mode="M8"/>
</axsl:template>
<axsl:template mode="M8" priority="3999" match="oai:ListRecords/oai:record">
<axsl:choose>
<axsl:when test="count(oai:metadata) = 1"/>
<axsl:otherwise>
<li>
<a title="Link to where this pattern was expected" target="out" href="schematron-out.html#{generate-id(.)}">
<i/>ERROR: ListRecords: a record does not have exactly one metadata element<b/>
</a>
</li>
</axsl:otherwise>
</axsl:choose>
<axsl:apply-templates mode="M8"/>
</axsl:template>
<axsl:template mode="M8" priority="3998" match="oai:ListRecords/oai:record/oai:metadata">
<axsl:choose>
<axsl:when test="olac:olac"/>
<axsl:otherwise>
<li>
<a title="Link to where this pattern was expected" target="out" href="schematron-out.html#{generate-id(.)}">
<i/>ERROR: ListRecords: a metadata element has no olac sub-element<b/>
</a>
</li>
</axsl:otherwise>
</axsl:choose>
<axsl:apply-templates mode="M8"/>
</axsl:template>
<axsl:template mode="M8" priority="-1" match="text()"/>
<axsl:template priority="-1" match="text()"/>
</axsl:stylesheet>
