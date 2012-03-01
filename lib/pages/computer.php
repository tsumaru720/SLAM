<?php
require_once(dirname(__FILE__)."/../../lib/functions/mysqlConnect.php");
require_once(dirname(__FILE__)."/../../lib/functions/redirect.php");

function initPage() {
	global $PAGE_CATEGORY, $PAGE_SUB_CATEGORY, $SQL;
	$PAGE_CATEGORY = "Computers";
	if ($_GET['a'] == "new") {
		$PAGE_SUB_CATEGORY = "Add Computer";
	} elseif ($_GET['a'] == "find") {
		$PAGE_SUB_CATEGORY = "Search";
	}
	
	mysqlConnect($SQL['HOST'], $SQL['USERNAME'], $SQL['PASSWORD'], $SQL['PORT']);
}

function doHeader() {
    ?><link rel="stylesheet" type="text/css" href="css/form.css">
	<link rel="stylesheet" type="text/css" href="css/resultsTable.css">
	<script src="script/popout.js"></script>
<?php
}

function getPageTitle() {
    return 'Computer Management';
}

function showPageBody() {
	if ($_GET['a'] == "new") {
		doNewComputer();
	} elseif ($_GET['a'] == "edit") {
		doEditComputer();
	} elseif ($_GET['a'] == "find") {
		doFindComputer();
	} elseif ($_GET['a'] == "view") {
		doViewComputer();
	} else {
		redirect('?p=computer&a=find');
	}
}

function doNewComputer() {
	global $SQL;
	mysqlConnect($SQL['HOST'], $SQL['USERNAME'], $SQL['PASSWORD'], $SQL['PORT']);
	
	if ($_GET['t'] == "confirm") {
		foreach ($_POST as $key => $value) {
			$_POST[$key] = mysql_real_escape_string(strtoupper($value));
		}
		if ($_POST['serial'] == "" || $_POST['name'] == "") {
			echo '<p>Please ensure you provide a serial number and PC name</p>';
			return;
		}
		$query = mysql_query("SELECT id FROM ".$SQL['DATABASE'].".computers WHERE serial = '".$_POST['serial']."' LIMIT 0,9999");
		if ($serial = mysql_fetch_assoc($query)) {
			echo '<p>This serial number is already in the database, Please double check and try again</p>';
			echo '<p>Please click here to view the current entry in the database: <a href="?p=computer&a=find&query='.$_POST['serial'].'">'.$_POST['serial'].'</a></p>';
			return;
		}
		
		$query = mysql_query("SELECT id FROM ".$SQL['DATABASE'].".computers WHERE name = '".$_POST['name']."' AND active = 1 LIMIT 0,9999");
		if ($active = mysql_fetch_assoc($query)) {
			echo '<p>Please note, this PC name is already allocated to a machine that is currently in use</p>';
			echo '<p>Please click here to view the current entry in the database: <a href="?p=computer&a=find&query='.$_POST['name'].'">'.$_POST['name'].'</a></p>';
			return;		
		}
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
								'".$_SESSION['displayname']."', 
								'".$_SESSION['displayname']."', 
								'".time()."', 
								'".time()."', 
								'".$_POST['comments']."', 
								'".$_POST['active']."'
							)");
		echo '<p><b><a href="?p=computer&a=find&query='.$_POST['name'].'">'.$_POST['name'].'</a></b> has successfully been added to the database</p>';
	}
	$locationQuery = mysql_query("SELECT id,friendlyName FROM ".$SQL['DATABASE'].".locations ORDER BY friendlyName asc LIMIT 0,9999");
	$costQuery = mysql_query("SELECT value FROM ".$SQL['DATABASE'].".configuration WHERE name = 'defaultMaintenanceFee' LIMIT 0,1");
	$cost = mysql_fetch_assoc($costQuery);
	?>
	&nbsp;<br>
	<form method="post" action="?p=computer&a=new&t=confirm">
	<div>Computer Name: </div><input type="text" name="name"><br/>
	<div>Serial Number:</div><input type="text" name="serial"><b> - Please ensure this is correct. It cannot be changed</b><br/>
	<div>Location: </div><select name="location"><?php
		while ($location = mysql_fetch_assoc($locationQuery)) {
			echo '<option value="'.$location['id'].'">'.$location['friendlyName'].'</option>';
		}
	?></select><br/>
	<div>Active: </div><input type="checkbox" name="active" value="1" CHECKED><br/><br/>
	<div>Maintenance Fee:</div><input type="text" name="cost" value="<?php echo $cost['value']; ?>"><br/>
	<div>Comments: </div><textarea name="comments" rows="5" cols="25"></textarea><br/>

	
	<p><input type="submit" value="Add"></p>
	</form>
	<?php
}

function doEditComputer() {
	global $SQL;
	
	if (!empty($_POST['id'])) {
		$id = mysql_real_escape_string($_POST['id']);
	} elseif (isset($_GET['id']) && !empty($_GET['id'])) {
		$_GET['id'] = mysql_real_escape_string($_GET['id']);
		$id = mysql_real_escape_string($_GET['id']);
	} else {
		echo 'There was an error getting the information to view, please go back and try again.';
	}
	
	$query = mysql_query("SELECT computers.*, locations.friendlyname FROM ".$SQL['DATABASE'].".computers, ".$SQL['DATABASE'].".locations WHERE computers.id = '".$id."' AND computers.location = locations.id LIMIT 0,1");

	if (mysql_num_rows($query) == 0) {
		echo '<p>This ID is not in the Database!</p>';
		return;
	}
	
	$info = mysql_fetch_assoc($query);

	if ($_GET['t'] == "confirm") {
		foreach ($_POST as $key => $value) {
			$_POST[$key] = mysql_real_escape_string(strtoupper($value));
		}

		if ($_POST['name'] == "") {
			echo '<p>Please ensure you provide a serial number and PC name</p>';
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
		//$new['serial'] = $_POST['serial'];
		$new['serial'] = $old['serial'];
		$new['location'] = $_POST['location'];
		$new['active'] = filter_var($_POST['active'], FILTER_VALIDATE_BOOLEAN);
		$new['maintenanceFee'] = $_POST['cost'];
		$new['comments'] = $_POST['comments'];
		
		$changed = false;
		if ($old['serial'] != $new['serial']) {
			$query = mysql_query("SELECT id FROM ".$SQL['DATABASE'].".computers WHERE serial = '".$_POST['serial']."' LIMIT 0,9999");
			if ($serial = mysql_fetch_assoc($query)) {
				echo '<p>This serial number is already in the database, Please double check and try again</p>';
				echo '<p>Please click here to view the current entry in the database: <a href="?p=computer&a=find&query='.$_POST['serial'].'">'.$_POST['serial'].'</a></p>';
				return;
			} else {
				$changed = true;
				echo '<p>Change Serial</p>';
			}
		}
		
		if ($old['name'] != $new['name']) {
			$query = mysql_query("SELECT id FROM ".$SQL['DATABASE'].".computers WHERE name = '".$_POST['name']."' AND active = 1 LIMIT 0,9999");
			if ($active = mysql_fetch_assoc($query)) {
				echo '<p>Please note, this PC name is already allocated to a machine that is currently in use</p>';
				echo '<p>Please click here to view the current entry in the database: <a href="?p=computer&a=find&query='.$_POST['name'].'">'.$_POST['name'].'</a></p>';
				return;		
			} else {
				$changed = true;
				//updateChangeLog($computerID, $field, $old, $new)
				updateChangeLog($id, "Name", $old['name'], $new['name']);
			}
		}
		
		if ($old['location'] != $new['location']) {
			if (!is_numeric($new['location'])) {
				echo '<p>Location is invalid</p>';
				return;
			} else {
				$locationQuery = mysql_query("SELECT friendlyName FROM ".$SQL['DATABASE'].".locations WHERE id = '".$new['location']."' LIMIT 0,1");
				$location = mysql_fetch_assoc($locationQuery);

				if (empty($location['friendlyName'])) {
					echo '<p>Please choose a valid location</p>';
					return;
				}
				
				$changed = true;
				//updateChangeLog($computerID, $field, $old, $new)
				updateChangeLog($id, "Location", $info['friendlyname'], $location['friendlyName']);
			}
		}
		
		if ($old['active'] != $new['active']) {
			if (!is_bool($new['active'])) {
				echo '<p>Active Status is invalid</p>';
				return;
			} else {
				$changed = true;
				//updateChangeLog($computerID, $field, $old, $new)
				updateChangeLog($id, "Active Status", ($old['active'] == '1' ? 'Yes' : 'No'), ($new['active'] == '1' ? 'Yes' : 'No'));
			}
		}
		
		if ($old['maintenanceFee'] != $new['maintenanceFee']) {
			if (!is_numeric($new['maintenanceFee'])) {
				echo '<p>Fee is invalid</p>';
				return;
			} else {
				$changed = true;
				//updateChangeLog($computerID, $field, $old, $new)
				updateChangeLog($id, "Maintenance Fee", $old['maintenanceFee'], $new['maintenanceFee']);
			}
		}
		if (strtoupper($old['comments']) != strtoupper($new['comments'])) {
			$changed = true;
			//updateChangeLog($computerID, $field, $old, $new)
			updateChangeLog($id, "Comments", (empty($old['comments'])) ? 'None' : $old['comments'], (empty($new['comments'])) ? 'None' : $new['comments']);
		}
	}
	
	if ($changed == true) {
		$query = mysql_query("UPDATE ".$SQL['DATABASE'].".computers SET
								`serial` = '".$new['serial']."',
								`name` = '".$new['name']."',
								`location` = '".$new['location']."',
								`maintenanceFee` = '".$new['maintenanceFee']."',
								`modifiedby` = '".$_SESSION['displayname']."',
								`modified` = '".time()."',
								`comments` = '".$new['comments']."',
								`active` = '".$new['active']."'
								WHERE id = '".$id."'");
		redirect('?p=computer&a=view&r=1&id='.$id);
	} elseif ($changed == false && $_POST['id']) {
		echo '<p><b>Nothing has changed!</b></p>';
	}
	
	$locationQuery = mysql_query("SELECT id,friendlyName FROM ".$SQL['DATABASE'].".locations ORDER BY friendlyName ASC LIMIT 0,9999");
	?>
	&nbsp;<br>
	<form method="post" action="?p=computer&a=edit&t=confirm"><input type="hidden" name="id" value="<?php echo $info['id'];?>">
	<div>Computer Name: </div><input type="text" name="name" value="<?php echo $info['name'];?>"><br/>
	<div>Serial Number:</div><input type="text" name="serial" value="<?php echo $info['serial'];?>" size=30 DISABLED><br/><br/>
	<div>Location: </div><select name="location"><?php
		while ($location = mysql_fetch_assoc($locationQuery)) {
			echo '<option value="'.$location['id'].'"';
			echo ($location['id'] == $info['location']) ? ' SELECTED>' : '>';
			echo $location['friendlyName'].'</option>';
		}
	?></select><br/>
	<div>Active: </div><input type="checkbox" name="active"  value="1"<?php echo ($info['active'] == 1) ? " CHECKED" : "";?>><br/><br/>
	<div>Maintenance Fee:</div><input type="text" name="cost" value="<?php echo $info['maintenanceFee']; ?>"><br/>
	<div>Comments: </div><textarea name="comments" rows="5" cols="25"><?php echo $info['comments']; ?></textarea><br/>

	
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
		'".$_SESSION['displayname']."',
		'".$field."',
		'".$old."',
		'".$new."',
		'".time()."')");
}

function doViewComputer() {
	global $SQL;
	if (empty($_GET['id'])) {
		echo 'There was an error getting the information to view, please go back and try again.';
	} else {
		$_GET['id'] = mysql_real_escape_string($_GET['id']);
	}
	if ($_GET['r'] == "1") {
		//echo '<script>window.opener.location.href = window.opener.location.href</script>';
		//echo '<script>window.opener.document.forms[0].submit()</script>';
	}	
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
	
	<form method="post" action="?p=computer&a=edit&id=<?php echo $info['id']; ?>">
	<p><input type="submit" value="Edit this entry"></p>
	</form>
	<div>&nbsp;</div>
	<div><b>Assigned Software Licenses: [ <a href="?p=license&a=allocate&id=<?php echo $info['id']; ?>">Assign License</a> ]</b><table id="linkresults">
		<tr id="headers">
		<td>Ordered</td><td>Product</td><td>Serial Number</td>
		</tr>
		<tr>
		<?php
			$query = mysql_query("SELECT licenses.id, licenses.license, licenses.orderid, products.friendlyName, orders.date FROM ".$SQL['DATABASE'].".licenses LEFT JOIN ".$SQL['DATABASE'].".orders on licenses.orderid = orders.id, ".$SQL['DATABASE'].".products WHERE computerid = '".$_GET['id']."' AND licenses.product = products.id ORDER BY friendlyName DESC LIMIT 0,9999");
			if (mysql_num_rows($query) == 0) {
				echo '<tr><td colspan=3><b>No licenses assigned to this machine</b></td></tr>';
			}
			$i = "colour1";
			while ($license = mysql_fetch_assoc($query)) {
			$colour = $i;
				echo '<tr class="'.$colour.'" onclick="javascript:popout(\'?p=license&a=view&id='.$license['id'].'\');">';
				echo '<td>'.(($license['date']) ? date("d/m/Y @ H:i:s", $license['date']) : '&nbsp;').'</td><td>'.$license['friendlyName'].'</td><td>'.$license['license'].'</td></tr>';
				$i = ($i == "colour1") ? "colour2" : "colour1";
			}
		?>
	</table>
	</div>
	<div>&nbsp;</div>
	<div>&nbsp;</div>
	<div><b>PC Change Log:</b><table id="results">
		<tr id="headers">
		<td>Date</td><td>By</td><td>Field Changed</td><td>Old Value</td><td>New Value</td>
		</tr>
		<?php
			$query = mysql_query("SELECT * FROM ".$SQL['DATABASE'].".computerChangeLog WHERE computerid = '".$_GET['id']."' ORDER by date DESC LIMIT 0,9999");
			if (mysql_num_rows($query) == 0) {
				echo '<tr><td colspan=5><b>No changes made to this entry since it was created</b></td></tr>';
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

function doFindComputer() {
	global $SQL;

	$state = 'active';
	if (!empty($_POST['query'])) {
		$queryString = mysql_real_escape_string($_POST['query']);
		$queryLocation = mysql_real_escape_string($_POST['location']);
		$state = mysql_real_escape_string($_POST['state']);
	} elseif (isset($_GET['query'])) {
		$queryString = mysql_real_escape_string($_GET['query']);
		$queryLocation = '%';
		$state = mysql_real_escape_string($_GET['s']);
	} elseif (empty($_POST['query']) && isset($_POST['location'])) {
		$queryString = mysql_real_escape_string("%");
		$queryLocation = mysql_real_escape_string($_POST['location']);
		$state = mysql_real_escape_string($_POST['state']);
	}
	
	if ($state == 'active') {
		$active = 1;
	} elseif ($state == 'inactive') {
		$active = 0;
	} else {
		$active = '%';
	}
	
	if (empty($queryLocation)) { $queryLocation == '%'; }
	//$locationQuery = mysql_query("SELECT id,friendlyName FROM ".$SQL['DATABASE'].".locations ORDER BY friendlyName ASC LIMIT 0,9999");
	$locationQuery = mysql_query("SELECT locations.id, friendlyName, COUNT(name) as count FROM ".$SQL['DATABASE'].".locations LEFT JOIN ".$SQL['DATABASE'].".computers ON locations.id = computers.location AND computers.active = 1 WHERE hidden = '0' GROUP BY locations.friendlyName ORDER BY friendlyName ASC");
	?>
	&nbsp;Use % for wildcard searching<br><br>
	<form name="search" method="post" action="?p=computer&a=find">
	<div>Search for: </div><input type="text" name="query" value="<?php echo $queryString; ?>"><br/>
	<div>Location: </div><select name="location"><option value="%">All locations</option><?php
		while ($location = mysql_fetch_assoc($locationQuery)) {
			echo '<option value="'.$location['id'].'"';
			if (isset($queryLocation) && $queryLocation == $location['id']) {
				echo ' SELECTED';
			}
			echo '>'.$location['friendlyName'].' ('.$location['count'].')</option>';
			echo "\n";
		}
	?></select><br/>
	<div>&nbsp;</div><input type="radio" id="all" name="state" value="all"<?php echo ($state == 'all') ? ' CHECKED' : ''; ?>><label for="all">All</label>
	<input type="radio" name="state" id="active" value="active"<?php echo ($state == 'active') ? ' CHECKED' : ''; ?>><label for="active">Active Only</label>
	<input type="radio" name="state" id="inactive" value="inactive"<?php echo ($state == 'inactive') ? ' CHECKED' : ''; ?>><label for="inactive">Inactive Only</label>
	<p><input type="submit" value="Search" name="generic"> or <input type="submit" value="Search Change Log" name="changelog"></p>
	</form>
	<?php
	if ($_POST['changelog'] || $_GET['type'] == "changelog") {
		doChangeLogSearch($queryString, $queryLocation);
	} else {
		doGenericSearch($queryString, $queryLocation, $active);
	}
}

function doChangeLogSearch($queryString, $queryLocation) {
	global $SQL;
	if (isset($queryString)) {
		$query = mysql_query("SELECT computers.id, changelog.date, changelog.changedby, changelog.field, changelog.old, changelog.new, computers.name, computers.serial FROM ".$SQL['DATABASE'].".computerChangeLog as changelog, ".$SQL['DATABASE'].".computers WHERE (changelog.changedby LIKE '".$queryString."' OR changelog.old LIKE '".$queryString."' OR changelog.new LIKE '".$queryString."') AND computers.id = changelog.computerid ORDER BY date DESC LIMIT 0,9999");
		echo '<p>Found '.mysql_num_rows($query).' result(s) in <b>Change Log</b></p>';
		echo '<table id="linkresults">';
		echo '<tr id="headers">';
		echo '<td>PC Name</td><td>Serial Number</td><td>Changed</td><td>Field</td><td>Old Value</td><td>New Value</td><td>Changed By</td>';
		echo '</tr>';
		
		$i = "colour1";
		while ($info = mysql_fetch_assoc($query)) {
			if (strlen($info['old']) > 40) {
				$info['old'] = substr($info['old'], 0, 40).'...';
			}
			if (strlen($info['new']) > 40) {
				$info['new'] = substr($info['new'], 0, 40).'...';
			}
			$colour = $i;
			//echo '<tr class="'.$colour.'" onclick="javascript:window.location=\'?p=computer&a=view&id='.$info['id'].'\';">';
			echo '<tr class="'.$colour.'" onclick="javascript:popout(\'?p=computer&a=view&id='.$info['id'].'\');">';
			echo '<td>'.$info['name'].'</td><td>'.$info['serial'].'</td><td>'.date("d/m/Y @ H:i:s", $info['date']).'</td><td>'.$info['field'].'</td><td>'.$info['old'].'</td><td>'.$info['new'].'</td><td>'.$info['changedby'].'</td>';
			echo '</tr>';
			
			$i = ($i == "colour1") ? "colour2" : "colour1";
		}
		echo '</table>';
	}
}

function doGenericSearch($queryString, $queryLocation, $active) {
	global $SQL;
	
	if (isset($queryString)) {
		$query = mysql_query("SELECT computers.id, computers.name, computers.serial, locations.friendlyname, computers.created, computers.createdby, computers.modified, computers.modifiedby, computers.active FROM ".$SQL['DATABASE'].".computers, ".$SQL['DATABASE'].".locations WHERE (serial LIKE '".$queryString."' OR name LIKE '".$queryString."' OR createdby LIKE '".$queryString."' OR modifiedby LIKE '".$queryString."' OR locations.friendlyName LIKE '".$queryString."' OR comments LIKE '".$queryString."') AND computers.location = locations.id AND computers.location LIKE '".$queryLocation."' AND computers.active LIKE '".$active."' ORDER BY name ASC LIMIT 0,9999");
		echo '<p>Found '.mysql_num_rows($query).' result(s) in <b>Generic</b></p>';
		echo '<table id="linkresults">';
		echo '<tr id="headers">';
		echo '<td>PC Name</td><td>Serial Number</td><td>Location</td><td>Created on</td><td>Created by</td><td>Modified on</td><td>Modified by</td>';
		echo '</tr>';
		
		$i = "colour1";
		while ($info = mysql_fetch_assoc($query)) {
			$colour = $i;
			if ($info['active'] == 0) { $colour = "colour3"; }
			//echo '<tr class="'.$colour.'" onclick="javascript:window.location=\'?p=computer&a=view&id='.$info['id'].'\';">';
			echo '<tr class="'.$colour.'" onclick="javascript:popout(\'?p=computer&a=view&id='.$info['id'].'\');">';
			//echo '<tr class="'.$colour.'">';
			//<a style="display: block; width: 100%; height: 100%;" href="?p=computer&a=view&id='.$info['id'].'">
			echo '<td>'.$info['name'].'</td><td>'.$info['serial'].'</td><td>'.$info['friendlyname'].'</td><td>'.date("d/m/Y @ H:i:s", $info['created']).'</td><td>'.$info['createdby'].'</td><td>'.date("d/m/Y @ H:i:s", $info['modified']).'</td><td>'.$info['modifiedby'].'</td>';
			echo '</tr>';
			
			$i = ($i == "colour1") ? "colour2" : "colour1";
		}
		echo '</table>';
	}
}
?>

