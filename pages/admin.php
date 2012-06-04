<?php

function getPageTitle() {
	return 'Administration';
}

function doHeader() {
	global $activePage, $activeMenu;

	echo '<link rel="stylesheet" type="text/css" href="css/form.css">';
	echo '<link rel="stylesheet" type="text/css" href="css/results.css">';

	$activePage = 'admin';

	if ($_GET['a'] == "users") {
		$activeMenu = 'users';
	} elseif ($_GET['a'] == "config") {
		$activeMenu = 'config';
	} elseif ($_GET['a'] == "locations") {
		$activeMenu = 'locations';
	} elseif ($_GET['a'] == "products") {
		$activeMenu = 'products';
	} elseif ($_GET['a'] == "authorders") {
		$activeMenu = 'authorders';
	} elseif ($_GET['a'] == "status") {
		$activeMenu = 'status';
	} elseif ($_GET['a'] == "suppliers") {
		$activeMenu = 'suppliers';
	} else {
		//Default action
		$activeMenu = 'users';
	}

	return;
}

function getMenu() {
return array(
	'User Management' => array(
		'url' => '?p=admin&amp;a=users',
		'alias' => 'users',
	),
	'Configuration' => array(
		'url' => '?p=admin&amp;a=config',
		'alias' => 'config',
	),
	'Manage Locations' => array(
		'url' => '?p=admin&amp;a=locations',
		'alias' => 'locations',
	),
	'Manage Products' => array(
		'url' => '?p=admin&amp;a=products',
		'alias' => 'products',
	),
	'Can Authorize Orders' => array(
		'url' => '?p=admin&amp;a=authorders',
		'alias' => 'authorders',
	),
	'Order Statuses' => array(
		'url' => '?p=admin&amp;a=status',
		'alias' => 'status',
	),
	'Order Suppliers' => array(
		'url' => '?p=admin&amp;a=suppliers',
		'alias' => 'suppliers',
	),
);
}

function getBody() {
	if ($_GET['a'] == "users") {
		doUsers();
	} elseif ($_GET['a'] == "config") {
		doConfig();
	} elseif ($_GET['a'] == "locations") {
		doLocations();
	} elseif ($_GET['a'] == "products") {
		doProducts();
	} elseif ($_GET['a'] == "authorders") {
		doAuthOrders();
	} elseif ($_GET['a'] == "status") {
		doStatus();
	} elseif ($_GET['a'] == "suppliers") {
		doSuppliers();
	} else {
		//Default action
		doUsers();
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

/*
	if (!empty($errorMsg)) {
		echo '<p><span style="color: red;">'.$errorMsg.'</span></p>';
	}
*/

function doUsers() {
	global $SQL, $errorMsg;
	
	if ($_GET['t'] == 'new') {
		if ($_GET['c'] == 'confirm' && !$errorMsg) {
			$_POST['username'] = strtolower($_POST['username']);

			$query = mysql_query("SELECT id, displayName FROM ".$SQL['DATABASE'].".userAccounts WHERE username = '".mysql_real_escape_string($_POST['username'])."'");
			if ($user = mysql_fetch_assoc($query)) {
				showError('That username is already in use by <b>'.$user['displayName'].'</b>.<BR><BR>Please click here to view: <a href="?p=admin&a=users&t=edit='.$user['id'].'">'.$_POST['username'].'</a>',true);
				return;
			}

			if (strlen($_POST['password']) < 5) {
				showError('Password is too short', true);
				return;
			}
			if ($_POST['password'] != $_POST['cpassword']) {
				showError('Passwords do not match', true);
				return;
			}
			if ((!is_numeric($_POST['admin']) || ($_POST['admin'] && ($_POST['admin'] > 1) || ($_POST['admin'] < 0)))) {
				if ($_POST['admin'] != null) {
					showError('Value of Administrator checkbox is invalid', true);
					return;
				}
			}
			if ((!is_numeric($_POST['enabled']) || ($_POST['enabled'] && ($_POST['enabled'] > 1) || ($_POST['enabled'] < 0)))) {
				if ($_POST['enabled'] != null) {
					showError('Value of Enabled checkbox is invalid', true);
					return;
				}
			}
			if ((!is_numeric($_POST['fpassword']) || ($_POST['fpassword'] && ($_POST['fpassword'] > 1) || ($_POST['fpassword'] < 0)))) {
				if ($_POST['fpassword'] != null) {
					showError('Value of Force Password Change checkbox is invalid', true);
					return;
				}
			}
			if (empty($_POST['dname'])) {
				showError('Display Name cannot be empty', true);
				return;
			}

			$passwordHash = md5(strlen($_POST['username'].$_POST['password']).$_POST['username'].$_POST['password']);
			mysql_query("INSERT INTO  ".$SQL['DATABASE'].".userAccounts (
				`id` ,
				`username` ,
				`passwordHash` ,
				`firstName` ,
				`lastName` ,
				`displayName` ,
				`emailAddress` ,
				`created` ,
				`lastSeen` ,
				`enabled` ,
				`isAdmin` ,
				`forcePasswordChange`
				)
				VALUES (NULL ,  
				'".mysql_real_escape_string($_POST['username'])."',
				'".$passwordHash."',
				'".mysql_real_escape_string($_POST['fname'])."',
				'".mysql_real_escape_string($_POST['lname'])."',
				'".mysql_real_escape_string($_POST['dname'])."',
				'".mysql_real_escape_string($_POST['email'])."',
				'".time()."',
				'0',
				'".mysql_real_escape_string($_POST['enabled'])."',
				'".mysql_real_escape_string($_POST['admin'])."',
				'".mysql_real_escape_string($_POST['fpassword'])."')");

			//Clear $_POST variables so that they are not reused after a successful addition
			echo '<p>User account created: <a href="?p=admin&a=users&t=edit&id='.mysql_insert_id().'">'.$_POST['username'].'</a></p>';
			$_POST = array();
		}

		if (!empty($errorMsg)) {
			echo '<p><span style="color: red;">'.$errorMsg.'</span></p>';
		}	
		?>
		<form method="post" action="?p=admin&a=users&t=new&c=confirm">
		<label for="username">Username: </label><input type="text" name="username" value="<?php echo $_POST['username']; ?>"><br/>
		<label for="password">Password: </label><input type="password" name="password" value="<?php echo (array_key_exists('password',$_POST) ? $_POST['password'] : '12345');?>"><span class="infoText"><-- Case sensitive - Default is "12345"</span><br/>
		<label for="cpassword">Confirm Password: </label><input type="password" name="cpassword" value="<?php echo (array_key_exists('cpassword',$_POST) ? $_POST['cpassword'] : '12345');?>"><span class="infoText"><-- Case sensitive - Default is "12345"</span><br/><br/>
		
		<label for="fname">First Name: </label><input type="text" name="fname" value="<?php echo $_POST['fname']; ?>"><br/>
		<label for="lname">Last Name: </label><input type="text" name="lname" value="<?php echo $_POST['lname']; ?>"><br/>
		<label for="dname">Display Name: </label><input type="text" name="dname" value="<?php echo $_POST['dname']; ?>"><span class="infoText"><-- This will be displayed on all activity</span><br/>
		<label for="email">Email Address: </label><input type="text" name="email" value="<?php echo $_POST['email']; ?>"><br/><br/>
		<label for="admin">Administrator: </label><input type="checkbox" name="admin" value="1"<?php echo (($_POST['admin'] == 1) ? ' CHECKED' : ''); ?>><span class="infoText"><-- Give this user Admin access</span><br/>
		<label for="enabled">Enabled: </label><input type="checkbox" name="enabled" value="1"<?php echo (($_POST['enabled'] == 1 || !array_key_exists('enabled', $_POST)) ? ' CHECKED' : ''); ?>><span class="infoText"><-- Unticked means locked (Can't log in)</span><br/><br/>
		
		<label for="fpassword">Force Password Change: </label><input type="checkbox" name="fpassword" value="1"<?php echo (($_POST['fpassword'] == 1 || !array_key_exists('fpassword', $_POST)) ? ' CHECKED' : ''); ?>><span class="infoText"><-- Forces the user to change password on next log in</span><br/><br/>

		<input class="button" type="submit" name="action" value="Create Account"></form>
		<?php
	} elseif ($_GET['t'] == 'edit') {
		if (!empty($_POST['id'])) {
			$id = $_POST['id'];
		} elseif (isset($_GET['id']) && !empty($_GET['id'])) {
			$id = $_GET['id'];
		} else {
			echo '<p>There was an error getting the information to view, please go back and try again.</p>';
			return;
		}

		$query = mysql_query("SELECT * FROM ".$SQL['DATABASE'].".userAccounts WHERE id = '".mysql_real_escape_string($id)."'");
		if (mysql_num_rows($query) == 0) {
			echo '<p>This ID is not in the Database!</p>';
			return;
		}

		if ($_POST['action'] == "Edit details" || !$_POST) {
			$info = mysql_fetch_assoc($query);

			if ($_POST) {
				$display['username'] = $info['username'];
				$display['fname'] = $_POST['fname'];
				$display['lname'] = $_POST['lname'];
				$display['dname'] = $_POST['dname'];
				$display['email'] = $_POST['email'];
				$display['admin'] = $_POST['admin'];
				$display['enabled'] = $_POST['enabled'];
				$display['fpassword'] = $_POST['fpassword'];
			}

			if ($_GET['c'] == 'confirm' && !$errorMsg) {
				$_POST['username'] = strtolower($_POST['username']);

				if ((!is_numeric($_POST['admin']) || ($_POST['admin'] && ($_POST['admin'] > 1) || ($_POST['admin'] < 0)))) {
					if ($_POST['admin'] != null) {
						showError('Value of Administrator checkbox is invalid', true);
						return;
					}
				}
				if ((!is_numeric($_POST['enabled']) || ($_POST['enabled'] && ($_POST['enabled'] > 1) || ($_POST['enabled'] < 0)))) {
					if ($_POST['enabled'] != null) {
						showError('Value of Enabled checkbox is invalid', true);
						return;
					}
				}
				if ((!is_numeric($_POST['fpassword']) || ($_POST['fpassword'] && ($_POST['fpassword'] > 1) || ($_POST['fpassword'] < 0)))) {
					if ($_POST['fpassword'] != null) {
						showError('Value of Force Password Change checkbox is invalid', true);
						return;
					}
				}
				if (empty($_POST['dname'])) {
					showError('Display Name cannot be empty', true);
					return;
				}


				mysql_query("UPDATE ".$SQL['DATABASE'].".userAccounts SET
					`firstName` =  '".mysql_real_escape_string($_POST['fname'])."',
					`lastName` =  '".mysql_real_escape_string($_POST['lname'])."',
					`displayName` =  '".mysql_real_escape_string($_POST['dname'])."',
					`emailAddress` =  '".mysql_real_escape_string($_POST['email'])."',
					`enabled` =  '".mysql_real_escape_string($_POST['enabled'])."',
					`isAdmin` =  '".mysql_real_escape_string($_POST['admin'])."',
					`forcePasswordChange` =  '".mysql_real_escape_string($_POST['fpassword'])."'
					WHERE  id = ".mysql_real_escape_string($id));


				echo '<p>Details Saved</p>';
				header('Location: '.(dirname($_SERVER['SCRIPT_NAME']) != '/' ? dirname($_SERVER['SCRIPT_NAME']) : '').'/?p=admin&a=users');
				return;
			} else {
				if (!$display) {
					$display['username'] = $info['username'];
					$display['fname'] = $info['firstName'];
					$display['lname'] = $info['lastName'];
					$display['dname'] = $info['displayName'];
					$display['email'] = $info['emailAddress'];
					$display['admin'] = $info['isAdmin'];
					$display['enabled'] = $info['enabled'];
					$display['fpassword'] = $info['forcePasswordChange'];
				}
			}
		} else {
			mysql_query("DELETE FROM ".$SQL['DATABASE'].".userAccounts WHERE id = ".mysql_real_escape_string($id));
			mysql_query("DELETE FROM ".$SQL['DATABASE'].".userPreferences WHERE userID = ".mysql_real_escape_string($id));
			header('Location: '.(dirname($_SERVER['SCRIPT_NAME']) != '/' ? dirname($_SERVER['SCRIPT_NAME']) : '').'/?p=admin&a=users');
			return;
		}

		if (!empty($errorMsg)) {
			echo '<p><span style="color: red;">'.$errorMsg.'</span></p>';
		}

		?>
		<form method="post" action="?p=admin&a=users&t=edit&c=confirm">
		<input type="hidden" name="id" value="<?php echo $id;?>">
		<label for="username">Currently Editing: </label><input type="text" name="username" value="<?php echo $display['username']; ?>" DISABLED><br/><BR>
		
		<label for="fname">First Name: </label><input type="text" name="fname" value="<?php echo $display['fname']; ?>"><br/>
		<label for="lname">Last Name: </label><input type="text" name="lname" value="<?php echo $display['lname']; ?>"><br/>
		<label for="dname">Display Name: </label><input type="text" name="dname" value="<?php echo $display['dname']; ?>"><span class="infoText"><-- This will be displayed on all activity</span><br/>
		<label for="email">Email Address: </label><input type="text" name="email" value="<?php echo $display['email']; ?>"><br/><br/>
		<label for="admin">Administrator: </label><input type="checkbox" name="admin" value="1"<?php echo (($display['admin'] == 1) ? ' CHECKED' : ''); ?>><span class="infoText"><-- Give this user Admin access</span><br/>
		<label for="enabled">Enabled: </label><input type="checkbox" name="enabled" value="1"<?php echo (($display['enabled'] == 1) ? ' CHECKED' : ''); ?>><span class="infoText"><-- Unticked means locked (Can't log in)</span><br/><br/>
		
		<label for="fpassword">Force Password Change: </label><input type="checkbox" name="fpassword" value="1"<?php echo (($display['fpassword'] == 1) ? ' CHECKED' : ''); ?>><span class="infoText"><-- Forces the user to change password on next log in</span><br/><br/>

		<input class="button" type="submit" name="action" value="Edit details"> &nbsp; <input type="submit" name="action" value="Delete account"></form>
		<?php
	} else {
	
		require_once('share/duration.php');
	
		echo '<a class="link" href="?p=admin&a=users&t=new"><img src="images/add.png"><br>New</a>';
		$query = mysql_query("SELECT * FROM ".$SQL['DATABASE'].".userAccounts ORDER BY enabled DESC, firstName ASC");
		echo '<table id="results" style="width: 900px;">';
		echo '<tr id="header">
				<td>Username</td>
				<td>Display Name</td>
				<td>First Name</td>
				<td>Last Name</td>
				<td>Email</td>
				<td>Admin</td>
				<td>Last Activity</td>
				</tr>';
		
		$i = "colour1";
		while ($info = mysql_fetch_assoc($query)) {
			$colour = $i;
			echo '<tr class="'.$colour.'">';
			echo '<td><a style="text-align: left; display: block;" href="?p=admin&a=users&t=edit&id='.$info['id'].'"><img style="vertical-align: middle" src="images/user-'.($info['enabled'] ? "enabled" : "disabled").'.png" title="Account '.($info['enabled'] ? "Enabled" : "Locked").'">'.$info['username'].'</a></td>';
			echo '<td>'.$info['displayName'].'</td>';
			echo '<td>'.$info['firstName'].'</td>';
			echo '<td>'.$info['lastName'].'</td>';
			echo '<td>'.$info['emailAddress'].'</td>';
			echo '<td><img style="vertical-align: middle" src="images/'.($info['isAdmin'] ? "tick" : "cross").'.png"></td>';
			if ($info['lastSeen'] == 0) {
				echo '<td>Never</td>';
			} else {
				echo '<td>'.((time() - $info['lastSeen']) == 0 ? 'Just now' : duration(time() - $info['lastSeen']).' ago').'</td>';
			}
			echo '</tr>';
			$i = ($i == "colour1") ? "colour2" : "colour1";
		}
		echo '</table>';
	}
}

function doConfig() {
	global $SQL;
	
	if ($_GET['t'] == 'confirm') {
		foreach ($_POST as $key => $value) {
			mysql_query("UPDATE ".$SQL['DATABASE'].".configuration SET value = '".mysql_real_escape_string($value)."' WHERE name = '".mysql_real_escape_string($key)."'");
		}
		echo '<p>Done</p>';
	}
	$query = mysql_query("SELECT * FROM ".$SQL['DATABASE'].".configuration");
	?>
	<form method="post" action="?p=admin&a=config&t=confirm">
	<?php
		while ($info = mysql_fetch_assoc($query)) {
			echo '<label style="width: 250px;" for="'.$info['name'].'">'.$info['friendlyName'].': </label>';
			if (!empty($info['values'])) {
				echo '<select name="'.$info['name'].'">';
				foreach (explode(',',$info['values']) as $value) {
					echo '<option value="'.$value.'"'.($info['value'] == $value ? ' SELECTED' : '').'>'.$value.'</option>';
				}
				echo '</select>';
			} else {
				echo '<input type="text" name="'.$info['name'].'" value="'.$info['value'].'">';
			}
			echo '<br/>';
		}
	?>
	<input class="button" type="submit" value="Save">
	<?php
}

function doLocations() {
	global $SQL, $errorMsg;
	$defaultTable = 'locations';
	
	if ($_GET['t'] == 'view') {
		$_GET['id'] = mysql_real_escape_string($_GET['id']);
		$query = mysql_query("SELECT id, friendlyName, corporate FROM ".$SQL['DATABASE'].".".$defaultTable." WHERE id = '".$_GET['id']."'");
		$info = mysql_fetch_assoc($query);
		?>
		<form method="post" action="?p=admin&a=locations&t=confirm">
		<input type="hidden" name="id" value="<?php echo $info['id']; ?>">
		<label for="name">Location Name: </label><input type="text" name="name" value="<?php echo $info['friendlyName']; ?>"><br/>
		<label for="corporate">Corporate Location?: </label><input type="checkbox" name="corporate" value="1"<?php echo ($info['corporate'] == '1') ? ' CHECKED' : ''?>><br/>
		<input class="button" type="submit" name="action" value="Edit"> &nbsp; 
		<?php
		
		$query = mysql_query("SELECT name, serial FROM ".$SQL['DATABASE'].".computers WHERE location = '".$info['id']."' AND active = '1'");
		
		if (mysql_num_rows($query) > 0) {
			echo '- Cannot delete as this Loation still has active items allocated to it (See Below)</form></p>';
			echo '<table id="results">';
			echo '<tr id="header">
					<td>Name</td><td>Serial</td>
					</tr>';
			$i = "colour1";
			while ($locinfo = mysql_fetch_assoc($query)) {
				$colour = $i;
				echo '<tr class="'.$colour.'">';
				echo '<td>'.$locinfo['name'].'</td><td>'.$locinfo['serial'].'</td>';
				echo '</tr>';
				$i = ($i == "colour1") ? "colour2" : "colour1";
			}
			echo '</table>';
			echo '</form>';
			echo '<form method="post" action="?p=admin&a=locations&t=cost">';
			echo '<input type="hidden" name="id" value="'.$info['id'].'">';
			echo '<div>Change all PCs maintenance cost to: </div><input type="text" name="cost">';
			echo '<input type="submit" value="Go">';
		} else {
			echo '<input type="submit" name="action" value="Delete"></form></p>';
		}
	} elseif ($_GET['t'] == 'cost') {
		$_POST['id'] = mysql_real_escape_string($_POST['id']);
		
		$query = mysql_query("SELECT friendlyName FROM ".$SQL['DATABASE'].".locations WHERE id = '".$_POST['id']."'");
		$info = mysql_fetch_assoc($query);
		if (!is_numeric($_POST['id'])) {
			echo '<p>Please insert a numeric price.</p>';
			return;
		}
		
		if (empty($info['friendlyName'])) {
			echo '<p>ID Not in Database!</p>';
			return;
		} else {
			$query = mysql_query("SELECT id, maintenanceFee FROM ".$SQL['DATABASE'].".computers WHERE location = '".$_POST['id']."'");
			while ($info = mysql_fetch_assoc($query)) {
				if ($info['maintenanceFee'] != $_POST['cost']) {
					updateComputerChangeLog($info['id'], "Maintenance Fee", $info['maintenanceFee'], mysql_real_escape_string($_POST['cost']));
				}
			}
			$query = mysql_query("UPDATE ".$SQL['DATABASE'].".computers SET maintenanceFee = '".mysql_real_escape_string($_POST['cost'])."', modifiedby = '".$_SESSION['displayName']."', modified = '".time()."' WHERE location = '".$_POST['id']."'");
			header('Location: '.(dirname($_SERVER['SCRIPT_NAME']) != '/' ? dirname($_SERVER['SCRIPT_NAME']) : '').'/?p=admin&a=locations');
		}
	} elseif ($_GET['t'] == 'confirm') {
		$query = mysql_query("SELECT friendlyName FROM ".$SQL['DATABASE'].".".$defaultTable." WHERE id = '".mysql_real_escape_string($_POST['id'])."'");
		$info = mysql_fetch_assoc($query);
		if (empty($info['friendlyName'])) {
			echo '<p>ID Not in Database!</p>';
			return;
		} else {
			if ($_POST['action'] == "Edit") {
				if (empty($_POST['name'])) {
					echo '<p>Please ensure you provide a location name</p>';
					return;
				}
				$query = mysql_query("UPDATE ".$SQL['DATABASE'].".".$defaultTable." SET
										`friendlyName` = '".mysql_real_escape_string($_POST['name'])."',
										`corporate` = '".(($_POST['corporate']) ? '1' : '0')."'
										WHERE ".$defaultTable.".id = '".mysql_real_escape_string($_POST['id'])."'");
				header('Location: '.(dirname($_SERVER['SCRIPT_NAME']) != '/' ? dirname($_SERVER['SCRIPT_NAME']) : '').'/?p=admin&a=locations');
			} else {
				if (empty($_POST['id'])) {
					echo '<p>Please ensure you provide a location id</p>';
					return;
				}
				
				$query = mysql_query("SELECT name, serial FROM ".$SQL['DATABASE'].".computers WHERE location = '".mysql_real_escape_string($_POST['id'])."' AND active = '1'");
	
				if (mysql_num_rows($query) > 0) {
					echo '<p>Cannot delete as this location still has actve assets assigned to it.</p>';
					return;
				}
				$query = mysql_query("SELECT name, serial FROM ".$SQL['DATABASE'].".computers WHERE location = '".mysql_real_escape_string($_POST['id'])."'");
				if (mysql_num_rows($query) == 0) {
					$query = mysql_query("DELETE FROM ".$SQL['DATABASE'].".locations WHERE locations.id = '".mysql_real_escape_string($_POST['id'])."'");
				} else {
					$query = mysql_query("UPDATE ".$SQL['DATABASE'].".locations SET `hidden` = '1' WHERE locations.id = '".mysql_real_escape_string($_POST['id'])."'");
				}
				header('Location: '.(dirname($_SERVER['SCRIPT_NAME']) != '/' ? dirname($_SERVER['SCRIPT_NAME']) : '').'/?p=admin&a=locations');
			}
		}
	} elseif ($_GET['t'] == 'new') {
		if (!$_POST) {
			?>
			<form method="post" action="?p=admin&a=locations&t=new">
			<input type="hidden" name="id" value="<?php echo $info['id']; ?>">
			<label for="name">Location Name: </label><input type="text" name="name"><br/>
			<label for="corporate">Corporate Location?: </label><input type="checkbox" name="corporate" value="1" CHECKED><br/>
			<input class="button" type="submit" name="action" value="Add">
			<?php
		} else {
			$query = mysql_query("SELECT id FROM ".$SQL['DATABASE'].".".$defaultTable." WHERE friendlyName = '".mysql_real_escape_string($_POST['name'])."'");
			if (mysql_num_rows($query) > 0) {
				echo '<p>Location is already in the Database!</p>';
				return;
			}
			if (empty($_POST['name'])) {
					echo '<p>Please ensure you provide a location name</p>';
					return;
			}
			$query = mysql_query("INSERT INTO ".$SQL['DATABASE'].".".$defaultTable." (
									`id` ,
									`friendlyName`,
									`corporate`,
									`hidden`
									)
									VALUES (
									NULL ,
									'".mysql_real_escape_string($_POST['name'])."',
									'".(($_POST['corporate']) ? '1' : '0')."',
									'0'
									)");
			header('Location: '.(dirname($_SERVER['SCRIPT_NAME']) != '/' ? dirname($_SERVER['SCRIPT_NAME']) : '').'/?p=admin&a=locations');
		}
	} else {
		echo '<a class="link" href="?p=admin&a=locations&t=new"><img src="images/add.png"><br>New</a>';
		$query = mysql_query("SELECT locations.id, friendlyName, corporate, COUNT(name) FROM ".$SQL['DATABASE'].".locations LEFT JOIN ".$SQL['DATABASE'].".computers ON locations.id = computers.location AND computers.active = 1 WHERE hidden = '0' GROUP BY locations.friendlyName ORDER BY friendlyName ASC");
		echo '<table id="results" style="width: 300px;">';
		echo '<tr id="header">
				<td>Location Name</td>
				<td>Type</td>
				<td>Count</td>
				</tr>';
		
		$i = "colour1";
		while ($info = mysql_fetch_assoc($query)) {
			$colour = $i;
			echo '<tr class="'.$colour.'">';
			echo '<td style="text-align: left;"><a style="display: block;" href="?p=admin&a=locations&t=view&id='.$info['id'].'">'.$info['friendlyName'].'</a></td>';
			echo '<td>'.(($info['corporate'] == '1') ? 'Corporate' : 'Franchise').'</td>';
			echo '<td>'.$info['COUNT(name)'].'</td>';
			echo '</tr>';
			$i = ($i == "colour1") ? "colour2" : "colour1";
		}
		echo '</table>';
	}
}

function updateComputerChangeLog($computerID, $field, $old, $new) {
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

function doProducts() {
	global $SQL, $errorMsg;
	$defaultTable = 'products';
	
	if ($_GET['t'] == 'view') {
		$_GET['id'] = mysql_real_escape_string($_GET['id']);
		$query = mysql_query("SELECT id, friendlyName, defaultKey FROM ".$SQL['DATABASE'].".".$defaultTable." WHERE id = '".$_GET['id']."'");
		$info = mysql_fetch_assoc($query);
		?>
		<form method="post" action="?p=admin&a=products&t=confirm">
		<input type="hidden" name="id" value="<?php echo $info['id']; ?>">
		<label for="name">Product Name: </label><input type="text" size="40" name="name" value="<?php echo $info['friendlyName']; ?>"><br/>
		<label for="key">Default Key: </label><input type="text" size="40" name="key" value="<?php echo $info['defaultKey']; ?>"><br/>
		<input class="button" type="submit" name="action" value="Edit"> &nbsp; <input type="submit" name="action" value="Delete"></form>
		<?php
	} elseif ($_GET['t'] == 'confirm') {
		$query = mysql_query("SELECT friendlyName FROM ".$SQL['DATABASE'].".".$defaultTable." WHERE id = '".mysql_real_escape_string($_POST['id'])."'");
		$info = mysql_fetch_assoc($query);
		if (empty($info['friendlyName'])) {
			echo '<p>ID Not in Database!</p>';
			return;
		} else {
			if ($_POST['action'] == "Edit") {
				if (empty($_POST['name'])) {
					echo '<p>Please ensure you provide a product name</p>';
					return;
				}
				$query = mysql_query("UPDATE ".$SQL['DATABASE'].".".$defaultTable." SET
										`friendlyName` = '".mysql_real_escape_string($_POST['name'])."',
										`defaultKey` = '".mysql_real_escape_string($_POST['key'])."'
										WHERE ".$defaultTable.".id = '".mysql_real_escape_string($_POST['id'])."'");
				header('Location: '.(dirname($_SERVER['SCRIPT_NAME']) != '/' ? dirname($_SERVER['SCRIPT_NAME']) : '').'/?p=admin&a=products');
			} else {
				if (empty($_POST['id'])) {
					echo '<p>Please ensure you provide a product id</p>';
					return;
				}
				$query = mysql_query("DELETE FROM ".$SQL['DATABASE'].".".$defaultTable." WHERE ".$defaultTable.".id = '".mysql_real_escape_string($_POST['id'])."'");
				header('Location: '.(dirname($_SERVER['SCRIPT_NAME']) != '/' ? dirname($_SERVER['SCRIPT_NAME']) : '').'/?p=admin&a=products');
			}
		}
	} elseif ($_GET['t'] == 'new') {
		if (!$_POST) {
			?>
			<form method="post" action="?p=admin&a=products&t=new">
			<input type="hidden" name="id" value="<?php echo $info['id']; ?>">
			<label for="name">Product Name: </label><input type="text" size="40" name="name"><br/>
			<label for="key">Default Key: </label><input type="text" size="40" name="key"><br/>
			<input class="button" type="submit" name="action" value="Add">
			<?php
		} else {
			$query = mysql_query("SELECT id FROM ".$SQL['DATABASE'].".".$defaultTable." WHERE friendlyName = '".mysql_real_escape_string($_POST['name'])."'");
			if (mysql_num_rows($query) > 0) {
				echo '<p>Product is already in the Database!</p>';
				return;
			}
			if (empty($_POST['name'])) {
					echo '<p>Please ensure you provide a product name</p>';
					return;
			}
			$query = mysql_query("INSERT INTO ".$SQL['DATABASE'].".".$defaultTable." (
									`id` ,
									`friendlyName`,
									`defaultKey`
									)
									VALUES (
									NULL ,
									'".mysql_real_escape_string($_POST['name'])."',
									'".mysql_real_escape_string($_POST['key'])."'
									)");
			header('Location: '.(dirname($_SERVER['SCRIPT_NAME']) != '/' ? dirname($_SERVER['SCRIPT_NAME']) : '').'/?p=admin&a=products');
		}
	} else {
		echo '<a class="link" href="?p=admin&a=products&t=new"><img src="images/add.png"><br>New</a>';
		$query = mysql_query("SELECT products.id, products.friendlyName, products.defaultKey, COUNT(product) FROM ".$SQL['DATABASE'].".".$defaultTable." LEFT JOIN ".$SQL['DATABASE'].".licenses ON licenses.product = products.id GROUP BY products.friendlyName ORDER BY friendlyName ASC");
		echo '<table id="results" style="width: 600px;">';
		echo '<tr id="header">
				<td>Product</td>
				<td>Key</td>
				<td>Count</td>
				</tr>';
		
		$i = "colour1";
		while ($info = mysql_fetch_assoc($query)) {
			$colour = $i;
			echo '<tr class="'.$colour.'">';
			echo '<td style="text-align: left;"><a style="display: block;" href="?p=admin&a=products&t=view&id='.$info['id'].'">'.$info['friendlyName'].'</a></td>';
			echo '<td>'.$info['defaultKey'].'</td>';
			echo '<td>'.$info['COUNT(product)'].'</td>';
			echo '</tr>';
			$i = ($i == "colour1") ? "colour2" : "colour1";
		}
		echo '</table>';
	}
}

function doAuthOrders() {
	global $SQL, $errorMsg;
	$defaultTable = 'ordersCanAuthorize';
	
	if ($_GET['t'] == 'view') {
		$_GET['id'] = mysql_real_escape_string($_GET['id']);
		$query = mysql_query("SELECT id, friendlyName FROM ".$SQL['DATABASE'].".".$defaultTable." WHERE id = '".$_GET['id']."'");
		$info = mysql_fetch_assoc($query);
		?>
		<form method="post" action="?p=admin&a=authorders&t=confirm">
		<input type="hidden" name="id" value="<?php echo $info['id']; ?>">
		<label for="name">Name: </label><input type="text" name="name" value="<?php echo $info['friendlyName']; ?>"><br/>
		<input class="button" type="submit" name="action" value="Edit"> &nbsp; <input type="submit" name="action" value="Delete"></form>
		<?php
	} elseif ($_GET['t'] == 'confirm') {
		$query = mysql_query("SELECT friendlyName FROM ".$SQL['DATABASE'].".".$defaultTable." WHERE id = '".mysql_real_escape_string($_POST['id'])."'");
		$info = mysql_fetch_assoc($query);
		if (empty($info['friendlyName'])) {
			echo '<p>ID Not in Database!</p>';
			return;
		} else {
			if ($_POST['action'] == "Edit") {
				if (empty($_POST['name'])) {
					echo '<p>Please ensure you provide a persons name</p>';
					return;
				}
				$query = mysql_query("UPDATE ".$SQL['DATABASE'].".".$defaultTable." SET
										`friendlyName` = '".mysql_real_escape_string($_POST['name'])."'
										WHERE ".$defaultTable.".id = '".mysql_real_escape_string($_POST['id'])."'");
				header('Location: '.(dirname($_SERVER['SCRIPT_NAME']) != '/' ? dirname($_SERVER['SCRIPT_NAME']) : '').'/?p=admin&a=authorders');
			} else {
				if (empty($_POST['id'])) {
					echo '<p>Please ensure you provide a persons id</p>';
					return;
				}
				$query = mysql_query("DELETE FROM ".$SQL['DATABASE'].".".$defaultTable." WHERE ".$defaultTable.".id = '".mysql_real_escape_string($_POST['id'])."'");
				header('Location: '.(dirname($_SERVER['SCRIPT_NAME']) != '/' ? dirname($_SERVER['SCRIPT_NAME']) : '').'/?p=admin&a=authorders');
			}
		}
	} elseif ($_GET['t'] == 'new') {
		if (!$_POST) {
			?>
			<form method="post" action="?p=admin&a=authorders&t=new">
			<input type="hidden" name="id" value="<?php echo $info['id']; ?>">
			<label for="name">Name: </label><input type="text" name="name"><br/>
			<input class="button" type="submit" name="action" value="Add">
			<?php
		} else {
			$query = mysql_query("SELECT id FROM ".$SQL['DATABASE'].".".$defaultTable." WHERE friendlyName = '".mysql_real_escape_string($_POST['name'])."'");
			if (mysql_num_rows($query) > 0) {
				echo '<p>Name is already in the Database!</p>';
				return;
			}
			if (empty($_POST['name'])) {
					echo '<p>Please ensure you provide a persons name</p>';
					return;
			}
			$query = mysql_query("INSERT INTO ".$SQL['DATABASE'].".".$defaultTable." (
									`id` ,
									`friendlyName` 
									)
									VALUES (
									NULL ,
									'".mysql_real_escape_string($_POST['name'])."'
									)");
			header('Location: '.(dirname($_SERVER['SCRIPT_NAME']) != '/' ? dirname($_SERVER['SCRIPT_NAME']) : '').'/?p=admin&a=authorders');
		}
	} else {
		echo '<a class="link" href="?p=admin&a=authorders&t=new"><img src="images/add.png"><br>New</a>';
		$query = mysql_query("SELECT id, friendlyName FROM ".$SQL['DATABASE'].".".$defaultTable." ORDER BY friendlyName ASC");
		echo '<table id="results" style="width: 200px;">';
		echo '<tr id="header">
				<td>Name</td>
				</tr>';
		
		$i = "colour1";
		while ($info = mysql_fetch_assoc($query)) {
			$colour = $i;
			echo '<tr class="'.$colour.'">';
			echo '<td style="text-align: left;"><a style="display: block;" href="?p=admin&a=authorders&t=view&id='.$info['id'].'">'.$info['friendlyName'].'</a></td>';
			echo '</tr>';
			$i = ($i == "colour1") ? "colour2" : "colour1";
		}
		echo '</table>';
	}
}

function doStatus() {
	global $SQL;
	$defaultTable = 'orderStatuses';
	if ($_GET['t'] == 'view') {
		$_GET['id'] = $_GET['id'];
		$query = mysql_query("SELECT id, friendlyName FROM ".$SQL['DATABASE'].".".$defaultTable." WHERE id = '".mysql_real_escape_string($_GET['id'])."'");
		$info = mysql_fetch_assoc($query);
		?>
		<form method="post" action="?p=admin&a=status&t=confirm">
		<input type="hidden" name="id" value="<?php echo $info['id']; ?>">
		<label for="name">Status: </label><input type="text" name="name" value="<?php echo $info['friendlyName']; ?>"><br/>
		<input class="button" type="submit" name="action" value="Edit"> &nbsp; <input type="submit" name="action" value="Delete"></form>
		<?php
	} elseif ($_GET['t'] == 'confirm') {
		$query = mysql_query("SELECT friendlyName FROM ".$SQL['DATABASE'].".".$defaultTable." WHERE id = '".mysql_real_escape_string($_POST['id'])."'");
		$info = mysql_fetch_assoc($query);
		if (empty($info['friendlyName'])) {
			echo '<p>ID Not in Database!</p>';
			return;
		} else {
			if ($_POST['action'] == "Edit") {
				if (empty($_POST['name'])) {
					echo '<p>Please ensure you provide a status description</p>';
					return;
				}
				$query = mysql_query("UPDATE ".$SQL['DATABASE'].".".$defaultTable." SET
										`friendlyName` = '".mysql_real_escape_string($_POST['name'])."'
										WHERE ".$defaultTable.".id = '".mysql_real_escape_string($_POST['id'])."'");
				header('Location: '.(dirname($_SERVER['SCRIPT_NAME']) != '/' ? dirname($_SERVER['SCRIPT_NAME']) : '').'/?p=admin&a=status');
			} else {
				if (empty($_POST['id'])) {
					echo '<p>Please ensure you provide a status id</p>';
					return;
				}
				$query = mysql_query("DELETE FROM ".$SQL['DATABASE'].".".$defaultTable." WHERE ".$defaultTable.".id = '".mysql_real_escape_string($_POST['id'])."'");
				header('Location: '.(dirname($_SERVER['SCRIPT_NAME']) != '/' ? dirname($_SERVER['SCRIPT_NAME']) : '').'/?p=admin&a=status');
			}
		}
	} elseif ($_GET['t'] == 'new') {
		if (!$_POST) {
			?>
			<form method="post" action="?p=admin&a=status&t=new">
			<input type="hidden" name="id" value="<?php echo $info['id']; ?>">
			<label for="name">Status: </label><input type="text" name="name"><br/>
			<input class="button" type="submit" name="action" value="Add">
			<?php
		} else {
			$query = mysql_query("SELECT id FROM ".$SQL['DATABASE'].".".$defaultTable." WHERE friendlyName = '".mysql_real_escape_string($_POST['name'])."'");
			if (mysql_num_rows($query) > 0) {
				echo '<p>Status is already in the Database!</p>';
				return;
			}
			if (empty($_POST['name'])) {
				echo '<p>Please ensure you provide a status description</p>';
				return;
			}
			$query = mysql_query("INSERT INTO ".$SQL['DATABASE'].".".$defaultTable." (
									`id` ,
									`friendlyName` 
									)
									VALUES (
									NULL ,
									'".mysql_real_escape_string($_POST['name'])."'
									)");
			header('Location: '.(dirname($_SERVER['SCRIPT_NAME']) != '/' ? dirname($_SERVER['SCRIPT_NAME']) : '').'/?p=admin&a=status');
		}
	} else {
		echo '<a class="link" href="?p=admin&a=status&t=new"><img src="images/add.png"><br>New</a>';
		
		$query = mysql_query("SELECT id, friendlyName FROM ".$SQL['DATABASE'].".".$defaultTable." ORDER BY friendlyName ASC");
		echo '<table id="results" style="width: 600px;">';
		echo '<tr id="header">
				<td>Status</td>
				</tr>';
		
		$i = "colour1";
		while ($info = mysql_fetch_assoc($query)) {
			$colour = $i;
			echo '<tr class="'.$colour.'">';
			echo '<td style="text-align: left;"><a style="display: block;" href="?p=admin&a=status&t=view&id='.$info['id'].'">'.$info['friendlyName'].'</a></td>';
			echo '</tr>';
			$i = ($i == "colour1") ? "colour2" : "colour1";
		}
		echo '</table>';
	}
}

function doSuppliers() {
	global $SQL;
	$defaultTable = 'orderSuppliers';
	if ($_GET['t'] == 'view') {
		$_GET['id'] = $_GET['id'];
		$query = mysql_query("SELECT id, friendlyName FROM ".$SQL['DATABASE'].".".$defaultTable." WHERE id = '".mysql_real_escape_string($_GET['id'])."'");
		$info = mysql_fetch_assoc($query);
		?>
		<form method="post" action="?p=admin&a=suppliers&t=confirm">
		<input type="hidden" name="id" value="<?php echo $info['id']; ?>">
		<label for="name">Supplier Name: </label><input type="text" name="name" value="<?php echo $info['friendlyName']; ?>"><br/>
		<input class="button" type="submit" name="action" value="Edit"> &nbsp; <input type="submit" name="action" value="Delete"></form>
		<?php
	} elseif ($_GET['t'] == 'confirm') {
		$query = mysql_query("SELECT friendlyName FROM ".$SQL['DATABASE'].".".$defaultTable." WHERE id = '".mysql_real_escape_string($_POST['id'])."'");
		$info = mysql_fetch_assoc($query);
		if (empty($info['friendlyName'])) {
			echo '<p>ID Not in Database!</p>';
			return;
		} else {
			if ($_POST['action'] == "Edit") {
				if (empty($_POST['name'])) {
					echo '<p>Please ensure you provide a supplier name</p>';
					return;
				}
				$query = mysql_query("UPDATE ".$SQL['DATABASE'].".".$defaultTable." SET
										`friendlyName` = '".mysql_real_escape_string($_POST['name'])."'
										WHERE ".$defaultTable.".id = '".mysql_real_escape_string($_POST['id'])."'");
				header('Location: '.(dirname($_SERVER['SCRIPT_NAME']) != '/' ? dirname($_SERVER['SCRIPT_NAME']) : '').'/?p=admin&a=suppliers');
			} else {
				if (empty($_POST['id'])) {
					echo '<p>Please ensure you provide a supplier id</p>';
					return;
				}
				$query = mysql_query("DELETE FROM ".$SQL['DATABASE'].".".$defaultTable." WHERE ".$defaultTable.".id = '".mysql_real_escape_string($_POST['id'])."'");
				header('Location: '.(dirname($_SERVER['SCRIPT_NAME']) != '/' ? dirname($_SERVER['SCRIPT_NAME']) : '').'/?p=admin&a=suppliers');
			}
		}
	} elseif ($_GET['t'] == 'new') {
		if (!$_POST) {
			?>
			<form method="post" action="?p=admin&a=suppliers&t=new">
			<input type="hidden" name="id" value="<?php echo $info['id']; ?>">
			<label for="name">Supplier Name: </label><input type="text" name="name"><br/>
			<input class="button" type="submit" name="action" value="Add">
			<?php
		} else {
			$query = mysql_query("SELECT id FROM ".$SQL['DATABASE'].".".$defaultTable." WHERE friendlyName = '".mysql_real_escape_string($_POST['name'])."'");
			if (mysql_num_rows($query) > 0) {
				echo '<p>Supplier is already in the Database!</p>';
				return;
			}
			if (empty($_POST['name'])) {
				echo '<p>Please ensure you provide a supplier name</p>';
				return;
			}
			$query = mysql_query("INSERT INTO ".$SQL['DATABASE'].".".$defaultTable." (
									`id` ,
									`friendlyName` 
									)
									VALUES (
									NULL ,
									'".$_POST['name']."'
									)");
			header('Location: '.(dirname($_SERVER['SCRIPT_NAME']) != '/' ? dirname($_SERVER['SCRIPT_NAME']) : '').'/?p=admin&a=suppliers');
		}
	} else {
		echo '<a class="link" href="?p=admin&a=suppliers&t=new"><img src="images/add.png"><br>New</a>';
		
		$query = mysql_query("SELECT id, friendlyName FROM ".$SQL['DATABASE'].".".$defaultTable." ORDER BY friendlyName ASC");
		echo '<table id="results" style="width: 600px;">';
		echo '<tr id="header">
				<td>Supplier Name</td>
				</tr>';
		
		$i = "colour1";
		while ($info = mysql_fetch_assoc($query)) {
			$colour = $i;
			echo '<tr class="'.$colour.'">';
			echo '<td style="text-align: left;"><a style="display: block;" href="?p=admin&a=suppliers&t=view&id='.$info['id'].'">'.$info['friendlyName'].'</a></td>';
			echo '</tr>';
			$i = ($i == "colour1") ? "colour2" : "colour1";
		}
		echo '</table>';
	}
}

/*function niy() {
	echo '<div style="margin-top: 200px;text-align: center;">';
	echo '<img src="images/error.png">';
	echo '<h2>Not Implemented Yet</h2>';
	echo '</div>';
}*/
?>
