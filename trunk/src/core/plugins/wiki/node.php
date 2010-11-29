<?
/*
	@page "Page name" -> <a href="Page name">Page name</a>
	@page[Page name or URL] "Link name" -> <a href="Page name or URL">Link name</a>
	@page[Page name or URL] -> <a href="Page name or URL">Page name or URL</a>

*/

define('uri_pattern', '(ftps?|mailto|https?):\/{0,2}[a-z0-9:\-\._~?#&=%\/\$\+@]+[a-z0-9:\-_~?#&=%\/\$\+@]');

/*
	*bold* -> <strong>bold</strong>
	/italic/ -> <em>italic</em>
	_subscript_ -> <sub>subscript</sub>
	^superscript^ -> <sup>superscript</sup>
*/


function exclude_replace($pattern, $callback, $text) {
	if ($text == "")
		return $text;

	$originalText = $text;

	$excludes = array(0=>array());
	$escape_pattern = '/`\[\{([^}]|\}[^\]]|\}\][^`])+\}\]`/';
	if(preg_match_all($escape_pattern, $text, $excludes)) {
		$text = preg_replace($escape_pattern, '`[{}]`', $text, -1, $count);
		if($count != count($excludes[0]))
			die('Something strange: excludes = '.(count($excludes[0])).', count = '.$count);
	}

	$text = preg_replace_callback($pattern, $callback, $text);

	if (count($excludes[0]) > 0)
		foreach($excludes[0] as $exclude)
			$text = preg_replace('/`\[\{\}\]`/', $exclude, $text, 1);

	if (isValidXHtml($text))
		return $text;
	else
		return $originalText;
}

function remove_escape($text) {
	return str_replace(array('`[{', '}]`'), array('', ''), $text);
}

function make_link($link, $name) {
	if (! preg_match('/^'.uri_pattern.'$/i', $link))
		$link = www.($link == "/" ? '' : wikiUrlEncode($link));

	return '`[{<a onclick="javascript:editOff()" href="'.$link.'">'.$name.'</a>}]`';
}

abstract class base_callback {
	abstract public function callback($matches);
	abstract public function pattern();
}

class url_callback extends base_callback {
	public function pattern() {
		return '/('.uri_pattern.')/i';
	}

	public function callback($matches) {
		return make_link($matches[1], $matches[1]);
	}
}

class tag_callback extends base_callback {
	private $tag;
//	private $sign;
	private $openSign;
	private $closeSign;
	public function __construct($tag, $openSign, $closeSign = NULL) {
		$this->tag = $tag;
		$this->openSign = preg_quote($openSign, '/');
		$this->closeSign = $closeSign ? preg_quote($closeSign, '/') : $this->openSign;
	}
	public function pattern() {
		return '/'.$this->openSign.'([^'.$this->closeSign.'\n\r]+)'.$this->closeSign.'/';
	}
	public function callback($matches) {
		return '`[{<'.$this->tag.'>}]`'.$matches[1].'`[{</'.$this->tag.'>}]`';
	}
}

class replace_pull {
	private static $callbacks;
	public static function replace($text) {
		if (! replace_pull::$callbacks)
			replace_pull::$callbacks = array(
				new url_callback(),
				new tag_callback('strong', '*'),
				new tag_callback('em', '//'),
				new tag_callback('sup', '^'),
				new tag_callback('sub', '_'));
		foreach(replace_pull::$callbacks as $callback) {
			$text = exclude_replace($callback->pattern(), array($callback, 'callback'), $text);
		}

		return $text;
	}
}

function format_wiki($text) {
	$text = preg_replace_callback(
		'/@page\s+"([^"]+)"/', create_function('$matches', '
			return make_link($matches[1], $matches[1]);'), $text);
	$text = preg_replace_callback(
		'/@page\s*\[([^\]]+)\]\s+"(?P<name>[^"]+)"/', create_function('$matches', '
			return make_link($matches[1], $matches[2]);'), $text);
	$text = preg_replace_callback(
		'/@page\s*\[([^\]]+)\]/', create_function('$matches', '
			return make_link($matches[1], $matches[1]);'), $text);
	$text = preg_replace_callback(
		'/\[\[([^\]]+)\]\]/', create_function('$matches', '
			return make_link($matches[1], $matches[1]);'), $text);

	return remove_escape(replace_pull::replace($text));
}

function isValidXHtml($xhtml) {
	$xhtml = trim($xhtml);
	if (preg_match('/^[^<>]*$/', $xhtml))
		return true;

	return preg_match('/(<(\w+)(\s*\w+\s*=\s*(\'[^\']*\'|"[^"]*")\s*)*>((?>[^<>]*)|(?R))<\/\2>)/', trim($xhtml));
}

?>