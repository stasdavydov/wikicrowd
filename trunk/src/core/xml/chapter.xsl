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
		doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN"
		doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"
		media-type="text/html"
		cdata-section-elements=""/>
	<xsl:param name="UID"/>
	<xsl:param name="MODE"/>

	<xsl:include href="core.xsl"/>

	<xsl:template match="/">
		<html xml:lang="ru" lang="RU">
			<head><title><xsl:value-of select="/chapter/@title"/> | <xsl:value-of select="$config//property[@name='title']/@value"/></title>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
				<link rel="shortcut icon" href="{$config//property[@name='www']/@value}core/img/favicon.gif" />
				<link rel="stylesheet" type="text/css" href="{$config//property[@name='www']/@value}core/css/main.css"/>
				<link rel="stylesheet" type="text/css" href="{$config//property[@name='www']/@value}core/css/plugins.css"/>
				<style type="text/css">h1 { margin: 0.25em 0 0.5em 0.65em; } </style>
				<script type="text/javascript">var www = '<xsl:value-of select="$config//property[@name='www']/@value"/>';</script>
				<script type="text/javascript" charset="windows-1251" src="{$config//property[@name='www']/@value}core/js/base.js">//<!--"--></script>
				<script type="text/javascript" charset="utf-8" src="{$config//property[@name='www']/@value}core/js/locale.js">//<!--"--></script>
				<script type="text/javascript" charset="windows-1251" src="{$config//property[@name='www']/@value}core/js/auth.js">//<!--"--></script>
				<xsl:if test="$MODE = 'edit'">
					<script type="text/javascript" charset="windows-1251" src="{$config//property[@name='www']/@value}core/js/fade.js">//<!--"--></script>
					<script type="text/javascript" charset="windows-1251" src="{$config//property[@name='www']/@value}core/js/person.js">//<!--"--></script>
					<script type="text/javascript" charset="windows-1251" src="{$config//property[@name='www']/@value}core/js/chapter.js">//<!--"--></script>
					<script type="text/javascript" charset="windows-1251" src="{$config//property[@name='www']/@value}core/js/plugins.js">//<!--"--></script>
				</xsl:if>
			</head>
			<body class="{$MODE}">
				<xsl:call-template name="menu"/>
				<xsl:if test="$MODE = 'edit'">
					<div class="help"><a href="javascript:help()" title="{$locale//message[@id='WikiHelpTip']/@text}">?</a>
						<div class="content" id="help-content">
							<xsl:value-of select="$locale//message[@id='WikiHelp']" disable-output-escaping="yes"/>
						</div>
					</div>
				</xsl:if>
				<h1><xsl:value-of select="/chapter/@title"/> &#0187; <a href="{$config//property[@name='www']/@value}"><xsl:value-of select="$config//property[@name='title']/@value"/></a></h1>
				<div id="chapter">
					<xsl:choose>
						<xsl:when test="$MODE = 'edit'">
							<xsl:for-each select="/chapter/block">
								<div class="part" id="{@id}:{@type}:{@rev}">
									<xsl:apply-templates select="." mode="wiki"/>
									<xsl:call-template name="changes"/>
								</div>
							</xsl:for-each>                                              
						</xsl:when>

						<xsl:otherwise>
							<xsl:for-each select="/chapter/block[not(@deleted)]">
								<div class="part" id="{@id}:{@type}:{@rev}">
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
