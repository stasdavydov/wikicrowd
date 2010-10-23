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

	<xsl:param name="PAGE"/>
	<xsl:param name="PAGESIZE"/>

	<xsl:template match="/">
		<html xml:lang="{$LOCALE}">
			<head><title><xsl:value-of select="$locale//message[@id='AllChanges']/@text"/> &#0187; <xsl:value-of select="$config//property[@name='title']/@value"/></title>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
				<link rel="shortcut icon" href="{$config//property[@name='www']/@value}core/img/favicon.gif" />
				<link rel="alternate" type="application/rss+xml" title="RSS" href="{$config//property[@name='www']/@value}allchanges/rss/"/>
				<script type="text/javascript">var www = '<xsl:value-of select="$config//property[@name='www']/@value"/>';</script>
				<script type="text/javascript" src="{$config//property[@name='www']/@value}core/js/base.js">//<!--"--></script>
				<script type="text/javascript" charset="utf-8" src="{$config//property[@name='www']/@value}core/js/locale.js">//<!--"--></script>
				<script type="text/javascript" src="{$config//property[@name='www']/@value}core/js/auth.js">//<!--"--></script>
				<link rel="stylesheet" type="text/css" href="{$config//property[@name='www']/@value}core/css/main.css"/>
				<link rel="stylesheet" type="text/css" href="{$config//property[@name='www']/@value}core/css/plugins.css"/>
				<style type="text/css">h1 { margin: 0.25em 0 0.5em 0.65em; } 
ul { margin: 0 0 0.15em 0; list-style-type: none; padding: 0.15em 0.15em 0.15em 0.25em; }
ul li { display: block; margin: 0 0 0.75em 0; }
h2 { margin: 0 0 0.15em 0; }
</style>
			</head>
			<body>
				<xsl:call-template name="menu">
					<xsl:with-param name="page">allchanges</xsl:with-param>
				</xsl:call-template>
				<h1><xsl:value-of select="$locale//message[@id='AllChanges']/@text"/> &#0187; <a href="{$config//property[@name='www']/@value}"><xsl:value-of select="$config//property[@name='title']/@value"/></a></h1>

				<div id="chapter">
					<xsl:call-template name="ad-top"/>

					<xsl:call-template name="pages"/>

					<xsl:choose>
						<xsl:when test="count(/changes/change) &gt; 0">
							<ul style="clear:left">
								<xsl:for-each select="/changes/change">
									<xsl:sort select="position()" data-type="number" order="descending"/>

									<xsl:if test="position() &gt;= ($PAGE - 1) * $PAGESIZE and position() &lt; $PAGE * $PAGESIZE">
										<xsl:apply-templates select="."/>
									</xsl:if>
								</xsl:for-each>
							</ul>
						</xsl:when>
						<xsl:otherwise>
							<p style="clear:left"><xsl:value-of select="$locale//message[@id='NoChanges']/@text"/></p>
						</xsl:otherwise>
					</xsl:choose>
				</div>

				<xsl:call-template name="copyright"/>
			</body>
		</html>	
	</xsl:template>

	<xsl:template match="change">
		<li>
			<h2><a class="chapterlink" href="{$config//property[@name='www']/@value}{php:function('wikiUrlEncode', string(@chapter))}#block{child::previous/@id}">
				<xsl:call-template name="chapter-exactly-title">
					<xsl:with-param name="title" select="@chapter"/>
				</xsl:call-template>
			</a></h2>
			<xsl:apply-templates select="previous" mode="diff"/>
			<div class="info serv">
				<a href="{$config//property[@name='www']/@value}person/{child::previous/@author}"><xsl:value-of select="child::previous/@author"/></a>: <xsl:call-template name="time">
					<xsl:with-param name="ts" select="child::previous/@created-ts"/>
					<xsl:with-param name="date" select="child::previous/@created-date"/>
				</xsl:call-template>
			</div>
		</li>
	</xsl:template>

	<xsl:template name="pages">
		<div class="pages">
			<xsl:if test="count(/changes/change) &gt; $PAGESIZE">
				<div class="label">
					<xsl:value-of select="$locale//message[@id='Pages']/@text"/> 
				</div>
				<ul>
					<xsl:call-template name="page">
						<xsl:with-param name="page" select="1"/>
					</xsl:call-template>
				</ul>
			</xsl:if>
		</div>
	</xsl:template>

	<xsl:template name="page">
		<xsl:param name="page"/>

		<li>
			<xsl:choose>
				<xsl:when test="$page = $PAGE">
					<span>
						<xsl:value-of select="$page"/>
					</span>
				</xsl:when>
				<xsl:otherwise>
					<a href="?page={$page}">
						<xsl:value-of select="$page"/>
					</a>
				</xsl:otherwise>
			</xsl:choose>
		</li>
		<xsl:if test="$page &lt; ceiling(count(/changes/change) div $PAGESIZE)">
			<xsl:call-template name="page">
				<xsl:with-param name="page" select="$page + 1"/>
			</xsl:call-template>
		</xsl:if>
	</xsl:template>

</xsl:stylesheet>
