<?php
require_once(dirname(__FILE__)."/../../lib/functions/mysqlConnect.php");
require_once(dirname(__FILE__)."/../../lib/functions/redirect.php");

function initPage() {
	global $PAGE_CATEGORY, $PAGE_SUB_CATEGORY, $SQL;
	$PAGE_CATEGORY = "Admin";
	if ($_GET['a'] == "locations") {
		$PAGE_SUB_CATEGORY = "Manage Locations";
	} elseif ($_GET['a'] == "products") {
		$PAGE_SUB_CATEGORY = "Manage Products";
	} elseif ($_GET['a'] == "auth") {
		$PAGE_SUB_CATEGORY = "Can Authorize Orders";
	} elseif ($_GET['a'] == "status") {
		$PAGE_SUB_CATEGORY = "Order Statuses";
	} elseif ($_GET['a'] == "suppliers") {
		$PAGE_SUB_CATEGORY = "Order Suppliers";
	} elseif ($_GET['a'] == "config") {
		$PAGE_SUB_CATEGORY = "Configuration";
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
  return 'Admin';
}

function showPageBody() {
	if ($_GET['a'] == "config") {
		doConfig();
	} elseif ($_GET['a'] == "locations") {
		doLocations();
	} elseif ($_GET['a'] == "auth") {
		doAuth();
	} elseif ($_GET['a'] == "status") {
		doStatus();
	} elseif ($_GET['a'] == "suppliers") {
		doSuppliers();
	} elseif ($_GET['a'] == "products") {
		doProducts();
	}
}

function doConfig() {
	global $SQL;
	
	if ($_GET['t'] == 'confirm') {
		foreach ($_POST as $key => $value) {
			mysql_query("UPDATE ".$SQL['DATABASE'].".configuration SET value = '".$value."' WHERE name = '".$key."'");
		}
		echo '<p>Done</p>';
	}
	$query = mysql_query("SELECT * FROM ".$SQL['DATABASE'].".configuration");
	?>
	<form method="post" action="?p=admin&a=config&t=confirm">
	<?php
		while ($info = mysql_fetch_assoc($query)) {
			echo '<div>'.$info['friendlyName'].': </div><input type="text" name="'.$info['name'].'" value="'.$info['value'].'"><br/>';
		}
	?>
	<p><div>&nbsp;</div><input type="submit" value="Edit"> &nbsp; 
	<?php
}

function doNavHeader($type, $subCategory) {
	if ($type == 'locations') {
		echo '<div class="nav2">'.buildNavigationLine($subCategory, array(
													'New Location' => '?p=admin&a=locations&t=new',
													'Manage Locations' => '?p=admin&a=locations'
													)).'</div>';
	} elseif ($type == 'products') {
		echo '<div class="nav2">'.buildNavigationLine($subCategory, array(
													'New Product' => '?p=admin&a=products&t=new',
													'Manage Products' => '?p=admin&a=products'
													)).'</div>';
	} elseif ($type == 'auth') {
		echo '<div class="nav2">'.buildNavigationLine($subCategory, array(
													'New Name' => '?p=admin&a=auth&t=new',
													'Manage Names' => '?p=admin&a=auth'
													)).'</div>';
	} elseif ($type == 'status') {
		echo '<div class="nav2">'.buildNavigationLine($subCategory, array(
													'New Status' => '?p=admin&a=status&t=new',
													'Manage Statuses' => '?p=admin&a=status'
													)).'</div>';
	} elseif ($type == 'suppliers') {
		echo '<div class="nav2">'.buildNavigationLine($subCategory, array(
													'New Supplier' => '?p=admin&a=suppliers&t=new',
													'Manage Suppliers' => '?p=admin&a=suppliers'
													)).'</div>';
	}
	echo '<div>&nbsp;</div>';
}

function doLocations() {
	global $SQL;
	//echo '<div class="nav2">[ New Location ]</div>';

	if ($_GET['t'] == 'view') {
		doNavHeader('locations', 'Manage Locations');
		$_GET['id'] = mysql_real_escape_string($_GET['id']);
		$query = mysql_query("SELECT id, friendlyName, corporate FROM ".$SQL['DATABASE'].".locations WHERE id = '".$_GET['id']."'");
		$info = mysql_fetch_assoc($query);
		?>
		<form method="post" action="?p=admin&a=locations&t=confirm">
		<input type="hidden" name="id" value="<?php echo $info['id']; ?>">
		<div>Location Name: </div><input type="text" name="location" value="<?php echo $info['friendlyName']; ?>"><br/>
		<div>Corporate Location: </div><input type="checkbox" name="corporate" value="1"<?php echo ($info['corporate'] == '1') ? ' CHECKED' : ''?>><br/>
		<p><div>&nbsp;</div><input type="submit" name="action" value="Edit"> &nbsp; 
		<?php
		$query = mysql_query("SELECT name, serial FROM ".$SQL['DATABASE'].".computers WHERE location = '".$_GET['id']."' AND active = '1'");
		
		if (mysql_num_rows($query) > 0) {
			echo '- Cannot delete as this Loation still has active items allocated to it (See Below)</form></p>';
			echo '<table id="results">';
			echo '<tr id="headers">
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
			echo '<div>Change all PCs cost to: </div><input type="text" name="cost">';
			echo '<input type="submit" value="Go">';
		} else {
			echo '<input type="submit" name="action" value="Delete"></form></p>';
		}
	} elseif ($_GET['t'] == 'cost') {
		doNavHeader('locations', 'Manage Locations');
		foreach ($_POST as $key => $value) {
			$_POST[$key] = mysql_real_escape_string($value);
		}
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
					updateComputerChangeLog($info['id'], "Maintenance Fee", $info['maintenanceFee'], $_POST['cost']);
				}
			}
			$query = mysql_query("UPDATE ".$SQL['DATABASE'].".computers SET maintenanceFee = '".$_POST['cost']."', modifiedby = '".$_SESSION['displayname']."', modified = '".time()."' WHERE location = '".$_POST['id']."'");
			redirect("?p=admin&a=locations");
		}
	} elseif ($_GET['t'] == 'confirm') {
		doNavHeader('locations', 'Manage Locations');
		foreach ($_POST as $key => $value) {
			$_POST[$key] = mysql_real_escape_string($value);
		}
		$query = mysql_query("SELECT friendlyName FROM ".$SQL['DATABASE'].".locations WHERE id = '".$_POST['id']."'");
		$info = mysql_fetch_assoc($query);
		if (empty($info['friendlyName'])) {
			echo '<p>ID Not in Database!</p>';
			return;
		} else {
			if ($_POST['action'] == "Edit") {
				if (empty($_POST['location'])) {
					echo '<p>Please ensure you provide a location name</p>';
					return;
				}
				$query = mysql_query("UPDATE ".$SQL['DATABASE'].".locations SET
									`friendlyName` = '".$_POST['location']."',
									`corporate` = '".(($_POST['corporate']) ? '1' : '0')."'
									WHERE locations.id = '".$_POST['id']."';");
				redirect('?p=admin&a=locations');
			} else {
				$query = mysql_query("SELECT name, serial FROM ".$SQL['DATABASE'].".computers WHERE location = '".$_POST['id']."' AND active = '1'");
	
				if (mysql_num_rows($query) > 0) {
					echo '<p>Cannot delete as this location still has actve assets assigned to it.</p>';
					return;
				}
				$query = mysql_query("SELECT name, serial FROM ".$SQL['DATABASE'].".computers WHERE location = '".$_POST['id']."'");
				if (mysql_num_rows($query) == 0) {
					$query = mysql_query("DELETE FROM ".$SQL['DATABASE'].".locations WHERE locations.id = '".$_POST['id']."'");
				} else {
					$query = mysql_query("UPDATE ".$SQL['DATABASE'].".locations SET `hidden` = '1' WHERE locations.id = '".$_POST['id']."'");
				}
				redirect('?p=admin&a=locations');
				
			}
		}
	} elseif ($_GET['t'] == 'new') {
		doNavHeader('locations', 'New Location');
		if (!$_POST) {
			?>
			<form method="post" action="?p=admin&a=locations&t=new">
			<input type="hidden" name="id" value="<?php echo $info['id']; ?>">
			<div>Location Name: </div><input type="text" name="location"><br/>
			<div>Corporate Location: </div><input type="checkbox" name="corporate" value="1" CHECKED><br/>
			<p><div>&nbsp;</div><input type="submit" name="action" value="Add"> &nbsp; 
			<?php
		} else {
			foreach ($_POST as $key => $value) {
				$_POST[$key] = mysql_real_escape_string($value);
			}
			$query = mysql_query("SELECT id FROM ".$SQL['DATABASE'].".locations WHERE friendlyName = '".$_POST['location']."' AND hidden = '0'");
			if (mysql_num_rows($query) > 0) {
				echo '<p>Location is already in the Database!</p>';
				return;
			}
			if (empty($_POST['location'])) {
				echo '<p>Please ensure you provide a location name</p>';
				return;
			}
			$query = mysql_query("INSERT INTO ".$SQL['DATABASE'].".locations (
									`id` ,
									`friendlyName` ,
									`corporate` ,
									`hidden`
									)
									VALUES (
									NULL, 
									'".$_POST['location']."', 
									'".(($_POST['corporate']) ? '1' : '0')."', 
									'0')");
			redirect('?p=admin&a=locations');
		}
	} else {
		doNavHeader('locations', 'Manage Locations');
		$query = mysql_query("SELECT locations.id, friendlyName, corporate, COUNT(name) FROM ".$SQL['DATABASE'].".locations LEFT JOIN ".$SQL['DATABASE'].".computers ON locations.id = computers.location AND computers.active = 1 WHERE hidden = '0' GROUP BY locations.friendlyName ORDER BY friendlyName ASC");
		echo '<table id="results" style="width: 300px;">';
		echo '<tr id="headers">
				<td>Location Name</td><td>Type</td><td>Count</td>
				</tr>';
		
		$i = "colour1";
		while ($info = mysql_fetch_assoc($query)) {
			$colour = $i;
			//echo '<tr>';
			echo '<tr class="'.$colour.'">';
			echo '<td style="text-align: left;"><a style="display: block;" href="?p=admin&a=locations&t=view&id='.$info['id'].'">'.$info['friendlyName'].'</a></td><td>'.(($info['corporate'] == '1') ? 'Corporate' : 'Franchise').'</td><td>'.$info['COUNT(name)'].'</td>';
			echo '</tr>';
			$i = ($i == "colour1") ? "colour2" : "colour1";
		}
		echo '</table>';
	}
}

function doAuth() {
	global $SQL;
	$defaultTable = 'ordersCanAuthorize';
	if ($_GET['t'] == 'view') {
		doNavHeader('auth', 'Manage Names');
		$_GET['id'] = mysql_real_escape_string($_GET['id']);
		$query = mysql_query("SELECT id, friendlyName FROM ".$SQL['DATABASE'].".".$defaultTable." WHERE id = '".$_GET['id']."'");
		$info = mysql_fetch_assoc($query);
		?>
		<form method="post" action="?p=admin&a=auth&t=confirm">
		<input type="hidden" name="id" value="<?php echo $info['id']; ?>">
		<div>Name: </div><input type="text" name="name" value="<?php echo $info['friendlyName']; ?>"><br/>
		<p><div>&nbsp;</div><input type="submit" name="action" value="Edit"> &nbsp; <input type="submit" name="action" value="Delete"></form></p>
		<?php
	} elseif ($_GET['t'] == 'confirm') {
		doNavHeader('auth', 'Manage Names');
		foreach ($_POST as $key => $value) {
			$_POST[$key] = mysql_real_escape_string($value);
		}
		$query = mysql_query("SELECT friendlyName FROM ".$SQL['DATABASE'].".".$defaultTable." WHERE id = '".$_POST['id']."'");
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
										`friendlyName` = '".$_POST['name']."'
										WHERE ".$defaultTable.".id = '".$_POST['id']."'");
				redirect('?p=admin&a=auth');
			} else {
				if (empty($_POST['name'])) {
					echo '<p>Please ensure you provide a persons name</p>';
					return;
				}
				$query = mysql_query("DELETE FROM ".$SQL['DATABASE'].".".$defaultTable." WHERE ".$defaultTable.".id = '".$_POST['id']."'");
				redirect('?p=admin&a=auth');
			}
		}
	} elseif ($_GET['t'] == 'new') {
		doNavHeader('auth', 'New Name');
		if (!$_POST) {
			?>
			<form method="post" action="?p=admin&a=auth&t=confirm&t=new">
			<input type="hidden" name="id" value="<?php echo $info['id']; ?>">
			<div>Name: </div><input type="text" name="name"><br/>
			<p><div>&nbsp;</div><input type="submit" name="action" value="Add">
			<?php
		} else {
			foreach ($_POST as $key => $value) {
				$_POST[$key] = mysql_real_escape_string($value);
			}
			$query = mysql_query("SELECT id FROM ".$SQL['DATABASE'].".".$defaultTable." WHERE friendlyName = '".$_POST['name']."'");
			if (mysql_num_rows($query) > 0) {
				echo '<p>Name is already in the Database!</p>';
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
			redirect('?p=admin&a=auth');
		}
	} else {
		doNavHeader('auth', 'Manage Names');
		$query = mysql_query("SELECT id, friendlyName FROM ".$SQL['DATABASE'].".".$defaultTable." ORDER BY friendlyName ASC");
		echo '<table id="results" style="width: 200px;">';
		echo '<tr id="headers">
				<td>Name</td>
				</tr>';
		
		$i = "colour1";
		while ($info = mysql_fetch_assoc($query)) {
			$colour = $i;
			echo '<tr class="'.$colour.'">';
			echo '<td style="text-align: left;"><a style="display: block;" href="?p=admin&a=auth&t=view&id='.$info['id'].'">'.$info['friendlyName'].'</a></td>';
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
		doNavHeader('status', 'Manage Statuses');
		$_GET['id'] = mysql_real_escape_string($_GET['id']);
		$query = mysql_query("SELECT id, friendlyName FROM ".$SQL['DATABASE'].".".$defaultTable." WHERE id = '".$_GET['id']."'");
		$info = mysql_fetch_assoc($query);
		?>
		<form method="post" action="?p=admin&a=status&t=confirm">
		<input type="hidden" name="id" value="<?php echo $info['id']; ?>">
		<div>Status: </div><input type="text" name="name" value="<?php echo $info['friendlyName']; ?>"><br/>
		<p><div>&nbsp;</div><input type="submit" name="action" value="Edit"> &nbsp; <input type="submit" name="action" value="Delete"></form></p>
		<?php
	} elseif ($_GET['t'] == 'confirm') {
		doNavHeader('status', 'Manage Statuses');
		foreach ($_POST as $key => $value) {
			$_POST[$key] = mysql_real_escape_string($value);
		}
		$query = mysql_query("SELECT friendlyName FROM ".$SQL['DATABASE'].".".$defaultTable." WHERE id = '".$_POST['id']."'");
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
										`friendlyName` = '".$_POST['name']."'
										WHERE ".$defaultTable.".id = '".$_POST['id']."'");
				redirect('?p=admin&a=status');
			} else {
				if (empty($_POST['name'])) {
					echo '<p>Please ensure you provide a status description</p>';
					return;
				}
				$query = mysql_query("DELETE FROM ".$SQL['DATABASE'].".".$defaultTable." WHERE ".$defaultTable.".id = '".$_POST['id']."'");
				redirect('?p=admin&a=status');
			}
		}
	} elseif ($_GET['t'] == 'new') {
		doNavHeader('status', 'New Status');
		if (!$_POST) {
			?>
			<form method="post" action="?p=admin&a=status&t=confirm&t=new">
			<input type="hidden" name="id" value="<?php echo $info['id']; ?>">
			<div>Status: </div><input type="text" name="name"><br/>
			<p><div>&nbsp;</div><input type="submit" name="action" value="Add">
			<?php
		} else {
			foreach ($_POST as $key => $value) {
				$_POST[$key] = mysql_real_escape_string($value);
			}
			$query = mysql_query("SELECT id FROM ".$SQL['DATABASE'].".".$defaultTable." WHERE friendlyName = '".$_POST['name']."'");
			if (mysql_num_rows($query) > 0) {
				echo '<p>Status is already in the Database!</p>';
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
			redirect('?p=admin&a=status');
		}
	} else {
		doNavHeader('status', 'Manage Statuses');
		$query = mysql_query("SELECT id, friendlyName FROM ".$SQL['DATABASE'].".".$defaultTable." ORDER BY friendlyName ASC");
		echo '<table id="results" style="width: 600px;">';
		echo '<tr id="headers">
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
		doNavHeader('suppliers', 'Manage Suppliers');
		$_GET['id'] = mysql_real_escape_string($_GET['id']);
		$query = mysql_query("SELECT id, friendlyName FROM ".$SQL['DATABASE'].".".$defaultTable." WHERE id = '".$_GET['id']."'");
		$info = mysql_fetch_assoc($query);
		?>
		<form method="post" action="?p=admin&a=suppliers&t=confirm">
		<input type="hidden" name="id" value="<?php echo $info['id']; ?>">
		<div>Supplier Name: </div><input type="text" name="name" value="<?php echo $info['friendlyName']; ?>"><br/>
		<p><div>&nbsp;</div><input type="submit" name="action" value="Edit"> &nbsp; <input type="submit" name="action" value="Delete"></form></p>
		<?php
	} elseif ($_GET['t'] == 'confirm') {
		doNavHeader('suppliers', 'Manage Suppliers');
		foreach ($_POST as $key => $value) {
			$_POST[$key] = mysql_real_escape_string($value);
		}
		$query = mysql_query("SELECT friendlyName FROM ".$SQL['DATABASE'].".".$defaultTable." WHERE id = '".$_POST['id']."'");
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
										`friendlyName` = '".$_POST['name']."'
										WHERE ".$defaultTable.".id = '".$_POST['id']."'");
				redirect('?p=admin&a=suppliers');
			} else {
				if (empty($_POST['name'])) {
					echo '<p>Please ensure you provide a supplier name</p>';
					return;
				}
				$query = mysql_query("DELETE FROM ".$SQL['DATABASE'].".".$defaultTable." WHERE ".$defaultTable.".id = '".$_POST['id']."'");
				redirect('?p=admin&a=suppliers');
			}
		}
	} elseif ($_GET['t'] == 'new') {
		doNavHeader('suppliers', 'New Supplier');
		if (!$_POST) {
			?>
			<form method="post" action="?p=admin&a=suppliers&t=confirm&t=new">
			<input type="hidden" name="id" value="<?php echo $info['id']; ?>">
			<div>Supplier Name: </div><input type="text" name="name"><br/>
			<p><div>&nbsp;</div><input type="submit" name="action" value="Add">
			<?php
		} else {
			foreach ($_POST as $key => $value) {
				$_POST[$key] = mysql_real_escape_string($value);
			}
			$query = mysql_query("SELECT id FROM ".$SQL['DATABASE'].".".$defaultTable." WHERE friendlyName = '".$_POST['name']."'");
			if (mysql_num_rows($query) > 0) {
				echo '<p>Supplier is already in the Database!</p>';
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
			redirect('?p=admin&a=suppliers');
		}
	} else {
		doNavHeader('suppliers', 'Manage Suppliers');
		$query = mysql_query("SELECT id, friendlyName FROM ".$SQL['DATABASE'].".".$defaultTable." ORDER BY friendlyName ASC");
		echo '<table id="results" style="width: 600px;">';
		echo '<tr id="headers">
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

function doProducts() {
	global $SQL;
	if ($_GET['t'] == 'view') {
		doNavHeader('products', 'Manage Products');
		$_GET['id'] = mysql_real_escape_string($_GET['id']);
		$query = mysql_query("SELECT id, friendlyName, defaultKey FROM ".$SQL['DATABASE'].".products WHERE id = '".$_GET['id']."'");
		$info = mysql_fetch_assoc($query);
		?>
		<form method="post" action="?p=admin&a=products&t=confirm">
		<input type="hidden" name="id" value="<?php echo $info['id']; ?>">
		<div>Product Name: </div><input type="text" name="name" size="40" value="<?php echo $info['friendlyName']; ?>"><br/>
		<div>Default Key: </div><input type="text" name="key" size="40" value="<?php echo $info['defaultKey']; ?>"><br/>
		<p><div>&nbsp;</div><input type="submit" name="action" value="Edit"> &nbsp; 
		<?php
		$query = mysql_query("SELECT licenses.license, computers.name FROM ".$SQL['DATABASE'].".licenses as licenses LEFT JOIN ".$SQL['DATABASE'].".computers as computers ON licenses.computerid = computers.id LEFT JOIN ".$SQL['DATABASE'].".orders as orders ON licenses.orderid = orders.id LEFT JOIN ".$SQL['DATABASE'].".products as products ON licenses.product = products.id WHERE products.id = '".$info['id']."' ORDER BY computers.name DESC");
		
		if (mysql_num_rows($query) > 0) {
			echo '- Cannot delete as this product still has active licenses allocated to it (See Below)</form></p>';
			echo '<table id="results">';
			echo '<tr id="headers">
					<td>Name</td><td>License</td>
					</tr>';
			$i = "colour1";
			while ($locinfo = mysql_fetch_assoc($query)) {
				$colour = $i;
				echo '<tr class="'.$colour.'">';
				echo '<td>'.$locinfo['name'].'</td><td>'.$locinfo['license'].'</td>';
				echo '</tr>';
				$i = ($i == "colour1") ? "colour2" : "colour1";
			}

		} else {
			echo '<input type="submit" name="action" value="Delete"></form></p>';
			
		}
	} elseif ($_GET['t'] == 'confirm') {
		doNavHeader('products', 'Manage Products');
		foreach ($_POST as $key => $value) {
			$_POST[$key] = mysql_real_escape_string($value);
		}
		$query = mysql_query("SELECT friendlyName FROM ".$SQL['DATABASE'].".products WHERE id = '".$_POST['id']."'");
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
				$query = mysql_query("UPDATE ".$SQL['DATABASE'].".products SET
										`friendlyName` = '".$_POST['name']."',
										`defaultKey` = '".$_POST['key']."'
										WHERE products.id = '".$_POST['id']."';");
				redirect('?p=admin&a=products');
			} else {
				$query = mysql_query("SELECT licenses.license, computers.name FROM ".$SQL['DATABASE'].".licenses as licenses LEFT JOIN ".$SQL['DATABASE'].".computers as computers ON licenses.computerid = computers.id LEFT JOIN ".$SQL['DATABASE'].".orders as orders ON licenses.orderid = orders.id LEFT JOIN ".$SQL['DATABASE'].".products as products ON licenses.product = products.id WHERE products.id = '".$info['id']."' ORDER BY computers.name DESC");
	
				if (mysql_num_rows($query) > 0) {
					echo '<p>Cannot delete as this product still has licenses assigned to it.</p>';
					return;
				}
				if (empty($_POST['name'])) {
					echo '<p>Please ensure you provide a product name</p>';
					return;
				}
				$query = mysql_query("DELETE FROM ".$SQL['DATABASE'].".products WHERE products.id = '".$_POST['id']."'");
				redirect('?p=admin&a=products');
				
			}
		}
	} elseif ($_GET['t'] == 'new') {
		doNavHeader('products', 'New Product');
		if (!$_POST) {
			?>
			<form method="post" action="?p=admin&a=products&t=confirm&t=new">
			<input type="hidden" name="id" value="<?php echo $info['id']; ?>">
			<div>Product Name: </div><input type="text" size="40" name="name"><br/>
			<div>Default Key: </div><input type="text" size="40" name="key"><br/>
			<p><div>&nbsp;</div><input type="submit" name="action" value="Add">
			<?php
		} else {
			foreach ($_POST as $key => $value) {
				$_POST[$key] = mysql_real_escape_string($value);
			}
			$query = mysql_query("SELECT id FROM ".$SQL['DATABASE'].".products WHERE friendlyName = '".$_POST['name']."'");
			if (mysql_num_rows($query) > 0) {
				echo '<p>Product is already in the Database!</p>';
				return;
			}
			$query = mysql_query("INSERT INTO ".$SQL['DATABASE'].".products (
									`id` ,
									`friendlyName` ,
									`defaultKey`
									)
									VALUES (
									NULL ,
									'".$_POST['name']."',
									'".$_POST['key']."'
									)");
			redirect('?p=admin&a=products');
		}
	} else {
		doNavHeader('products', 'Manage Products');
		$query = mysql_query("SELECT products.id, products.friendlyName, products.defaultKey, COUNT(product) FROM ".$SQL['DATABASE'].".products LEFT JOIN ".$SQL['DATABASE'].".licenses ON licenses.product = products.id GROUP BY products.friendlyName ORDER BY friendlyName ASC");
		echo '<table id="results" style="width: 600px;">';
		echo '<tr id="headers">
				<td>Name</td><td>Default Key</td><td>Count</td>
				</tr>';
		
		$i = "colour1";
		while ($info = mysql_fetch_assoc($query)) {
			$colour = $i;
			echo '<tr class="'.$colour.'">';
			echo '<td style="text-align: left;"><a style="display: block;" href="?p=admin&a=products&t=view&id='.$info['id'].'">'.$info['friendlyName'].'</a></td><td>'.$info['defaultKey'].'</td><td>'.$info['COUNT(product)'].'</td>';
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
		'".$_SESSION['displayname']."',
		'".$field."',
		'".$old."',
		'".$new."',
		'".time()."')");
}
?>

