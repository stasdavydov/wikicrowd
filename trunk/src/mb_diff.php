<?
function myUtf8_encode($utf8str) {
	$len = iconv_strlen($utf8str, 'utf-8');

	if ($len == 0)
		return array();

	$myStr = array_fill(0, $len, ' ');
	for($i = 0; $i<$len; $i++)
		$myStr[$i] = iconv_substr($utf8str, $i, 1, 'utf-8');
	return $myStr;
}

function myUtf8_decode($utf8str) {
	return implode($utf8str);
}

function myUtf8_strlen($utf8Str) {
	return count($utf8Str);
}

function myUtf8_append($str1, $str2) {
	if (is_array($str2))
		foreach($str2 as $c)
			$str1[] = $c;
	else
		$str1[] = $str2;

	return $str1;
}

function myUtf8_substr($utf8Str, $from, $length = NULL) {
	return array_slice($utf8Str, $from, $length ? $length : (myUtf8_strlen($utf8Str) - $from));
}

//$myStr = myUtf8_encode(iconv('windows-1251', 'utf-8', 'Жопа!'));
//print_r($myStr);
//echo "strlen: ".myUtf8_strlen($myStr)."\n";
//echo "substr(2, 3): ".print_r(myUtf8_substr($myStr, 2, 3), true)."\n";

//exit;

function LCQ($old, $new, $oldFrom = 0, $newFrom = 0, $oldTo = NULL, $newTo = NULL) {
	global $debug;
//	$s = getmicrotime();
	if ($oldTo === NULL)
		$oldTo = myUtf8_strlen($old);
	if ($newTo === NULL)
		$newTo = myUtf8_strlen($new);
	$maxLen = 0;
	$lcq = NULL;

	if ($debug)
		echo 'LCQ ['.$old.'] ['.$new.'] '.$oldFrom.', '.$newFrom.', '.$oldTo.', '.$newTo."\n";

	for($oldIdx = $oldFrom; $oldIdx < $oldTo; $oldIdx++) {
		for($newIdx = $newFrom; $newIdx < $newTo && $oldIdx < $oldTo; $newIdx++) {
			if($old[$oldIdx] == $new[$newIdx]) {
				for($equalLen = 1;
					$newIdx + $equalLen < $newTo && $oldIdx + $equalLen < $oldTo
						&& $old[$oldIdx + $equalLen] == $new[$newIdx + $equalLen]; 
					$equalLen++);
				if ($equalLen > $maxLen) {
					$lcq = array(
						$oldIdx, 
						$newIdx, 
						$oldIdx + $equalLen < $oldTo,
						$newIdx + $equalLen < $newTo,
						$equalLen);
					$maxLen = $equalLen;

					$newIdx += $maxLen;
					$oldIdx += $maxLen;
				}
			}
		}
	}

//	$e = getmicrotime(); echo sprintf("%.3f\n", ($e-$s));

	return $lcq;
}

function myUtf8_diff($old, $new) {
	global $debug;
	if ($debug)
		echo 'diff ['.$old.'] ['.$new."]\n";

	$oldLen = myUtf8_strlen($old);
	$newLen = myUtf8_strlen($new);

	if ($oldLen == 0 && $newLen == 0)
		return array('');
	else if ($oldLen == 1 && $newLen == 1) {
		if ($old == $new)
			return array($new);
		else
			return myUtf8_append(array('<ins>'), myUtf8_append($new, '</ins>'));
	} else if ($oldLen == 0 && $newLen > 0)
		return myUtf8_append(array('<ins>'), myUtf8_append($new, '</ins>'));
	else if ($oldLen > 0 && $newLen == 0)
		return myUtf8_append(array('<del>'), myUtf8_append($old, '</del>'));

	// 1. find LCQ
	$lcq = LCQ($old, $new);
	if ($debug)
		echo 'LCQ: '.print_r($lcq, true)."\n";
	if ($lcq === NULL || ($lcq[4] <= $lcq[0] && $lcq[4] <= $lcq[1] && $lcq[4] < 10))
		return myUtf8_append(array('<ins>'), myUtf8_append($new, '</ins>'));

		
	$result = array();

	// 2. evaluate diff on part before LCQ
	if ($lcq[0] > 0 && $lcq[1] == 0)
		$result = myUtf8_append($result, 
			myUtf8_append(array('<del>'), myUtf8_append(myUtf8_substr($old, 0, $lcq[0]), '</del>')));
	else if ($lcq[0] == 0 && $lcq[1] > 0)
		$result = myUtf8_append($result, 
			myUtf8_append(array('<ins>'), myUtf8_append(myUtf8_substr($new, 0, $lcq[1]), '</ins>')));
	else if ($lcq[0] > 0 && $lcq[1] > 0)
		$result = myUtf8_append($result, 
			myUtf8_diff(myUtf8_substr($old, 0, $lcq[0]), myUtf8_substr($new, 0, $lcq[1])));

	$result = myUtf8_append($result, myUtf8_substr($new, $lcq[1], $lcq[4]));

	// 3. evaluate diff on part after LCQ
	if ($lcq[2] && ! $lcq[3])
		$result = myUtf8_append($result, 
			myUtf8_append(array('<del>'), myUtf8_append(myUtf8_substr($old, $lcq[0] + $lcq[4]), '</del>')));
	else if (! $lcq[2] && $lcq[3])
		$result = myUtf8_append($result, 
			myUtf8_append(array('<ins>'), myUtf8_append(myUtf8_substr($new, $lcq[1] + $lcq[4]), '</ins>')));
	else if ($lcq[2] && $lcq[3])
		$result = myUtf8_append($result, 
			myUtf8_diff(myUtf8_substr($old, $lcq[0] + $lcq[4]), myUtf8_substr($new, $lcq[1] + $lcq[4])));

	return $result;	
}

function diff($old, $new) {
	return myUtf8_decode(myUtf8_diff(myUtf8_encode($old), myUtf8_encode($new)));
}

function getmicrotime() {
	list($nano, $sec) = explode(' ', microtime());
	return $sec+$nano;
}

function xmlDiffCDATA($strOriginal, $strNew, $el) {
	$doc = $el->ownerDocument;

	$cdata = diff($strOriginal, $strNew);
	$el->appendChild($doc->createCDATASection($cdata));
}
?>
