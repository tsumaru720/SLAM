<?php

function getPageTitle() {
	return 'Administration';
}

function doHeader() {
	global $activePage, $activeMenu;

	echo '<link rel="stylesheet" type="text/css" href="css/form.css">';
	echo '<link rel="stylesheet" type="text/css" href="css/results.css">';
	echo '<script src="script/popout.js"></script>';

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

function doUsers() {
	echo '<div style="margin-top: 200px;text-align: center;">';
	echo '<img src="images/error.png">';
	echo '<h2>Not Implemented Yet</h2>';
	echo '</div>';
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
