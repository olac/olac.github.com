<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
   <xsl:template name="format-status-and-type">
      <!--Called when the context element is <status>-->
      <i>
         <xsl:if test="@endDate">
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
   </xsl:template>
   <xsl:template name="format-date">
      <!--Called from the element that contains the <issued> element-->
      <xsl:value-of select="substring(issued,1,4)"/>
      <xsl:text>-</xsl:text>
      <xsl:value-of select="substring(issued,5,2)"/>
      <xsl:text>-</xsl:text>
      <xsl:value-of select="substring(issued,7,2)"/>
   </xsl:template>
</xsl:stylesheet>
