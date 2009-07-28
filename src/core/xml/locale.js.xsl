<?xml version="1.0" encoding="windows-1251" standalone="yes"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:str="http://exslt.org/strings"
	extension-element-prefixes="str"
	exclude-result-prefixes="">
	<xsl:output 
		method="text" 
		version="1.0" 
		indent="no" 
		encoding="utf-8"
		omit-xml-declaration="yes"
		media-type="text/plain"
		cdata-section-elements=""/>

	<xsl:include href="core.xsl"/>

	<xsl:template match="/">
var Locale = {
<xsl:apply-templates select="//message"/>
<xsl:apply-templates select="//function"/>
	getMessage: function(id) {
		var text = this[id];
		for(var i = 1; i <xsl:text disable-output-escaping="yes">&lt;</xsl:text> arguments.length; i++)
			text = text.replace('%' + i, arguments[i]);
		return text;
	}
};
	</xsl:template>

	<xsl:template match="message">
<xsl:value-of select="@id"/>: '<xsl:call-template name="str:replace">
	<xsl:with-param name="string" select="@text"/>
	<xsl:with-param name="search">'</xsl:with-param>
	<xsl:with-param name="replace">\'</xsl:with-param>
	</xsl:call-template>',
</xsl:template>

	<xsl:template match="function">
<xsl:value-of select="@id"/>: function(<xsl:value-of select="@params"/>) {
<xsl:value-of select="text()" disable-output-escaping="yes"/>
},
</xsl:template>

</xsl:stylesheet>
