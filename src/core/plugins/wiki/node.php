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
				: www.($matches[1] == "/"
					? ''
					: wikiUrlEncode($matches[1]))).'">'.$matches[1].'</a>';
	} else if (count($matches) == 3) {
	 	return '<a onclick="javascript:editOff()" href="'.
			(preg_match('/^https?:\/\//i', $matches[1]) 
				? $matches[1]
				: www.($matches[1] == "/"
					? ''
					: wikiUrlEncode($matches[1]))).'">'.$matches[2].'</a>';
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
	return '<strong>'.format_with_tags($matches[1]).'</strong>';
}
function replace_wiki_italic_callback($matches) {
	return '<em>'.format_with_tags($matches[1]).'</em>';
}
function replace_wiki_subscript_callback($matches) {
	return '<sub>'.format_with_tags($matches[1]).'</sub>';
}
function replace_wiki_superscript_callback($matches) {
	return '<sup>'.format_with_tags($matches[1]).'</sup>';
}

function trace($text) {
	if (DEBUG)
		echo "Trace: $text\n";
}

function format_with_tags($text) {
	trace($text);
	$text = preg_replace_callback('/\*([^*\n\r<>]+)\*/', 'replace_wiki_bold_callback', $text);
	$text = preg_replace_callback('/\/([^\/\n\r<>]+)\//', 'replace_wiki_italic_callback', $text);
	$text = preg_replace_callback('/_([^_\n\r<>]+)_/', 'replace_wiki_subscript_callback', $text);
	$text = preg_replace_callback('/\^([^\^\n\r<>]+)\^/', 'replace_wiki_superscript_callback', $text);

	return $text;
}

function format_wiki($text) {
	$text = preg_replace_callback(
		'/@page\s+"([^"]+)"/', 'replace_wiki_callback', $text);
	$text = preg_replace_callback(
		'/@page\s*\[([^\]]+)\]\s+"([^"]+)"/', 'replace_wiki_callback', $text);
	$text = preg_replace_callback(
		'/@page\s*\[([^\]]+)\]/', 'replace_wiki_callback', $text);

	return format_with_tags($text);
}
?>