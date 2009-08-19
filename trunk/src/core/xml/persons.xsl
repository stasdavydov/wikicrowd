<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:php="http://php.net/xsl"
	xmlns="http://www.w3.org/1999/xhtml"
	exclude-result-prefixes=""
	extension-element-prefixes="php">
	<xsl:output 
		method="xml" 
		version="1.0" 
		indent="no" 
		encoding="utf-8"
		omit-xml-declaration="yes"
		doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"
		doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"
		media-type="text/html"
		cdata-section-elements=""/>

	<xsl:include href="core.xsl"/>

	<xsl:template match="/">
		<html xml:lang="{$LOCALE}">
			<head><title><xsl:value-of select="$locale//message[@id='Persons']/@text"/> &#0187; <xsl:value-of select="$config//property[@name='title']/@value"/></title>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
				<link rel="shortcut icon" href="{$config//property[@name='www']/@value}core/img/favicon.gif" />
				<link rel="alternate" type="application/rss+xml" title="RSS" href="{$config//property[@name='www']/@value}allchanges/rss/"/>
				<script type="text/javascript">var www = '<xsl:value-of select="$config//property[@name='www']/@value"/>';</script>
				<script type="text/javascript" charset="windows-1251" src="{$config//property[@name='www']/@value}core/js/base.js">//<!--"--></script>
				<script type="text/javascript" charset="utf-8" src="{$config//property[@name='www']/@value}core/js/locale.js">//<!--"--></script>
				<script type="text/javascript" charset="windows-1251" src="{$config//property[@name='www']/@value}core/js/auth.js">//<!--"--></script>
				<link rel="stylesheet" type="text/css" href="{$config//property[@name='www']/@value}core/css/main.css"/>
				<style type="text/css">h1 { margin: 0.25em 0 0.5em 0.65em; } </style>
			</head>
			<body>
				<xsl:call-template name="menu">
<!--					<xsl:with-param name="page">allchanges</xsl:with-param>-->
				</xsl:call-template>
				<h1><xsl:value-of select="$locale//message[@id='Persons']/@text"/> &#0187; <a href="{$config//property[@name='www']/@value}"><xsl:value-of select="$config//property[@name='title']/@value"/></a></h1>

				<div id="chapter">
					<xsl:for-each select="//person">
						<xsl:sort select="@name" order="ascending"/>
						<xsl:apply-templates select="."/>
						<xsl:if test="not(position() = last())">
							<xsl:text>, </xsl:text>
						</xsl:if>
					</xsl:for-each>
					<xsl:text>.</xsl:text>
				</div>
				<xsl:call-template name="copyright"/>
			</body>
		</html>
	</xsl:template>

	<xsl:template match="person">
		<a href="{$config//property[@name='www']/@value}person/{@uid}">
			<xsl:value-of select="@name"/>
		</a>
	</xsl:template>
</xsl:stylesheet>
