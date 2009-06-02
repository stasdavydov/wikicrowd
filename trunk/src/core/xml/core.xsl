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

	<xsl:param name="UID"/>
	<xsl:param name="MODE"/>
	<xsl:param name="NAME"/>
	<xsl:param name="VERSION"/>

	<xsl:include href="import.xsl"/>

	<xsl:variable name="config" select="document('../config.xml')"/>

	<xsl:template name="copyright">
		<p class="copyright">WikiCrowd <xsl:value-of select="$VERSION"/> by <a href="http://davidovsv.narod.ru/">Stas Davydov</a> and <a href="http://outcorp-ru.blogspot.com/">Outcorp</a> &#0169; 2008-2009.</p>
	</xsl:template>

	<xsl:template name="menu">
		<div class="menu">
			<xsl:if test="count(/chapter) > 0 and not($UID = '') and $MODE = 'view'">
				<a href="?">Редактировать</a>
			</xsl:if>
			<xsl:if test="count(/chapter) > 0 and not($UID = '') and $MODE = 'edit'">
				<a href="?view">Посмотреть</a>
			</xsl:if>
			<div class="person">
				<xsl:choose>
					<xsl:when test="count(/person[@uid=$UID]) = 0 and not($UID = '')"><a class="person" href="{$config//property[@name='www']/@value}person/{$UID}"><xsl:value-of select="$NAME"/></a></xsl:when>
					<xsl:when test="count(/person[@uid=$UID]) = 1">
						<span class="person"><xsl:value-of select="$NAME"/></span>
					</xsl:when>
				</xsl:choose> <xsl:choose>
					<xsl:when test="not($UID = '')"> | <a href="javascript:logout()">Выйти</a></xsl:when>
					<xsl:when test="$UID = ''"><a href="{$config//property[@name='www']/@value}auth/">Войти</a></xsl:when>
				</xsl:choose> | <a href="{$config//property[@name='www']/@value}">Домой</a>
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
			<a class="changes serv" id="loadchanges{@id}" href="javascript:loadChanges('{@id}')" title="Посмотреть список изменений">*</a>
		</xsl:if>
	</xsl:template>

<!--	<xsl:template match="text" mode="edit">
		<xsl:apply-templates select="text()"/>
	</xsl:template>
-->
	<xsl:template match="text">
		<xsl:call-template name="wiki">
			<xsl:with-param name="text"><xsl:value-of select="text()"/></xsl:with-param>
		</xsl:call-template>
	</xsl:template>

	<xsl:template name="url-encode">
		<xsl:param name="text"/>
		<xsl:choose>
			<xsl:when test="contains($text, ' ')">
				<xsl:value-of select="substring-before($text, ' ')"/>%20<xsl:call-template name="url-encode">
					<xsl:with-param name="text"><xsl:value-of select="substring-after($text, ' ')"/></xsl:with-param>
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise><xsl:value-of select="$text"/></xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template name="wiki">
		<xsl:param name="text"/>
		<xsl:choose>
			<xsl:when test="contains($text, '@page')">
				<xsl:call-template name="wiki">
					<xsl:with-param name="text" select="substring-before($text, '@page')"/>
				</xsl:call-template>
				<xsl:variable name="second" select="substring-after($text, '@page')"/>
				<xsl:variable name="name" select="substring-before(substring-after($second, '&quot;'), '&quot;')"/>
				
				<a class="link">
					<xsl:if test="$MODE = 'edit'">
						<xsl:attribute name="onclick">javascript:editOff()</xsl:attribute>
					</xsl:if>
					<xsl:attribute name="href"><xsl:choose>
					<xsl:when test="substring-after($second, '[')">
						<xsl:value-of select="substring-before(substring-after($second, '['), ']')"/>
					</xsl:when>
					<xsl:otherwise>
						<xsl:value-of select="$config//property[@name='www']/@value"/><xsl:call-template name="url-encode">
							<xsl:with-param name="text"><xsl:value-of select="$name"/></xsl:with-param>
							</xsl:call-template>
					</xsl:otherwise>
				</xsl:choose></xsl:attribute><xsl:value-of select="$name"/></a>
				<xsl:call-template name="wiki">
					<xsl:with-param name="text" select="substring-after(substring-after($second, '&quot;'), '&quot;')"/>
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise><xsl:value-of select="$text"/></xsl:otherwise>
		</xsl:choose>
	</xsl:template>
</xsl:stylesheet>

