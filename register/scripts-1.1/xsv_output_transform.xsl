<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="text"/>
 
<xsl:template  match="/">
<xsl:variable name="errors"  select="//@instanceErrors"/>
<xsl:variable name="accessed" select="//@instanceAssessed"/>
<xsl:variable name="crash"  select="//@crash"/>
<xsl:choose>
		<xsl:when test="number($errors)=0">
			<xsl:text>XML valid - OK.</xsl:text>
		</xsl:when>
		<xsl:otherwise>
			InstanceAssessed:	<xsl:value-of select="$accessed"/>
			number of errors:	<xsl:value-of select="$errors"/>
			<xsl:if test="$crash = 'true'">
			validator crash during target reading
			</xsl:if>
		</xsl:otherwise>
</xsl:choose>
</xsl:template>

</xsl:stylesheet>