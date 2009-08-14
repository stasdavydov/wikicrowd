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

	<xsl:template name="wiki">
		<xsl:param name="text"/>

<!--		<xsl:comment>wiki(<xsl:value-of select="$text"/>)</xsl:comment>-->
		<xsl:choose>
			<xsl:when test="contains($text,' *')">
				<xsl:call-template name="wiki">
					<xsl:with-param name="text" select="concat(substring-before($text, ' *'),' ')"/>
				</xsl:call-template>
				<xsl:variable name="bold" select="substring-before(substring-after($text, ' *'),'*')"/>
				<strong>
				<xsl:call-template name="wiki">
					<xsl:with-param name="text" select="$bold"/>
				</xsl:call-template>	
				</strong>				
				<xsl:call-template name="wiki">
					<xsl:with-param name="text" select="concat(' ',substring-after(substring-after($text,' *'),'*'))"/>
				</xsl:call-template>				
			</xsl:when>	

			<xsl:when test="contains($text,' /')">
				<xsl:call-template name="wiki">
					<xsl:with-param name="text" select="concat(substring-before($text, ' /'),' ')"/>
				</xsl:call-template>
				<xsl:variable name="italic" select="substring-before(substring-after($text, ' /'),'/')"/>
				<i>
				<xsl:call-template name="wiki">
					<xsl:with-param name="text" select="$italic"/>
				</xsl:call-template>	
				</i>				
				<xsl:call-template name="wiki">
					<xsl:with-param name="text" select="concat(' ',substring-after(substring-after($text,' /'),'/'))"/>
				</xsl:call-template>				
			</xsl:when>	
			
			<xsl:when test="contains($text,' _')">
				<xsl:call-template name="wiki">
					<xsl:with-param name="text" select="concat(substring-before($text, ' _'),' ')"/>
				</xsl:call-template>
				<xsl:variable name="sub" select="substring-before(substring-after($text, ' _'),'_')"/>
				<u>
				<xsl:call-template name="wiki">
					<xsl:with-param name="text" select="$sub"/>
				</xsl:call-template>	
				</u>				
				<xsl:call-template name="wiki">
					<xsl:with-param name="text" select="concat(' ',substring-after(substring-after($text,' _'),'_'))"/>
				</xsl:call-template>				
			</xsl:when>	
		
			<!-- @page "inner"
				 @page[URL] "outer" -->
			 
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
						<xsl:variable name="uri" select="substring-before(substring-after($second, '['), ']')"/>
						<xsl:choose>
							<xsl:when test="contains($uri, 'http://')">
								<xsl:value-of select="$uri"/>
							</xsl:when>
							<xsl:otherwise>
								<xsl:value-of select="php:function('rawurlencode', $uri)"/>
								<xsl:if test="$MODE = 'view'">?view</xsl:if>
							</xsl:otherwise>
						</xsl:choose>
					</xsl:when>
					<xsl:otherwise>
						<xsl:value-of select="$config//property[@name='www']/@value"/><xsl:value-of select="php:function('rawurlencode', $name)"/>
					</xsl:otherwise>
				</xsl:choose></xsl:attribute><xsl:value-of select="$name"/></a>
				<xsl:call-template name="wiki">
					<xsl:with-param name="text" select="substring-after(substring-after($second, '&quot;'), '&quot;')"/>
				</xsl:call-template>
			</xsl:when>
			
			<xsl:otherwise>
			<xsl:value-of select="$text"/>
			</xsl:otherwise>
	
		</xsl:choose>
	</xsl:template>
</xsl:stylesheet>
