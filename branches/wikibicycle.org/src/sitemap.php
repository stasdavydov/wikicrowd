<?php
	require_once dirname(__FILE__).'/core.php';

	$sitemapFile = HOME.'/sitemap.xml.gz';

	define('LAST_MOD', 'Y-m-d');

	if (! file_exists($sitemapFile) || filemtime($sitemapFile) < PROJECT_MTIME) {
		$f = gzopen(HOME.'/sitemap.xml.gz', 'w9');

		gzwrite($f, '<?xml version="1.0" encoding="UTF-8"?>'."\n");
		gzwrite($f, '<urlset xmlns="http://www.google.com/schemas/sitemap/0.84">'.
			'<url>'.
				'<loc>'.($baseUrl =
				(array_key_exists('HTTPS', $_SERVER) ? 'https' : 'http').'://'.
				$_SERVER['SERVER_NAME'].
				(! array_key_exists('SERVER_PORT', $_SERVER) || $_SERVER['SERVER_PORT'] == 80
					? '' : ':'.$_SERVER['SERVER_PORT']).
				www).'</loc>'.
				'<lastmod>'.($modTime = date(LAST_MOD, PROJECT_MTIME)).'</lastmod>'.
				'<changefreq>weekly</changefreq>'.
				'<priority>0.5</priority>'.
			'</url>'.
			'<url>'.
				'<loc>'.$baseUrl.'allchanges/</loc>'.
				'<lastmod>'.$modTime.'</lastmod>'.
				'<changefreq>daily</changefreq>'.
				'<priority>0.7</priority>'.
			'</url>');
		$dir = opendir(CHAPTERS);
		while($file = readdir($dir)) {
			if (preg_match('/\.xml$/', $file)) {
				if(preg_match('/<chapter title="(?P<name>[^"]+)"/', 
					file_get_contents(CHAPTERS.$file), $matches)) {
					$name = wikiUrlEncode($matches['name']);
					gzwrite($f, 
						'<url>'.
							'<loc>'.$baseUrl.$name.'</loc>'.
							'<lastmod>'.$modTime.'</lastmod>'.
							'<changefreq>monthly</changefreq>'.
							'<priority>0.2</priority>'.
						'</url>');
				}
			}
		}
		closedir($dir);

		gzwrite($f, "</urlset>\n");
		gzclose($f);
	}

	header('Content-Type: text/xml');
	header('Content-Encoding: gzip');

	readfile($sitemapFile);
?>
