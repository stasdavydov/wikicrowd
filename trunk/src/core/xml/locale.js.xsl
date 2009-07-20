<?xml version="1.0" encoding="windows-1251" standalone="yes"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
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
<xsl:apply-templates select="//message[not(@id = 'getMinutesText' or @id = 'getHoursText' or @id = 'getDaysText')]"/>
	getMessage: function(id) {
		var text = this[id];
		for(var i = 1; i <xsl:text disable-output-escaping="yes">&lt;</xsl:text> arguments.length; i++)
			text = text.replace('%' + i, arguments[i]);
		return text;
	},
	getMinutesText: function(minutes) {
		<xsl:value-of select="//message[@id = 'getMinutesText']/text()" disable-output-escaping="yes"/>
	},
	getHoursText: function(hours) {
		<xsl:value-of select="//message[@id = 'getHoursText']/text()" disable-output-escaping="yes"/>
	},
	getDaysText: function(days) {
		<xsl:value-of select="//message[@id = 'getDaysText']/text()" disable-output-escaping="yes"/>
	}
};
	</xsl:template>

	<xsl:template match="message">
<xsl:value-of select="@id"/>: '<xsl:call-template name="replace">
	<xsl:with-param name="text" select="@text"/>
	<xsl:with-param name="search">'</xsl:with-param>
	<xsl:with-param name="replace">\'</xsl:with-param>
	</xsl:call-template>',
</xsl:template>

</xsl:stylesheet>
