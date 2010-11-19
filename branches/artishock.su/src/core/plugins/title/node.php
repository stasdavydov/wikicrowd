<?php

class title extends textblock {
	public function __construct($chapter) {
		parent::__construct($chapter);
	}

	public function create($author, $text, $nil = NULL) {
		parent::create('title', $author, $text);
	}
}
?>