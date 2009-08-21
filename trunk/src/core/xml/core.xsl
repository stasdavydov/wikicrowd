<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0"
	xmlns="http://www.w3.org/1999/xhtml"
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
		cdata-section-elements=""/>

	<xsl:param name="ADMIN"/>
	<xsl:param name="CANEDIT"/>
	<xsl:param name="CANVIEW"/>
	<xsl:param name="UID"/>
	<xsl:param name="MODE"/>
	<xsl:param name="NAME"/>
	<xsl:param name="VERSION"/>
	<xsl:param name="LOCALE"/>

	<xsl:include href="import.xsl"/>

	<xsl:variable name="config" select="document('../config.xml')"/>
	<xsl:variable name="locale" select="document(concat('locale/', $LOCALE, '.xml'))/locale"/>

	<xsl:template name="copyright">
		<p class="copyright"><a href="http://code.google.com/p/wikicrowd/">WikiCrowd</a> v.<xsl:value-of select="$VERSION"/> by <a href="http://davidovsv.narod.ru/">Stas Davydov</a> and <a href="http://outcorp-ru.blogspot.com/">Outcorp</a>.<br/>
			<xsl:choose>
				<xsl:when test="$config//property[@name='license']/@value">
					<xsl:value-of select="$config//property[@name='license']/@value" disable-output-escaping="yes"/>
				</xsl:when>
				<xsl:otherwise>License: <a href="http://www.gnu.org/licenses/lgpl.html">LGPL</a>.</xsl:otherwise>
			</xsl:choose>
		</p>
	</xsl:template>

	<xsl:template name="menu">
		<xsl:param name="page"/>
		<div class="menu">
			<div class="leftside">
				<xsl:choose>
					<xsl:when test="count(/chapter) > 0 and $MODE = 'view' and $CANEDIT = '1'">
						<a href="?"><xsl:value-of select="$locale//message[@id='edit']/@text"/></a>
					</xsl:when>
					<xsl:when test="count(/chapter) > 0 and $MODE = 'edit' and $CANVIEW = '1'">
						<a href="?view"><xsl:value-of select="$locale//message[@id='view']/@text"/></a>
					</xsl:when>
				</xsl:choose>
			</div>
			<div class="rightside">
				<xsl:choose>
					<xsl:when test="count(/person[@uid=$UID]) = 0 and not($UID = 'guest')">
						<a class="person" href="{$config//property[@name='www']/@value}person/{$UID}"><xsl:value-of select="$NAME"/></a>
					</xsl:when>
					<xsl:when test="count(/person[@uid=$UID]) = 1 and not($UID = 'guest')">
						<span class="person"><xsl:value-of select="$NAME"/></span>
					</xsl:when>
				</xsl:choose>
				<xsl:choose>
					<xsl:when test="not($UID = 'guest')">
						<a href="javascript:logout()"><xsl:value-of select="$locale//message[@id='Logout']/@text"/></a>
					</xsl:when>
					<xsl:when test="$UID = 'guest'">
						<a href="{$config//property[@name='www']/@value}auth/"><xsl:value-of select="$locale//message[@id='Login']/@text"/></a>
					</xsl:when>
				</xsl:choose>
				<xsl:if test="$ADMIN = '1'">
					<a href="{$config//property[@name='www']/@value}configure/"><xsl:value-of select="$locale//message[@id='Configure']/@text"/></a>
				</xsl:if>
				<xsl:choose>
					<xsl:when test="$page = 'allchanges'">
						<span class="selected"><xsl:value-of select="$locale//message[@id='AllChanges']/@text"/></span>
					</xsl:when>
					<xsl:otherwise>
						<a href="{$config//property[@name='www']/@value}allchanges/"><xsl:value-of select="$locale//message[@id='AllChanges']/@text"/></a>
					</xsl:otherwise>
				</xsl:choose>
				<xsl:choose>
					<xsl:when test="$page = 'home'">
						<span class="selected"><xsl:value-of select="$locale//message[@id='ToHome']/@text"/></span>
					</xsl:when>
					<xsl:otherwise>
						<a href="{$config//property[@name='www']/@value}"><xsl:value-of select="$locale//message[@id='ToHome']/@text"/></a>
					</xsl:otherwise>
				</xsl:choose>
			</div>
		</div>
	</xsl:template>

	<xsl:template name="link">
   		<xsl:param name="text"/>
		<xsl:param name="delimeter"/>
		<a href="http://{substring-before($text, $delimeter)}">http://<xsl:value-of 
			select="substring-before($text, $delimeter)"/></a>
		<xsl:call-template name="textbr">
			<xsl:with-param name="text"><xsl:value-of select="$delimeter"/><xsl:value-of select="substring-after($text, $delimeter)"/></xsl:with-param>
		</xsl:call-template>
	</xsl:template>

	<xsl:template name="textbr">
		<xsl:param name="text"/>
		<xsl:choose>
			<xsl:when test="contains($text, '&#xD;')">
				<xsl:call-template name="textbr">
					<xsl:with-param name="text" select="substring-before($text, '&#xD;')"/>
				</xsl:call-template>
				<xsl:call-template name="textbr">
					<xsl:with-param name="text" select="substring-after($text, '&#xD;')"/>
				</xsl:call-template>
			</xsl:when>

			<xsl:when test="contains($text, '&#xA;')">
				<xsl:call-template name="textbr">
					<xsl:with-param name="text" select="substring-before($text, '&#xA;')"/>
				</xsl:call-template>
				<br/>
				<xsl:call-template name="textbr">
					<xsl:with-param name="text" select="substring-after($text, '&#xA;')"/>
				</xsl:call-template>
			</xsl:when>

			<xsl:when test="contains($text, ' - ')">
				<xsl:call-template name="textbr">
					<xsl:with-param name="text" select="substring-before($text, ' - ')"/>
				</xsl:call-template>
				<xsl:text disable-output-escaping="yes">&amp;nbsp;&amp;mdash; </xsl:text>
				<xsl:call-template name="textbr">
					<xsl:with-param name="text" select="substring-after($text, ' - ')"/>
				</xsl:call-template>
			</xsl:when>

			<xsl:when test="contains($text, 'http://')">
				<xsl:call-template name="textbr">
					<xsl:with-param name="text" select="substring-before($text, 'http://')"/>
				</xsl:call-template>
				<xsl:variable name="second" select="substring-after($text, 'http://')"/>
				
				<xsl:choose>
					<xsl:when test="contains($second, ' ')">
						<xsl:call-template name="link">
							<xsl:with-param name="text" select="$second"/>
							<xsl:with-param name="delimeter" select="' '"/>
						</xsl:call-template>
					</xsl:when>
					<xsl:when test="contains($second, '&#xA;')">
						<xsl:call-template name="link">
							<xsl:with-param name="text" select="$second"/>
							<xsl:with-param name="delimeter" select="'&#xA;'"/>
						</xsl:call-template>
					</xsl:when>
					<xsl:when test="contains($second, '&lt;')">
						<xsl:call-template name="link">
							<xsl:with-param name="text" select="$second"/>
							<xsl:with-param name="delimeter" select="'&lt;'"/>
						</xsl:call-template>
					</xsl:when>
					<xsl:when test="contains($second, '&gt;')">
						<xsl:call-template name="link">
							<xsl:with-param name="text" select="$second"/>
							<xsl:with-param name="delimeter" select="'&gt;'"/>
						</xsl:call-template>
					</xsl:when>
					<xsl:otherwise>
						<a href="http://{$second}">http://<xsl:value-of select="$second"/></a>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:when>

			<xsl:otherwise><xsl:value-of select="$text"/></xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template name="changes">
		<xsl:if test="count(previous) > 0">
			<a class="changes serv" id="loadchanges{@id}" href="javascript:loadChanges('{@id}')" title="{$locale//message[@id='ViewChangeList']/@text}">*</a>
		</xsl:if>
	</xsl:template>

	<xsl:template match="text">
		<xsl:call-template name="wiki">
			<xsl:with-param name="text"><xsl:value-of select="text()"/></xsl:with-param>
		</xsl:call-template>
	</xsl:template>

	<xsl:template name="time">
		<xsl:param name="ts"/>
		<xsl:param name="date"/>
		<span class="time:{$ts}"><xsl:value-of select="$date"/></span>
	</xsl:template>

	<xsl:template name="chapter-title">
		<xsl:param name="title"/>
		<xsl:param name="islink">false</xsl:param>
		<xsl:param name="linkprefix"/>
		<xsl:param name="woLinks">false</xsl:param>
		<xsl:choose>
			<xsl:when test="contains($title, '/')">
				<xsl:call-template name="chapter-title">
					<xsl:with-param name="title" select="substring-after($title, '/')"/>
					<xsl:with-param name="islink" select="$islink"/>
					<xsl:with-param name="linkprefix" select="substring-before($title, '/')"/>
					<xsl:with-param name="woLinks" select="$woLinks"/>
				</xsl:call-template>
				<xsl:text> &#0187; </xsl:text>
				<xsl:call-template name="chapter-title">
					<xsl:with-param name="title" select="substring-before($title, '/')"/>
					<xsl:with-param name="islink">true</xsl:with-param>
					<xsl:with-param name="linkprefix" select="concat($linkprefix, '/')"/>
					<xsl:with-param name="woLinks" select="$woLinks"/>
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:choose>
					<xsl:when test="$islink = 'true' and not($woLinks = 'true')">
						<a>
							<xsl:attribute name="href">
								<xsl:value-of select="$config//property[@name='www']/@value"/>
								<xsl:if test="not($linkprefix = '/')">
									<xsl:value-of select="php:function('wikiUrlEncode', $linkprefix)"/>
								</xsl:if>
								<xsl:value-of select="php:function('wikiUrlEncode', substring-before($title, '/'))"/>
								<xsl:value-of select="php:function('wikiUrlEncode', $title)"/>
								<xsl:if test="$MODE = 'view' and $CANVIEW = '1'">?view</xsl:if>
							</xsl:attribute>
							<xsl:value-of select="$title"/>
						</a>
					</xsl:when>
					<xsl:otherwise>
						<xsl:value-of select="$title"/>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template name="breadcrump">
		<xsl:param name="title"/>
		<xsl:param name="islink">false</xsl:param>
		<xsl:param name="linkprefix"/>
		<xsl:choose>
			<xsl:when test="contains($title, '/')">
				<xsl:call-template name="breadcrump">
					<xsl:with-param name="title" select="substring-before($title, '/')"/>
					<xsl:with-param name="islink">true</xsl:with-param>
					<xsl:with-param name="linkprefix" select="concat($linkprefix, '/')"/>
				</xsl:call-template>
				<xsl:text> &#0187; </xsl:text>
				<xsl:call-template name="breadcrump">
					<xsl:with-param name="title" select="substring-after($title, '/')"/>
					<xsl:with-param name="islink" select="$islink"/>
					<xsl:with-param name="linkprefix" select="substring-before($title, '/')"/>
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:choose>
					<xsl:when test="$islink = 'true'">
						<a>
							<xsl:attribute name="href">
								<xsl:value-of select="$config//property[@name='www']/@value"/>
								<xsl:if test="not($linkprefix = '/')">
									<xsl:value-of select="php:function('wikiUrlEncode', $linkprefix)"/>
								</xsl:if>
								<xsl:value-of select="php:function('wikiUrlEncode', substring-before($title, '/'))"/>
								<xsl:value-of select="php:function('wikiUrlEncode', $title)"/>
								<xsl:if test="$MODE = 'view' and $CANVIEW = '1'">?view</xsl:if>
							</xsl:attribute>
							<xsl:value-of select="$title"/>
						</a>
					</xsl:when>
					<xsl:otherwise>
						<xsl:value-of select="$title"/>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template name="chapter-exactly-title">
		<xsl:param name="title"/>
		<xsl:choose>
			<xsl:when test="contains($title, '/')">
				<xsl:call-template name="chapter-exactly-title">
					<xsl:with-param name="title" select="substring-after($title, '/')"/>
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$title"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
</xsl:stylesheet>

