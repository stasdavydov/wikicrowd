<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl"
	xmlns="http://www.google.com/schemas/sitemap/0.84"
	exclude-result-prefixes=""
	extension-element-prefixes="php">
	<xsl:output 
		method="xml" 
		version="1.0" 
		indent="no" 
		encoding="utf-8"
		omit-xml-declaration="no"
		media-type="text/xml"
		cdata-section-elements=""/>

	<xsl:include href="core.xsl"/>

	<xsl:param name="BASE_URL"/>
	<xsl:param name="LAST_MOD"/>
	<xsl:param name="HOME"/>

	<xsl:template match="/">
		<urlset>
			<xsl:call-template name="url">
				<xsl:with-param name="url" select="$BASE_URL"/>
				<xsl:with-param name="freq">weekly</xsl:with-param>
				<xsl:with-param name="priority">0.5</xsl:with-param>
			</xsl:call-template>
			<xsl:call-template name="url">
				<xsl:with-param name="url"><xsl:value-of select="$BASE_URL"/>allchanges/</xsl:with-param>
				<xsl:with-param name="freq">daily</xsl:with-param>
				<xsl:with-param name="priority">0.7</xsl:with-param>
			</xsl:call-template>
			<xsl:for-each select="//chapter">
				<xsl:sort select="@title" order="ascending"/>
				<xsl:if test="not(@title = $HOME)">
					<xsl:call-template name="url">
						<xsl:with-param name="url">
							<xsl:value-of select="$BASE_URL"/>
							<xsl:value-of select="php:function('wikiUrlEncode', string(@title))"/>
						</xsl:with-param>
						<xsl:with-param name="freq">monthly</xsl:with-param>
						<xsl:with-param name="priority">0.2</xsl:with-param>
					</xsl:call-template>
				</xsl:if>
			</xsl:for-each>
		</urlset>
	</xsl:template>

	<xsl:template name="url">
		<xsl:param name="url"/>
		<xsl:param name="freq"/>
		<xsl:param name="priority"/>	
		<url>
			<loc><xsl:value-of select="$url"/></loc>
			<lastmod><xsl:value-of select="$LAST_MOD"/></lastmod>
			<changefreq><xsl:value-of select="$freq"/></changefreq>
			<priority><xsl:value-of select="$priority"/></priority>
		</url>
	</xsl:template>

</xsl:stylesheet>
