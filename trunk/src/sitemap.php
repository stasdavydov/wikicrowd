<?php
	require_once dirname(__FILE__).'/core.php';

	$sitemapFile = HOME.'/sitemap.xml.gz';
   	$xslFile = CORE.'xml/sitemap.xsl';

	define('LAST_MOD', 'Y-m-d');

	if (! file_exists(CHAPTERS_INDEX))
		ChapterIndex::getInstance();

	if (! file_exists($sitemapFile) || filemtime($sitemapFile) < PROJECT_MTIME 
		|| filemtime($sitemapFile) < filemtime(CHAPTERS_INDEX)) {

		$params = array(
			'BASE_URL'=>($baseUrl =
				(array_key_exists('HTTPS', $_SERVER) ? 'https' : 'http').'://'.
				$_SERVER['SERVER_NAME'].
				(! array_key_exists('SERVER_PORT', $_SERVER) || $_SERVER['SERVER_PORT'] == 80
					? '' : ':'.$_SERVER['SERVER_PORT']).
				www),
			'LAST_MOD'=>date(LAST_MOD, PROJECT_MTIME),
			'HOME'=>homePage);

		$gz = gzopen($sitemapFile, 'w9');
		gzwrite($gz, '<?xml version="1.0" encoding="utf-8"?>'."\n");
		gzwrite($gz, transformXML(CHAPTERS_INDEX, $xslFile, $params, PROJECT_MTIME));
		gzclose($gz);
		chmod($sitemapFile, 0666);
	}

	header('Content-Type: text/xml');
	header('Content-Encoding: gzip');

	readfile($sitemapFile);
?>
