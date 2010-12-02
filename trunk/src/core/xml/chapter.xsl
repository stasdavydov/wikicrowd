<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns="http://www.w3.org/1999/xhtml"
	exclude-result-prefixes="">
	<xsl:output 
		method="html" 
		version="1.0" 
		indent="no" 
		encoding="utf-8"
		omit-xml-declaration="no"
		doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"
		doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"
		media-type="text/html"
		cdata-section-elements=""/>
	<xsl:param name="UID"/>
	<xsl:param name="MODE"/>

	<xsl:include href="core.xsl"/>

	<xsl:template match="/">
		<html xml:lang="{$LOCALE}">
			<head>
				<title>
					<xsl:call-template name="chapter-title">
						<xsl:with-param name="title" select="/chapter/@title"/>
						<xsl:with-param name="woLinks">true</xsl:with-param>
					</xsl:call-template> | <xsl:value-of select="$config//property[@name='title']/@value"/>
				</title>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
				<link rel="shortcut icon" href="{$config//property[@name='www']/@value}core/img/favicon.gif" />
				<link rel="stylesheet" type="text/css" href="{$config//property[@name='www']/@value}core/css/main.css"/>
				<link rel="stylesheet" type="text/css" href="{$config//property[@name='www']/@value}core/css/plugins.css"/>
				<link rel="alternate" type="application/rss+xml" title="RSS" href="{$config//property[@name='www']/@value}allchanges/rss/"/>
				<script type="text/javascript">var www = '<xsl:value-of select="$config//property[@name='www']/@value"/>';</script>
				<script type="text/javascript" src="{$config//property[@name='www']/@value}core/js/base.js">//<!--"--></script>
				<script type="text/javascript" charset="utf-8" src="{$config//property[@name='www']/@value}core/js/locale.js">//<!--"--></script>
				<script type="text/javascript" src="{$config//property[@name='www']/@value}core/js/auth.js">//<!--"--></script>
				<xsl:if test="$MODE = 'edit'">
					<script type="text/javascript" src="{$config//property[@name='www']/@value}core/js/fade.js">//<!--"--></script>
					<script type="text/javascript" src="{$config//property[@name='www']/@value}core/js/person.js">//<!--"--></script>
					<script type="text/javascript" src="{$config//property[@name='www']/@value}core/js/chapter.js">//<!--"--></script>
					<script type="text/javascript" src="{$config//property[@name='www']/@value}core/js/plugins.js">//<!--"--></script>
				</xsl:if>
			</head>
			<body class="{$MODE}">
				<xsl:call-template name="menu">
					<xsl:with-param name="page">
						<xsl:if test="$config//property[@name='homePage']/@value = /chapter/@title">
							<xsl:text>home</xsl:text>
						</xsl:if>
					</xsl:with-param>
				</xsl:call-template>
				<xsl:if test="$MODE = 'edit'">
					<div class="help">
						<div class="content" id="help-content">
							<xsl:value-of select="$locale//message[@id='WikiHelp']" disable-output-escaping="yes"/>
						</div>
					</div>
				</xsl:if>
				<div class="part" id="chaptertitle__chaptertitle__0">
					<h1>
						<xsl:call-template name="chapter-exactly-title">
							<xsl:with-param name="title" select="/chapter/@title"/>
						</xsl:call-template>
					</h1>
				</div>
				<div id="chapter">
					<p class="breadcrump"> 
						<a href="{$config//property[@name='www']/@value}"><xsl:value-of select="$config//property[@name='title']/@value"/></a> &#0187; <xsl:call-template name="breadcrump">
							<xsl:with-param name="title" select="/chapter/@title"/>
						</xsl:call-template>
					</p>

					<xsl:choose>
						<xsl:when test="$MODE = 'edit'">
							<xsl:for-each select="/chapter/block">
								<div class="part" id="{@id}__{@type}__{@rev}">
									<xsl:apply-templates select="." mode="wiki"/>
									<xsl:call-template name="changes"/>
								</div>
							</xsl:for-each>                                              
						</xsl:when>

						<xsl:when test="$MODE = 'restricted'">
							<p><xsl:value-of select="$locale//message[@id='YouHaveNoPermissions']/@text"/></p>
						</xsl:when>

						<xsl:otherwise>
							<xsl:for-each select="/chapter/block[not(@deleted)]">
								<div class="part" id="{@id}__{@type}__{@rev}">
									<xsl:apply-templates select="." mode="wiki"/>
								</div>
							</xsl:for-each>
						</xsl:otherwise>
					</xsl:choose>
				</div>
				<xsl:call-template name="copyright"/>
			</body>
		</html>	
	</xsl:template>
</xsl:stylesheet>
