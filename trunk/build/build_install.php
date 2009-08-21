<?
    require '../src/core.php';
    $version = file_get_contents('version.txt');
    if(preg_match('/^(\d+)\.(\d+)\.(\d+)$/', $version, $matches)) {
    	$version = $matches[1].'.'.$matches[2].'.'.($matches[3] + 1);
    	echo "Build $version\n";
//    	file_put_contents('version.txt', $version);
	}

	// create zip version

	$fileName = 'wikicrowd-'.$version.'.zip';

	$zip = new ZipArchive;

	function addRecursive($zip, $path, $zipPath) {
		$d = opendir($path);
		while($f = readdir($d)) {
			if (preg_match('/^(\.|\.{2}|\.svn)$/', $f))
				continue;

			if (is_dir($path.$f)) {
				$zip->addEmptyDir($zipPath.$f);
				addRecursive($zip, $path.$f.'/', $zipPath.$f.'/');
			} else {
				$zip->addFile($path.$f, $zipPath.$f);
			}
		}
		closedir($d);
	}

	if (($res = $zip->open($fileName, ZIPARCHIVE::OVERWRITE)) === TRUE) {

		foreach(array(
			'.htaccess',
			'ajax.php',
			'auth.php',
			'changes.php',
			'chapter.php',
			'confirm.php',
			'configure.php',
			'core.php',
			'mb_diff.php',
			'rss.php',
			'user.php') as $file)
			$zip->addFile(HOME.$file, $file);

		addRecursive($zip, CACHE, 'cache/');
		addRecursive($zip, CHAPTERS, 'chapters/');
		addRecursive($zip, CORE, 'core/');
		addRecursive($zip, PERSONS, 'persons/');
		addRecursive($zip, HOME.'../build/migrate/', 'migrate/');

		$zip->close();

		// append zip to template.install.php
		$install = fopen('install.php', 'w');
		$template = fopen('template.install.php', 'r');
		while(! feof($template) && ($line = fgets($template)) !== FALSE) {
			if (preg_match('/\%version\%/', $line))
				$line = str_replace('%version%', $version, $line);

			if (preg_match('/\%emailregexp\%/', $line))
				$line = str_replace('%emailregexp%', EMAIL_REGEXP, $line);

			if (preg_match('/\%locales\%/', $line)) {
				echo 'Detect locales... ';
				$options = '';
				$dir = opendir(CORE.'xml/locale');
				while($f = readdir($dir)) {
					if (preg_match('/^([^.]+)\.xml$/', $f, $matchs)) {
						$locale = $matchs[1];
						echo $locale.' ';

						$dom = new DOMDocument();
						$dom->load(CORE.'xml/locale/'.$locale.'.xml');

						$options .= '\''.$locale.'\'=>\''.$dom->documentElement->getAttribute('language').'\',';
					}
				}
				closedir($dir);
				echo "\n";

				$line = str_replace('%locales%', $options, $line);
			}

			if (preg_match('/%embed\(([^)]+)\)%/', $line, $matchs)) {
				echo "Embed {$matchs[1]}\n";
				$line = '?>'.file_get_contents($matchs[1]).'<?';
			}

			fwrite($install, $line);

			if (preg_match('/^\/\* package/', $line)) {
				fwrite($install, chunk_split(base64_encode(file_get_contents($fileName))));
			}
		}
		fclose($template);
		fclose($install);

		unlink ($fileName);

		// create distributioin zip
		$zip = new ZipArchive;
		$zip->open($fileName, ZIPARCHIVE::OVERWRITE);
		$zip->addFile('readme.txt');
		$zip->addFile('install.php');
		$zip->close();

	} else {
		die('Cannot create '.$fileName.', code: '.$res);
	}
?>