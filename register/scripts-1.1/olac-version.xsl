<?xml version="1.0" standalone="yes"?>
<axsl:stylesheet xmlns:axsl="http://www.w3.org/1999/XSL/Transform" xmlns:sch="http://www.ascc.net/xml/schematron" xmlns:xs="http://www.w3.org/2001/XMLSchema-instance" xmlns:oai="http://www.openarchives.org/OAI/2.0/" xmlns:oai-identifier="http://www.openarchives.org/OAI/2.0/oai-identifier" xmlns:sr="http://www.openarchives.org/OAI/2.0/static-repository" xmlns:olac10="http://www.language-archives.org/OLAC/1.0/" xmlns:olac11="http://www.language-archives.org/OLAC/1.1/" xmlns:olac-archive10="http://www.language-archives.org/OLAC/1.0/olac-archive" xmlns:olac-archive11="http://www.language-archives.org/OLAC/1.1/olac-archive" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" version="1.0" xs:dummy-for-xmlns="" oai:dummy-for-xmlns="" oai-identifier:dummy-for-xmlns="" sr:dummy-for-xmlns="" olac10:dummy-for-xmlns="" olac11:dummy-for-xmlns="" olac-archive10:dummy-for-xmlns="" olac-archive11:dummy-for-xmlns="" dc:dummy-for-xmlns="" dcterms:dummy-for-xmlns="">
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
<axsl:apply-templates select="/" mode="M10"/></axsl:template>
  <axsl:template match="sr:Identify" priority="4000" mode="M10">
    <axsl:if test="oai:description/olac-archive10:olac-archive">In pattern oai:description/olac-archive10:olac-archive:
   OLAC 1.0 static
</axsl:if>
    <axsl:if test="oai:description/olac-archive11:olac-archive">In pattern oai:description/olac-archive11:olac-archive:
   OLAC 1.1 static
</axsl:if>
    <axsl:apply-templates mode="M10"/>
  </axsl:template>
  <axsl:template match="oai:Identify" priority="3999" mode="M10">
    <axsl:if test="oai:description/olac-archive10:olac-archive">In pattern oai:description/olac-archive10:olac-archive:
   OLAC 1.0 dynamic
</axsl:if>
    <axsl:if test="oai:description/olac-archive11:olac-archive">In pattern oai:description/olac-archive11:olac-archive:
   OLAC 1.1 dynamic
</axsl:if>
    <axsl:apply-templates mode="M10"/>
  </axsl:template>
  <axsl:template match="sr:ListRecords/oai:record" priority="3998" mode="M10">
    <axsl:if test="oai:metadata/olac10:olac">In pattern oai:metadata/olac10:olac:
   OLAC 1.0 static
</axsl:if>
    <axsl:if test="oai:metadata/olac11:olac">In pattern oai:metadata/olac11:olac:
   OLAC 1.1 static
</axsl:if>
    <axsl:apply-templates mode="M10"/>
  </axsl:template>
  <axsl:template match="oai:ListRecords/oai:record" priority="3997" mode="M10">
    <axsl:if test="oai:metadata/olac10:olac">In pattern oai:metadata/olac10:olac:
   OLAC 1.0 dynamic
</axsl:if>
    <axsl:if test="oai:metadata/olac11:olac">In pattern oai:metadata/olac11:olac:
   OLAC 1.1 dynamic
</axsl:if>
    <axsl:apply-templates mode="M10"/>
  </axsl:template>
  <axsl:template match="text()" priority="-1" mode="M10"/>
  <axsl:template match="text()" priority="-1"/>
</axsl:stylesheet>
