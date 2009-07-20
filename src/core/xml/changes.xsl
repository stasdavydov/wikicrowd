<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	exclude-result-prefixes="">

	<xsl:output 
		method="xml" 
		version="1.0" 
		indent="no" 
		encoding="utf-8"
		omit-xml-declaration="yes"
		cdata-section-elements=""/>

	<xsl:include href="core.xsl"/>

	<xsl:template match="/">
		<ul id="changes{$ID}">
			<xsl:for-each select="//previous">
				<xsl:sort select="@rev" data-type="number" order="descending"/>
				<li>
					<xsl:apply-templates select="." mode="diff"/>
					<div class="info serv">
						<a href="{$config//property[@name='www']/@value}person/{@author}"><xsl:value-of select="@author"/></a>: <span id="time{$ID}_{position()}"><xsl:value-of select="@created-date"/><script type="text/javascript">$('time<xsl:value-of select="$ID"/>_<xsl:value-of select="position()"/>').innerHTML=getTextTimeDifference(<xsl:value-of select="@created-ts"/>);</script></span>
						<xsl:if test="position() = 1"> (<xsl:value-of select="$locale//message[@id='LastVersion']/@text"/>)</xsl:if>
						<xsl:if test="position() = last()"> (<xsl:value-of select="$locale//message[@id='Original']/@text"/>)</xsl:if>
					</div>
				</li>
			</xsl:for-each>
			<li><a class="serv" href="javascript:closeChanges('{$ID}')"><xsl:value-of select="$locale//message[@id='CloseList']/@text"/></a></li>
		</ul>
	</xsl:template>

	<xsl:template match="del">
		<del><xsl:value-of select="."/></del>
	</xsl:template>

	<xsl:template match="ins">
		<ins><xsl:apply-templates/></ins>
	</xsl:template>
</xsl:stylesheet>
