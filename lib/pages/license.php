<?php
require_once(dirname(__FILE__)."/../../lib/functions/mysqlConnect.php");
require_once(dirname(__FILE__)."/../../lib/functions/redirect.php");

function initPage() {
	global $PAGE_CATEGORY, $PAGE_SUB_CATEGORY, $SQL;
	$PAGE_CATEGORY = "Licenses";
	if ($_GET['a'] == "new") {
		$PAGE_SUB_CATEGORY = "New License";
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
  return 'License Management';
}

function showPageBody() {
	if ($_GET['a'] == "new") {
		doNewLicense();
	} elseif ($_GET['a'] == "find") {
		doFindLicense();
	} elseif ($_GET['a'] == "view") {
		doViewLicense();
	} elseif ($_GET['a'] == "edit") {
		doEditLicense();
	} elseif ($_GET['a'] == "allocate") {
		doAllocateLicense();
	} elseif ($_GET['a'] == "remove") {
		doRemoveLicense();
	} elseif ($_GET['a'] == "delete") {
		doDeleteLicense();
	} else {
		redirect('?p=license&a=find');
	}
	
}

function doViewLicense() {
	global $SQL;
	
	$id = mysql_real_escape_string($_GET['id']);
	
	$query = mysql_query("SELECT licenses.id, licenses.license, licenses.computerid, licenses.orderid, computers.name, orders.orderno, products.friendlyName FROM ".$SQL['DATABASE'].".licenses as licenses LEFT JOIN ".$SQL['DATABASE'].".computers as computers ON licenses.computerid = computers.id LEFT JOIN ".$SQL['DATABASE'].".orders as orders ON licenses.orderid = orders.id LEFT JOIN ".$SQL['DATABASE'].".products as products ON licenses.product = products.id WHERE licenses.id = '".$id."'");
	$license = mysql_fetch_assoc($query);
	if (!empty($license['name'])) {
		echo '<div><b>Allocated to: </b><a href="?p=computer&a=view&id='.$license['computerid'].'">'.$license['name'].'</a> - [ <a href="?p=license&a=remove&id='.$license['id'].'">Remove Association</a> ]</div>';
	} else {
		echo '<div><b>Allocated to: </b>&nbsp;</div>';
	}
	echo '<div>&nbsp;</div>';
	
	if (!empty($license['orderno'])) {
		echo '<div><b>Order Number: </b><a href="?p=order&a=view&id='.$license['orderid'].'">'.$license['orderno'].'</a></div>';
	} else {
		echo '<div><b>Order Number: </b>&nbsp;</div>';
	}
	echo '<div>&nbsp;</div>';
	echo '<div><b>Product: </b>'.$license['friendlyName'].'</div>';
	echo '<div>&nbsp;</div>';
	echo '<div><b>Code: </b>'.$license['license'].'</div>';
	echo '<form method="post" action="?p=license&a=delete&id='.$license['id'].'">';
	echo '<br>';
	echo '<input type="submit" value="Delete License"><b> - <span style="color: red;">Please note this will remove any association for this current license and then remove the license altogether</span></b><br/>';
	echo '</form>';

}

function doEditLicense() {
	echo 'Not yet implemented';
}

function doRemoveLicense() {
	global $SQL;
	
	$id = mysql_real_escape_string($_GET['id']);
	
	//$query = mysql_query("SELECT id, computerid FROM ".$SQL['DATABASE'].".licenses WHERE id = '".$id."'");
	$query = mysql_query("SELECT licenses.id, licenses.computerid, licenses.license, products.friendlyName FROM ".$SQL['DATABASE'].".licenses LEFT JOIN ".$SQL['DATABASE'].".products ON licenses.product = products.id WHERE licenses.id = '".$id."' LIMIT 0,1");
	$license = mysql_fetch_assoc($query);
	if (empty($license['id'])) {
		echo '<p>ID not in database</p>';
	} else {
		mysql_query("UPDATE ".$SQL['DATABASE'].".licenses SET `computerid` = '0' WHERE id = '".$id."'");
		updateComputerChangeLog($license['computerid'], 'License', '', 'Removed '.$license['friendlyName'].' License');
		redirect('?p=computer&a=view&id='.$license['computerid']);
	}
}

function doDeleteLicense() {
	global $SQL;
	
	$id = mysql_real_escape_string($_GET['id']);
	
	//$query = mysql_query("SELECT id, computerid FROM ".$SQL['DATABASE'].".licenses WHERE id = '".$id."'");
	$query = mysql_query("SELECT licenses.id, licenses.computerid, licenses.license, licenses.orderid, products.friendlyName FROM ".$SQL['DATABASE'].".licenses LEFT JOIN ".$SQL['DATABASE'].".products ON licenses.product = products.id WHERE licenses.id = '".$id."' LIMIT 0,1");
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
		mysql_query("DELETE FROM ".$SQL['DATABASE'].".licenses WHERE licenses.id = '".$id."'");
		if ($license['computerid'] > 0) {
			redirect('?p=computer&a=view&id='.$license['computerid']);
		} elseif ($license['orderid'] > 0) {
			redirect('?p=order&a=view&id='.$license['orderid']);
		} else {
			redirect('?p=license&a=find');
		}
	}
}


function updateChangeLog($licenseID, $field, $old, $new) {
	echo 'Not yet implemented';
}

function doAllocateLicense() {
	global $SQL;

	if ($_GET['t'] == "confirm") {	
		$id = mysql_real_escape_string($_POST['id']);
		$computerQuery = mysql_query("SELECT name FROM ".$SQL['DATABASE'].".computers WHERE id = '".$id."' LIMIT 0,1");
		$computerInfo = mysql_fetch_assoc($computerQuery);
		if (empty($computerInfo['name'])) {
			echo '<p>Computer ID not in database</p>';
			return;
		}
		
		$changed = false;
		foreach ($_POST as $key => $value) {
			$value = mysql_real_escape_string($value);
			if (is_numeric($key)) {
				$changed = true;
				$query = mysql_query("SELECT licenses.id, licenses.license, products.friendlyName FROM ".$SQL['DATABASE'].".licenses LEFT JOIN ".$SQL['DATABASE'].".products ON licenses.product = products.id WHERE licenses.id = '".$value."' LIMIT 0,1");
				$license = mysql_fetch_assoc($query);
				if (empty($license['license'])) {
					echo '<p>Error detecting license, please try again</p>';
					return;
				} else {
					mysql_query("UPDATE ".$SQL['DATABASE'].".licenses SET `computerid` = '".$id."' WHERE licenses.id = '".$value."'");
					updateComputerChangeLog($id, 'License', '', 'Added '.$license['friendlyName'].' License');
					redirect('?p=computer&a=view&id='.$id);
				}
			}
		}
		if ($changed == false) {
			echo '<p>No licenses selected</p>';
			return;
		}
	} else {
		$id = mysql_real_escape_string($_GET['id']);
		$computerQuery = mysql_query("SELECT id, name FROM ".$SQL['DATABASE'].".computers WHERE id = '".$id."' LIMIT 0,1");
		$computerInfo = mysql_fetch_assoc($computerQuery);
		?>
		<form method="post" action="?p=license&a=allocate&t=confirm">
		<input type="hidden" name="id" value="<?php echo $computerInfo['id']; ?>">
		<div>Allocate to: </div><input type="text" name="count" value="<?php echo $computerInfo['name']; ?>" DISABLED><br/>
		<?php
		$query = mysql_query("SELECT licenses.id, licenses.license, orders.orderno, products.friendlyName FROM ".$SQL['DATABASE'].".licenses as licenses LEFT JOIN ".$SQL['DATABASE'].".computers as computers ON licenses.computerid = computers.id LEFT JOIN ".$SQL['DATABASE'].".orders as orders ON licenses.orderid = orders.id LEFT JOIN ".$SQL['DATABASE'].".products as products ON licenses.product = products.id WHERE licenses.computerid = '0' ORDER BY orders.date DESC");
		
		echo '<table id="results">';
		echo '<tr id="headers">';
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
}

function doNewLicense() {
	global $SQL;
	
	$_GET['id'] = mysql_real_escape_string($_GET['id']);
	if ($_GET['t'] == "confirm") {
		foreach ($_POST as $key => $value) {
			$_POST[$key] = mysql_real_escape_string($value);
		}
		$orderID = 0;
		if (!empty($_POST['order_no'])) {
			$orderQuery = mysql_query("SELECT id FROM ".$SQL['DATABASE'].".orders WHERE orderno = '".$_POST['order_no']."' LIMIT 0,1");
			$orderInfo = mysql_fetch_assoc($orderQuery);
			$orderID = $orderInfo['id'];
		}
		$postCount = (count($_POST) - 1) / 2;
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
			echo '<p>Please ensure you provide license information</p>';
			return;
		}
		foreach ($product as $key => $value) {
			if (empty($productKey[$key])) {
				$productQuery = mysql_query("SELECT friendlyName, defaultKey FROM ".$SQL['DATABASE'].".products WHERE id = '".$product[$key]."' LIMIT 0,1");
				$productInfo = mysql_fetch_assoc($productQuery);
				if (empty($productInfo['defaultKey'])) {
					echo '<p>You did not provide a key for item number '.$key.': <b>'.$productInfo['friendlyName'].'</b></p>';
					return;
				} else {
					$productKey[$key] = $productInfo['defaultKey'];
				}
			}
			$query = mysql_query("INSERT INTO ".$SQL['DATABASE'].".licenses (
			`id` ,
			`computerid` ,
			`orderid` ,
			`product` ,
			`license`
			) VALUES ( NULL ,
			'0',
			'".$orderID."',
			'".$product[$key]."',
			'".$productKey[$key]."')");
		}
		if ($orderID > 0) {
			updateOrderChangeLog($orderID, 'License', '', 'Added '.$postCount.' Licenses');
			redirect('?p=license&a=find&s=spare&query='.$_POST['order_no']);
		} else {
			redirect('?p=license&a=find&s=spare&query=%');
		}
		return;
	}
	
	$licenseQuery = mysql_query("SELECT id, friendlyName, defaultKey FROM ".$SQL['DATABASE'].".products ORDER BY friendlyName ASC LIMIT 0,9999");
	while ($license = mysql_fetch_assoc($licenseQuery)) {
		$productList[$license['id']] = (!empty($license['defaultKey'])) ? '(K) '.$license['friendlyName'].'' : $license['friendlyName'];
	}
	$licenseCount = (is_numeric($_POST['count'])) ? $_POST['count'] : 1;
	?>
	<form method="post" action="?p=license&a=new">
	<?php
		if (is_numeric($_GET['id'])) {
			echo '<input type="hidden" name="id" value="'.$_GET['id'].'">';
		} elseif (is_numeric($_POST['id'])) {
			echo '<input type="hidden" name="id" value="'.$_POST['id'].'">';
		}
	?>
	<div>Number of license: </div><input type="text" name="count" size="1" value="<?php echo $licenseCount; ?>"><br/>
	<p><div>&nbsp;</div><input type="submit" value="Update"></form></p>
	<hr>
	<form method="post" action="?p=license&a=new&t=confirm">
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
	<div style="width: 200px;">Add licenses to order number: </div><input type="text" name="order_no" size="7"<?php echo (!empty($orderInfo['orderno'])) ? ' value="'.$orderInfo['orderno'].'" DISABLED' : '';?>><br/>
	
	<div>&nbsp;</div><br><br>
	<div>&nbsp;</div><p>(K) indicates there is a default key stored and you are not required to provide one.</p>
	<?php
		for ($i = 1; $i <= $licenseCount; $i++) {
			echo '<div>Product #'.$i.'</div>';
			echo '<select name="product'.$i.'">';
			echo '<option value="">Please choose...</option>';
			foreach ($productList as $key => $value) {
				echo '<option value="'.$key.'">'.$value.'</option>';
			}
			echo '</select>';
			echo '&nbsp;Key: <input type="text" name="key'.$i.'" size="40"><br/>';
		}
	?>
	<p><div>&nbsp;</div><input type="submit" value="Add Licenses"></form></p>
	</form>
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
		'".$_SESSION['displayname']."',
		'".$field."',
		'".$old."',
		'".$new."',
		'".time()."')");
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

function doFindLicense() {
	global $SQL;
	
	$state = 'spare';
	$queryProduct = '%';
	if (!empty($_POST['query'])) {
		$queryString = mysql_real_escape_string($_POST['query']);
		$state = mysql_real_escape_string($_POST['state']);
		$queryProduct = mysql_real_escape_string($_POST['product']);
	} elseif (isset($_GET['query'])) {
		$queryString = mysql_real_escape_string($_GET['query']);
		$state = mysql_real_escape_string($_GET['s']);
	} elseif (empty($_POST['query']) && is_string($_POST['query'])) {
		$queryString = mysql_real_escape_string("%");
		$state = mysql_real_escape_string($_POST['state']);
		$queryProduct = mysql_real_escape_string($_POST['product']);
	}
	
	if ($state == 'spare') {
		$searchString = "= 0";
	} elseif ($state == 'allocated') {
		$searchString = " > 0";
	} else {
		$searchString = "LIKE '%'";
	}
	$productQuery = mysql_query("SELECT id,friendlyName FROM ".$SQL['DATABASE'].".products ORDER BY friendlyName ASC LIMIT 0,9999");
	?>
	&nbsp;Use % for wildcard searching<br><br>
	<form method="post" action="?p=license&a=find">
	<div>Search for: </div><input type="text" name="query" value="<?php echo $queryString; ?>"><br/>
	<div>Show only: </div><select name="product"><option value="%">All products</option><?php
		while ($product = mysql_fetch_assoc($productQuery)) {
			echo '<option value="'.$product['id'].'"';
			if (isset($queryProduct) && $queryProduct == $product['id']) {
				echo ' SELECTED';
			}
			echo '>'.$product['friendlyName'].'</option>';
			echo "\n";
		}
	?></select><br/>
	<div>&nbsp;</div><input type="radio" id="all" name="state" value="all"<?php echo ($state == 'all') ? ' CHECKED' : ''; ?>><label for="all">All</label>
	<input type="radio" name="state" id="spare" value="spare"<?php echo ($state == 'spare') ? ' CHECKED' : ''; ?>><label for="spare">Spare Licenses</label>
	<input type="radio" name="state" id="allocated" value="allocated"<?php echo ($state == 'allocated') ? ' CHECKED' : ''; ?>><label for="allocated">Allocated Licenses</label>
	<br/><br/>
	<p><input type="submit" value="Search"></p>
	</form>
	<?php

	
	if (isset($queryString)) {
		$query = mysql_query("SELECT licenses.id, licenses.license, computers.name, orders.orderno, products.friendlyName FROM ".$SQL['DATABASE'].".licenses as licenses LEFT JOIN ".$SQL['DATABASE'].".computers as computers ON licenses.computerid = computers.id LEFT JOIN ".$SQL['DATABASE'].".orders as orders ON licenses.orderid = orders.id LEFT JOIN ".$SQL['DATABASE'].".products as products ON licenses.product = products.id WHERE (computers.name LIKE '".$queryString."' OR computers.serial LIKE '".$queryString."' OR orders.orderno LIKE '".$queryString."' OR orders.reference LIKE '".$queryString."' OR licenses.license LIKE '".$queryString."') AND licenses.computerid ".$searchString." AND products.id LIKE '".$queryProduct."' ORDER BY orders.date DESC");
		echo '<p>Found '.mysql_num_rows($query).' result(s) in <b>'.$_POST['state'].'</b></p>';
		echo '<table id="linkresults">';
		echo '<tr id="headers">';
		echo '<td>Allocated To</td><td>Order Number Used</td><td>Product</td><td>Key</td>';
		echo '</tr>';
		
		$i = "colour1";
		while ($info = mysql_fetch_assoc($query)) {
			$colour = $i;
			//This license is not allocated to make it green
			if ($info['name'] == null) { $colour = "colour4"; }
			echo '<tr class="'.$colour.'" onclick="javascript:popout(\'?p=license&a=view&id='.$info['id'].'\');">';
			//echo '<tr class="'.$colour.'">';
			echo '<td>'.((!empty($info['name']) ? $info['name'] : 'n/a (Spare)')).'</td><td>'.((!empty($info['orderno']) ? $info['orderno'] : 'n/a')).'</td><td>'.$info['friendlyName'].'</td><td>'.$info['license'].'</td>';
			echo '</tr>';
			
			$i = ($i == "colour1") ? "colour2" : "colour1";
		}
		echo '</table>';
	}
	
}

?>

