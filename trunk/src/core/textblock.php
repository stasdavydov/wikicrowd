<?php
abstract class textblock extends block {
	public function __construct($chapter) {
		parent::__construct($chapter);
	}

	public function create($type, $author, $text = NULL) {
		parent::create($type, $author);
		if ($text)
			$this->text = $text;
	}

	public function __set($name, $value) {
//		if(DEBUG) {
//			fb($value, get_class($this).'::__set('.$name.')');
//			fb("Trace", FirePHP::TRACE);
//		}
		switch($name) {
			case 'text':
				$texts = $this->element->getElementsByTagName('text');
				$value = $value == NULL ? '' : $value;
				if ($texts->length > 0) {
					$textElement = $texts->item(0);
					if ($textElement->firstChild)
						$textElement->firstChild->nodeValue = $value;
					else
						$textElement->appendChild($this->element->ownerDocument->createTextNode($value));
				} else {
					$textElement = $this->element->ownerDocument->createElement('text');
					$textElement->appendChild(
						$this->element->ownerDocument->createTextNode($value));
					$this->element->appendChild($textElement);
				}
				break;
			default:
				parent::__set($name, $value);
				break;
		}
	}

	public function __get($name) {
		switch ($name) {
			case 'text':
				$texts = $this->element->getElementsByTagName('text');
				if ($texts->length > 0) {
					return $texts->item(0)->firstChild 
						? $texts->item(0)->firstChild->nodeValue
						: '';
				} else {
					return NULL;
				}
				break;
			default:
				return parent::__get($name);
		}
	}

	public function update($data, $author) {
		if (! array_key_exists('text', $data))
			internal(getMessage('TextIsNotSet'));

		$text = trim(stripslashes($data['text']));

		if (strcmp($text, $this->text) == 0)
			return false;

		$changed = false;

		if ($text == "") {
			$changed = $this->deleted = true;
			$this->text = '';
		} else {
			$texts = split("\n", $text);
			$first = true;
			$after = $this;

			$type = $this->type;

			foreach($texts as $text) {
				$text = trim($text);
//				if(DEBUG)
//					fb($text, 'textblock::update() text');

				if ($text != "") {

					if (preg_match('/@(\w+)/', $text, $command)) {
						// it could be a command to change block type
						if (blockfactory::blockTypeExists($command[1])) {
							$type = $command[1];
							$text = trim(substr($text, strlen('@'.$command[1])));
						}
					}

					if ($first) {
						if ($type != $this->type || $this->text != $text) {
					    	$this->text = $text;
				    		$this->deleted = $this->text == "";
//					    	if (DEBUG)
//					    		fb($this->deleted, 'deleted, text: '.$this->text);

					    	$this->type = $type;
							$changed = true;
						}
						$first = false;
					} else {
						blockfactory::loadPlugin($type);
						$newblock = new $type($this->chapter);
						$newblock->create($author, $text);

						$this->chapter->appendBlock($newblock, $after);
						$after = $newblock;
					}
				} 
			}
		}
		return $changed;
	}

	public function diff($conflict, $data) {
		if (! array_key_exists('text', $data))
			internal(getMessage('TextIsNotSet'));

		$texts = $conflict->getElementsByTagName('text');
		
		if ($texts->length == 0) {
			$textElement = $conflict->ownerDocument->createElement('text');
		} else {
			$textElement = $texts->item(0);
			while($textElement->firstChild)
				$textElement->removeChild($textElement->firstChild);
		}
		$textElement->appendChild(
			$conflict->ownerDocument->createTextNode(
				diff(trim(stripslashes($data['text'])), $this->text)));
	}

	public function diffRevisions($new, $old) {
		$newText = $new->getElementsByTagName('text')->item(0);
		$oldText = $old->getElementsByTagName('text')->item(0);

//		if (DEBUG) {
//			fb($newText->firstChild, 'new first child');
//			fb($oldText->firstChild, 'old first child');
//		}

		$diff = diff(
			$oldText->firstChild ? $oldText->firstChild->nodeValue : '', 
			$newText->firstChild ? $newText->firstChild->nodeValue : '');
		if ($old->getAttribute('type') != $new->getAttribute('type'))
			$diff = '<ins>@'.$new->getAttribute('type').'</ins> '.$diff;
		
//		if (DEBUG) {
//			fb($diff, get_class($this).'::diffRevisions('.$new->getAttribute('rev').', '.$old->getAttribute('rev'));
//		}

		if ($newText->firstChild)
			$newText->removeChild($newText->firstChild);
		$newText->appendChild($newText->ownerDocument->createTextNode($diff));
	}
}

?>