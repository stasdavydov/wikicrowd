<?
function mydom_createElement($dom, $elementName, $attrs = NULL, $children = NULL) {
	$el = $dom->createElement($elementName);
	if ($attrs)
		foreach($attrs as $name=>$value)
			$el->setAttribute($name, $value);
	if ($children)
		foreach($children as $child)
			$el->appendChild($child);
	return $el;
}

function mydom_appendChild($el, $child) {
	$el->appendChild($child);
	return $el;
}

function mydom_appendText($el, $text) {
	$el->appendChild($el->ownerDocument->createTextNode($text));
	return $el;
}

class myUtf8 {
	private $str;

	public function __construct($utf8str) {
		$len = iconv_strlen($utf8str, 'utf-8');

		if ($len == 0)
			$this->str = array();
		else {
			$this->str = array_fill(0, $len, ' ');
			for($i = 0; $i<$len; $i++)
				$this->set($i, iconv_substr($utf8str, $i, 1, 'utf-8'));
		}
	}

	public function get($i) {
		return $this->str[$i];
	}

	public function set($i, $c) {
		$this->str[$i] = $c;
	}

	public function toString() {
		return implode($this->str);
	}

	public function length() {
		return count($this->str);
	}

	public function append($str) {
		foreach($str->str as $c)
			$this->str[] = $c;
		return $this;
	}
		
	public function substr($from, $length = NULL) {
		$new = new myUtf8('');
		$new->str = array_slice($this->str, $from, 
			$length ? $length : ($this->length() - $from));
		return $new;
	}

	private static function LCQ($old, $new, $oldFrom = 0, $newFrom = 0, $oldTo = NULL, $newTo = NULL) {
//	$s = getmicrotime();
		if ($oldTo === NULL)
			$oldTo = $old->length();
		if ($newTo === NULL)
			$newTo = $new->length();
		$maxLen = 0;
		$lcq = NULL;

		for($oldIdx = $oldFrom; $oldIdx < $oldTo; $oldIdx++) {
			for($newIdx = $newFrom; $newIdx < $newTo && $oldIdx < $oldTo; $newIdx++) {
				if($old->get($oldIdx) == $new->get($newIdx)) {
					for($equalLen = 1;
						$newIdx + $equalLen < $newTo && $oldIdx + $equalLen < $oldTo
							&& $old->get($oldIdx + $equalLen) == $new->get($newIdx + $equalLen); 
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

	public static function diff($el, $old, $new) {
		$oldLen = $old->length();
		$newLen = $new->length();

		if ($oldLen == 0 && $newLen == 0)
			return;
		else if ($oldLen == 1 && $newLen == 1) {
			if ($old->str == $new->str)
				$el->appendChild($el->ownerDocument->createTextNode($new->toString()));
			else
				$el->appendChild(
					mydom_createElement($el->ownerDocument, 'ins', array(),
						array($el->ownerDocument->createTextNode($new->toString()))));
			return;
		} else if ($oldLen == 0 && $newLen > 0) {
			$el->appendChild(
				mydom_createElement($el->ownerDocument, 'ins', array(),
					array($el->ownerDocument->createTextNode($new->toString()))));
			return;
		} else if ($oldLen > 0 && $newLen == 0) {
			$el->appendChild(
				mydom_createElement($el->ownerDocument, 'del', array(),
					array($el->ownerDocument->createTextNode($old->toString()))));
			return;
		}

		// 1. find LCQ
		$lcq = self::LCQ($old, $new);
		if ($lcq === NULL || ($lcq[4] <= $lcq[0] && $lcq[4] <= $lcq[1] && $lcq[4] < 10)) {
			$el->appendChild(
				mydom_createElement($el->ownerDocument, 'ins', array(),
					array($el->ownerDocument->createTextNode($new->toString()))));
			return;
		}
		
		// 2. evaluate diff on part before LCQ
		if ($lcq[0] > 0 && $lcq[1] == 0)
			$el->appendChild(
				mydom_createElement($el->ownerDocument, 'del', array(),
					array($el->ownerDocument->createTextNode($old->substr(0, $lcq[0])->toString()))));
		else if ($lcq[0] == 0 && $lcq[1] > 0)
			$el->appendChild(
				mydom_createElement($el->ownerDocument, 'ins', array(),
					array($el->ownerDocument->createTextNode($new->substr(0, $lcq[1])->toString()))));
		else if ($lcq[0] > 0 && $lcq[1] > 0)
			self::diff($el, $old->substr(0, $lcq[0]), $new->substr(0, $lcq[1]));

		$el->appendChild($el->ownerDocument->createTextNode($new->substr($lcq[1], $lcq[4])->toString()));


		// 3. evaluate diff on part after LCQ
		if ($lcq[2] && ! $lcq[3])
			$el->appendChild(
				mydom_createElement($el->ownerDocument, 'del', array(),
					array($el->ownerDocument->createTextNode($old->substr($lcq[0] + $lcq[4])->toString()))));
		else if (! $lcq[2] && $lcq[3])
			$el->appendChild(
				mydom_createElement($el->ownerDocument, 'ins', array(),
					array($el->ownerDocument->createTextNode($new->substr($lcq[1] + $lcq[4])->toString()))));
		else if ($lcq[2] && $lcq[3])
			self::diff($el, $old->substr($lcq[0] + $lcq[4]), $new->substr($lcq[1] + $lcq[4]));

	}
}

function getmicrotime() {
	list($nano, $sec) = explode(' ', microtime());
	return $sec+$nano;
}
?>
