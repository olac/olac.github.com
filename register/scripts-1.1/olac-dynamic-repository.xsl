<?xml version="1.0" standalone="yes"?>
<axsl:stylesheet xmlns:axsl="http://www.w3.org/1999/XSL/Transform" xmlns:sch="http://www.ascc.net/xml/schematron" xmlns:xs="http://www.w3.org/2001/XMLSchema-instance" xmlns:oai="http://www.openarchives.org/OAI/2.0/" xmlns:olac="http://www.language-archives.org/OLAC/1.1/" xmlns:olac-archive="http://www.language-archives.org/OLAC/1.1/olac-archive" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" version="1.0" xs:dummy-for-xmlns="" oai:dummy-for-xmlns="" olac:dummy-for-xmlns="" olac-archive:dummy-for-xmlns="" dc:dummy-for-xmlns="" dcterms:dummy-for-xmlns="">
  <axsl:output method="text"/>
  <axsl:template match="*|@*" mode="schematron-get-full-path">
    <axsl:apply-templates select="parent::*" mode="schematron-get-full-path"/>
    <axsl:text>/</axsl:text>
    <axsl:if test="count(. | ../@*) = count(../@*)">@</axsl:if>
    <axsl:value-of select="name()"/>
    <axsl:text>[</axsl:text>
    <axsl:value-of select="1+count(preceding-sibling::*[name()=name(current())])"/>
    <axsl:text>]</axsl:text>
  </axsl:template>
  <axsl:template match="/">
<axsl:apply-templates select="/" mode="M6"/><axsl:apply-templates select="/" mode="M7"/><axsl:apply-templates select="/" mode="M8"/></axsl:template>
  <axsl:template match="oai:Identify" priority="4000" mode="M6">
    <axsl:choose>
      <axsl:when test="//oai:description/olac-archive:olac-archive"/>
      <axsl:otherwise>In pattern //oai:description/olac-archive:olac-archive:
   ERROR: Identify: description element has no olac-archive element
</axsl:otherwise>
    </axsl:choose>
    <axsl:if test="//oai:description/olac-archive:olac-archive">In pattern //oai:description/olac-archive:olac-archive:
   Identify: description element has an olac-archive element - OK
</axsl:if>
    <axsl:apply-templates mode="M6"/>
  </axsl:template>
  <axsl:template match="oai:Identify/oai:adminEmail" priority="3999" mode="M6">
    <axsl:choose>
      <axsl:when test="../oai:description/olac-archive:olac-archive/olac-archive:participant/@email=normalize-space(.)"/>
      <axsl:otherwise>In pattern ../oai:description/olac-archive:olac-archive/olac-archive:participant/@email=normalize-space(.):
   ERROR: Identify: adminEmail<axsl:text xml:space="preserve"> </axsl:text><axsl:value-of select="."/><axsl:text xml:space="preserve"> </axsl:text>doesn't appear in a participant element.
</axsl:otherwise>
    </axsl:choose>
    <axsl:if test="../oai:description/olac-archive:olac-archive/olac-archive:participant/@email=normalize-space(.)">In pattern ../oai:description/olac-archive:olac-archive/olac-archive:participant/@email=normalize-space(.):
   Identify: adminEmail<axsl:text xml:space="preserve"> </axsl:text><axsl:value-of select="."/><axsl:text xml:space="preserve"> </axsl:text>appears in a participant element with role<axsl:text xml:space="preserve"> </axsl:text><axsl:value-of select="../oai:description/olac-archive:olac-archive/olac-archive:participant[@email=normalize-space(current())]/@role"/><axsl:text xml:space="preserve"> </axsl:text>- OK
</axsl:if>
    <axsl:apply-templates mode="M6"/>
  </axsl:template>
  <axsl:template match="text()" priority="-1" mode="M6"/>
  <axsl:template match="oai:ListMetadataFormats" priority="4000" mode="M7">
    <axsl:choose>
      <axsl:when test="//oai:metadataFormat/oai:metadataPrefix[normalize-space(.) ='olac']"/>
      <axsl:otherwise>In pattern //oai:metadataFormat/oai:metadataPrefix[normalize-space(.) ='olac']:
   ERROR: ListMetadataFormats: olac missing
</axsl:otherwise>
    </axsl:choose>
    <axsl:if test="//oai:metadataFormat/oai:metadataPrefix[normalize-space(.) = 'olac']">In pattern //oai:metadataFormat/oai:metadataPrefix[normalize-space(.) = 'olac']:
   ListMetadataFormats: olac present - OK
</axsl:if>
    <axsl:apply-templates mode="M7"/>
  </axsl:template>
  <axsl:template match="oai:ListMetadataFormats/oai:metadataFormat" priority="3999" mode="M7">
    <axsl:choose>
      <axsl:when test="count(oai:metadataPrefix) = 1"/>
      <axsl:otherwise>In pattern count(oai:metadataPrefix) = 1:
   ERROR: ListMetadataFormats: incorrect number of metadataPrefix elements
</axsl:otherwise>
    </axsl:choose>
    <axsl:if test="count(oai:metadataPrefix) = 1">In pattern count(oai:metadataPrefix) = 1:
   ListMetadataFormats: correct number of metadataPrefix elements - OK
</axsl:if>
    <axsl:apply-templates mode="M7"/>
  </axsl:template>
  <axsl:template match="text()" priority="-1" mode="M7"/>
  <axsl:template match="oai:ListRecords" priority="4000" mode="M8">
    <axsl:choose>
      <axsl:when test="//oai:record"/>
      <axsl:otherwise>In pattern //oai:record:
   ERROR: ListRecords: no record element was found
</axsl:otherwise>
    </axsl:choose>
    <axsl:if test="//oai:record">In pattern //oai:record:
   ListRecords: at least one record was found - OK
</axsl:if>
    <axsl:apply-templates mode="M8"/>
  </axsl:template>
  <axsl:template match="oai:ListRecords/oai:record" priority="3999" mode="M8">
    <axsl:choose>
      <axsl:when test="count(oai:metadata) = 1"/>
      <axsl:otherwise>In pattern count(oai:metadata) = 1:
   ERROR: ListRecords: a record does not have exactly one metadata element
</axsl:otherwise>
    </axsl:choose>
    <axsl:apply-templates mode="M8"/>
  </axsl:template>
  <axsl:template match="oai:ListRecords/oai:record/oai:metadata" priority="3998" mode="M8">
    <axsl:choose>
      <axsl:when test="olac:olac"/>
      <axsl:otherwise>In pattern olac:olac:
   ERROR: ListRecords: a metadata element has no olac sub-element
</axsl:otherwise>
    </axsl:choose>
    <axsl:apply-templates mode="M8"/>
  </axsl:template>
  <axsl:template match="text()" priority="-1" mode="M8"/>
  <axsl:template match="text()" priority="-1"/>
</axsl:stylesheet>
