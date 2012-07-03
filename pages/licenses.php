<?php

function getPageTitle() {
	return 'License Management';
}

function doHeader() {
	global $activePage, $activeMenu;

	echo '<link rel="stylesheet" type="text/css" href="css/form.css">';
	echo '<link rel="stylesheet" type="text/css" href="css/results.css">';
	echo '<script src="script/popout.js"></script>';

	$activePage = 'licenses';

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
	'Find License' => array(
		'url' => '?p=licenses&amp;a=find',
		'alias' => 'find',
	),
	'Add License' => array(
		'url' => '?p=licenses&amp;a=new',
		'alias' => 'new',
	),
);
}

function getBody() {
	if ($_GET['a'] == "new") {
		doNewLicense();
	} elseif ($_GET['a'] == "edit") {
		doEditLicense();
	} elseif ($_GET['a'] == "find") {
		doFindLicense();
	} elseif ($_GET['a'] == "view") {
		doViewLicense();
	} elseif ($_GET['a'] == "allocate") {
		doAllocateLicense();
	} elseif ($_GET['a'] == "remove") {
		doRemoveLicense();
	} elseif ($_GET['a'] == "delete") {
		doDeleteLicense();
	} else {
		//Default action
		doFindLicense();
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
function doRemoveLicense() {
	global $SQL;
	
	$id = $_GET['id'];
	
	$query = mysql_query("SELECT licenses.id, licenses.computerid, licenses.license, products.friendlyName FROM ".$SQL['DATABASE'].".licenses LEFT JOIN ".$SQL['DATABASE'].".products ON licenses.product = products.id WHERE licenses.id = '".mysql_real_escape_string($id)."' LIMIT 0,1");
	$license = mysql_fetch_assoc($query);
	if (empty($license['id'])) {
		echo '<p>ID not in database</p>';
	} else {
		mysql_query("UPDATE ".$SQL['DATABASE'].".licenses SET `computerid` = '0' WHERE id = '".mysql_real_escape_string($id)."'");
		updateComputerChangeLog($license['computerid'], 'License', '', 'Removed '.$license['friendlyName'].' License');
		header('Location: '.(dirname($_SERVER['SCRIPT_NAME']) != '/' ? dirname($_SERVER['SCRIPT_NAME']) : '').'/?p=computers&a=view&id='.$license['computerid']);
	}
}

function doDeleteLicense() {
	global $SQL;
	
	$id = $_GET['id'];
	
	$query = mysql_query("SELECT licenses.id, licenses.computerid, licenses.license, licenses.orderid, products.friendlyName FROM ".$SQL['DATABASE'].".licenses LEFT JOIN ".$SQL['DATABASE'].".products ON licenses.product = products.id WHERE licenses.id = '".mysql_real_escape_string($id)."' LIMIT 0,1");
	$license = mysql_fetch_assoc($query);
	
	if (empty($license['id'])) {
		echo '<p>ID not in database</p>';
	} else {
		if ($license['computerid'] > 0) {
			updateComputerChangeLog($license['computerid'], 'License', '', 'Destroyed 1x'.$license['friendlyName'].' License');
		}
		if ($license['orderid'] > 0) {
			updateOrderChangeLog($license['orderid'], 'License', '', 'Destroyed 1x'.$license['friendlyName'].' License');
		}
		mysql_query("DELETE FROM ".$SQL['DATABASE'].".licenses WHERE licenses.id = '".mysql_real_escape_string($id)."'");
		if ($license['computerid'] > 0) {
			header('Location: '.(dirname($_SERVER['SCRIPT_NAME']) != '/' ? dirname($_SERVER['SCRIPT_NAME']) : '').'/?p=computers&a=view&id='.$license['computerid']);
		} elseif ($license['orderid'] > 0) {
			header('Location: '.(dirname($_SERVER['SCRIPT_NAME']) != '/' ? dirname($_SERVER['SCRIPT_NAME']) : '').'/?p=orders&a=view&id='.$license['orderid']);
		} else {
			header('Location: '.(dirname($_SERVER['SCRIPT_NAME']) != '/' ? dirname($_SERVER['SCRIPT_NAME']) : '').'/?p=licenses&a=find');
		}
	}
}

function doNewLicense() {
	global $SQL, $errorMsg;
	
	if (empty($errorMsg)) {
		if ($_GET['t'] == "confirm") {
		
			$orderID = 0;
			if (!empty($_POST['order_no'])) {
				$orderQuery = mysql_query("SELECT id FROM ".$SQL['DATABASE'].".orders WHERE orderno = '".mysql_real_escape_string($_POST['order_no'])."' LIMIT 0,1");
				$orderInfo = mysql_fetch_assoc($orderQuery);
				$orderID = $orderInfo['id'];
			}
			
			$postCount = (count($_POST) - 2) / 2;
			$changed = false;

			for ($i = 1; $i <= $postCount; $i++) {
				$changed = true;
				if (!empty($_POST['product'.$i])) {
					$product[$i] = $_POST['product'.$i];
				}
				
				if (!empty($_POST['key'.$i])) {
					$productKey[$i] = $_POST['key'.$i];
				}
			}
			if ($changed == false || count($product) == 0) {
				showError('Please ensure you provide license information',true);
				return;
			}
			
			foreach ($product as $key => $value) {
				if (empty($productKey[$key])) {
					$productQuery = mysql_query("SELECT friendlyName, defaultKey FROM ".$SQL['DATABASE'].".products WHERE id = '".mysql_real_escape_string($product[$key])."' LIMIT 0,1");
					$productInfo = mysql_fetch_assoc($productQuery);
					if (empty($productInfo['defaultKey'])) {
						showError('You did not provide a key for item number '.$key.': <b>'.$productInfo['friendlyName'].'</b>',true);
						return;
					} else {
						$productKey[$key] = $productInfo['defaultKey'];
					}
				}
			}
			foreach ($product as $key => $value) {
				$query = mysql_query("INSERT INTO ".$SQL['DATABASE'].".licenses (
				`id` ,
				`computerid` ,
				`orderid` ,
				`product` ,
				`license`
				) VALUES ( NULL ,
				'0',
				'".mysql_real_escape_string($orderID)."',
				'".mysql_real_escape_string($product[$key])."',
				'".mysql_real_escape_string($productKey[$key])."')");
			}
			
			if ($orderID > 0) {
				updateOrderChangeLog($orderID, 'License', '', 'Added '.$postCount.' Licenses');
				header('Location: '.(dirname($_SERVER['SCRIPT_NAME']) != '/' ? dirname($_SERVER['SCRIPT_NAME']) : '').'/?p=licenses&a=find&s=spare&query='.urlencode($_POST['order_no']));
			} else {
				header('Location: '.(dirname($_SERVER['SCRIPT_NAME']) != '/' ? dirname($_SERVER['SCRIPT_NAME']) : '').'/?p=licenses&a=find&s=spare&query=%');
			}
			return;
		}
	}
	
	
	$licenseQuery = mysql_query("SELECT id, friendlyName, defaultKey FROM ".$SQL['DATABASE'].".products ORDER BY friendlyName ASC");
	while ($license = mysql_fetch_assoc($licenseQuery)) {
		$productList[$license['id']] = (!empty($license['defaultKey'])) ? '(K) '.$license['friendlyName'].'' : $license['friendlyName'];
	}
	$licenseCount = (is_numeric($_POST['count'])) ? $_POST['count'] : 1;
	if ($licenseCount <= 0) { $licenseCount = 1; }
	
	if (!empty($errorMsg)) {
		echo '<p><span style="color: red;">'.$errorMsg.'</span></p>';
	}
	?>
	<form method="post" action="?p=licenses&a=new">
	<?php
		if (is_numeric($_GET['id'])) {
			echo '<input type="hidden" name="id" value="'.$_GET['id'].'">';
		} elseif (is_numeric($_POST['id'])) {
			echo '<input type="hidden" name="id" value="'.$_POST['id'].'">';
		}
	?>
	<label for="count">Number of Licenses: </label><input type="text" name="count" size="1" value="<?php echo $licenseCount; ?>">
	
	<br/>
	<br/>
		
	<input type="submit" class="button" value="Update"></form>
		
	<hr>
	
	<form method="post" action="?p=licenses&a=new&t=confirm">
	<?php
		if (is_numeric($_GET['id'])) {
			$id = $_GET['id'];
		} elseif (is_numeric($_POST['id'])) {
			$id = $_POST['id'];
		}
		if (!empty($id)) {
			$orderQuery = mysql_query("SELECT orderno FROM ".$SQL['DATABASE'].".orders WHERE id = '".$id."' LIMIT 0,1");
			$orderInfo = mysql_fetch_assoc($orderQuery);
		}
	?>
	<input type="hidden" name="order_no" value="<?php echo $orderInfo['orderno'];?>">
	<input type="hidden" name="count" value="<?php echo $licenseCount;?>">
	<label for="order_no" style="width: 200px;">Add licenses to order number: </label><input type="text" name="order_no" size="7"<?php echo (!empty($orderInfo['orderno'])) ? ' value="'.$orderInfo['orderno'].'" DISABLED' : '';?>>
	
	<br/>
	<br/>
	
	<span class="infoText">(K) indicates there is a default key stored and you are not required to provide one.</span>

	<br>
	
	<?php
		for ($i = 1; $i <= $licenseCount; $i++) {
			$fieldID = 'product'.$i;
			echo '<label for="'.$fieldID.'">Product #'.$i.'</label>';
			echo '<select name="'.$fieldID.'">';
			echo '<option value="">Please choose...</option>';
			foreach ($productList as $key => $value) {
				echo '<option value="'.$key.'"'.($_POST[$fieldID] == $key ? ' SELECTED' : '').'>'.$value.'</option>';
			}
			echo '</select>';
			echo '&nbsp;Key: <input type="text" name="key'.$i.'" size="40" value="'.$_POST['key'.$i].'"><br/>';
		}
	?>
	<input class="button" type="submit" value="Add Licenses"></form>
	<?php
}

function updateOrderChangeLog($orderID, $field, $old, $new) {
	global $SQL;
	mysql_query("INSERT INTO ".$SQL['DATABASE'].".orderChangeLog (
		`id` ,
		`orderid` ,
		`changedby` ,
		`field` ,
		`old` ,
		`new` ,
		`date`
		) VALUES (NULL,
		'".$orderID."',
		'".$_SESSION['displayName']."',
		'".$field."',
		'".$old."',
		'".$new."',
		'".time()."')");
}
		

function doEditLicense() {
	echo '<div style="margin-top: 200px;text-align: center;">';
	echo '<img src="images/error.png">';
	echo '<h2>Not Implemented Yet</h2>';
	echo '</div>';
}

function doAllocateLicense() {
	global $SQL, $errorMsg;
	
	if (empty($errorMsg)) {
		if ($_GET['t'] == "confirm") {	
			$id = $_POST['id'];
			$computerQuery = mysql_query("SELECT name FROM ".$SQL['DATABASE'].".computers WHERE id = '".mysql_real_escape_string($id)."' LIMIT 0,1");
			$computerInfo = mysql_fetch_assoc($computerQuery);
			if (empty($computerInfo['name'])) {
				showError('Computer was not found',true);
				return;
			}
			
			$changed = false;
			foreach ($_POST as $key => $value) {
				if (is_numeric($key)) {
					$changed = true;
					$query = mysql_query("SELECT licenses.id, licenses.license, products.friendlyName FROM ".$SQL['DATABASE'].".licenses LEFT JOIN ".$SQL['DATABASE'].".products ON licenses.product = products.id WHERE licenses.id = '".mysql_real_escape_string($value)."' LIMIT 0,1");
					$license = mysql_fetch_assoc($query);
					if (empty($license['license'])) {
						showError('There was an error matching one of the licenses. Maybe it has been used by someone else?',true);
						return;
					} else {
						mysql_query("UPDATE ".$SQL['DATABASE'].".licenses SET `computerid` = '".mysql_real_escape_string($id)."' WHERE licenses.id = '".mysql_real_escape_string($value)."'");
						updateComputerChangeLog($id, 'License', '', 'Added '.$license['friendlyName'].' License');
						header('Location: '.(dirname($_SERVER['SCRIPT_NAME']) != '/' ? dirname($_SERVER['SCRIPT_NAME']) : '').'/?p=computers&a=view&id='.$id);
					}
				}
			}
			if ($changed == false) {
				showError('No licenses were selected',true);
				return;
			}
		}
	}
		
	if ($_POST['id']) {
		$id = $_POST['id'];
	} else {
		$id = $_GET['id'];
	}
	
	$computerQuery = mysql_query("SELECT id, name FROM ".$SQL['DATABASE'].".computers WHERE id = '".mysql_real_escape_string($id)."' LIMIT 0,1");
	$computerInfo = mysql_fetch_assoc($computerQuery);
	
	if (!empty($errorMsg)) {
		echo '<p><span style="color: red;">'.$errorMsg.'</span></p>';
	}
	?>
	<form  style="width: 100%;" method="post" action="?p=licenses&a=allocate&t=confirm">
	<input type="hidden" name="id" value="<?php echo $computerInfo['id']; ?>">
	<label for="computer">Allocate to: </label><input type="text" name="computer" value="<?php echo $computerInfo['name']; ?>" DISABLED>
	
	<?php
	$query = mysql_query("SELECT licenses.id, licenses.license, orders.orderno, products.friendlyName FROM ".$SQL['DATABASE'].".licenses as licenses LEFT JOIN ".$SQL['DATABASE'].".computers as computers ON licenses.computerid = computers.id LEFT JOIN ".$SQL['DATABASE'].".orders as orders ON licenses.orderid = orders.id LEFT JOIN ".$SQL['DATABASE'].".products as products ON licenses.product = products.id WHERE licenses.computerid = '0' ORDER BY orders.date DESC");
	
	echo '<table id="results">';
	echo '<tr id="header">';
	echo '<td></td><td>Order Number Used</td><td>Product</td><td>Key</td>';
	echo '</tr>';
	
	if (mysql_num_rows($query) == 0) {
		echo '<tr><td colspan=4>No spare licenses found</td></tr>';
	} else {
		$i = "colour1";
		while ($license = mysql_fetch_assoc($query)) {
			$colour = $i;
			echo '<tr class="'.$colour.'">';
			echo '<td><input type="checkbox" name="'.$license['id'].'" value="'.$license['id'].'"></td><td>'.$license['orderno'].'</td><td>'.$license['friendlyName'].'</td><td>'.$license['license'].'</td>';
			echo '</tr>';
			$i = ($i == "colour1") ? "colour2" : "colour1";
		}
	}
	?>
	</table>
	<input type="submit" value="Allocate"><br/>
	</form>
	<?php
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

function doViewLicense() {
	global $SQL;
	
	$id = $_GET['id'];
	
	$query = mysql_query("SELECT licenses.id, licenses.license, licenses.computerid, licenses.orderid, computers.name, orders.orderno, products.friendlyName FROM ".$SQL['DATABASE'].".licenses as licenses LEFT JOIN ".$SQL['DATABASE'].".computers as computers ON licenses.computerid = computers.id LEFT JOIN ".$SQL['DATABASE'].".orders as orders ON licenses.orderid = orders.id LEFT JOIN ".$SQL['DATABASE'].".products as products ON licenses.product = products.id WHERE licenses.id = '".mysql_real_escape_string($id)."'");
	$license = mysql_fetch_assoc($query);
	if (!empty($license['name'])) {
		echo '<div><b>Allocated to: </b><a href="?p=computers&a=view&id='.$license['computerid'].'">'.$license['name'].'</a> - [ <a href="?p=licenses&a=remove&id='.$license['id'].'">Remove Association</a> ]</div>';
	} else {
		echo '<div><b>Allocated to: </b>&nbsp;</div>';
	}
	echo '<div>&nbsp;</div>';
	
	if (!empty($license['orderno'])) {
		echo '<div><b>Order Number: </b><a href="?p=orders&a=view&id='.$license['orderid'].'">'.$license['orderno'].'</a></div>';
	} else {
		echo '<div><b>Order Number: </b>&nbsp;</div>';
	}
	echo '<div>&nbsp;</div>';
	echo '<div><b>Product: </b>'.$license['friendlyName'].'</div>';
	echo '<div>&nbsp;</div>';
	echo '<div><b>Code: </b>'.$license['license'].'</div>';
	echo '<form method="post" action="?p=licenses&a=delete&id='.$license['id'].'">';
	echo '<br>';
	echo '<input type="submit" value="Delete License"><b> - <span style="color: red;">Please note this will remove any association for this current license and then remove the license altogether</span></b><br/>';
	echo '</form>';

}

function doFindLicense() {
	global $SQL, $config;
	
	if (isset($_POST['query'])) {
		$queryString = (!empty($_POST['query']) ? $_POST['query'] : '%');
		$state = $_POST['state'];
		$queryProduct = $_POST['product'];
		$queryType = $_POST['queryType'];
	} elseif (isset($_GET['query'])) {
		$queryString = $_GET['query'];
		$state = $_GET['state'];
		$queryType = $_GET['queryType'];
		$queryProduct = $_GET['product'];
	}
	
	if (!$state) { $state = 'spare'; }
	if (!$queryProduct) { $queryProduct = '%'; }
	
	if ($queryString) {
		if ($state == 'spare') {
			$stateString = 'Spare';
			$searchString = "= 0";
		} elseif ($state == 'allocated') {
			$stateString = 'Allocated';
			$searchString = " > 0";
		} else {
			//Dedeclare this in case someone has given us a bogus value
			$stateString = 'All';
			$searchString = "LIKE '%'";	
		}
	}
	
	//Check that we have not been given a bogus value
	if ($queryType != 'match' && $queryType != 'dmatch') { $queryType = 'match'; }
	
	$productQuery = mysql_query("SELECT id,friendlyName FROM ".$SQL['DATABASE'].".products ORDER BY friendlyName ASC");
	?>
	<form method="post" action="?p=licenses&a=find">
	<label for="query">Search for: </label><input type="text" name="query" value="<?php echo $queryString; ?>">
	<span class="infoText">% is considered a wildcard</span>
	
	<br/>
	
	<label for="product">Show only: </label><select name="product"><option value="%">All products</option><?php
		while ($product = mysql_fetch_assoc($productQuery)) {
			echo '<option value="'.$product['id'].'"';
			if (isset($queryProduct) && $queryProduct == $product['id']) {
				echo ' SELECTED';
			}
			echo '>'.$product['friendlyName'].'</option>';
			echo "\n";
		}
	?></select><br/>
	
	<label for="all">Show all</label><input type="radio" id="all" name="state" value="all"<?php echo ($state == 'all') ? ' CHECKED' : ''; ?>>
	<label for="spare">Show Spare</label><input type="radio" name="state" id="spare" value="spare"<?php echo ($state == 'spare') ? ' CHECKED' : ''; ?>>
	<label for="allocated">Show Allocated</label><input type="radio" name="state" id="allocated" value="allocated"<?php echo ($state == 'allocated') ? ' CHECKED' : ''; ?>>
	
	<p>&nbsp;</p>

	<label for="queryType">Query type: </label><select name="queryType">
		<option value="match"<?php echo ($queryType == 'match' ? ' SELECTED' : '');?>>Match</option>
		<option value="dmatch"<?php echo ($queryType == 'dmatch' ? ' SELECTED' : '');?>>Does not match</option>
	</select><br>
	<span class="infoText">"Match" will list all results that match above criteria, "Does not match" will list everything else</span>
	
	<p><input class="button" type="submit" value="Search"></p>
	</form>
	<?php

	$linkURL = '?p=licenses&a=find&query='.urlencode($queryString).'&state='.urlencode($state).'&product='.urlencode($queryProduct).'&queryType='.urlencode($queryType);
	
	if ($_POST) {
		header('Location: '.(dirname($_SERVER['SCRIPT_NAME']) != '/' ? dirname($_SERVER['SCRIPT_NAME']) : '').'/'.$linkURL);
	}
	
	if (isset($queryString)) {
		$queryString = mysql_real_escape_string($queryString);
		$state = mysql_real_escape_string($state);
		$queryProduct = mysql_real_escape_string($queryProduct);
		//TODO: Does not match
		$query = mysql_query("SELECT licenses.id, licenses.license, computers.name, orders.orderno, products.friendlyName FROM ".$SQL['DATABASE'].".licenses as licenses LEFT JOIN ".$SQL['DATABASE'].".computers as computers ON licenses.computerid = computers.id LEFT JOIN ".$SQL['DATABASE'].".orders as orders ON licenses.orderid = orders.id LEFT JOIN ".$SQL['DATABASE'].".products as products ON licenses.product = products.id WHERE (computers.name LIKE '".$queryString."' OR computers.serial LIKE '".$queryString."' OR orders.orderno LIKE '".$queryString."' OR orders.reference LIKE '".$queryString."' OR licenses.license LIKE '".$queryString."') AND licenses.computerid ".$searchString." AND products.id LIKE '".$queryProduct."' ORDER BY orders.date DESC");
		
		$resultCount = mysql_num_rows($query);
		echo '<p>Found '.$resultCount.' result(s) for <b>'.$stateString.'</b> - <a href="'.$linkURL.'">Link this query</a> (for Bookmarks or sharing)</p>';
		
		echo '<table id="linkresults">';
		echo '<tr id="header">';
		echo '<td>Allocated To</td><td>Order Number Used</td><td>Product</td><td>Key</td>';
		echo '</tr>';
		//TODO: Page Results
		
		if ($resultCount == 0) {
			echo '<tr class="noHover"><td colspan="4">No Results to display</td></tr>';
		} else {
			$i = "colour1";
			while ($info = mysql_fetch_assoc($query)) {
				$colour = $i;
				if ($info['name'] == null) { $colour = "colour4"; }
				if ($config['linkType'] == 'inline') {
					echo '<tr class="'.$colour.'" onclick="javascript:window.location=\'?p=licenses&a=view&id='.$info['id'].'\';">';
				} elseif ($config['linkType'] == 'popout') {
					echo '<tr class="'.$colour.'" onclick="javascript:popout(\'?p=licenses&a=view&id='.$info['id'].'\');">';
				}
				echo '<td>'.((!empty($info['name']) ? $info['name'] : 'n/a (Spare)')).'</td><td>'.((!empty($info['orderno']) ? $info['orderno'] : 'n/a')).'</td><td>'.$info['friendlyName'].'</td><td>'.$info['license'].'</td>';
				echo '</tr>';
				
				$i = ($i == "colour1") ? "colour2" : "colour1";
			}
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
