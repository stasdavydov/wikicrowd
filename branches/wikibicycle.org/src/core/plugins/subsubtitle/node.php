<?php

class subsubtitle extends textblock {
	public function __construct($chapter) {
		parent::__construct($chapter);
	}

	public function create($author, $text, $nil = NULL) {
		parent::create('subsubtitle', $author, $text);
	}
}
?>