<?xml version="1.0" standalone="yes"?>
<axsl:stylesheet xmlns:axsl="http://www.w3.org/1999/XSL/Transform" xmlns:sch="http://www.ascc.net/xml/schematron" xmlns:xs="http://www.w3.org/2001/XMLSchema-instance" xmlns:oai="http://www.openarchives.org/OAI/2.0/" xmlns:oai-identifier="http://www.openarchives.org/OAI/2.0/oai-identifier" xmlns:sr="http://www.openarchives.org/OAI/2.0/static-repository" xmlns:olac="http://www.language-archives.org/OLAC/1.1/" xmlns:olac-archive="http://www.language-archives.org/OLAC/1.1/olac-archive" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" version="1.0" xs:dummy-for-xmlns="" oai:dummy-for-xmlns="" oai-identifier:dummy-for-xmlns="" sr:dummy-for-xmlns="" olac:dummy-for-xmlns="" olac-archive:dummy-for-xmlns="" dc:dummy-for-xmlns="" dcterms:dummy-for-xmlns="">
  <axsl:output method="html"/>
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
      <h2 title="Schematron contact-information is at the end of                   this page"><font color="#FF0080">Schematron</font> Report
      </h2>
      <h1 title=" "/>
      <div class="errors">
        <ul>
          <h3>Schema location requirement</h3>
          <axsl:apply-templates select="/" mode="M8"/>
          <a href="http://www.language-archives.org/OLAC/repositories.html#Identify" target="SRDOCO" title="Link to User Documentation:">
            <h3 class="linked">OLAC-PMH Identify requirement</h3>
          </a>
          <axsl:apply-templates select="/" mode="M9"/>
          <a href="http://www.language-archives.org/OLAC/repositories.html#ListMetadataFormats" target="SRDOCO" title="Link to User Documentation:">
            <h3 class="linked">OLAC-PMH ListMetadataFormats requirement</h3>
          </a>
          <axsl:apply-templates select="/" mode="M10"/>
          <a href="http://www.language-archives.org/OLAC/repositories.html#ListRecords" target="SRDOCO" title="Link to User Documentation:">
            <h3 class="linked">OLAC-PMH ListRecords requirement</h3>
          </a>
          <axsl:apply-templates select="/" mode="M11"/>
        </ul>
      </div>
      <hr color="#FF0080"/>
      <p>
        <font size="2">Schematron Report by David Carlisle.
      <a href="http://www.ascc.net/xml/resource/schematron/schematron.html" title="Link to the home page of the Schematron,                  a tree-pattern schema language"><font color="#FF0080">The Schematron</font></a> by
      <a href="mailto:ricko@gate.sinica.edu.tw" title="Email to Rick Jelliffe (pronounced RIK JELIF)">Rick Jelliffe</a>,
      <a href="http://www.sinica.edu.tw" title="Link to home page of Academia Sinica">Academia Sinica Computing Centre</a>.
      </font>
      </p>
    </html>
  </axsl:template>
  <axsl:template match="/sr:Repository" priority="4000" mode="M8">
    <axsl:choose>
      <axsl:when test="not(contains(@xs:schemaLocation,'http://www.openarchives.org/OAI/2.0/static-repository.xsd'))"/>
      <axsl:otherwise>
        <li>
          <a href="schematron-out.html#{generate-id(.)}" target="out" title="Link to where this pattern was expected"><i/>ERROR: Repository: schemaLocation contains http://www.openarchives.org/OAI/2.0/static-repository.xsd<b/></a>
        </li>
      </axsl:otherwise>
    </axsl:choose>
    <axsl:choose>
      <axsl:when test="contains(@xs:schemaLocation,'http://www.language-archives.org/OLAC/1.1/static-repository.xsd')"/>
      <axsl:otherwise>
        <li>
          <a href="schematron-out.html#{generate-id(.)}" target="out" title="Link to where this pattern was expected"><i/>ERROR: Repository: schemaLocation doesn't contain http://www.language-archives.org/OLAC/1.1/static-repository.xsd<b/></a>
        </li>
      </axsl:otherwise>
    </axsl:choose>
    <axsl:apply-templates mode="M8"/>
  </axsl:template>
  <axsl:template match="text()" priority="-1" mode="M8"/>
  <axsl:template match="/sr:Repository" priority="4000" mode="M9">
    <axsl:choose>
      <axsl:when test="sr:Identify|oai:Identify"/>
      <axsl:otherwise>
        <li>
          <a href="schematron-out.html#{generate-id(.)}" target="out" title="Link to where this pattern was expected"><i/>ERROR: Repository doesn't have an Identify element<b/></a>
        </li>
      </axsl:otherwise>
    </axsl:choose>
    <axsl:if test="sr:Identify|oai:Identify">
      <li>
        <a href="schematron-out.html#{generate-id(.)}" target="out" title="Link to where this pattern was found"><i/>Repository has an Identify element - OK<b/></a>
      </li>
    </axsl:if>
    <axsl:apply-templates mode="M9"/>
  </axsl:template>
  <axsl:template match="/sr:Repository/sr:Identify" priority="3999" mode="M9">
    <axsl:choose>
      <axsl:when test="oai:description/olac-archive:olac-archive"/>
      <axsl:otherwise>
        <li>
          <a href="schematron-out.html#{generate-id(.)}" target="out" title="Link to where this pattern was expected"><i/>ERROR: Identify: description element has no olac-archive element<b/></a>
        </li>
      </axsl:otherwise>
    </axsl:choose>
    <axsl:if test="oai:description/olac-archive:olac-archive">
      <li>
        <a href="schematron-out.html#{generate-id(.)}" target="out" title="Link to where this pattern was found"><i/>Identify: description element has an olac-archive element - OK<b/></a>
      </li>
    </axsl:if>
    <axsl:choose>
      <axsl:when test="oai:description/oai-identifier:oai-identifier"/>
      <axsl:otherwise>
        <li>
          <a href="schematron-out.html#{generate-id(.)}" target="out" title="Link to where this pattern was expected"><i/>ERROR: Identify: description element has no oai-identifier element<b/></a>
        </li>
      </axsl:otherwise>
    </axsl:choose>
    <axsl:if test="oai:description/oai-identifier:oai-identifier">
      <li>
        <a href="schematron-out.html#{generate-id(.)}" target="out" title="Link to where this pattern was found"><i/>Identify: description element has an oai-identifier element - OK<b/></a>
      </li>
    </axsl:if>
    <axsl:apply-templates mode="M9"/>
  </axsl:template>
  <axsl:template match="sr:Identify/oai:adminEmail" priority="3998" mode="M9">
    <axsl:choose>
      <axsl:when test="../oai:description/olac-archive:olac-archive/olac-archive:participant/@email=normalize-space(.)"/>
      <axsl:otherwise>
        <li>
          <a href="schematron-out.html#{generate-id(.)}" target="out" title="Link to where this pattern was expected"><i/>ERROR: Identify: adminEmail<axsl:text xml:space="preserve"> </axsl:text><axsl:value-of select="."/><axsl:text xml:space="preserve"> </axsl:text>doesn't appear in a participant element.<b/></a>
        </li>
      </axsl:otherwise>
    </axsl:choose>
    <axsl:if test="../oai:description/olac-archive:olac-archive/olac-archive:participant/@email=normalize-space(.)">
      <li>
        <a href="schematron-out.html#{generate-id(.)}" target="out" title="Link to where this pattern was found"><i/>Identify: adminEmail<axsl:text xml:space="preserve"> </axsl:text><axsl:value-of select="."/><axsl:text xml:space="preserve"> </axsl:text>appears in a participant element with role<axsl:text xml:space="preserve"> </axsl:text><axsl:value-of select="../oai:description/olac-archive:olac-archive/olac-archive:participant[@email=normalize-space(current())]/@role"/><axsl:text xml:space="preserve"> </axsl:text>- OK<b/></a>
      </li>
    </axsl:if>
    <axsl:apply-templates mode="M9"/>
  </axsl:template>
  <axsl:template match="text()" priority="-1" mode="M9"/>
  <axsl:template match="/sr:Repository" priority="4000" mode="M10">
    <axsl:choose>
      <axsl:when test="sr:ListMetadataFormats"/>
      <axsl:otherwise>
        <li>
          <a href="schematron-out.html#{generate-id(.)}" target="out" title="Link to where this pattern was expected"><i/>ERROR: Repository doesn't have a ListMetadataFormats element<b/></a>
        </li>
      </axsl:otherwise>
    </axsl:choose>
    <axsl:if test="sr:ListMetadataFormats">
      <li>
        <a href="schematron-out.html#{generate-id(.)}" target="out" title="Link to where this pattern was found"><i/>Repository has a ListMetadataFormats element - OK<b/></a>
      </li>
    </axsl:if>
    <axsl:apply-templates mode="M10"/>
  </axsl:template>
  <axsl:template match="/sr:Repository/sr:ListMetadataFormats" priority="3999" mode="M10">
    <axsl:choose>
      <axsl:when test="oai:metadataFormat/oai:metadataPrefix[normalize-space(text())='olac']"/>
      <axsl:otherwise>
        <li>
          <a href="schematron-out.html#{generate-id(.)}" target="out" title="Link to where this pattern was expected"><i/>ERROR: ListMetadataFormats: olac missing<b/></a>
        </li>
      </axsl:otherwise>
    </axsl:choose>
    <axsl:if test="oai:metadataFormat/oai:metadataPrefix[normalize-space(text())='olac']">
      <li>
        <a href="schematron-out.html#{generate-id(.)}" target="out" title="Link to where this pattern was found"><i/>ListMetadataFormats: olac present - OK<b/></a>
      </li>
    </axsl:if>
    <axsl:apply-templates mode="M10"/>
  </axsl:template>
  <axsl:template match="/sr:Repository/sr:ListMetadataFormats/oai:metadataFormat" priority="3998" mode="M10">
    <axsl:choose>
      <axsl:when test="count(oai:metadataPrefix) = 1"/>
      <axsl:otherwise>
        <li>
          <a href="schematron-out.html#{generate-id(.)}" target="out" title="Link to where this pattern was expected"><i/>ERROR: ListMetadataFormats: incorrect number of metadataPrefix elements<b/></a>
        </li>
      </axsl:otherwise>
    </axsl:choose>
    <axsl:if test="count(oai:metadataPrefix) = 1">
      <li>
        <a href="schematron-out.html#{generate-id(.)}" target="out" title="Link to where this pattern was found"><i/>ListMetadataFormats: correct number of metadataPrefix elements - OK<b/></a>
      </li>
    </axsl:if>
    <axsl:apply-templates mode="M10"/>
  </axsl:template>
  <axsl:template match="text()" priority="-1" mode="M10"/>
  <axsl:template match="/sr:Repository" priority="4000" mode="M11">
    <axsl:choose>
      <axsl:when test="sr:ListRecords"/>
      <axsl:otherwise>
        <li>
          <a href="schematron-out.html#{generate-id(.)}" target="out" title="Link to where this pattern was expected"><i/>ERROR: Repository doesn't have a ListRecords element<b/></a>
        </li>
      </axsl:otherwise>
    </axsl:choose>
    <axsl:if test="sr:ListRecords">
      <li>
        <a href="schematron-out.html#{generate-id(.)}" target="out" title="Link to where this pattern was found"><i/>Repository has a ListRecords element - OK<b/></a>
      </li>
    </axsl:if>
    <axsl:apply-templates mode="M11"/>
  </axsl:template>
  <axsl:template match="/sr:Repository/sr:ListRecords" priority="3999" mode="M11">
    <axsl:choose>
      <axsl:when test="oai:record"/>
      <axsl:otherwise>
        <li>
          <a href="schematron-out.html#{generate-id(.)}" target="out" title="Link to where this pattern was expected"><i/>ERROR: ListRecords: no record element was found<b/></a>
        </li>
      </axsl:otherwise>
    </axsl:choose>
    <axsl:if test="oai:record">
      <li>
        <a href="schematron-out.html#{generate-id(.)}" target="out" title="Link to where this pattern was found"><i/>ListRecords: at least one record was found - OK<b/></a>
      </li>
    </axsl:if>
    <axsl:apply-templates mode="M11"/>
  </axsl:template>
  <axsl:template match="/sr:Repository/sr:ListRecords/oai:record" priority="3998" mode="M11">
    <axsl:choose>
      <axsl:when test="count(oai:metadata) = 1"/>
      <axsl:otherwise>
        <li>
          <a href="schematron-out.html#{generate-id(.)}" target="out" title="Link to where this pattern was expected"><i/>ERROR: ListRecords: a record does not have exactly one metadata element<b/></a>
        </li>
      </axsl:otherwise>
    </axsl:choose>
    <axsl:choose>
      <axsl:when test="oai:metadata/olac:olac"/>
      <axsl:otherwise>
        <li>
          <a href="schematron-out.html#{generate-id(.)}" target="out" title="Link to where this pattern was expected"><i/>ERROR: ListRecords: a metadata element has no olac sub-element<b/></a>
        </li>
      </axsl:otherwise>
    </axsl:choose>
    <axsl:apply-templates mode="M11"/>
  </axsl:template>
  <axsl:template match="text()" priority="-1" mode="M11"/>
  <axsl:template match="text()" priority="-1"/>
</axsl:stylesheet>
