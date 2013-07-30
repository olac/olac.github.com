<?xml version="1.0" encoding="UTF-8"?>
<!--OLAC-to-DC crosswalk, by Gary Simons
    Last modified: 23 October 2001
    See http://www.language-archives.org/NOTE/olac_to_dc.html for documentation.-->
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:olac="http://www.language-archives.org/OLAC/0.4/">
	<xsl:output method="xml"/>
	<xsl:output indent="yes"/>
<!--<xsl:template match="/" priority="-1">
		<xsl:element name="records">
			<xsl:for-each select="//olac:olac">
				<xsl:call-template name="oai_dc"/>
			</xsl:for-each>
		</xsl:element>
	</xsl:template> -->
	<xsl:template name="oai_dc">
		<xsl:element name="dc" namespace="http://purl.org/dc/elements/1.1/">
			<xsl:attribute name="schemaLocation" namespace="http://www.w3.org/2001/XMLSchema-instance">http://purl.org/dc/elements/1.1/ http://www.openarchives.org/OAI/1.1/dc.xsd</xsl:attribute>
			<xsl:for-each select="*">
				<xsl:choose>
					<xsl:when test="name(.) = 'contributor' or name(.) = 'creator'">
						<xsl:element name="{name(.)}" namespace="http://purl.org/dc/elements/1.1/">
							<xsl:value-of select="."/>
							<xsl:call-template name="parenthetic_refinement"/>
						</xsl:element>
					</xsl:when>
					<xsl:when test="name(.) = 'date'">
						<xsl:element name="date" namespace="http://purl.org/dc/elements/1.1/">
							<xsl:value-of select="@code"/>
							<xsl:if test="@code and text()">
								<xsl:text>, </xsl:text>
							</xsl:if>
							<xsl:value-of select="."/>
							<xsl:call-template name="parenthetic_refinement"/>
						</xsl:element>
					</xsl:when>
					<xsl:when test="name(.) = 'language' or name(.) = 'rights' or name(.) = 'type'">
						<xsl:call-template name="code_and_content"/>
					</xsl:when>
					<xsl:when test="name(.) = 'format'">
						<xsl:call-template name="refined_element">
							<xsl:with-param name="element" select="'format'"/>
							<xsl:with-param name="refinement" select="'MIME type'"/>
						</xsl:call-template>
					</xsl:when>
					<xsl:when test="name(.) = 'format.cpu'">
						<xsl:call-template name="refined_element">
							<xsl:with-param name="element" select="'format'"/>
							<xsl:with-param name="refinement" select="'CPU'"/>
						</xsl:call-template>
					</xsl:when>
					<xsl:when test="name(.) = 'format.encoding'">
						<xsl:call-template name="refined_element">
							<xsl:with-param name="element" select="'format'"/>
							<xsl:with-param name="refinement" select="'Encoding'"/>
						</xsl:call-template>
					</xsl:when>
					<xsl:when test="name(.) = 'format.markup'">
						<xsl:call-template name="refined_element">
							<xsl:with-param name="element" select="'format'"/>
							<xsl:with-param name="refinement" select="'Markup scheme'"/>
						</xsl:call-template>
					</xsl:when>
					<xsl:when test="name(.) = 'format.os'">
						<xsl:call-template name="refined_element">
							<xsl:with-param name="element" select="'format'"/>
							<xsl:with-param name="refinement" select="'Operating system'"/>
						</xsl:call-template>
					</xsl:when>
					<xsl:when test="name(.) = 'format.sourcecode'">
						<xsl:call-template name="refined_element">
							<xsl:with-param name="element" select="'format'"/>
							<xsl:with-param name="refinement" select="'Source code'"/>
						</xsl:call-template>
					</xsl:when>
					<xsl:when test="name(.) = 'relation'">
						<xsl:element name="relation" namespace="http://purl.org/dc/elements/1.1/">
							<xsl:if test="@refine = 'isPartOf'">
								<xsl:text>Is part of: </xsl:text>
							</xsl:if>
							<xsl:if test="@refine = 'isVersionOf'">
								<xsl:text>Is version of: </xsl:text>
							</xsl:if>
							<xsl:if test="@refine = 'hasPart'">
								<xsl:text>Has part: </xsl:text>
							</xsl:if>
							<xsl:if test="@refine = 'hasVersion'">
								<xsl:text>Has version: </xsl:text>
							</xsl:if>
							<xsl:if test="@refine = 'requires'">
								<xsl:text>Requires: </xsl:text>
							</xsl:if>
							<xsl:value-of select="."/>
						</xsl:element>
					</xsl:when>
					<xsl:when test="name(.) = 'subject.language'">
						<xsl:call-template name="refined_element">
							<xsl:with-param name="element" select="'subject'"/>
							<xsl:with-param name="refinement" select="'Language'"/>
						</xsl:call-template>
					</xsl:when>
					<xsl:when test="name(.) = 'type.functionality'">
						<xsl:call-template name="refined_element">
							<xsl:with-param name="element" select="'type'"/>
							<xsl:with-param name="refinement" select="'Software functionality'"/>
						</xsl:call-template>
					</xsl:when>
					<xsl:when test="name(.) = 'type.linguistic'">
						<xsl:call-template name="refined_element">
							<xsl:with-param name="element" select="'type'"/>
							<xsl:with-param name="refinement" select="'Linguistic data type'"/>
						</xsl:call-template>
					</xsl:when>
					<xsl:when test="name(.) = 'title'">
						<xsl:element name="title" namespace="http://purl.org/dc/elements/1.1/">
							<xsl:if test="@refine='alternative'">
								<xsl:text>[</xsl:text>
							</xsl:if>
							<xsl:value-of select="."/>
							<xsl:if test="@refine='alternative'">
								<xsl:text>]</xsl:text>
							</xsl:if>
						</xsl:element>
					</xsl:when>
					<xsl:otherwise>
						<xsl:call-template name="content_alone"/>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:for-each>
		</xsl:element>
	</xsl:template>
	<xsl:template name="content_alone">
		<xsl:element name="{name(.)}" namespace="http://purl.org/dc/elements/1.1/">
			<xsl:value-of select="."/>
		</xsl:element>
	</xsl:template>
	<xsl:template name="code_and_content">
		<xsl:element name="{name()}" namespace="http://purl.org/dc/elements/1.1/">
			<xsl:value-of select="@code"/>
			<xsl:if test="@code and text()">
				<xsl:text>, </xsl:text>
			</xsl:if>
			<xsl:value-of select="."/>
		</xsl:element>
	</xsl:template>
	<xsl:template name="parenthetic_refinement">
		<xsl:if test="@refine">
			<xsl:text> (</xsl:text>
			<xsl:value-of select="@refine"/>
			<xsl:text>)</xsl:text>
		</xsl:if>
	</xsl:template>
	<xsl:template name="refined_element">
		<xsl:param name="element"/>
		<xsl:param name="refinement"/>
		<xsl:element name="{$element}" namespace="http://purl.org/dc/elements/1.1/">
			<xsl:if test="$refinement">
				<xsl:value-of select="$refinement"/>
				<xsl:text>: </xsl:text>
			</xsl:if>
			<xsl:value-of select="@code"/>
			<xsl:if test="@code and text()">
				<xsl:text>, </xsl:text>
			</xsl:if>
			<xsl:value-of select="."/>
		</xsl:element>
	</xsl:template>
</xsl:stylesheet>
