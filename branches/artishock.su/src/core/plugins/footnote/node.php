<?php

class footnote extends textblock {
	public function __construct($chapter) {
		parent::__construct($chapter);
	}

	public function create($author, $text, $nil = NULL) {
		parent::create('footnote', $author, $text);
	}
}
?>