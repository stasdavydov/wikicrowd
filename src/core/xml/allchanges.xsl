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
			<head><title><xsl:value-of select="$locale//message[@id='AllChanges']/@text"/> | <xsl:value-of select="$config//property[@name='title']/@value"/></title>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
				<link rel="shortcut icon" href="{$config//property[@name='www']/@value}core/img/favicon.gif" />
				<script type="text/javascript">var www = '<xsl:value-of select="$config//property[@name='www']/@value"/>';</script>
				<script type="text/javascript" charset="windows-1251" src="{$config//property[@name='www']/@value}core/js/base.js">//<!--"--></script>
				<script type="text/javascript" charset="utf-8" src="{$config//property[@name='www']/@value}core/js/locale.js">//<!--"--></script>
				<script type="text/javascript" charset="windows-1251" src="{$config//property[@name='www']/@value}core/js/auth.js">//<!--"--></script>
				<link rel="stylesheet" type="text/css" href="{$config//property[@name='www']/@value}core/css/main.css"/>
				<link rel="stylesheet" type="text/css" href="{$config//property[@name='www']/@value}core/css/plugins.css"/>
				<style type="text/css">h1 { margin: 0.25em 0 0.5em 0.65em; } 
ul { margin: 0 0 0.15em 0; list-style-type: none; padding: 0.15em 0.15em 0.15em 0.25em; }
ul li { display: block; margin: 0 0 0.75em 0; }
.chapterlink { margin: 0 0 0.15em 0; }
</style>
			</head>
			<body>
				<xsl:call-template name="menu">
					<xsl:with-param name="page">allchanges</xsl:with-param>
				</xsl:call-template>
				<h1><xsl:value-of select="$locale//message[@id='AllChanges']/@text"/> &#0187; <a href="{$config//property[@name='www']/@value}"><xsl:value-of select="$config//property[@name='title']/@value"/></a></h1>

				<div id="chapter">
					<xsl:choose>
						<xsl:when test="count(/changes/change) &gt; 0">
							<ul>
								<xsl:for-each select="/changes/change">
									<xsl:sort select="position()" data-type="number" order="descending"/>
									<xsl:apply-templates select="."/>
								</xsl:for-each>
							</ul>
						</xsl:when>
						<xsl:otherwise>
							<p><xsl:value-of select="$locale//message[@id='NoChanges']/@text"/></p>
						</xsl:otherwise>
					</xsl:choose>
				</div>

				<xsl:call-template name="copyright"/>
			</body>
		</html>	
	</xsl:template>

	<xsl:template match="change">
		<li>
			<h2><a class="chapterlink" href="{$config//property[@name='www']/@value}{php:function('rawurlencode', string(@chapter))}#block{child::previous/@id}">
				<xsl:value-of select="@chapter"/>
			</a></h2>
			<xsl:apply-templates select="previous" mode="diff"/>
			<div class="info serv">
				<a href="{$config//property[@name='www']/@value}person/{child::previous/@author}"><xsl:value-of select="child::previous/@author"/></a>: <span id="time_{child::previous/@id}_{position()}"><xsl:value-of select="child::previous/@created-date"/><script type="text/javascript">$('time_<xsl:value-of select="child::previous/@id"/>_<xsl:value-of select="position()"/>').innerHTML=getTextTimeDifference(<xsl:value-of select="child::previous/@created-ts"/>);</script></span>
			</div>
		</li>
	</xsl:template>
</xsl:stylesheet>
