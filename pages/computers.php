<?php

function getPageTitle() {
	return 'Computer Management';
}

function doHeader() {
	global $activePage, $activeMenu;

	echo '<link rel="stylesheet" type="text/css" href="css/form.css">';
	echo '<link rel="stylesheet" type="text/css" href="css/results.css">';
	echo '<script src="script/popout.js"></script>';

	$activePage = 'computers';

	if ($_GET['a'] == "new") {
		$activeMenu = 'new';
	} elseif ($_GET['a'] == "find") {
		$activeMenu = 'find';
	} else {
		//Default action
		$activeMenu = 'find';
	}

	return;
}

function getMenu() {
return array(
	'Find Computer' => array(
		'url' => '?p=computers&amp;a=find',
		'alias' => 'find',
	),
	'Add Computer' => array(
		'url' => '?p=computers&amp;a=new',
		'alias' => 'new',
	),
);
}

function getBody() {
	if ($_GET['a'] == "new") {
		doNewComputer();
	} elseif ($_GET['a'] == "edit") {
		doEditComputer();
	} elseif ($_GET['a'] == "find") {
		doFindComputer();
	} elseif ($_GET['a'] == "view") {
		doViewComputer();
	} else {
		//Default action
		doFindComputer();
	}
}

function showError($error, $fatal = false) {
	global $errorMsg;
	$errorMsg = $error;
	getBody();
	if ($fatal == true) {
		die();
	}
}

function doNewComputer() {
	global $SQL, $config, $errorMsg;
	
	if (empty($errorMsg)) {
		if ($_GET['t'] == "confirm") {
			$_POST['name'] = strtoupper($_POST['name']);
			$_POST['serial'] = strtoupper($_POST['serial']);

			if ($_POST['serial'] == "" || $_POST['name'] == "") {
				showError('Please ensure you provide a serial number and PC name',true);
				return;
			}
			$query = mysql_query("SELECT id FROM ".$SQL['DATABASE'].".computers WHERE serial = '".mysql_real_escape_string($_POST['serial'])."'");
			if ($serial = mysql_fetch_assoc($query)) {
				showError('This serial number is already in the database, Please double check and try again<BR><BR>Please click here to view the current entry in the database: <a href="?p=computers&a=find&query='.$_POST['serial'].'">'.$_POST['serial'].'</a>',true);
				return;
			}
			
			$query = mysql_query("SELECT id FROM ".$SQL['DATABASE'].".computers WHERE name = '".mysql_real_escape_string($_POST['name'])."' AND active = 1");
			if ($active = mysql_fetch_assoc($query)) {
				showError('Please note, this PC name is already allocated to a machine that is currently in use<br><br>Please click here to view the current entry in the database: <a href="?p=computers&a=find&query='.$_POST['name'].'">'.$_POST['name'].'</a>',true);
				return;		
			}

			if (!is_numeric($_POST['location'])) {
				showError('Location is invalid', true);
				return;
			}
			$locationQuery = mysql_query("SELECT friendlyName FROM ".$SQL['DATABASE'].".locations WHERE id = '".mysql_real_escape_string($_POST['location'])."' LIMIT 0,1");
			$location = mysql_fetch_assoc($locationQuery);
			if (empty($location['friendlyName'])) {
				showError('Please choose a valid location', true);
				return;
			}
			if (!is_bool(filter_var($_POST['active'], FILTER_VALIDATE_BOOLEAN))) {
				showError('Active status is invalid', true);
				return;
			}
			if (!is_numeric($_POST['cost'])) {
				showError('Fee is invalid', true);
				return;
			}


			echo '<p><b><a href="?p=computers&a=find&query='.$_POST['name'].'">'.$_POST['name'].'</a></b> has successfully been added to the database</p>';
			$_POST = sanitize($_POST);
			$query = mysql_query("INSERT INTO ".$SQL['DATABASE'].".computers (
									`id`,
									`serial`, 
									`name`, 
									`location`, 
									`maintenanceFee`,
									`createdby`, 
									`modifiedby`, 
									`created`, 
									`modified`,
									`comments`,
									`active`
								) VALUES (
									NULL, 
									'".$_POST['serial']."', 
									'".$_POST['name']."', 
									'".$_POST['location']."', 
									'".$_POST['cost']."', 
									'".$_SESSION['displayName']."', 
									'".$_SESSION['displayName']."', 
									'".time()."', 
									'".time()."', 
									'".$_POST['comments']."', 
									'".$_POST['active']."'
								)");
			$_POST = array();
		}
	}


	$locationQuery = mysql_query("SELECT id,friendlyName FROM ".$SQL['DATABASE'].".locations ORDER BY friendlyName asc");

	if (!empty($errorMsg)) {
		echo '<p><span style="color: red;">'.$errorMsg.'</span></p>';
	}
	?>
	<form method="post" action="?p=computers&a=new&t=confirm">
	<label for="name">Computer name: </label><input type="text" name="name" value="<?php echo $_POST['name']; ?>"><br/>
	<label for="serial">Serial number: </label><input type="text" name="serial" value="<?php echo $_POST['serial']; ?>"><span class="infoText">Please ensure this is correct. It can only be changed by an Administrator!</span><br/>
	<label for="location">Location: </label><select name="location"><?php
		while ($location = mysql_fetch_assoc($locationQuery)) {
			echo '<option value="'.$location['id'].'"'.(($_POST['location'] == $location['id']) ? ' SELECTED' : '').'>'.$location['friendlyName'].'</option>';
		}
	?></select><br/>
	<label for="active">Active: </label><input type="checkbox" name="active" value="1"<?php if (!empty($_POST['active']) || $_POST['active'] == 1) { echo ' CHECKED'; }?>><br/><br/>
	<label for="cost">Maintenance Fee (£): </label><input type="text" name="cost" size="5" value="<?php echo (($_POST['cost']) ? $_POST['cost'] : $config['defaultMaintenanceFee']); ?>"><br/>
	<label for="comments" style="vertical-align: top;">Comments: </label><textarea name="comments" rows="15" cols="80"><?php echo $_POST['comments']; ?></textarea><br/>

	
	<p><input class="button" type="submit" value="Add"></p>
	</form>
	<?php
}

function doFindComputer() {
	global $SQL;

	//ini_set('session.cache_limiter', 'private');

	if (isset($_POST['query'])) {
		$queryString = (!empty($_POST['query']) ? $_POST['query'] : '%');
		$queryLocation = (!empty($_POST['location']) ? $_POST['location'] : '%');
		$searchIn = $_POST['searchIn'];
		$state = $_POST['state'];
		$queryType = $_POST['queryType'];
	} elseif (isset($_GET['query'])) {
		$queryString = $_GET['query'];
		$queryLocation = (!empty($_GET['location']) ? $_GET['location'] : '%');
		$searchIn = $_GET['searchIn'];
		$state = $_GET['state'];
		$queryType = $_GET['queryType'];
	}

	if (!$state) { $state = "active"; }

	if ($queryString) {
		if ($state == 'active') {
			$active = 1;
		} elseif ($state == 'inactive') {
			$active = 0;
		} else  {
			//Dedeclare this in case someone has given us a bogus value
			$state = 'all';
			$active = '%';
		}
	}

	//Check that we have not been given a bogus value
	if ($queryType != 'match' && $queryType != 'dmatch') { $queryType = 'match'; }
	if ($searchIn != 'computers' && $searchIn != 'chlog') { $searchIn = 'computers'; }

	$locationQuery = mysql_query("SELECT locations.id, friendlyName, COUNT(name) as count FROM ".$SQL['DATABASE'].".locations LEFT JOIN ".$SQL['DATABASE'].".computers ON locations.id = computers.location AND computers.active = 1 WHERE hidden = '0' GROUP BY locations.friendlyName ORDER BY friendlyName ASC");
	?>
	<form name="search" method="post" action="?p=computers&a=find">

	<label for="query">Search for: </label><input type="text" name="query" value="<?php echo $queryString;?>">
	<span class="infoText">% is considered a wildcard</span>

	<br>

	<label for="location">Location: </label><select name="location"><option value="%">All locations</option><?php
		while ($location = mysql_fetch_assoc($locationQuery)) {
			echo '<option value="'.$location['id'].'"';
			if (isset($queryLocation) && $queryLocation == $location['id']) {
				echo ' SELECTED';
			}
			echo '>'.$location['friendlyName'].' ('.$location['count'].')</option>';
			echo "\n";
		}
	?></select>

	<br>

	<label for="searchIn">Search in: </label><select name="searchIn">
		<option value="computers"<?php echo ($searchIn == 'computers' ? ' SELECTED' : '');?>>Computers</option>
		<option value="chlog"<?php echo ($searchIn == 'chlog' ? ' SELECTED' : '');?>>Change log</option>
	</select>

	<br>


	<label for="all">Show all</label><input type="radio" name="state" id="all" value="all"<?php echo ($state == 'all' ? ' CHECKED' : '');?>>
	<label for="active">Show active only</label><input type="radio" name="state" id="active" value="active"<?php echo ($state == 'active' ? ' CHECKED' : '');?>>
	<label for="inactive">Show inactive only</label><input type="radio" name="state" id="inactive" value="inactive"<?php echo ($state == 'inactive' ? ' CHECKED' : '');?>>


	<p>&nbsp;</p>

	<label for="queryType">Query type: </label><select name="queryType">
		<option value="match"<?php echo ($queryType == 'match' ? ' SELECTED' : '');?>>Match</option>
		<option value="dmatch"<?php echo ($queryType == 'dmatch' ? ' SELECTED' : '');?>>Does not match</option>
	</select><br>
	<span class="infoText">"Match" will list all results that match above criteria, "Does not match" will list everything else</span>

	<p><input class="button" type="submit" value="Search" name="search"></p>
	</form>
	<?php

	if ($queryString) {
		if ($searchIn == 'computers') {
			doComputerSearch($queryString, $queryLocation, $queryType, $state, $active);
		} elseif ($searchIn == 'chlog') {
			doChangeLogSearch($queryString, $queryLocation, $queryType);
		}
	}
}


function doComputerSearch($queryString, $queryLocation, $queryType, $state, $active) {
	global $SQL, $config;
	
	//original
	//$query = mysql_query("SELECT computers.id, computers.name, computers.serial, locations.friendlyname, computers.created, computers.createdby, computers.modified, computers.modifiedby, computers.active FROM ".$SQL['DATABASE'].".computers, ".$SQL['DATABASE'].".locations WHERE (serial LIKE '".$queryString."' OR name LIKE '".$queryString."' OR createdby LIKE '".$queryString."' OR modifiedby LIKE '".$queryString."' OR locations.friendlyName LIKE '".$queryString."' OR comments LIKE '".$queryString."') AND computers.location = locations.id AND computers.location LIKE '".$queryLocation."' AND computers.active LIKE '".$active."' ORDER BY name ASC");

	$linkURL = '?p=computers&a=find&query='.urlencode($queryString).'&location='.urlencode($queryLocation).'&searchIn=computers&state='.urlencode($state).'&queryType='.urlencode($queryType);
	if ($_POST) {
		header('Location: '.(dirname($_SERVER['SCRIPT_NAME']) != '/' ? dirname($_SERVER['SCRIPT_NAME']) : '').'/'.$linkURL);
	}
	
	$queryString = mysql_real_escape_string($queryString);
	$queryLocation = mysql_real_escape_string($queryLocation);
	$queryType = mysql_real_escape_string($queryType);
	$state = mysql_real_escape_string($state);

	$queryString = "SELECT computers.id, computers.name, computers.serial, locations.friendlyname, computers.created, computers.createdby, computers.modified, computers.modifiedby, computers.active FROM ".$SQL['DATABASE'].".computers, ".$SQL['DATABASE'].".locations WHERE (serial LIKE '".$queryString."' OR name LIKE '".$queryString."' OR createdby LIKE '".$queryString."' OR modifiedby LIKE '".$queryString."' OR locations.friendlyName LIKE '".$queryString."' OR comments LIKE '".$queryString."') AND computers.location = locations.id AND computers.location LIKE '".$queryLocation."' AND computers.active LIKE '".$active."' ORDER BY name ASC";
	if ($queryType == 'dmatch') {
		$queryString = "SELECT 	computers.id, computers.name, computers.serial, locations.friendlyname, computers.created, computers.createdby, computers.modified, computers.modifiedby, computers.active FROM ".$SQL['DATABASE'].".computers LEFT JOIN (".$queryString.") AS subquery ON computers.id = subquery.id INNER JOIN ".$SQL['DATABASE'].".locations ON locations.id = computers.location WHERE subquery.id IS NULL";
	}

	$query = mysql_query($queryString);
	$resultCount = mysql_num_rows($query);
	echo '<p>Found '.$resultCount.' result(s) in <b>Computers</b> - <a href="'.$linkURL.'">Link this query</a> (for Bookmarks or sharing)</p>';

	echo '<table id="linkresults">';
	echo '<tr id="header">';
	echo '<td>PC Name</td><td>Serial Number</td><td>Location</td><td>Created on</td><td>Created by</td><td>Modified on</td><td>Modified by</td>';
	echo '</tr>';
	//TODO: Page Results

	if ($resultCount == 0) {
		echo '<tr class="noHover"><td colspan="7">No Results to display</td></tr>';
	} else {
		$i = "colour1";
		while ($info = mysql_fetch_assoc($query)) {
			$colour = $i;
			if ($info['active'] == 0) { $colour = "colour3"; }
			if ($config['linkType'] == 'inline') {
				echo '<tr class="'.$colour.'" onclick="javascript:window.location=\'?p=computers&a=view&id='.$info['id'].'\';">';
			} elseif ($config['linkType'] == 'popout') {
				echo '<tr class="'.$colour.'" onclick="javascript:popout(\'?p=computers&a=view&id='.$info['id'].'\');">';
			}
			echo '<td>'.$info['name'].'</td><td>'.$info['serial'].'</td><td>'.$info['friendlyname'].'</td><td>'.date("d/m/Y @ H:i:s", $info['created']).'</td><td>'.$info['createdby'].'</td><td>'.date("d/m/Y @ H:i:s", $info['modified']).'</td><td>'.$info['modifiedby'].'</td>';
			echo '</tr>';
			
			$i = ($i == "colour1") ? "colour2" : "colour1";
		}
	}
	echo '</table>';
}

function doChangeLogSearch($queryString, $queryLocation, $queryType) {
	global $SQL, $config;

	//original
	//$query = mysql_query("SELECT computers.id, changelog.date, changelog.changedby, changelog.field, changelog.old, changelog.new, computers.name, computers.serial FROM ".$SQL['DATABASE'].".computerChangeLog as changelog, ".$SQL['DATABASE'].".computers WHERE (changelog.changedby LIKE '".$queryString."' OR changelog.old LIKE '".$queryString."' OR changelog.new LIKE '".$queryString."') AND computers.id = changelog.computerid ORDER BY date DESC LIMIT 0,9999");
	
	$linkURL = '?p=computers&a=find&query='.urlencode($queryString).'&location='.urlencode($queryLocation).'&searchIn=chlog&queryType='.urlencode($queryType);
	if ($_POST) {
		header('Location: '.(dirname($_SERVER['SCRIPT_NAME']) != '/' ? dirname($_SERVER['SCRIPT_NAME']) : '').'/'.$linkURL);
	}

	$queryString = mysql_real_escape_string($queryString);
	$queryLocation = mysql_real_escape_string($queryLocation);
	$queryType = mysql_real_escape_string($queryType);

	$queryString = "SELECT changelog.id as unid, computers.id, changelog.date, changelog.changedby, changelog.field, changelog.old, changelog.new, computers.name, computers.serial FROM ".$SQL['DATABASE'].".computerChangeLog as changelog, ".$SQL['DATABASE'].".computers WHERE (changelog.changedby LIKE '".$queryString."' OR changelog.old LIKE '".$queryString."' OR changelog.new LIKE '".$queryString."') AND computers.id = changelog.computerid ORDER BY date DESC";
	if ($queryType == 'dmatch') {
		$queryString = "SELECT computers.id, changelog.date, changelog.changedby, changelog.field, changelog.old, changelog.new, computers.name, computers.serial FROM ".$SQL['DATABASE'].".computerChangeLog as changelog LEFT JOIN (".$queryString.") AS subquery ON changelog.id = subquery.unid INNER JOIN ".$SQL['DATABASE'].".computers ON changelog.computerid = computers.id WHERE subquery.unid IS NULL";
	}
	//$query = mysql_query("SELECT computers.id, changelog.date, changelog.changedby, changelog.field, changelog.old, changelog.new, computers.name, computers.serial FROM ".$SQL['DATABASE'].".computerChangeLog as changelog, ".$SQL['DATABASE'].".computers WHERE (changelog.changedby LIKE '".$queryString."' OR changelog.old LIKE '".$queryString."' OR changelog.new LIKE '".$queryString."') AND computers.id = changelog.computerid ORDER BY date DESC LIMIT 0,9999");
	$query = mysql_query($queryString); 
	$resultCount = mysql_num_rows($query);
	echo '<p>Found '.$resultCount.' result(s) in <b>Change Log</b> - <a href="'.$linkURL.'">Link this query</a> (for Bookmarks or sharing)</p>';
	
	echo '<table id="linkresults">';
	echo '<tr id="header">';
	echo '<td>PC Name</td><td>Serial Number</td><td>Changed</td><td>Field</td><td>Old Value</td><td>New Value</td><td>Changed By</td>';
	echo '</tr>';
	//TODO: Page Results

	if ($resultCount == 0) {
		echo '<tr class="noHover"><td colspan="7">No Results to display</td></tr>';
	} else {
		$i = "colour1";
		while ($info = mysql_fetch_assoc($query)) {
			if (strlen($info['old']) > 40) {
				$info['old'] = substr($info['old'], 0, 40).'...';
			}
			if (strlen($info['new']) > 40) {
				$info['new'] = substr($info['new'], 0, 40).'...';
			}
			$colour = $i;
			if ($config['linkType'] == 'inline') {
				echo '<tr class="'.$colour.'" onclick="javascript:window.location=\'?p=computers&a=view&id='.$info['id'].'\';">';
			} elseif ($config['linkType'] == 'popout') {
				echo '<tr class="'.$colour.'" onclick="javascript:popout(\'?p=computers&a=view&id='.$info['id'].'\');">';
			}
			echo '<td>'.$info['name'].'</td><td>'.$info['serial'].'</td><td>'.date("d/m/Y @ H:i:s", $info['date']).'</td><td>'.$info['field'].'</td><td>'.$info['old'].'</td><td>'.$info['new'].'</td><td>'.$info['changedby'].'</td>';
			echo '</tr>';
			
			$i = ($i == "colour1") ? "colour2" : "colour1";
		}
	}
	echo '</table>';
}

function doEditComputer() {
	global $SQL, $config, $errorMsg;

	if (!empty($_POST['id'])) {
		$id = $_POST['id'];
	} elseif (isset($_GET['id']) && !empty($_GET['id'])) {
		$id = $_GET['id'];
	} else {
		echo '<p>There was an error getting the information to view, please go back and try again.</p>';
		return;
	}
	
	$query = mysql_query("SELECT computers.*, locations.friendlyname FROM ".$SQL['DATABASE'].".computers, ".$SQL['DATABASE'].".locations WHERE computers.id = '".mysql_real_escape_string($id)."' AND computers.location = locations.id LIMIT 0,1");

	if (mysql_num_rows($query) == 0) {
		echo '<p>This ID is not in the Database!</p>';
		return;
	}
	
	$info = mysql_fetch_assoc($query);
	$display = $info;

	if ($_POST) {
		$display['name'] = $_POST['name'];
		$display['location'] = $_POST['location'];
		$display['active'] = filter_var($_POST['active'], FILTER_VALIDATE_BOOLEAN);
		$display['maintenanceFee'] = $_POST['cost'];
		$display['comments'] = $_POST['comments'];
	}

	if (empty($errorMsg)) {	
		if ($_GET['t'] == "confirm") {
			$_POST['name'] = strtoupper($_POST['name']);

			if ($_POST['name'] == "") {
				showError('Please ensure you provide a PC name',true);
				return;
			}
			
			$old['name'] = $info['name'];
			$old['serial'] = $info['serial'];
			$old['location'] = $info['location'];
			$old['active'] = $info['active'];
			$old['maintenanceFee'] = $info['maintenanceFee'];
			$old['comments'] = $info['comments'];

			foreach ($old as $key => $value) {
				$old[$key] = mysql_real_escape_string($value);
			}
			
			$new['name'] = $_POST['name'];
			$new['serial'] = $old['serial'];
			$new['location'] = $_POST['location'];
			$new['active'] = filter_var($_POST['active'], FILTER_VALIDATE_BOOLEAN);
			$new['maintenanceFee'] = $_POST['cost'];
			$new['comments'] = $_POST['comments'];

			foreach ($new as $key => $value) {
				if (!is_bool($value)) {
					$new[$key] = mysql_real_escape_string($value);
				}
			}

			$query = mysql_query("SELECT id FROM ".$SQL['DATABASE'].".computers WHERE name = '".$new['name']."' AND active = 1 AND id <> ".$id."");
			if ($active = mysql_fetch_assoc($query)) {
				showError('Please note, this PC name is already allocated to a machine that is currently in use<br><br>Please click here to view the current entry in the database: <a href="?p=computers&a=find&query='.$_POST['name'].'">'.$_POST['name'].'</a>', true);
				return;		
			}
			if (!is_numeric($new['location'])) {
				showError('Location is invalid', true);
				return;
			}
			$locationQuery = mysql_query("SELECT friendlyName FROM ".$SQL['DATABASE'].".locations WHERE id = '".$new['location']."' LIMIT 0,1");
			$location = mysql_fetch_assoc($locationQuery);
			if (empty($location['friendlyName'])) {
				showError('Please choose a valid location', true);
				return;
			}
			if (!is_bool($new['active'])) {
				showError('Active status is invalid', true);
				return;
			}
			if (!is_numeric($new['maintenanceFee'])) {
				showError('Fee is invalid', true);
				return;
			}

			$changed = false;
			
			if ($old['name'] != $new['name']) {
				$changed = true;
				updateChangeLog($id, "Name", $old['name'], $new['name']);
			}
			
			if ($old['location'] != $new['location']) {
				$changed = true;
				updateChangeLog($id, "Location", $info['friendlyname'], $location['friendlyName']);
			}
			
			if ($old['active'] != $new['active']) {
				$changed = true;
				updateChangeLog($id, "Active Status", ($old['active'] == '1' ? 'Yes' : 'No'), ($new['active'] == '1' ? 'Yes' : 'No'));
			}
			
			if ($old['maintenanceFee'] != $new['maintenanceFee']) {
				$changed = true;
				updateChangeLog($id, "Maintenance Fee", $old['maintenanceFee'], $new['maintenanceFee']);
			}
			if (strtolower($old['comments']) != strtolower($new['comments'])) {
				$changed = true;
				updateChangeLog($id, "Comments", (empty($old['comments'])) ? 'None' : $old['comments'], (empty($new['comments'])) ? 'None' : $new['comments']);
			}
		}
	
		if ($changed == true) {
			$query = mysql_query("UPDATE ".$SQL['DATABASE'].".computers SET
									`serial` = '".$new['serial']."',
									`name` = '".$new['name']."',
									`location` = '".$new['location']."',
									`maintenanceFee` = '".$new['maintenanceFee']."',
									`modifiedby` = '".$_SESSION['displayName']."',
									`modified` = '".time()."',
									`comments` = '".$new['comments']."',
									`active` = '".$new['active']."'
									WHERE id = '".$id."'");
			header('Location: '.(dirname($_SERVER['SCRIPT_NAME']) != '/' ? dirname($_SERVER['SCRIPT_NAME']) : '').'/?p=computers&a=view&r=1&id='.$id);
		} elseif ($changed == false && $_POST['id']) {
			showError('Nothing has changed', true);
			return;
		}
	}

	$locationQuery = mysql_query("SELECT id,friendlyName FROM ".$SQL['DATABASE'].".locations ORDER BY friendlyName ASC");

	if (!empty($errorMsg)) {
		echo '<p><span style="color: red;">'.$errorMsg.'</span></p>';
	}
	?>
	<form method="post" action="?p=computers&a=edit&t=confirm"><input type="hidden" name="id" value="<?php echo $info['id'];?>">
	<label for="name">Computer name: </label><input type="text" name="name" value="<?php echo $display['name'];?>"><br/>
	<label for="serial">Serial number: </label><input type="text" name="serial" value="<?php echo $display['serial'];?>" DISABLED><br/><br/>
	<label for="location">Location: </label><select name="location"><?php
		while ($location = mysql_fetch_assoc($locationQuery)) {
			echo '<option value="'.$location['id'].'"';
			echo ($location['id'] == $display['location']) ? ' SELECTED>' : '>';
			echo $location['friendlyName'].'</option>';
		}
	?></select><br/>
	<label for="active">Active: </label><input type="checkbox" name="active"  value="1"<?php echo ($display['active'] == 1) ? " CHECKED" : "";?>><br/><br/>
	<label for="cost">Maintenance Fee (£): </label><input type="text" name="cost" size="5" value="<?php echo $display['maintenanceFee']; ?>"><br/>
	<label for="comments" style="vertical-align: top;">Comments: </label><textarea name="comments" rows="15" cols="80"><?php echo $display['comments']; ?></textarea><br/>

	
	<p><input type="submit" value="Edit Details"></p>
	</form>
	<?php
}

function updateChangeLog($computerID, $field, $old, $new) {
	global $SQL;
	mysql_query("INSERT INTO ".$SQL['DATABASE'].".computerChangeLog (
		`id`,
		`computerid`,
		`changedby`,
		`field`,
		`old`,
		`new`,
		`date`
		) VALUES (NULL,
		'".$computerID."',
		'".$_SESSION['displayName']."',
		'".$field."',
		'".$old."',
		'".$new."',
		'".time()."')");
}

function doViewComputer() {
	global $SQL, $config;
	if (empty($_GET['id'])) {
		echo '<p>There was an error getting the information to view, please go back and try again.</p>';
		return;
	}
	$_GET['id'] = mysql_real_escape_string($_GET['id']);

	$query = mysql_query("SELECT computers.*, locations.friendlyname FROM ".$SQL['DATABASE'].".computers, ".$SQL['DATABASE'].".locations WHERE computers.id = '".$_GET['id']."' AND computers.location = locations.id LIMIT 0,1");
	if (mysql_num_rows($query) == 0) {
		echo '<p>This ID is not in the Database!</p>';
		return;
	}
	$info = mysql_fetch_assoc($query);
	echo ($info['active'] == 1) ? "" : "<div><b style='color: red;'>This PC is marked as being inactive!</b></div><BR>";
	
	?>
	<div><b>Computer Name:</b> <?php echo $info['name']; ?></div>
	<div>&nbsp;</div>
	<div><b>Serial Number:</b> <?php echo $info['serial']; ?></div>
	<div>&nbsp;</div>
	<div><b>Location:</b> <?php echo $info['friendlyname']; ?></div>
	<div>&nbsp;</div>
	<div><b>Active?:</b> <?php echo ($info['active'] == 1) ? "Yes" : "No"; ?></div>
	<div>&nbsp;</div>
	<div><b>Maintenance Cost:</b> &pound;<?php echo number_format($info['maintenanceFee'],2); ?></div>
	<div>&nbsp;</div>
	<div><b>Comments:</b> <p><pre><?php echo (empty($info['comments'])) ? "No comments" : $info['comments']; ?></pre></p></div>
	
	<form method="post" action="?p=computers&a=edit&id=<?php echo $info['id']; ?>">
	<p><input type="submit" value="Edit this entry"></p>
	</form>
	<div>&nbsp;</div>
	<div><b>Assigned Software Licenses: [ <a href="?p=licenses&a=allocate&id=<?php echo $info['id']; ?>">Assign License</a> ]</b><table id="linkresults">
		<tr id="header">
		<td>Ordered</td><td>Product</td><td>Serial Number</td>
		</tr>
		<tr>
		<?php
			$query = mysql_query("SELECT licenses.id, licenses.license, licenses.orderid, products.friendlyName, orders.date FROM ".$SQL['DATABASE'].".licenses LEFT JOIN ".$SQL['DATABASE'].".orders on licenses.orderid = orders.id, ".$SQL['DATABASE'].".products WHERE computerid = '".$_GET['id']."' AND licenses.product = products.id ORDER BY friendlyName DESC LIMIT 0,9999");
			if (mysql_num_rows($query) == 0) {
				echo '<tr class="noHover"><td colspan=3><b>No licenses assigned to this machine</b></td></tr>';
			}
			$i = "colour1";
			while ($license = mysql_fetch_assoc($query)) {
			$colour = $i;
				if ($config['linkType'] == 'inline') {
					echo '<tr class="'.$colour.'" onclick="javascript:window.location=\'?p=licenses&a=view&id='.$license['id'].'\';">';
				} elseif ($config['linkType'] == 'popout') {
					echo '<tr class="'.$colour.'" onclick="javascript:popout(\'?p=licenses&a=view&id='.$license['id'].'\');">';
				}
				
				echo '<td>'.(($license['date']) ? date("d/m/Y @ H:i:s", $license['date']) : '&nbsp;').'</td><td>'.$license['friendlyName'].'</td><td>'.$license['license'].'</td></tr>';
				$i = ($i == "colour1") ? "colour2" : "colour1";
			}
		?>
	</table>
	</div>
	<div>&nbsp;</div>
	<div>&nbsp;</div>
	<div><b>PC Change Log:</b><table id="results">
		<tr id="header">
		<td>Date</td><td>By</td><td>Field Changed</td><td>Old Value</td><td>New Value</td>
		</tr>
		<?php
			$query = mysql_query("SELECT * FROM ".$SQL['DATABASE'].".computerChangeLog WHERE computerid = '".$_GET['id']."' ORDER by date DESC LIMIT 0,9999");
			if (mysql_num_rows($query) == 0) {
				echo '<tr class="noResults"><td colspan=5><b>No changes made to this entry since it was created</b></td></tr>';
			}
			$i = "colour1";
			while ($chglog = mysql_fetch_assoc($query)) {
			$colour = $i;
				if (strlen($chglog['old']) > 40) {
					$chglog['old'] = substr($chglog['old'], 0, 40).'...';
				}
				if (strlen($chglog['new']) > 40) {
					$chglog['new'] = substr($chglog['new'], 0, 40).'...';
				}
				echo '<tr class="'.$colour.'"><td style="width: 200px;">'.date("d/m/Y @ H:i:s", $chglog['date']).'</td><td  style="width: 200px;">'.$chglog['changedby'].'</td><td>'.$chglog['field'].'</td><td>'.$chglog['old'].'</td><td>'.$chglog['new'].'</td></tr>';
				$i = ($i == "colour1") ? "colour2" : "colour1";
			}
		?>
		
	</table>
	</div>
	<?php
}

?>
