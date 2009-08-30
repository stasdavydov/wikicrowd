<?php

class html extends textblock {
	public function __construct($chapter) {
		parent::__construct($chapter);
	}

	public function create($author, $text) {
		parent::create('html', $author, $text);
	}

	/*
	 Copy paste from textblock::diffRevisions with one change:
	 replace < to &lt; and > to &gt; for compared texts.
	*/
	public function diffRevisions($new, $old) {
		$newText = $new->getElementsByTagName('text')->item(0);
		$oldText = $old->getElementsByTagName('text')->item(0);

		$tr = array('<'=>'&lt;', '>'=>'&gt;');
		$diff = diff(
			$oldText->firstChild ? strtr($oldText->firstChild->nodeValue, $tr) : '', 
			$newText->firstChild ? strtr($newText->firstChild->nodeValue, $tr) : '');
		if ($old->getAttribute('type') != $new->getAttribute('type'))
			$diff = '<ins>@'.$new->getAttribute('type').'</ins> '.$diff;
		
		if ($newText->firstChild)
			$newText->removeChild($newText->firstChild);
		$newText->appendChild($newText->ownerDocument->createTextNode($diff));
	}

}
?>