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
	
	<xsl:param name="MODE"/>
	<xsl:param name="LAST"/>

	<xsl:template match="response | chapter">
		<response>
			<xsl:for-each select="updated | inserted | conflict | block[@created-ts > $LAST]">
				<xsl:element name="{name()}">
					<xsl:for-each select="@*">
						<xsl:copy/>
					</xsl:for-each>
					<xsl:if test="name() = 'block' and preceding-sibling::block[position()=1]">
						<xsl:attribute name="prev-block-id"><xsl:value-of select="preceding-sibling::block[position()=1]/@id"/></xsl:attribute>
					</xsl:if>
					<xsl:if test="name() = 'block' and following-sibling::block[position()=1]">
						<xsl:attribute name="next-block-id"><xsl:value-of select="following-sibling::block[position()=1]/@id"/></xsl:attribute>
					</xsl:if>
					
					<xsl:choose>
						<xsl:when test="$MODE = 'edit' and not(name() = 'conflict')">
							<xsl:text disable-output-escaping="yes">&lt;![CDATA[</xsl:text>
							<xsl:apply-templates select="." mode="wiki"/>
							<xsl:text disable-output-escaping="yes">]]&gt;</xsl:text>
						</xsl:when>
						<xsl:when test="$MODE = 'view' and not(name() = 'conflict')">
							<xsl:text disable-output-escaping="yes">&lt;![CDATA[</xsl:text>
							<xsl:apply-templates select="." mode="view"/>
							<xsl:text disable-output-escaping="yes">]]&gt;</xsl:text>
						</xsl:when>
						<xsl:otherwise>
							<xsl:apply-templates select="." mode="diff"/>
						</xsl:otherwise>
					</xsl:choose>
					
				</xsl:element>
			</xsl:for-each>
		</response>
	</xsl:template>

	<xsl:template match="/">
		<xsl:apply-templates select="response | chapter"/>
	</xsl:template>

</xsl:stylesheet>