<?php

class subsubtitle extends textblock {
	public function __construct($chapter) {
		parent::__construct($chapter);
	}

	public function create($author, $text) {
		parent::create('subsubtitle', $author, $text);
	}
}
?>