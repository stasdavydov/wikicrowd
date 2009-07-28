<?php
	class migrate_0_0_7_to_0_0_8 implements migration {
		private $admin = NULL;

		private $anyoneCanRegister = false;
		private $newUserCanEdit = true;
		private $newUserCanView = true;
		private $guestCanEdit = false;
		private $guestCanView = true;

		private $accessPlans = array(
			array('Personal private', true, false, true, false, false),
			array('Personal public', false, false, false, false, true),
			array('Community private', true, true, true, false, false),
			array('Community public', true, true, true, false, true),
			array('Anonymous wiki (like Wikipedia)', true, true, true, true, true));
		private $accessPlan = 3;

		public function getFromVersion() { return '0.0.7'; }
		public function getToVersion() { return '0.0.8'; }

		public function customUI() {
			// custom UI for persons migration: must select the admin
?><style type="text/css">
._007_008 table { border-right: 1px solid #999; border-bottom: 1px solid #999; }
._007_008 td { text-align: center; border-left: 1px solid #999; }
._007_008 td.ne { border-left:none; padding-right:0.25em;text-align:left;}
._007_008 td label, ._007_008 td input { display:inline; margin: 0; float:none;}
._007_008 tr.odd { background: #EEE; }
._007_008 th { border: 1px solid #999; border-right: none; font-size:90%;}
._007_008 th.ne { border: none; border-bottom: 1px solid #999; }
</style>
<fieldset class="_007_008">
<legend>Admin and Access rights</legend>
<label for="admin_007_008">Choose Wiki admin:</label>
<select id="admin_007_008" name="admin_007_008">
<option value="">Select one</option>
<?
			$dir = opendir(getcwd().'/persons') or die('Cannot open '.getcwd().'/persons'.' dir.');
			while($f = readdir($dir)) {
				if (! preg_match('/^(guest|system)\.xml$/', $f) && preg_match('/\.xml$/', $f)) {
					$dom = new DOMDocument();
					$dom->load(getcwd().'/persons/'.$f);
					$person = $dom->documentElement;
?><option value="<?=$person->getAttribute('uid')?>"<?

					if ($person->getAttribute('uid') == $this->admin)
						echo ' selected="selected"';

?>><?=$person->getAttribute('name')?> &lt;<?=
	$person->getAttribute('email')?>&gt;</option>
<?
				}
			}

?></select>

<p>Set up access rights.</p>

<table border="0" cellspacing="0">
<col style="width: 2em;"/>
<thead>
<tr><th colspan="2" class="ne">&nbsp;</th>
<th>Anyone can register</th>
<th>New user can edit</th>
<th>New user can read</th>
<th>Guest can edit</th>
<th>Guest can read</th></tr>
</thead>
<tbody>
<?
			foreach($this->accessPlans as $plan=>$details) {
?><tr<?	if ($plan % 2) echo ' class="odd"'; ?>><td><input type="radio" name="plan_007_008" <?
				if ($plan == $this->accessPlan)
					echo 'checked="checked" ';
?>id="plan<?=$plan?>_007_008" value="<?=$plan?>"/></td>
<td class="ne"><label for="plan<?=$plan?>_007_008"><?=$details[0]?></label></td>
<?			
				for($idx = 1; $idx < count($details); $idx++) {
?><td><input type="checkbox" <? if ($details[$idx]) echo 'checked="checked" '; ?>disabled="disabled"/></td>
<?
				}
?></tr>
<?
			}
?><tr<?		if (count($this->accessPlans) % 2) echo ' class="odd"'; ?>><td><input type="radio" name="plan_007_008" id="plan<?=count($accessPlans)?>_007_008" <?
				if (count($this->accessPlans) == $this->accessPlan)
					echo 'checked="checked" ';
?>value="<?=count($this->accessPlans)?>"/></td>
<td class="ne"><label for="plan<?=count($this->accessPlans)?>_007_008">Your choice</label></td>
<td><input type="checkbox" name="anyoneCanRegister_007_008" value="1"<?
			if ($this->anyoneCanRegister) echo ' checked="checked"'; ?>/></td>
<td><input type="checkbox" name="newUserCanEdit_007_008" value="1"<?
			if ($this->newUserCanEdit) echo ' checked="checked"'; ?>/></td>
<td><input type="checkbox" name="newUserCanView_007_008" value="1"<?
			if ($this->newUserCanView) echo ' checked="checked"'; ?>/></td>
<td><input type="checkbox" name="guestCanEdit_007_008" value="1"<?
			if ($this->guestCanEdit) echo ' checked="checked"'; ?>/></td>
<td><input type="checkbox" name="guestCanView_007_008" value="1"<?
			if ($this->guestCanView) echo ' checked="checked"'; ?>/></td>
</tr>
</tbody>
</table>

</fieldset>
<?
		}

		public function checkCustomUI(&$errors) {
			$this->admin = trim($_POST['admin_007_008']);
			if ($this->admin == "") {
				$errors[] = "Wiki admin is required. Please select one.";
			}

			$accessPlan = array_key_exists('plan_007_008', $_POST) ? $_POST['plan_007_008'] : count($this->accessPlans);
			$this->anyoneCanRegister = array_key_exists('anyoneCanRegister_007_008', $_POST) && $_POST['anyoneCanRegister_007_008'];
			$this->newUserCanEdit = array_key_exists('newUserCanEdit_007_008', $_POST) && $_POST['newUserCanEdit_007_008'];
			$this->newUserCanView = array_key_exists('newUserCanView_007_008', $_POST) && $_POST['newUserCanView_007_008'];
			$this->guestCanEdit = array_key_exists('guestCanEdit_007_008', $_POST) && $_POST['guestCanEdit_007_008'];
			$this->guestCanView = array_key_exists('guestCanView_007_008', $_POST) && $_POST['guestCanView_007_008'];
		}

		public function doMigrate() {
			$migrator = migrator::getInstance();

			if ($this->accessPlan < count($this->accessPlans)) {
				$this->anyoneCanRegister = $this->accessPlans[$this->accessPlan][1];
				$this->newUserCanEdit = $this->accessPlans[$this->accessPlan][2];
				$this->newUserCanView = $this->accessPlans[$this->accessPlan][3];
				$this->guestCanEdit = $this->accessPlans[$this->accessPlan][4];
				$this->guestCanView = $this->accessPlans[$this->accessPlan][5];
			}

			// migrate config file
			$migrator->setProperty('anyoneCanRegister', $this->anyoneCanRegister);
			$migrator->setProperty('newUserCanEdit', $this->newUserCanEdit);
			$migrator->setProperty('newUserCanView', $this->newUserCanView);

			// migrate chapters: change 'system' to new admin user
			$dir = opendir(getcwd().'/chapters');
			while($chapter = readdir($dir)) {
				if (is_file(getcwd().'/chapters/'.$chapter) && preg_match('/\.xml$/', $chapter)) {
					$dom = new DOMDocument();
					$dom->load(getcwd().'/chapters/'.$chapter);
					$xpath = new DOMXPath($dom);
					$nodes = $xpath->query("//*[@author = 'system']");
					if ($nodes->length > 0) {
						for($i = 0; $i < $nodes->length; $i++)
							$nodes->item($i)->setAttribute('author', $this->admin);
						$dom->save(getcwd().'/chapters/'.$chapter);
					}
				}
			}
			closedir($dir);

			// migrate persons
			$dir = opendir(getcwd().'/persons');
			while($person = readdir($dir)) {
				if (is_file(getcwd().'/persons/'.$person) && preg_match('/\.xml$/', $person)) {
					$dom = new DOMDocument();
					$dom->load(getcwd().'/persons/'.$person);
					if ($this->admin == $dom->documentElement->getAttribute('uid')) {
						$dom->documentElement->setAttribute('admin', true);
						$dom->documentElement->setAttribute('can-edit', true);
						$dom->documentElement->setAttribute('can-view', true);
					} else {
						$dom->documentElement->setAttribute('can-edit', $this->newUserCanEdit);
						$dom->documentElement->setAttribute('can-view', $this->newUserCanView);
					}
					$dom->save(getcwd().'/persons/'.$person);
				}
			}
			closedir($dir);

			// remove old fake account 'system'
			unlink(getcwd().'/persons/system.xml');

			// create guest account
			$guest = createPerson('guest', md5(time()), 'Guest', 'noreply', 'Guest account', 
				$this->guestCanEdit, $this->guestCanView, false);
			$guest->save(getcwd().'/persons/guest.xml');

			return TRUE;
		}
	}

	migrator::getInstance()->addToChain(new migrate_0_0_7_to_0_0_8());
?>