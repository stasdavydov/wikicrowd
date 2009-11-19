<?xml version="1.0" encoding="utf-8" standalone="yes"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	exclude-result-prefixes="">
	<xsl:output 
		method="xml" 
		version="1.0" 
		indent="no" 
		encoding="UTF-8"
		omit-xml-declaration="yes"
		cdata-section-elements=""/>
	<xsl:param name="MODE"/>

	<xsl:include href="core.xsl"/>

	<xsl:template match="/">
		<table border="0" cellspacing="0" cellpadding="3">
			<xsl:choose>
				<xsl:when test="$MODE = 'sandbox'">
					<thead>
						<tr>
							<th><xsl:value-of select="$locale//message[@id='User']/@text"/></th>
							<th><xsl:value-of select="$locale//message[@id='Grant']/@text"/></th>
						</tr>
					</thead>
					<tbody>
						<xsl:apply-templates select="//person" mode="sandbox"/>
					</tbody>
				</xsl:when>
				<xsl:otherwise>
					<thead>
						<tr>
							<th><xsl:value-of select="$locale//message[@id='User']/@text"/></th>
							<th><xsl:value-of select="$locale//message[@id='Admin']/@text"/></th>
							<th><xsl:value-of select="$locale//message[@id='CanEdit']/@text"/></th>
							<th><xsl:value-of select="$locale//message[@id='CanRead']/@text"/></th>
						</tr>
					</thead>
					<tbody>
						<xsl:apply-templates select="//person"/>
					</tbody>
				</xsl:otherwise>
			</xsl:choose>
		</table>
	</xsl:template>

	<xsl:template match="person">
		<tr>
			<td>
				<a href="{$config//property[@name='www']/@value}person/{@uid}">
					<xsl:value-of select="@uid"/>
				</a>
			</td>
			<td>
				<input type="checkbox" name="user[{@uid}][admin]" value="1">
					<xsl:if test="@admin = '1' and not(@uid = 'guest')">
						<xsl:attribute name="checked">checked</xsl:attribute>
					</xsl:if>
					<xsl:if test="@uid = 'guest'">
						<xsl:attribute name="disabled">disabled</xsl:attribute>
						<xsl:attribute name="title">
							<xsl:value-of select="$locale//message[@id='GuestNotAdmin']/@text"/>
						</xsl:attribute>
					</xsl:if>
				</input>
			</td>
			<td>
				<input type="checkbox" name="user[{@uid}][canEdit]" value="1">
					<xsl:if test="@can-edit = '1'">
						<xsl:attribute name="checked">checked</xsl:attribute>
					</xsl:if>
				</input>
			</td>
			<td>
				<input type="checkbox" name="user[{@uid}][canView]" value="1">
					<xsl:if test="@can-view = '1'">
						<xsl:attribute name="checked">checked</xsl:attribute>
					</xsl:if>
				</input>
			</td>
		</tr>
	</xsl:template>

	<xsl:template match="person" mode="sandbox">
		<tr>
			<td>
				<xsl:value-of select="@uid"/>
			</td>
			<td>
				<input type="checkbox" name="sandbox[{@uid}]" value="1"/>
			</td>
		</tr>
	</xsl:template>
</xsl:stylesheet>
