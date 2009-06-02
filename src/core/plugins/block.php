<?php
require_once HOME.'mb_diff.php';

class chapter {
	private $chapterFile;
    private $dom;
    private $response;	// track of changes on update

	static private function getChapterName($fromReferer = false) {
		$chapter = NULL;
		if ($fromReferer) {
			$myaddress = $_SERVER['SERVER_NAME'].www;
			if (($pos = strpos($_SERVER['HTTP_REFERER'], $myaddress)) !== FALSE) {
				$chapter = substr($_SERVER['HTTP_REFERER'], $pos + strlen($myaddress));
				if (($pos = strpos($chapter, '?')) !== FALSE)
					$chapter = substr($chapter, 0, $pos);
				if (($pos = strpos($chapter, '#')) !== FALSE)
					$chapter = substr($chapter, 0, $pos);
			}
			$chapter = rawurldecode($chapter);
		} else 
			$chapter = trim($_GET['chapter']);
		return $chapter;
	}

	private function getTitle() { return $this->dom->documentElement->getAttribute('title'); }

	static private function getChapterFileName($chapterName) {
		return makeFileName(fileNamePartEncode(trim($chapterName)).'.xml');
	}

	public function __construct($ajaxUsing = true) {
		global $LOCKFILE;

		$chapterName = chapter::getChapterName($ajaxUsing);
		if ($chapterName === NULL) {
  			header('HTTP/1.0 404 Not Found');
	   		exit;
		} else if ($chapterName == "") {
			header('Location: '.www.homePage);
			exit;
		}

		$this->chapterFile = CHAPTERS . chapter::getChapterFileName($chapterName);

		$this->dom = new DOMDocument('1.0', 'utf-8');

		enterCriticalSection($LOCKFILE);

		if (! file_exists($this->chapterFile)) {
			$this->dom->appendChild($this->dom->implementation->createDocumentType(
				'chapter', 'WikiCrowd', '../core/xml/wikicrowd.dtd'));
			$this->dom->appendChild($this->dom->createElement('chapter'));
			$this->dom->documentElement->setAttribute('title', $chapterName);

			blockfactory::loadPlugin('par');
			$par = new par($this);
			$par->create('system', 'type you text here');
			$this->appendBlock($par);
			
			$this->dom->save($this->chapterFile);
		} else {
			$this->dom->load($this->chapterFile);
		}
	}

	public function __destruct() {
		global $LOCKFILE;
		exitCriticalSection($LOCKFILE);
	}

	public function lastModified() {
		return filemtime($this->chapterFile);
	}

	public function createElement($name) {
		return $this->dom->createElement($name);
	}

	public function getElementById($id) {
		$xpath = new DOMXPath($this->dom);
		$elements = $xpath->query('//block[@id = \''.$id.'\']');

		if ($elements->length == 1)
			return $elements->item(0);
		else if ($elements->length > 1)
			internal('Слишком много элементов с ID '.$id);
		else
			return NULL;
	}

	public function getElementsByTagName($name) {
		return $this->dom->getElementsByTagName($name);
	}

	private function createEvent($name, $block) {
		$event = $this->response->createElement($name);
		$imported = $this->response->importNode($block->element, true);
		foreach($imported->childNodes as $child) {
//			if (DEBUG) {
//				fb($child->nodeType, 'chapter::createEvent() node type');
//				fb($child->nodeName, 'chapter::createEvent() node name');
//			}
			if ($child->nodeType != XML_ELEMENT_NODE 
				|| $child->nodeName != 'previous')
			$event->appendChild($child->cloneNode(true));
		}
		foreach($imported->attributes as $name=>$node) {
//			if (DEBUG) {
//				fb($name, 'chapter::createEvent() copy attribute name');
//				fb($node->value, 'chapter::createEvent() copy attribute value');
//			}

			$event->setAttribute($name, $node->value);
		}
		$this->response->documentElement->appendChild($event);
		return $event;
	}

	private function fireBlockUpdated($block) {
//	    if (DEBUG)
//	    	fb($block->id, 'chapter::fireBlockUpdated');
		if ($this->response) 
			$this->createEvent('updated', $block);
	}

	private function fireBlockInserted($block) {
//	    if (DEBUG)
//	    	fb($block->id, 'chapter::fireBlockInserted');
		if ($this->response) {
			$event = $this->createEvent('inserted', $block);
			if ($block->element->previousSibling)
				$event->setAttribute('prev-block-id', $block->element->previousSibling->getAttribute('id'));
			if ($block->element->nextSibling)
				$event->setAttribute('next-block-id', $block->element->nextSibling->getAttribute('id'));
		}
	}

	private function fireConflict($block, $data) {
//	    if (DEBUG)
//	    	fb($data, 'chapter::fireConflict '.$block->id);
		if ($this->response) {
			$event = $this->createEvent('conflict', $block);
			$event->setAttribute('your-rev', $data['rev']);
			$block->diff($event, $data);
		}
	}

	private function respond() {
		$eventCount = 0;
		$start = $this->response->documentElement->firstChild;
		while($start) {
			$eventCount++;
			$start = $start->nextSibling;
		}

//		if (DEBUG)
//			fb($this->response->saveXML(), 'chapter::respond()');

		if ($eventCount == 0) 
			warn('Такое впечатление, что ничего не изменилось.');
		else
			echo transformDOM($this->response, CORE.'xml/response.xsl', array('MODE' => 'edit'));
	}

	public function changedSince($last, $mode) {
		echo transformDOM($this->dom, CORE.'xml/response.xsl', 
			array('MODE' => $mode, 'LAST' => $last));
	}

	public function edit($id, $rev) {
		echo transformXML($this->chapterFile, CORE.'xml/form.xsl', array('ID' => $id, 'REV' => $rev), 
			XSL_MTIME);
	}

	private function getBlock($id) {
		if ($element = $this->getElementById($id)) {
			$type = $element->getAttribute('type');
			blockfactory::loadPlugin($type);
			$block = new $type($this);
			$block->load($element);
			return $block;
		} else
			return NULL;
	}

	public function update($data, $author) {
		$this->response = new DOMDocument('1.0', 'utf-8');
		$this->response->appendChild($this->response->createElement('response'));

		// load data
		if (! array_key_exists('id', $data))
			internal('ID не задан.');

		$id = trim($data['id']);
		if ($id == "") 
			internal('ID пуст.');

		$rev = trim($data['rev']);
		$rev = $rev == "" ? 0 : $rev;

		if (! ($block = $this->getBlock($id)))
			internal("Элемент с ID $id не найден.");

//		if (DEBUG)
//			fb($id, 'Update element');

		// check conflicts
		$overwrite = array_key_exists('overwrite', $data) ? true : false;
		if ($block->rev > $rev && ! $overwrite) {
			$this->fireConflict($block, $data);
		} else {
			// update
			$previous = new previous($block);
//			if (DEBUG)
//				fb($data, 'Update block with');
			if ($block->update($data, $author)) {
//				fb($block, 'Block updated');
				$block->element->appendChild($previous->element);
				$block->increaseRevision($author);
				$this->fireBlockUpdated($block);
			}
			// save new version
			$this->dom->save($this->chapterFile);
		}
		$this->respond();
	}

	public function changes($id) {
		$chapterChangesFile = CACHE
			.makeFileName(chapter::getChapterFileName($this->getTitle())
			.'.changes.'.$id.'.xml');

		if (! (file_exists($chapterChangesFile) && filemtime($chapterChangesFile) >= 
			max(filemtime($this->chapterFile), getlastmod(), filemtime(HOME.'mb_diff.php')))) {

			$dom = new DOMDocument('1.0', 'utf-8');
			$changes = $dom->createElement('changes');
			$changes->setAttribute('id', $id);
			$dom->appendChild($changes);

			if (! ($block = $this->getBlock($id)))
				internal("Элемент с ID $id не найден.");
		
			$block->chnages($changes);

			$dom->save($chapterChangesFile);
		}
		echo transformXML($chapterChangesFile, CORE.'xml/changes.xsl', array('ID'=>$id), XSL_MTIME);
	}

	public function appendBlock($block, $afterBlock = NULL) {
		$before = $afterBlock ? $afterBlock->element->nextSibling : NULL;
		if($before)
			$this->dom->documentElement->insertBefore($block->element, $before);
		else
			$this->dom->documentElement->appendChild($block->element);

		$this->fireBlockInserted($block);
	}

	public function transform($xslFile, $params) {
		echo transformXML($this->chapterFile, $xslFile, $params, XSL_MTIME);
	}
}

class blockfactory {
	private static function getTypeClassFile($type) {
		return PLUGINS.$type.'/node.php';
	}

	public static function loadPlugin($type) {
		if (! (preg_match('/[\w\d_]+/', $type) 
				&& include_once(blockfactory::getTypeClassFile($type))))
			internal('Недопустимый тип блока: @'.$type); 
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

	public function chnages($changes) {
		$previousNodes = $this->element->getElementsByTagName('previous');
		if ($previousNodes->length > 0) {

			$revisions = array();

			$new = new previous($this);
			$revisions[$new->element->getAttribute('rev')] = 
				$changes->ownerDocument->importNode($new->element, true);

			foreach($previousNodes as $old)
				$revisions[$old->getAttribute('rev')] = 
					$changes->ownerDocument->importNode($old, true);

			for($rev = $this->rev; $rev > 0; $rev--) {
				$new = $revisions[$rev];
				$old = $revisions[$rev-1];
				$this->diffRevisions($new, $old);
				$changes->appendChild($new);
			}
			$changes->appendChild($revisions[0]);
		}
	}

	abstract public function update($data, $author);
	abstract public function diff($conflict, $data);	// diff last block version with raw data
														// and append result to $conflict node

	abstract public function diffRevisions($new, $old);	// diff two versions of block value
														// and save diff result to $new

}

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
			internal('Текст не задан.');

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
			internal('Текст не задан.');

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