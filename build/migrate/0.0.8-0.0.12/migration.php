<?php
/*
	Minor migration: remove old lib/ folder from core/
*/
class migrate_0_0_8_to_0_0_12 implements migration {
	public function getFromVersion() { return '0.0.8'; }
	public function getToVersion() { return '0.0.12'; }

	public function customUI() { return FALSE; }
	public function checkCustomUI(&$errors) {}

	public function doMigrate() { 
		unlinkRecursive(getcwd().'/core/lib');	
		return TRUE;
	}
}
?>