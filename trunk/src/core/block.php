<?php
class blockfactory {
	private static function getTypeClassFile($type) {
		return PLUGINS.$type.'/node.php';
	}

	public static function loadPlugin($type) {
		if (! (preg_match('/[\w\d_]+/', $type) 
				&& include_once(blockfactory::getTypeClassFile($type))))
			internal(getMessage('WrongBlockType').': @'.$type); 
	}

	public static function blockTypeExists($type) {
		return preg_match('/[\w\d_]+/', $type) && file_exists(blockfactory::getTypeClassFile($type));
	}
}

abstract class versioned {
	private $chapter;
	private $element;

	protected function __construct($chapter) {
		$this->chapter = $chapter;
	}

	protected function create($nodeName, $author) {
		$this->element = $this->chapter->createElement($nodeName);
		$this->rev = 0;
		$this->created = time();
		$this->author = $author;
	}

	protected function __set($name, $value) {
		switch($name) {
			case 'element':
				$this->element = $value;
				break;
			case 'rev':
			case 'author':
				$this->element->setAttribute($name, $value);
				break;
			case 'created':
				$this->element->setAttribute('created-ts', $value);
				$this->element->setAttribute('created-date', date('d/m/Y H:i', $value));
				break;
			default:
				throw new Exception('Wrong class field name set: '.$name);
		}
	}

	public function __get($name) {
		switch($name) {
			case 'element': return $this->element;
			case 'author': return $this->element->getAttribute('author');
			case 'created': return $this->element->getAttribute('created-ts');
			case 'rev': return $this->element->getAttribute('rev');
			case 'chapter': return $this->chapter;
			default:
				throw new Exception('Wrong class field name get: '.$name);
		}
	}

	public function increaseRevision($author) {
		$this->author = $author;
		$this->created = time();
		$this->rev = $this->rev + 1;
	}
}

class previous extends versioned {
	public function __construct($block, $deep = true) {
		parent::__construct($block->chapter);
		parent::create('previous', $block->author);
		if ($deep) {
			$child = $block->element->firstChild;
			while($child) {
				if ($child->nodeType != XML_ELEMENT_NODE || $child->nodeName != 'previous')
					$this->element->appendChild($child->cloneNode(true));
				$child = $child->nextSibling;
			}
		}
		foreach($block->element->attributes as $name=>$node) {
//			if (DEBUG) {
//				fb($name, 'previous::__construct() copy attribute name');
//				fb($node->value, 'previous::__construct() copy attribute value');
//			}

			$this->element->setAttribute($name, $node->value);
		}
	}

	public function __get($name) {
		switch($name) {
			case 'type': return $this->element->getAttribute('type');
			default: return parent::__get($name);
		}
	}

	public function __set($name, $value) {
		parent::__set($name, $value);
	}
}

abstract class block extends versioned {
	public function __construct($chapter) {
		parent::__construct($chapter);
	}

	protected function create($type, $author) {
		parent::create('block', $author);

		$this->type = $type;
		$blocks = $this->chapter->getElementsByTagName('block');
		$this->id = 'b'.($blocks->length + 1);
	}

	public function load($element) {
		$this->element = $element;
	}

	public function __get($name) {
		switch($name) {
			case 'id': return $this->element->getAttribute('id');
			case 'type': return $this->element->getAttribute('type');
			case 'deleted': return $this->element->getAttribute('deleted') == "deleted";
			default: return parent::__get($name);
		}
	}

	protected function __set($name, $value) {
		switch($name) {
			case 'id':
			case 'type':
				$this->element->setAttribute($name, $value);
				break;
			case 'deleted':
				if($value)
					$this->element->setAttribute('deleted', 'deleted');
				else
					$this->element->removeAttribute('deleted');
				break;
			default:
				parent::__set($name, $value);
		}
	}

	public function changes($changes, $last = FALSE) {
		$previousNodes = $this->element->getElementsByTagName('previous');
		if ($previousNodes->length > 0) {

			$revisions = array();

			$new = new previous($this);
			$revisions[$new->element->getAttribute('rev')] = 
				$changes->ownerDocument->importNode($new->element, true);

			foreach($previousNodes as $old) {
				$revisions[$old->getAttribute('rev')] = 
					$changes->ownerDocument->importNode($old, true);
			}

			for($rev = $this->rev; $rev > 0; $rev--) {
				$new = $revisions[$rev];
				$old = $revisions[$rev-1];
				$this->diffRevisions($new, $old);
				$changes->appendChild($new);

				if ($last)
					break;
			}
			if (! $last)
				$changes->appendChild($revisions[0]);
		}
	}

	abstract public function update($data, $author);
	abstract public function diff($conflict, $data);	// diff last block version with raw data
														// and append result to $conflict node

	abstract public function diffRevisions($new, $old);	// diff two versions of block value
														// and save diff result to $new
	abstract public function getNextBlockType();
}

require_once 'textblock.php';

?>