<?php

abstract class theme {
	public function transform($chapter, $person, $mode) {
		$params = array(
			'MODE'=>personCan($person, $mode) ? $mode : 'restricted',
			'UID'=>$person->getAttribute('uid'),
			'NAME'=>$person->getAttribute('name'),
			'ADMIN'=>isAdmin($person),
			'CANEDIT'=>personCanEdit($person),
			'CANVIEW'=>personCanView($person)
		);
		
		echo $chapter->transform(
			$mode == "edit" ? $this->getEditXslPath() : $this->getViewXslPath(), $params);
	}

	public abstract function getEditXslPath();
	public abstract function getViewXslPath();
}

?>