<?php

class listitem extends textblock {
	public function __construct($chapter) {
		parent::__construct($chapter);
	}

	public function create($author, $text, $nil = NULL) {
		parent::create('listitem', $author, $text);
	}

	public function getNextBlockType() {
		return $this->type;
	}
}
?>