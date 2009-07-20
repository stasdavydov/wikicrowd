<?php
	
	function copydir($src, $dst, $except) {
		if (preg_match($except, $src))
			return;

		echo 'Copy '.$src.' to '.$dst."\n";

		if (is_file($src))
			copy($src, $dst);
		else {
			if (! file_exists($dst))
				mkdir($dst);
	
		    if (! preg_match('/\/$/', $src))
		    	$src .= '/';
	    	if (! preg_match('/\/$/', $dst))
	    		$dst .= '/';

			$s = opendir($src) or die('Cannot open '.$src.' dir');
			$d = opendir($dst) or die('Cannot open '.$dst.' dir');

			while($f = readdir($s))
				if (! preg_match('/^\.{1,2}$/', $f))
					copydir($src.$f, $dst.$f, $except);
		}
	}

	copydir('../src', '../../test', '/(\.svn|\.htaccess)/');

?>