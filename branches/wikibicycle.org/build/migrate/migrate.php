<?php
// Migration executes from installation process
// core.php is loaded

interface migration {
	public function getFromVersion();	// Migrate from version, i.e. '0.0.7'
	public function getToVersion();	// Migrate to version, i.e. '0.0.8' (usualy the next version)

	public function customUI();	// Prints some custom form fields to collect data from user.
								// Returns TRUE if custom UI is exist, FALSE otherwise.

	public function checkCustomUI(&$errors); // Check user input and append message on error(s)

	public function doMigrate();	// Execute migration. Returns TRUE if successful and
									// FALSE otherwise.
}

class migrator implements migration {
	private $config;
	private $dom;
	private $migrationChain;

	private static $instance;

	public static function getInstance() {
		if (! migrator::$instance) {
			migrator::$instance = new migrator();
			migrator::$instance->loadMigrations();
		}
		return migrator::$instance;
	}

	private function __construct() {
		// load classes
		$this->migrationChain = array();
		$this->config = getcwd().'/core/config.xml';
		$this->dom = new DOMDocument();
		$this->dom->load($this->config);
	}

	public function __destruct() {
		$this->dom->save($this->config);
	}

	private function loadMigrations() {
		$myPath = dirname(__FILE__);
		$dir = opendir($myPath);
		while($f = readdir($dir)) {
			if (is_dir($myPath.'/'.$f) 
				&& preg_match('/^(\d+\.\d+\.\d+)-(\d+\.\d+\.\d+)$/', $f)
				&& file_exists($myPath.'/'.$f.'/migration.php')) {

				require_once $myPath.'/'.$f.'/migration.php';
			}
		}
		closedir($dir);

		uksort($this->migrationChain, 'version_compare');
	}

	public function addToChain($migration) {
		if ($migration instanceof migration)
			$this->migrationChain[$migration->getFromVersion()] = $migration;
		else
			die ('Wrong migration adding: '.$migration);
	}

	public function getFromVersion() { return $this->dom->documentElement->getAttribute('version'); }
	public function getToVersion() { global $version; return $version; }

	public function customUI() {
		$hasCustomUI = false;

		foreach($this->migrationChain as $fromVersion => $migration) {
			if (version_compare($this->getFromVersion(), $migration->getFromVersion(), '<='))
				$hasCustomUI |= $migration->customUI();
		}

		return $hasCustomUI;
	}

	public function checkCustomUI(&$errors) {
		foreach($this->migrationChain as $fromVersion => $migration) {
			if (version_compare($this->getFromVersion(), $migration->getFromVersion(), '<='))
				$migration->checkCustomUI($errors);
		}
	}

	public function doMigrate() {
		// execute migration chain	
		foreach($this->migrationChain as $fromVersion => $migration) {
			if (version_compare($this->getFromVersion(), $migration->getFromVersion(), '<=')) {

				echo 'Migrate from '.$this->getFromVersion().' to '.$migration->getToVersion().'... ';
				
				$migration->doMigrate();
				$this->dom->documentElement->setAttribute('version', $migration->getToVersion());
	
				echo "done.<br/>\n";
			}
		}
		
		if (version_compare($this->getFromVersion(), $this->getToVersion(), '<'))
			$this->dom->documentElement->setAttribute('version', $this->getToVersion());

	}

	public function setProperty($name, $value) {
		$xpath = new DOMXPath($this->dom);
		$property = $xpath->query("//property[@name = '$name']");

		if ($property->length == 0) {
			$property = $this->dom->createElement('property');
			$this->dom->documentElement->appendChild($property);
		} else
			$property = $property->item(0);

		$property->setAttribute('name', $name);
		$property->setAttribute('value', $value);
	}
}

?>