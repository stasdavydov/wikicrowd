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

	<xsl:include href="core.xsl"/>
	
	<xsl:param name="ID"/>
	<xsl:param name="REV"/>

	<xsl:template match="/">
		<form>
			<xsl:choose>
				<xsl:when test="$ID = 'chaptertitle'">
					<xsl:apply-templates select="/chapter" mode="form"/>
				</xsl:when>
				<xsl:otherwise>
					<xsl:apply-templates select="//block[@id = $ID and @rev = $REV]" mode="form"/>
				</xsl:otherwise>
			</xsl:choose>
		</form>
	</xsl:template>

</xsl:stylesheet>