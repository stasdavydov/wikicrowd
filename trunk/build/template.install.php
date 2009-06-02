<?php
	$version = '%version%';

	$pathinfo = pathinfo($_SERVER['SCRIPT_FILENAME']);
	$www = substr($_SERVER['REQUEST_URI'], 0, 
		strlen($_SERVER['REQUEST_URI']) - strlen($pathinfo['basename']));

	$errors = array();

	if (strcasecmp($_SERVER['REQUEST_METHOD'], 'post') == 0) {
		$title = trim($_POST['title']);
		$homePage = trim($_POST['homePage']);
		$supportEmail = trim($_POST['supportEmail']);

		if($title == "")
			$errors[] = 'Title is required';
		if ($homePage == "")
			$errors[] = 'Home page is required';

		if (count($errors) == 0) {
			// 1. unpack
			$me = fopen(__FILE__, 'r') or die('Cannot open myself');
    
			while(! feof($me) && ($line = fgets($me)) !== FALSE)
				if (preg_match('/^\/\* package/', $line))
					break;
			$chunks = '';
			while(! feof($me) && ($line = fgets($me)) !== FALSE)
				if (preg_match('/^\*\/ \?>/', $line))
					break;
				else
					$chunks .= trim($line);
    
			fclose($me);
    
			$zipFileName = 'wikicrowd.tmp.zip';
			$zip = fopen($zipFileName, 'w') or die('Cannot create temporary zip file');
			fwrite($zip, base64_decode($chunks));
			fclose($zip);
    
			$zip = new ZipArchive;
			$zip->open($zipFileName);
			$zip->extractTo(dirname(__FILE__));
			$zip->close();

			unlink($zipFileName);

			// 2. create config.xml
			$dom = new DOMDocument('1.0', 'utf-8');
			$dom->appendChild($dom->implementation->createDocumentType(
				'config', 'WikiCrowd', 'xml/wikicrowd.dtd'));
			$config = $dom->createElement('config');
			$config->setAttribute('version', $version);
			$dom->appendChild($config);

			function addProperty($dom, $name, $value) {
				$property = $dom->createElement('property');
				$property->setAttribute('name', $name);
				$property->setAttribute('value', $value);
				$dom->documentElement->appendChild($property);
			}
			addProperty($dom, 'www', $www);
			addProperty($dom, 'title', $title);
			addProperty($dom, 'supportEmail', $supportEmail);
			addProperty($dom, 'homePage', $homePage);

			$dom->save('core/config.xml');
    
			// 3. update .htaccess
			file_put_contents('.htaccess', str_replace('%www%', $www, file_get_contents('.htaccess')));

			// 4. remove myself
			unlink(__FILE__);

			// 5. redirect to home page
			header('Location: '.$www);

			exit;
		}
	}

?><html><head><title>WikiCrowd installation</title>
<style type="text/css">
body, input { font-family: "Trebuchet MS", "Arial", serif; font-size: 100%; }
fieldset { width: 32em; border: 1px dotted #999; padding: 0.5em;}
* html fieldset { width: 34em; }
input { display: block; float:right; margin: 0.75em 0 0 0;}
label { display: block; clear: both; float: left; margin: 1em 0.25em 0 0;}
.optional { color: #999; }
.error { color: #C00; }
</style>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
</head>
<body>
<h1>WikiCrowd installation</h1>
<?php
	$reqFails = array();
	// check system requirements

	@file_put_contents('test.tmp', '');
	if (! @file_exists('test.tmp')) {
		$reqFails[] = 'Have no access to create files in '.dirname(__FILE__);
	} 
	@unlink('test.tmp');

	if (version_compare(phpversion(), '5.2', '<'))
		$reqFails[] = 'PHP version 5.2 or later required';

	if (!class_exists('DOMDocument'))
		$reqFails[] = 'DOM support is required';

	if (!class_exists('XSLTProcessor'))
		$reqFails[] = 'XSL suuport is required';

	if(! function_exists('iconv'))
		$reqFails[] = 'iconv support is required';

	if (count($reqFails) > 0) {
?><p>The following issue<?= count($reqFails) > 1 ? 's' : '' ?> should be fixed before installation:</p>
<ul class="error">
<?
		foreach($reqFails as $error) {
?><li><?=$error?></li><?
		}
?></ul>
<p><a href="<?=$_SERVER['REQUEST_URI']?>">Try again</a> when fixed.</p>
<?	} else {
?>
<p>Please, correct the following information if required and press "Install".</p>
<?php

		if (count($errors) > 0) {
?><ul class="error"><?
			foreach($errors as $error) {
?><li><?=$error?></li><?
			}
?></ul><?
		}

?>
<form method="post" action="">
<fieldset>
Installing WikiCrowd into http://<?=$_SERVER['SERVER_NAME']?><?=$_SERVER['SERVER_PORT'] == 80 ? '' : ':'.$SERVER['SERVER_PORT']?><?=$www?>
<label for="title">Title of wiki site:</label> <input type="text" name="title" id="title" size="50" value="WikiCrowd"/>
<label for="homePage">Home page name:</label> <input type="text" name="homePage" id="homePage" size="50" value="Home"/>
<label class="optional" for="supportEmail"><nobr>Support's e-mail:</nobr><br/><small>(optional)</small></label> <input type="text" name="supportEmail" id="supportEmail" size="50" value=""/>
</fieldset>
<input style="float:left; padding:0.25em;font-size:110%;" type="submit" value="Install"/>
</form>
<?php

	}
?>
</body>
</html>

<?php
/* package
*/ ?>
