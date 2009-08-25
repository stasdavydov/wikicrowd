<?
/*
	@page "Page name" -> <a href="Page name">Page name</a>
	@page[Page name or URL] "Link name" -> <a href="Page name or URL">Link name</a>
	@page[Page name or URL] -> <a href="Page name or URL">Page name or URL</a>

*/

function replace_wiki_callback($matches) {
	if(count($matches) == 2) {
		return '<a onclick="javascript:editOff()" href="'.
			(preg_match('/^https?:\/\//i', $matches[1]) 
				? $matches[1]
				: www.wikiUrlEncode($matches[1])).'">'.$matches[1].'</a>';
	} else if (count($matches) == 3) {
	 	return '<a onclick="javascript:editOff()" href="'.
			(preg_match('/^https?:\/\//i', $matches[1]) 
				? $matches[1]
				: www.wikiUrlEncode($matches[1])).'">'.$matches[2].'</a>';
	} else
		internal('Wrong matches: ', print_r($matches, true));
}

/*
	*bold* -> <strong>bold</strong>
	/italic/ -> <em>italic</em>
	_subscript_ -> <sub>subscript</sub>
	^superscript^ -> <sup>superscript</sup>
*/

function replace_wiki_bold_callback($matches) {
	return '<strong>'.$matches[1].'</strong>';
}

function format_wiki($text) {
	$text = preg_replace_callback(
		'/@page\s+"([^"]+)"/', 'replace_wiki_callback', $text);
	$text = preg_replace_callback(
		'/@page\s*\[([^\]]+)\]\s+"([^"]+)"/', 'replace_wiki_callback', $text);
	$text = preg_replace_callback(
		'/@page\s*\[([^\]]+)\]/', 'replace_wiki_callback', $text);

	$text = preg_replace('/\*([^*\n\r]+)\*/', '<strong>$1</strong>', $text);
//	$text = preg_replace('/\/([^/\n\r]+)\//', '<em>$1</em>', $text);
	$text = preg_replace('/_([^_\n\r]+)_/', '<sub>$1</sub>', $text);
	$text = preg_replace('/\^([^\^\n\r]+)\^/', '<sup>$1</sup>', $text);

	return $text;
}
?>