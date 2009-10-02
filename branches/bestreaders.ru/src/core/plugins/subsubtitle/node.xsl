<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns="http://www.w3.org/1999/xhtml"
	exclude-result-prefixes="">
	<xsl:output 
		method="xml" 
		version="1.0" 
		indent="no" 
		encoding="utf-8"
		omit-xml-declaration="yes"
		cdata-section-elements=""/>

	<xsl:template match="*[@type='subsubtitle']" mode="wiki">
		<h4 class="text"><xsl:if test="@deleted"><xsl:attribute name="class">text deleted</xsl:attribute></xsl:if><xsl:apply-templates select="text" mode="wiki"/></h4>
	</xsl:template>

	<xsl:template match="*[@type='subsubtitle']/text" mode="diff">
		<xsl:apply-templates select="."/>
	</xsl:template>

	<xsl:template match="*[@type='subsubtitle']" mode="form"><text><xsl:value-of select="text/text()"/></text></xsl:template>
</xsl:stylesheet>