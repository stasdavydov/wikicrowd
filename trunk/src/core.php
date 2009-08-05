<?
define('HOME', dirname(__FILE__).'/');
define('CACHE', HOME.'cache/');
define('PERSONS', HOME.'persons/');
define('CORE', HOME.'core/');
define('CHAPTERS', HOME.'chapters/');
define('PLUGINS', CORE.'plugins/');
$LOCKFILE = fopen(CACHE.'lock', 'w');
define('IMPORT_XSL_FILE', CORE.'xml/import.xsl');

// load configuration
if (file_exists(CORE.'config.xml')) {
	$dom = new DOMDocument();
	$dom->load(CORE.'config.xml');
	$properties = $dom->getElementsByTagName('property');
	for($i = 0; $i < $properties->length; $i++) {
		$property = $properties->item($i);
		define($property->getAttribute('name'), $property->getAttribute('value'));
	}
	define('VERSION', $dom->documentElement->getAttribute('version'));
}

// load locale
if(! defined('LOCALE'))
	define('LOCALE', 'ru');
if (file_exists(CORE.'xml/locale/'.LOCALE.'.xml')) {
	$dom = new DOMDocument();
	$dom->load(CORE.'xml/locale/'.LOCALE.'.xml');
	$messages = $dom->getElementsByTagName('message');
	for($i = 0; $i < $messages->length; $i++) {
		$message = $messages->item($i);
		define('locale\\'.$message->getAttribute('id'), $message->getAttribute('text'));
	}
}

function getMessage($id) {
	return str_replace('\\n', "\n", constant('locale\\'.$id));
}

function plugins_mtime($filePattern) {
	$d = opendir(PLUGINS);
	$mtime = 0;
	while($f = readdir($d))
		if (is_dir(PLUGINS.$f) && file_exists(PLUGINS.$f.$filePattern))
			$mtime = max($mtime, filemtime(PLUGINS.$f.$filePattern));
	closedir($d);
	return $mtime;
}

define('PROJECT_MTIME', max(plugins_mtime('/node.xsl'), 
	@filemtime(IMPORT_XSL_FILE), 
	filemtime(CORE.'xml/core.xsl'),
	filemtime(HOME.'mb_diff.php')));

// check import.xsl for plugins XSL files
if (! file_exists(IMPORT_XSL_FILE) || filemtime(IMPORT_XSL_FILE) < PROJECT_MTIME) {
	$f = fopen(IMPORT_XSL_FILE, 'w');
	fwrite($f, '<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml" exclude-result-prefixes="">
<xsl:output method="xml" version="1.0" indent="no" encoding="utf-8"	omit-xml-declaration="yes" cdata-section-elements=""/>
');
	$d = opendir(PLUGINS);
	while($plugin = readdir($d)) {
		if (is_dir(PLUGINS.$plugin) && preg_match('/^[\w\d_]+$/', $plugin)
			&& file_exists(PLUGINS.$plugin.'/node.xsl')) {
			fwrite($f, '<xsl:include href="../plugins/'.$plugin.'/node.xsl"/>
');
		}
	}
	closedir($d);
	fwrite($f, '</xsl:stylesheet>');
	fclose($f);
}

function mergePluginFiles($into, $filePart) {
	if (! file_exists($into) || filemtime($into) < plugins_mtime($filePart)) {
		$f = fopen($into, 'w');
		$d = opendir(PLUGINS);
		while($plugin = readdir($d)) {
			if (is_dir(PLUGINS.$plugin) && preg_match('/^[\w\d_]+$/', $plugin) 
				&& file_exists(PLUGINS.$plugin.$filePart)) {
				fwrite($f, file_get_contents(PLUGINS.$plugin.$filePart));
			}
		}
		closedir($d);
		fclose($f);
	}
}
mergePluginFiles(CORE.'js/plugins.js', '/node.js');
mergePluginFiles(CORE.'css/plugins.css', '/node.css');

require_once CORE.'block.php';
require_once CORE.'person.php';

define ('DEBUG', true);
if (DEBUG) {
	require_once(CORE.'firephp/fb.php');
}

function transformDOM($dom, $xslFile, $params) {
	$xsldoc = new DOMDocument();
	$xsldoc->load($xslFile);
	$xslproc = new XSLTProcessor();
	$xslproc->registerPHPFunctions();
	$xslproc->setParameter('', 'VERSION', VERSION);
	$xslproc->setParameter('', 'LOCALE', LOCALE);
	foreach($params as $param=>$value)
		$xslproc->setParameter('', $param, $value);
	$xslproc->importStyleSheet($xsldoc);
	return $xslproc->transformToXML($dom);
}

function transformXML2DOM($xmlFile, $xslFile, $params) {
	$dom = new DOMDocument();
	$dom->load($xmlFile);

	$xsldoc = new DOMDocument();
	$xsldoc->load($xslFile);
	$xslproc = new XSLTProcessor();
	$xslproc->registerPHPFunctions();
	$xslproc->setParameter('', 'VERSION', VERSION);
	$xslproc->setParameter('', 'LOCALE', LOCALE);
	foreach($params as $param=>$value)
		$xslproc->setParameter('', $param, $value);
	$xslproc->importStyleSheet($xsldoc);
	return $xslproc->transformToDoc($dom);
}

function getRelativeFilePath($path, $relatedTo) {
	if ($pos = (strpos($path, $relatedTo) === FALSE))
		return $path;
	else
		return substr_replace($path, '', $pos, strlen($relatedTo));
}

function fileNamePartEncode($name) {
	return preg_match('/^[\w\d_\-.]+$/', $name) 
		? $name
		: str_replace('/', '_', base64_encode($name));
}

function makeFileName($someFileName) {
	if (strlen($someFileName) > 100) {
		$ext = ($pos = strrpos($someFileName, '.')) !== FALSE ? substr($someFileName, $pos) : '';
		return substr($someFileName, 0, 100) . md5($someFileName). $ext;
	} else
		return $someFileName;
}

function transformXML($xmlFile, $xslFile, $params, $mtime = 0) {
	$cacheFile = CACHE.
		preg_replace('/[^\w\d-_]/', '_', getRelativeFilePath($xmlFile, HOME)).'.'.
		preg_replace('/[^\w\d-_]/', '_', getRelativeFilePath($xslFile, HOME)).'.';

	foreach($params as $name=>$value)
		$cacheFile .= $name.'_'.fileNamePartEncode($value);

	$cacheFile = makeFileName($cacheFile);

	$cache_mtime = @filemtime($cacheFile);
	if (file_exists($cacheFile) 
		&& $cache_mtime >= filemtime($xmlFile) 
		&& $cache_mtime >= filemtime($xslFile) 
		&& $cache_mtime >= $mtime)
		return file_get_contents($cacheFile);

	$dom = new DOMDocument();
	$dom->load($xmlFile);

	$data = transformDOM($dom, $xslFile, $params);
	file_put_contents($cacheFile, $data);
	return $data;
}

function enterCriticalSection($f) {
	while(! flock($f, LOCK_EX))
		usleep(100);
}

function exitCriticalSection($f) {
	flock($f, LOCK_UN);
}

function warn($msg) {
?><warn><?=$msg?></warn><?
		
	ob_end_flush();
	exit;
}

function internal($msg) {
	if (supportEmail != NULL)
		@mail (supportEmail, 
			'=?UTF-8?b?'.base64_encode('['.title.'] '.getMessage('InternalError')).'?=', 
			chunk_split(base64_encode(
			getMessage('InternalErrorOccured')."\n$msg\n\n".
			'$_SERVER: '.print_r($_SERVER, true)."\n")), 
			"From: ".title." <".supportEmail.">\n".
			"Content-Type: text/plain;\r\n\tcharset=UTF-8\n".
			"Content-Transfer-Encoding: base64\n");

	warn(getMessage('InternalError').': '.$msg);
}

function menu($current = '') {
	$person = getSessionPerson();
?><div class="menu"><div class="rightside"><?
	
	if ($current == "auth") {
?><span><?=getMessage('Login')?></span><?
	} else {
?><a class="person" href="<?=www?>person/<?=$person->getAttribute('uid')?>"><?=$person->getAttribute('name')?></a><a href="javascript:logout()"><?=getMessage('Logout')?></a><?
	}

	if ($current == "configure") {
?><span><?=getMessage('Configure')?></span><?
	} else if(isAdmin($person)) {
?><a href="<?=www?>configure/"><?=getMessage('Configure')?></a><?
	}

?><a href="<?=www?>allchanges/"><?=getMessage('AllChanges')?></a><a href="<?=
	www?>"><?=getMessage('ToHome')?></a></div></div>
<?
}

function setProperty($dom, $name, $value) {
	$xpath = new DOMXPath($dom);
	$property = $xpath->query("//property[@name = '$name']");

	if ($property->length == 0) {
		$property = $dom->createElement('property');
		$dom->documentElement->appendChild($property);
	} else
		$property = $property->item(0);

	$property->setAttribute('name', $name);
	$property->setAttribute('value', $value);
}

?>