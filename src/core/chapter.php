<?php
require_once HOME.'mb_diff.php';

class chapter {
	private $chapterFile;
    private $dom;
    private $response;	// track of changes on update
    private $allChanges;// track all changes

	static private function getChapterName($fromReferer = false) {
		$chapter = NULL;
		
		if ($fromReferer) {
			$myaddress = $_SERVER['SERVER_NAME'].www;
			if (($pos = strpos($_SERVER['HTTP_REFERER'], $myaddress)) !== FALSE) {
				$chapter = substr($_SERVER['HTTP_REFERER'], $pos + strlen($myaddress));
			}
		} else  {
			$chapter = substr($_SERVER['REQUEST_URI'], strlen(www));
		}

		if (($pos = strpos($chapter, '?')) !== FALSE)
			$chapter = substr($chapter, 0, $pos);
		if (($pos = strpos($chapter, '#')) !== FALSE)
			$chapter = substr($chapter, 0, $pos);

		$chapter = trim(rawurldecode($chapter));

		return $chapter;
	}

	private function getTitle() { return $this->dom->documentElement->getAttribute('title'); }

	static private function getChapterFileName($chapterName) {
		return makeFileName(fileNamePartEncode(trim($chapterName)).'.xml');
	}

	/**
	 * @note Usualy it doesn't require to pass $chapterName by param because
	 * it will be requested from environment. There is only one exception: when you want
	 * to manually create chapter with the specific name.
	 */
	public function __construct($ajaxUsing = true, $chapterName = NULL) {
		global $LOCKFILE;

		if ($chapterName === NULL) {
			$chapterName = chapter::getChapterName($ajaxUsing);
			if ($chapterName === NULL) {
  				header('HTTP/1.0 404 Not Found');
	   			exit;
			} else if ($chapterName == "") {
				header('Location: '.www.rawurlencode(homePage));
				exit;
			}
		}

		$this->chapterFile = CHAPTERS . chapter::getChapterFileName($chapterName);
		$this->dom = new DOMDocument('1.0', 'utf-8');

		enterCriticalSection($LOCKFILE);

		$person = getSessionPerson();
		if (! file_exists($this->chapterFile)) {

			if (! personCanEdit($person)) {
	  			header('HTTP/1.0 404 Not Found');
		   		exit;
			}

			$this->dom->appendChild($this->dom->implementation->createDocumentType(
				'chapter', 'WikiCrowd', 'http://wikicrowd.googlecode.com/svn/trunk/src/core/xml/wikicrowd.dtd'));
			$this->dom->appendChild($this->dom->createElement('chapter'));
			$this->dom->documentElement->setAttribute('title', $chapterName);

			$par = new par($this);
			$par->create($person->getAttribute('uid'), 'type you text here');
			$par->id = 'firstline';
			$this->appendBlock($par);

			$this->save(false);	
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
			internal(getMessage('ToManyElementsWithID').' '.$id);
		else
			return NULL;
	}

	public function getElementsByTagName($name) {
		return $this->dom->getElementsByTagName($name);
	}

	private function createChange($block) {
		$change = $this->allChanges->createElement('change');
		$change->setAttribute('chapter', $this->getTitle());

		if($block->rev > 0) {
//		    if (DEBUG)
//		    	fb(get_class_methods($block), 'block');
			$block->changes($change, true);
		} else {
			$new = new previous($block);
			$imported = $this->allChanges->importNode($new->element, true);
			$change->appendChild($imported);
		}

		$this->allChanges->documentElement->appendChild($change);
	}

	private function createEvent($name, $block) {
		$event = $this->response->createElement($name);
		$imported = $this->response->importNode($block->element, true);
		foreach($imported->childNodes as $child) {
			if ($child->nodeType != XML_ELEMENT_NODE 
				|| $child->nodeName != 'previous')
			$event->appendChild($child->cloneNode(true));
		}
		foreach($imported->attributes as $name=>$node) {
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

		if ($this->allChanges)
			$this->createChange($block);
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

		if ($this->allChanges)
			$this->createChange($block);
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
			warn(getMessage('NothingIsChanged'));
		else
			echo transformDOM($this->response, CORE.'xml/response.xsl', array('MODE' => 'edit'));
	}

	public function changedSince($last, $mode) {
		echo transformDOM($this->dom, CORE.'xml/response.xsl', 
			array('MODE' => $mode, 'LAST' => $last));
	}

	public function edit($id, $rev) {
		echo transformXML($this->chapterFile, CORE.'xml/form.xsl', array('ID' => $id, 'REV' => $rev), 
			PROJECT_MTIME);
	}

	public function getBlock($id) {
		if ($element = $this->getElementById($id)) {
			$type = $element->getAttribute('type');
			$block = new $type($this);
			$block->load($element);
			return $block;
		} else
			return NULL;
	}

	public function save($saveChanges = true) {
		// save new version
		$this->dom->save($this->chapterFile);

		if ($saveChanges) {
			// save changes log
			$this->allChanges->save(CORE.'changes.xml');
		}
	}

	public function update($data, $author) {
		$this->response = new DOMDocument('1.0', 'utf-8');
		$this->response->appendChild($this->response->createElement('response'));

		$this->allChanges = new DOMDocument('1.0', 'utf-8');
		if(file_exists(CORE.'changes.xml'))
			$this->allChanges->load(CORE.'changes.xml');
		else
			$this->allChanges->appendChild($this->allChanges->createElement('changes'));

		// load data
		if (! array_key_exists('id', $data))
			internal(getMessage('IDisNotSet'));

		$id = trim($data['id']);
		if ($id == "") 
			internal(getMessage('IDisEmpty'));

		$rev = trim($data['rev']);
		$rev = $rev == "" ? 0 : $rev;

		if (! ($block = $this->getBlock($id)))
			internal(sprintf(getMessage('ElementWithIDNotFound'), $id));

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

			$this->save();
		}
		$this->respond();
	}

	public function changes($id) {
		$chapterChangesFile = CACHE
			.makeFileName(chapter::getChapterFileName($this->getTitle())
			.'.changes.'.$id.'.xml');

		if (! (file_exists($chapterChangesFile) 
			&& filemtime($chapterChangesFile) > max(PROJECT_MTIME, filemtime($this->chapterFile)))) {

			$dom = new DOMDocument('1.0', 'utf-8');
			$changes = $dom->createElement('changes');
			$changes->setAttribute('id', $id);
			$dom->appendChild($changes);

			if (! ($block = $this->getBlock($id)))
				internal(sprintf(getMessage('ElementWithIDNotFound'), $id));
		
			$block->changes($changes);

			$dom->save($chapterChangesFile);
		}
		echo transformXML($chapterChangesFile, CORE.'xml/changes.xsl', array('ID'=>$id), PROJECT_MTIME);
	}

	public function appendBlock($block, $afterBlock = NULL) {
		$before = $afterBlock ? $afterBlock->element->nextSibling : NULL;
		if(! is_null($before))
			$this->dom->documentElement->insertBefore($block->element, $before);
		else
			$this->dom->documentElement->appendChild($block->element);

		$this->fireBlockInserted($block);
	}

	public function transform($xslFile, $params) {
		echo transformXML($this->chapterFile, $xslFile, $params, PROJECT_MTIME);
	}

	public function exists() {
		return file_exists($this->chapterFile);
	}
}

require_once 'block.php';


?>