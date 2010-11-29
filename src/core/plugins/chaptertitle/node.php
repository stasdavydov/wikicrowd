<?php

class chaptertitle extends textblock {
	public function __construct($chapter) {
		parent::__construct($chapter);
	}

	public function __get($name) {
		switch($name) {
			case 'id': return 'chaptertitle';
			case 'type': return 'chaptertitle';
			case 'deleted': return false;
			case 'element': return null;
			case 'rev': return 0;
			case 'author': return 'system';
			case 'created': return $this->chapter->lastModified();
			case 'text': return $this->chapter->getTitle();
			default: return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		throw new Exception('Forbiden');
	}

	public function update($data, $author) { 
		if (! array_key_exists('text', $data))
			internal(getMessage('TextIsNotSet'));

		$text = trim(stripslashes($data['text']));
		if (strcmp($text, $this->text) != 0) {
			$this->chapter->setTitle($text, $author);
			return true;
		} else
			return false;
	}

	public function create($author, $text, $nil = NULL) { throw new Exception('Forbiden'); }
	public function getNextBlockType() { throw new Exception('Forbiden'); }
	public function diff($conflict, $data) { throw new Exception('Forbiden'); }
	public function diffRevisions($new, $old) {	throw new Exception('Forbiden'); }
}
?>