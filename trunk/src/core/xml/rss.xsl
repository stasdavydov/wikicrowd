<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl"
	exclude-result-prefixes=""
	extension-element-prefixes="php">
	<xsl:output 
		method="xml" 
		version="1.0" 
		indent="no" 
		encoding="utf-8"
		omit-xml-declaration="yes"
		media-type="application/rss+xml"
		cdata-section-elements=""/>

	<xsl:include href="core.xsl"/>

	<xsl:template match="/">
		<rss version="2.0">
			<channel>
				<title><xsl:value-of select="$config//property[@name='title']/@value"/>: <xsl:value-of select="$locale//message[@id='AllChanges']/@text"/></title>
				<link><xsl:value-of select="$wwwHost"/></link>
				<description><xsl:value-of select="$locale//message[@id='RSSDescription']/@text"/> <xsl:value-of select="$config//property[@name='title']/@value"/></description>
				<language><xsl:value-of select="$LOCALE"/></language>
				<generator>WikiCrowd</generator>
				<image>
					<url><xsl:value-of select="$wwwHost"/>core/img/favicon.gif</url>
					<title><xsl:value-of select="$config//property[@name='title']/@value"/>: <xsl:value-of select="$locale//message[@id='AllChanges']/@text"/></title>
					<link><xsl:value-of select="$wwwHost"/></link>
					<width>32</width>
					<height>32</height>
				</image>
				<xsl:for-each select="/changes/change">
					<xsl:sort select="position()" data-type="number" order="descending"/>
					<xsl:apply-templates select="."/>
				</xsl:for-each>
			</channel>
		</rss>
	</xsl:template>

	<xsl:template match="change">
		<item>
			<title>
				<xsl:value-of select="@chapter"/>
			</title>
			<description><xsl:text disable-output-escaping="yes">&lt;![CDATA[</xsl:text>
				<xsl:apply-templates select="previous" mode="diff"/>
			<xsl:text disable-output-escaping="yes">]]&gt;</xsl:text></description>
			<link><xsl:value-of select="$wwwHost"/><xsl:value-of select="php:function('wikiUrlEncode', string(@chapter))"/>#block<xsl:value-of select="child::previous/@id"/></link>
			<author>noreply@wikicrowd.org (<xsl:value-of select="child::previous/@author"/>)</author>
			<guid isPermaLink="false"><xsl:value-of select="$wwwHost"/><xsl:value-of select="php:function('wikiUrlEncode', string(@chapter))"/>:<xsl:value-of select="child::previous/@id"/>:<xsl:value-of select="child::previous/@rev"/></guid>
			<pubDate><xsl:value-of select="php:function('gmdate', 'D, d M Y H:i:s', string(child::previous/@created-ts))"/> GMT</pubDate>
		</item>
	</xsl:template>
</xsl:stylesheet>
