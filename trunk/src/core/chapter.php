<?php
require_once HOME.'mb_diff.php';

class chapter {
	private $chapterFile;
    private $dom;
    private $response;  // track of changes on update
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

	public function getTitle() { return $this->dom->documentElement->getAttribute('title'); }

	public function setTitle($title, $author) {
		$oldTitle = $this->getTitle();

		// change title
		$this->dom->documentElement->setAttribute('title', $title);
		$this->dom->save($this->chapterFile);

		// rename file
		rename($this->chapterFile, CHAPTERS.self::getChapterFileName($title));

		// update chapter index
		ChapterIndex::getInstance()->renameChapter($oldTitle, $title);
		ChapterIndex::getInstance()->save();

		// update rename table
		$dom = new DOMDocument('1.0', 'UTF-8');
		if (! file_exists($fileName = CORE.'renametable.xml')) {
			$dom->appendChild($dom->createElement('renametable'));
		} else {
			$dom->load($fileName);
		}

		$renamed = $dom->createElement('renamed');
		$renamed->setAttribute('from', $oldTitle);
		$renamed->setAttribute('to', $title);
		$renamed->setAttribute('ts', time());
		$renamed->setAttribute('author', $author);

		// include all chapters for updating links
		$titles = ChapterIndex::getInstance()->getTitles($oldTitle);
		for($i = 0; $i < $titles->length; $i++) {
			$chapTitle = $titles->item($i);
			$entry = $dom->createElement('entry');
			$entry->setAttribute('title', $chapTitle->getAttribute('title'));
			$renamed->appendChild($entry);
		}
		$dom->documentElement->appendChild($renamed);
		$dom->save($fileName);

		// update "child" pages
		$titles = ChapterIndex::getInstance()->getTitles($oldTitle);
		$oldTitleLen = strlen($oldTitle);
		for($i = 0; $i < $titles->length; $i++) {
			$chapTitle = $titles->item($i);
			$chapTitle = $chapTitle->getAttribute('title');
			if (strpos($chapTitle, $oldTitle) === 0) {
				try {
					$childChap = new chapter(false, $chapTitle, false);
					$childChap->setTitle(/* $newTitle = */$title . substr($chapTitle, $oldTitleLen), $author);
//					$this->fireChapterRenamed($chapTitle, $newTitle, $author);
				} catch(ChapterNotFoundException $e) {
					// it can happen on cascade renaming
					// we can ignore it, because the page was already renamed
				}
			}
		}
	}

	private function checkRenames() {
		if (! file_exists($fileName = CORE.'renametable.xml')
			|| filemtime($fileName) < filemtime($this->chapterFile))
			return;

		$dom = new DOMDocument('1.0', 'UTF-8');
		$dom->load($fileName);

		// update links by table
		$xpath = new DOMXPath($dom);
		$actualRenames = $xpath->query(
			'//renamed/entry[@title = \''.$this->getTitle().'\']');
		if ($actualRenames->length > 0) {
			$xpath = new DOMXPath($this->dom);
			$blocks = $xpath->query('//block');

			$changed = false;

			$person = getSessionPerson();
			$author = $person->getAttribute('uid');

			$this->allChanges = new DOMDocument('1.0', 'utf-8');
			if(file_exists(CORE.'changes.xml'))
				$this->allChanges->load(CORE.'changes.xml');
			else
				$this->allChanges->appendChild($this->allChanges->createElement('changes'));

			for($i = 0; $i < $actualRenames->length; $i++) {
				$entry = $actualRenames->item($i);
				$rename = $entry->parentNode;
				$from = $rename->getAttribute('from');
				$to = $rename->getAttribute('to');

				for($j = 0; $j < $blocks->length; $j++) {
					$element = $blocks->item($j);
					$type = $element->getAttribute('type');
					$block = new $type($this);
					$block->load($element);
					$previous = new previous($block);

					if ($block->updateLink($from, $to, $author)) {
						$block->element->appendChild($previous->element);
						$block->increaseRevision($author);
						$this->fireBlockUpdated($block);

						$changed |= true;
					}
				}
				$rename->removeChild($entry);
			}
			if ($changed) {
				// save new version
				$this->dom->save($this->chapterFile);

				// save changes log
				$this->allChanges->save(CORE.'changes.xml');
			}
		}
		$dom->save($fileName);
	}

	static private function getChapterFileName($chapterName) {
		return makeFileName(fileNamePartEncode(trim($chapterName)).'.xml');
	}

	/**
	 * @note Usualy it doesn't require to pass $chapterName by param because
	 * it will be requested from environment. There is only one exception: when you want
	 * to manually create chapter with the specific name.
	 */
	public function __construct($ajaxUsing = true, $chapterName = NULL, $createIfPossible = true) {
		global $LOCKFILE;

		if ($chapterName === NULL) {
			$chapterName = chapter::getChapterName($ajaxUsing);
			if ($chapterName === NULL) {
  				error404();
			} else if ($chapterName == "") {
				$chapterName = homePage;	// determine / as a default home page
			}
		}

		$this->chapterFile = CHAPTERS . self::getChapterFileName($chapterName);
		$this->dom = new DOMDocument('1.0', 'utf-8');

		enterCriticalSection($LOCKFILE);

		$person = getSessionPerson();
		if (! file_exists($this->chapterFile)) {

			if (! personCanEdit($person)) {
                error404();
			}

			if (! $createIfPossible) {
				throw new ChapterNotFoundException($chapterName);
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

			ChapterIndex::getInstance()->appendChapter($chapterName);
			ChapterIndex::getInstance()->save();
		} else {
			$this->dom->load($this->chapterFile);
			$this->checkRenames();
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

	private function fireChapterRenamed($oldTitle, $newTitle, $author) {
		if ($this->response) {
			$event = $this->response->createElement('chapterrenamed');
			$event->setAttribute('oldtitle', $oldTitle);
			$event->setAttribute('newtitle', $newTitle);
			$event->setAttribute('author', $author);
			$this->response->documentElement->appendChild($event);

			if ($this->allChanges) {
				$change = $this->allChanges->createElement('rename');
				$change->setAttribute('old', $oldTitle);
				$change->setAttribute('new', $newTitle);
				$change->setAttribute('created-ts', $ts = time());
				$change->setAttribute('created-date', date('d/m/Y H:i', $ts));
				$change->setAttribute('author', $author);

				$change->appendChild(
					$this->allChanges->importNode($event, true));
				$this->allChanges->documentElement->appendChild($change);
				$this->allChanges->save(CORE.'changes.xml');
			}
			
			return $event;
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

		// load data
		if (! array_key_exists('id', $data))
			internal(getMessage('IDisNotSet'));

		$id = trim($data['id']);
		if ($id == "") 
			internal(getMessage('IDisEmpty'));

		$this->allChanges = new DOMDocument('1.0', 'utf-8');
		if(file_exists(CORE.'changes.xml'))
			$this->allChanges->load(CORE.'changes.xml');
		else
			$this->allChanges->appendChild($this->allChanges->createElement('changes'));

		if ($id == "chaptertitle") {
			if (! array_key_exists('text', $data))
				internal(getMessage('TextIsNotSet'));

			$text = trim(stripslashes($data['text']));
			$oldTitle = $this->getTitle();
			$chapTitle = new chaptertitle($this);

			if ($chapTitle->update($data, $author)) {
				$this->fireChapterRenamed($oldTitle, $text, $author);
			}
		} else {
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


class ChapterIndex {
	private $dom;
	private static $instance;

	public static function getInstance() {
		if (self::$instance == NULL)
			self::$instance = new ChapterIndex();
		return self::$instance;
	}

	private function __construct() {
		if ($this->dom == NULL) {
			$this->dom = new DOMDocument('1.0', 'UTF-8');
			if (! file_exists(CHAPTERS_INDEX)) {
				$this->dom->appendChild($this->dom->createElement('chapters'));

				$dir = opendir(CHAPTERS);
				while($file = readdir($dir)) {
					if (preg_match('/\.xml$/', $file)
						&& preg_match('/<chapter title="(?P<title>[^"]+)"/', 
							file_get_contents(CHAPTERS.$file), $matches))
						$this->appendChapter($matches['title']);
				}
				closedir($dir);
				$this->save();
			} else {
				$this->load(CHAPTERS_INDEX);
			}
		}
	}

	private function load() {
		$this->dom->load(CHAPTERS_INDEX);
	}

	public function save() {
		$this->dom->save(CHAPTERS_INDEX);
	}

	public function appendChapter($chapTitle) {
		$xpath = new DOMXPath($this->dom);
		$chap = $xpath->query('//chapter[@title = \''.$chapTitle.'\']');
		if ($chap->length == 0) {
			$chap = $this->dom->createElement('chapter');
			$chap->setAttribute('title', $chapTitle);
			$this->dom->documentElement->appendChild($chap);
		}
	}

	public function renameChapter($oldTitle, $newTitle) {
	 	$this->removeChapter($oldTitle);
	 	$this->appendChapter($newTitle);
	}

	private function removeChapter($chapTitle) {
		$xpath = new DOMXPath($this->dom);
		$chap = $xpath->query('//chapter[@title = \''.$chapTitle.'\']');
		if ($chap->length > 1) {
			throw new Exception('Wrong case, more than one chapter with the same name "'.$chapTitle.'"');
		} else if ($chap->length == 1)
			$this->dom->documentElement->removeChild($chap->item(0));
	}

	public function getTitles($exceptTitle = NULL) {
		$xpath = new DOMXPath($this->dom);
		return $xpath->query('//chapter'.
			($exceptTitle == NULL ? '' : '[not(@title = \''.$exceptTitle.'\')]'));
	}
}

class ChapterNotFoundException extends Exception {
	private $title;
	public function __construct($title) {
		$this->title = $title;
	}

	public function getTitle() { return $this->title; }
}

require_once 'block.php';

?>