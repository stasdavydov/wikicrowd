<?php
/*
	Add 'theme' property to config.xml
*/
class migrate_0_0_15_to_0_0_16 implements migration {
	public function getFromVersion() { return '0.0.15'; }
	public function getToVersion() { return '0.0.16'; }

	public function customUI() { return FALSE; }

	public function checkCustomUI(&$errors) {}

	public function doMigrate() { 
	    $migrator = migrator::getInstance();
		$migrator->setProperty('theme', 'default');

		return TRUE;
	}
}

migrator::getInstance()->addToChain(new migrate_0_0_15_to_0_0_16());
?>