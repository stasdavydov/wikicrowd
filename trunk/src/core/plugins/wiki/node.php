<?
/*
	@page "Page name" -> <a href="Page name">Page name</a>
	@page[Page name or URL] "Link name" -> <a href="Page name or URL">Link name</a>
	@page[Page name or URL] -> <a href="Page name or URL">Page name or URL</a>

*/

define('dont_touch', '[don\'t touch]');

/*
	*bold* -> <strong>bold</strong>
	/italic/ -> <em>italic</em>
	_subscript_ -> <sub>subscript</sub>
	^superscript^ -> <sup>superscript</sup>
*/


function isValidXHtml($xhtml) {
	$xhtml = trim($xhtml);
	if (preg_match('/^[^<>]*$/', $xhtml))
		return true;

	return preg_match('/(<(\w+)(\s*\w+\s*=\s*(\'[^\']*\'|"[^"]*")\s*)*>((?>[^<>]*)|(?R))<\/\2>)/', trim($xhtml));
}

class tag_formatter {
	private static $pull;
	private $start;
	private $tag;

	private function __construct($tag, $start) {
		$this->tag = $tag;
		$this->start = $start;
	}

	public function apply($text) {
		$pattern = '/'.$this->start.'([^'.$this->start.'\n\r<>]+)'.$this->start.'/';
		return preg_replace_callback($pattern, array($this, 'callback'), $text);
	}

	private function callback($matches) {
		$formatted = tag_formatter::apply_formatters($matches[1]);
		if (isValidXHtml($formatted))
			return '<'.$this->tag.'>'.$formatted.'</'.$this->tag.'>';
		return $matches[1];
	}

	private static function apply_formatters($text) {
		foreach(tag_formatter::$pull as $tf)
			$text = $tf->apply($text);
		return $text;
	}

	private function replace_link_callback($matches) {
		if(count($matches) == 2) {
			return dont_touch.
				'<a onclick="javascript:editOff()" href="'.
				(preg_match('/^https?:\/\//i', $matches[1]) 
					? $matches[1]
					: www.($matches[1] == "/"
						? ''
						: wikiUrlEncode($matches[1]))).'">'.$matches[1].'</a>'.
				dont_touch;
		} else if (count($matches) == 3) {
	 		return dont_touch.
	 			'<a onclick="javascript:editOff()" href="'.
				(preg_match('/^https?:\/\//i', $matches[1]) 
					? $matches[1]
					: www.($matches[1] == "/"
						? ''
						: wikiUrlEncode($matches[1]))).'">'.$matches[2].'</a>'.
				dont_touch;
		} else
			internal('Wrong matches: ', print_r($matches, true));
	}

	public static function format($text) {
		if (! tag_formatter::$pull) {
			tag_formatter::$pull = array(
				new tag_formatter('strong', '\*'),
				new tag_formatter('em', '\/'),
				new tag_formatter('sup', '\^'),
				new tag_formatter('sub', '_'));
		}

		$text = preg_replace_callback(
			'/@page\s+"([^"]+)"/', array('tag_formatter', 'replace_link_callback'), $text);
		$text = preg_replace_callback(
			'/@page\s*\[([^\]]+)\]\s+"([^"]+)"/', array('tag_formatter', 'replace_link_callback'), $text);
		$text = preg_replace_callback(
			'/@page\s*\[([^\]]+)\]/', array('tag_formatter', 'replace_link_callback'), $text);

		$parts = preg_split('/'.preg_quote(dont_touch).'/', $text);
		$numParts = count($parts);
		for($i = 0; $i < $numParts; $i+=2)  {
			$part = $parts[$i];
			foreach(tag_formatter::$pull as $tf)
				$part = $tf->apply($part);
			$parts[$i] = $part;
		}
		return implode($parts);
	}
}


function trace($text) {
	if (DEBUG)
		echo "Trace: $text\n";
}

function format_wiki($text) {
	return tag_formatter::format($text);
}
?>