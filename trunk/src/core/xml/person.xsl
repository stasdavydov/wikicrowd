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
		<xsl:choose>
			<xsl:when test="$MODE = 'edit'">
				<xsl:apply-templates mode="edit"/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:apply-templates mode="view"/>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template match="person" mode="edit">
		<html xml:lang="ru" lang="RU">
			<head><title><xsl:value-of select="/person/@name"/> | <xsl:value-of select="$config//property[@name='title']/@value"/></title>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
				<link rel="stylesheet" type="text/css" href="{$config//property[@name='www']/@value}core/css/main.css"/>
				<style type="text/css">
h1 { margin: 0.25em 0 0.5em 0.65em; }
.form { width: 46%; margin: 1em 0 1em 0; padding: 0.25em; }
.form input { font-size: 100%; margin: 0.5em 0 0 0; padding: 0.15em; }
.form label { display: block; margin: 0; }
.form label input { display: block; font: 100%/100% sans-serif; margin: 0 0 0.5em 0; padding: 0; width: 40%;}
.form label textarea { display: block; width: 99%; }
.form .error, .form .notice { font-weight: bold; }
.form label input.inline { width: auto; margin: 0.2em;}
.form label.cb { margin: 0 0 0.5em 0; }
				</style>
				<script type="text/javascript">var www = '<xsl:value-of select="$config//property[@name='www']/@value"/>';</script>
				<script type="text/javascript" charset="windows-1251" src="{$config//property[@name='www']/@value}core/js/base.js">//<!--"--></script>
				<script type="text/javascript" charset="windows-1251" src="{$config//property[@name='www']/@value}core/js/auth.js">//<!--"--></script>
				<script type="text/javascript" charset="windows-1251" src="{$config//property[@name='www']/@value}core/js/person.js">//<!--"--></script>
			</head>
			<body>
				<xsl:call-template name="menu"/>
				<h1><xsl:value-of select="/person/@name"/> &#0187; <a href="{$config//property[@name='www']/@value}"><xsl:value-of select="$config//property[@name='title']/@value"/></a></h1>
				<div id="person" class="form">
					<form method="get" action="" onsubmit="javascript:return savePerson()">
						<input type="hidden" id="originalemail" value="{/person/@email}"/>
						<label for="name">Ваше имя: <input type="text" id="name" value="{/person/@name}"/></label>
						<label for="email">Ваш e-mail (для связи, на сайте не публикуется): <input type="text" id="email" value="{/person/@email}"/></label>
						<label for="notify" class="cb">Присылать уведомления об ответах на форуме <input type="checkbox" class="inline" id="notify">
							<xsl:if test="/person/@notify = 'true'">
								<xsl:attribute name="checked">checked</xsl:attribute>
							</xsl:if>
						</input></label>
						<label for="info">Информация о Вас (будет опубликована на сайте): <textarea id="info" rows="7" cols="60"><xsl:value-of select="/person/info"/></textarea></label>
						<label><br/>Чтобы изменить пароль, введите Ваш старый и новый пароли:</label>
						<label for="regoldpassword">Старый пароль:<input type="text" id="regoldpassword"/></label>
						<label for="regpassword">Новый пароль:<input type="text" id="regpassword"/></label>

						<div id="regnotice"></div>
						<input type="submit" value="Сохранить"/>
					</form>
				</div>
				<xsl:call-template name="copyright"/>
			</body>
		</html>	
	</xsl:template>

	<xsl:template match="person" mode="view">
		<html xml:lang="ru" lang="RU">
			<head><title><xsl:value-of select="/person/@name"/> | <xsl:value-of select="$config//property[@name='title']/@value"/></title>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
				<script type="text/javascript">var www = '<xsl:value-of select="$config//property[@name='www']/@value"/>';</script>
				<script type="text/javascript" charset="windows-1251" src="{$config//property[@name='www']/@value}core/js/base.js">//<!--"--></script>
				<script type="text/javascript" charset="windows-1251" src="{$config//property[@name='www']/@value}core/js/auth.js">//<!--"--></script>
				<script type="text/javascript" charset="windows-1251" src="{$config//property[@name='www']/@value}core/js/person.js">//<!--"--></script>
				<link rel="stylesheet" type="text/css" href="{$config//property[@name='www']/@value}core/css/main.css"/>
				<style type="text/css">h1 { margin: 0.25em 0 0.5em 0.65em; }</style>
			</head>
			<body>
				<xsl:call-template name="menu"/>
				<h1><xsl:value-of select="/person/@name"/> &#0187; <a href="{$config//property[@name='www']/@value}"><xsl:value-of select="$config//property[@name='title']/@value"/></a></h1>
				<div id="person">
					<xsl:call-template name="textbr">
						<xsl:with-param name="text"><xsl:value-of select="info"/></xsl:with-param>
					</xsl:call-template>
				</div>
				<xsl:call-template name="copyright"/>
			</body>
		</html>	
	</xsl:template>
</xsl:stylesheet>
