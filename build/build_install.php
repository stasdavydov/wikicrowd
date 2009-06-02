<?
    require '../src/core.php';
    $version = file_get_contents('version.txt');
    if(preg_match('/^(\d+)\.(\d+)\.(\d+)$/', $version, $matches)) {
    	echo "Build $matches[1].$matches[2].$matches[3]\n";
    	file_put_contents('version.txt', ($matches[1].'.'.$matches[2].'.'.($matches[3] + 1)));
	}

	// create zip version

	$fileName = 'wikicrowd-'.$version.'.zip';

	$zip = new ZipArchive;

	function addRecursive($zip, $path, $zipPath) {
		$d = opendir($path);
		while($f = readdir($d)) {
			if ($f != "." && $f != "..") {
				if (is_dir($path.$f)) {
					$zip->addEmptyDir($zipPath.$f);
					addRecursive($zip, $path.$f.'/', $zipPath.$f.'/');
				} else
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
			'chapter.php',
			'confirm.php',
			'core.php',
			'mb_diff.php',
			'person.php') as $file)
			$zip->addFile(HOME.$file, $file);

		addRecursive($zip, CACHE, 'cache/');
		addRecursive($zip, CHAPTERS, 'chapters/');
		addRecursive($zip, CORE, 'core/');
		addRecursive($zip, PERSONS, 'persons/');

		$zip->close();

		// append zip to template.install.php
		$install = fopen('install.php', 'w');
		$template = fopen('template.install.php', 'r');
		while(! feof($template) && ($line = fgets($template)) !== FALSE) {
			if (preg_match('/\%version\%/', $line))
				$line = str_replace('%version%', $version, $line);

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