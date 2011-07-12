<?php
/*
	Rename chapter files to fit in to 100 character length.
*/
class migrate_0_0_17_to_0_0_18 implements migration {
	public function getFromVersion() { return '0.0.17'; }
	public function getToVersion() { return '0.0.18'; }

	public function customUI() { return FALSE; }

	public function checkCustomUI(&$errors) {}

	public function doMigrate() { 
		$maxFileLen = 100;
		$hashlen = 32;

		$dir = opendir($chapterPath = getcwd().'/chapters/');
		while($f = readdir($dir)) {
			if (preg_match('/\.xml$/', $f) && strlen($f) > $maxFileLen + strlen('.xml')) {
				$newName = substr($f, 0, $maxFileLen - $hashlen) . 
					substr($f, -strlen('.xml')-$hashlen, $hashlen) . '.xml';
				if (! rename($chapterPath.$f, $chapterPath.$newName)) {
					echo "Cannot rename $chapterPath$f to $chapterPath$newName\n";
					return false;
				}
			}
		}
		closedir($dir);

		return TRUE;
	}
}

migrator::getInstance()->addToChain(new migrate_0_0_17_to_0_0_18());
?>