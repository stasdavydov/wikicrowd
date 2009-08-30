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
		cdata-section-elements="text"/>

	<xsl:template match="*[@type='html']" mode="wiki">
		<div class="text"><xsl:if test="@deleted"><xsl:attribute name="class">text deleted</xsl:attribute></xsl:if><xsl:value-of select="text" disable-output-escaping="yes"/></div>
	</xsl:template>

	<xsl:template match="*[@type='html']/text" mode="diff">
		<xsl:value-of select="text()" disable-output-escaping="yes"/>
	</xsl:template>

	<xsl:template match="*[@type='html']" mode="form"><text><xsl:value-of select="text/text()"/></text></xsl:template>
</xsl:stylesheet>