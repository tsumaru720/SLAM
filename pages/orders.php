<?php

function getPageTitle() {
	return 'Order Tracking';
}

function doHeader() {
	global $activePage, $activeMenu;

	echo '<link rel="stylesheet" type="text/css" href="css/form.css">';
	echo '<link rel="stylesheet" type="text/css" href="css/results.css">';
	echo '<script src="script/popout.js"></script>';

	$activePage = 'orders';

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
	'Find Order' => array(
		'url' => '?p=orders&amp;a=find',
		'alias' => 'find',
	),
	'Add Order' => array(
		'url' => '?p=orders&amp;a=new',
		'alias' => 'new',
	),
);
}

function getBody() {
	if ($_GET['a'] == "new") {
		doNewOrder();
	} elseif ($_GET['a'] == "edit") {
		doEditOrder();
	} elseif ($_GET['a'] == "find") {
		doFindOrder();
	} elseif ($_GET['a'] == "view") {
		doViewOrder();
	} else {
		//Default action
		doFindOrder();
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

function doNewOrder() {
	global $SQL, $errorMsg;
	
	if (empty($errorMsg)) {
		if ($_GET['t'] == "confirm") {
			if (!empty($_POST['order_no'])) { $_POST['order_no'] = strtoupper($_POST['order_no']); }
			if (!empty($_POST['reference'])) { $_POST['reference'] = strtoupper($_POST['reference']); }
			if ($_POST['supplier'] == 'other') {
				if (!empty($_POST['supplier_other'])) {
					$_POST['supplier'] = $_POST['supplier_other'];
					$query = mysql_query("SELECT friendlyName FROM ".$SQL['DATABASE'].".orderSuppliers WHERE friendlyName = '".mysql_real_escape_string($_POST['supplier'])."' LIMIT 0,1");	
					if (mysql_num_rows($query) == 0) {
						$query = mysql_query("INSERT INTO ".$SQL['DATABASE'].".orderSuppliers (
							`id` ,
							`friendlyName`
							)
							VALUES (
							NULL , '".mysql_real_escape_string($_POST['supplier'])."'
						)");
					}
				} else {
					echo '<p>Please ensure you provide valid supplier name</p>';
					return;
				}
			}

			if (!is_bool(filter_var($_POST['licenses'], FILTER_VALIDATE_BOOLEAN))) {
				showError('"Contains Licenses" value was not recognised',true);
				return;
			}

			if (!is_numeric($_POST['cost'])) {
				showError('Please ensure you provide valid cost', true);
				return;
			}
			if (empty($_POST['ordered'])) {
				showError('Your order must have detail of what is being ordered',true);
				return;
			}

			$complete = 0;
			$cancelled = 0;

			$_POST = sanitize($_POST);
			
			$query = mysql_query("INSERT INTO ".$SQL['DATABASE'].".orders (
									`id`,
									`orderno`,
									`reference`,
									`date`,
									`enteredby`,
									`orderedfor`,
									`authby`,
									`supplier`,
									`enduser`,
									`containslicenses`,
									`products`,
									`cost`,
									`status`,
									`completed`,
									`cancelled`,
									`nominalcode`,
									`comments`,
									`confirmed`
									) VALUES (NULL, 
									'".$_POST['order_no']."',
									'".$_POST['reference']."',
									'".time()."',
									'".$_SESSION['displayName']."',
									'".$_POST['ordered_for']."',
									'".$_POST['auth_by']."',
									'".$_POST['supplier']."',
									'".$_POST['end_user']."',
									'".$_POST['licenses']."',
									'".$_POST['ordered']."',
									'".$_POST['cost']."',
									'".$_POST['status']."',
									'".$complete."',
									'".$cancelled."',
									'".$_POST['nominal_code']."',
									'".$_POST['comments']."',
									'0')");
			echo '<p><b><a href="?p=orders&a=view&id='.mysql_insert_id().'">Your Order</a></b> has successfully been added to the database</p>';
			$_POST = array();
		}
	}
	
	$authorizeQuery = mysql_query("SELECT friendlyName FROM ".$SQL['DATABASE'].".ordersCanAuthorize ORDER BY friendlyName ASC");
	$statusQuery = mysql_query("SELECT friendlyName FROM ".$SQL['DATABASE'].".orderStatuses ORDER BY friendlyName asc");
	$suppliersQuery = mysql_query("SELECT friendlyName FROM ".$SQL['DATABASE'].".orderSuppliers ORDER BY friendlyName asc");

	if (!empty($errorMsg)) {
		echo '<p><span style="color: red;">'.$errorMsg.'</span></p>';
	}
	?>
	
	<form method="post" action="?p=orders&a=new&t=confirm">

	<label for="order_no">Order number: </label><input type="text" name="order_no" value="<?php echo $_POST['order_no']; ?>">
	
	<br/>

	<label for="reference">Reference: </label><input type="text" name="reference" value="<?php echo $_POST['reference']; ?>">

	<br/>

	<label for="ordered_for">Ordered for: </label><input type="text" name="ordered_for" value="<?php echo $_POST['ordered_for']; ?>">

	<br/>

	<label for="auth_by">Authorized by: </label><select name="auth_by">
	<option value="none">n/a</option><?php
		while ($auth = mysql_fetch_assoc($authorizeQuery)) {
			echo '<option value="'.$auth['friendlyName'].'"'.(($_POST['auth_by'] == $auth['friendlyName']) ? ' SELECTED' : '').'>'.$auth['friendlyName'].'</option>';
		}
	?></select>

	<br/> <br>

	<label for="supplier">Supplier: </label><select name="supplier"><?php
		while ($supplier = mysql_fetch_assoc($suppliersQuery)) {
			echo '<option value="'.$supplier['friendlyName'].'"'.(($_POST['supplier'] == $supplier['friendlyName']) ? ' SELECTED' : '').'>'.$supplier['friendlyName'].'</option>';
		}
	?>
	<option value="other">Other supplier ... (Please detail)</option>
	</select>

	<br/>

	<label for="supplier_other"><i>Other supplier</i>: </label><input type="text" name="supplier_other" value="<?php echo $_POST['supplier_other']; ?>"><span class="infoText">Only use this when "Supplier" is set to other</span>

	<br/> <br>

	<label for="end_user">End user: </label><input type="text" name="end_user" value="<?php echo $_POST['end_user']; ?>">

	<br/>

	<label for="licenses" style="vertical-align: top;">Order contains licenses: </label><input type="checkbox" name="licenses" value="1"<?php if (!empty($_POST['licenses']) || $_POST['licenses'] == 1) { echo ' CHECKED'; }?>><span class="infoText"><-- Tick this if the order contains licensed software</span>

	<br/>

	<label for="cost">Total Cost: </label><input type="text" name="cost" value="<?php echo $_POST['cost']; ?>"><span class="infoText">Without &pound; symbol</span>

	<br/>

	<label for="status">Order status: </label><select name="status"><?php
		while ($status = mysql_fetch_assoc($statusQuery)) {
			echo '<option value="'.$status['friendlyName'].'"'.(($_POST['status'] == $status['friendlyName']) ? ' SELECTED' : '').'>'.$status['friendlyName'].'</option>';
		}
	?></select>

	<br/>

	<label for="nominal_code">Nominal code: </label><input type="text" name="nominal_code" value="<?php echo $_POST['nominal_code']; ?>">

	<br/>

	<label for="ordered" style="vertical-align: top;">Order details: </label><textarea name="ordered" rows="15" cols="80"><?php echo $_POST['ordered']; ?></textarea>

	<br/>

	<label for="comments" style="vertical-align: top;">Comments: </label><textarea name="comments" rows="15" cols="80"><?php echo $_POST['comments']; ?></textarea>
	
	<p><input class="button" type="submit" value="Add"></p>
	</form>
	<?php

}

function doEditOrder() {
	global $SQL, $errorMsg;

	if (!empty($_POST['id'])) {
		$id = $_POST['id'];
	} elseif (isset($_GET['id']) && !empty($_GET['id'])) {
		$id = $_GET['id'];
	} else {
		echo '<p>There was an error getting the information to view, please go back and try again.</p>';
		return;
	}
	
	$query = mysql_query("SELECT * FROM ".$SQL['DATABASE'].".orders WHERE id = '".mysql_real_escape_string($id)."' LIMIT 0,1");

	if (mysql_num_rows($query) == 0) {
		echo '<p>This ID is not in the Database!</p>';
		return;
	}
	
	$info = mysql_fetch_assoc($query);
	$display = $info;

	if ($_POST) {
		$display['orderno'] = $_POST['order_no'];
		$display['reference'] = $_POST['reference'];
		$display['orderedfor'] = $_POST['ordered_for'];
		$display['authby'] = $_POST['auth_by'];
		$display['supplier'] = $_POST['supplier'];
		$display['enduser'] = $_POST['end_user'];
		$display['containslicenses'] = filter_var($_POST['licenses'], FILTER_VALIDATE_BOOLEAN);
		$display['products'] = $_POST['ordered'];
		$display['cost'] = $_POST['cost'];
		$display['status'] = $_POST['status'];
		$display['nominalcode'] = $_POST['nominal_code'];
		$display['comments'] = $_POST['comments'];
		$display['confirmed'] = filter_var($_POST['confirmed'], FILTER_VALIDATE_BOOLEAN);
	}

	if (empty($errorMsg)) {	
		if ($_GET['t'] == 'confirm') {
			if (!empty($_POST['order_no'])) { $_POST['order_no'] = strtoupper($_POST['order_no']); }
			if (!empty($_POST['reference'])) { $_POST['reference'] = strtoupper($_POST['reference']); }

			if ($_POST['supplier'] == 'other') {
				if (!empty($_POST['supplier_other'])) {
					$_POST['supplier'] = $_POST['supplier_other'];
					$query = mysql_query("SELECT friendlyName FROM ".$SQL['DATABASE'].".orderSuppliers WHERE friendlyName = '".mysql_real_escape_string($_POST['supplier'])."' LIMIT 0,1");
					if (mysql_num_rows($query) == 0) {
						$query = mysql_query("INSERT INTO ".$SQL['DATABASE'].".orderSuppliers (
							`id` ,
							`friendlyName`
							)
							VALUES (
							NULL , '".mysql_real_escape_string($_POST['supplier'])."'
						)");
					}
				} else {
					showError('Please ensure you provide valid supplier name',true);
					return;
				}
			}

			$old['orderno'] = $info['orderno'];
			$old['reference'] = $info['reference'];
			$old['orderedfor'] = $info['orderedfor'];
			$old['authby'] = $info['authby'];
			$old['supplier'] = $info['supplier'];
			$old['enduser'] = $info['enduser'];
			$old['containslicenses'] = $info['containslicenses'];
			$old['products'] = $info['products'];
			$old['cost'] = $info['cost'];
			$old['status'] = $info['status'];
			$old['completed'] = $info['completed'];
			$old['cancelled'] = $info['cancelled'];
			$old['nominalcode'] = $info['nominalcode'];
			$old['comments'] = $info['comments'];
			$old['confirmed'] = $info['confirmed'];
			
			foreach ($old as $key => $value) {
				$old[$key] = mysql_real_escape_string($value);
			}
			
			$new['orderno'] = $_POST['order_no'];
			$new['reference'] = $_POST['reference'];
			$new['orderedfor'] = $_POST['ordered_for'];
			$new['authby'] = $_POST['auth_by'];
			$new['supplier'] = $_POST['supplier'];
			$new['enduser'] = $_POST['end_user'];
			$new['containslicenses'] = filter_var($_POST['licenses'], FILTER_VALIDATE_BOOLEAN);
			$new['products'] = $_POST['ordered'];
			$new['cost'] = $_POST['cost'];
			$new['status'] = $_POST['status'];
			if ($new['status'] == "Complete") {
				$new['completed'] = true;
				$new['cancelled'] = false;
			} elseif ($new['status'] == "Cancelled") {
				$new['completed'] = false;
				$new['cancelled'] = true;
			} else {
				$new['completed'] = false;
				$new['cancelled'] = false;
			}
			$new['nominalcode'] = $_POST['nominal_code'];
			$new['comments'] = $_POST['comments'];
			$new['confirmed'] = filter_var($_POST['confirmed'], FILTER_VALIDATE_BOOLEAN);
			
			foreach ($new as $key => $value) {
				if (!is_bool($value)) {
					$new[$key] = mysql_real_escape_string($value);
				}
			}

			if (!is_bool($new['containslicenses'])) {
				showError('"Contains Licenses" value was not recognised',true);
				return;
			}

			if (!is_numeric($new['cost'])) {
				showError('Please ensure you provide valid cost', true);
				return;
			}
			if (empty($new['products'])) {
				showError('Your order must have detail of what is being ordered',true);
				return;
			}


			$changed = false;
			$redirect = '?p=orders&a=view&id='.$id;
			
			if (strtoupper($old['orderno']) != strtoupper($new['orderno'])) {
				$changed = true;
				updateChangeLog($id, "Order Number", $old['orderno'], $new['orderno']);
			}
			if (strtoupper($old['reference']) != strtoupper($new['reference'])) {
				$changed = true;
				updateChangeLog($id, "Reference", $old['reference'], $new['reference']);
			}
			if ($old['orderedfor'] != $new['orderedfor']) {
				$changed = true;
				updateChangeLog($id, "Ordered For", $old['orderedfor'], $new['orderedfor']);
			}
			if ($old['authby'] != $new['authby']) {
				$changed = true;
				updateChangeLog($id, "Authorized By", $old['authby'], $new['authby']);
			}
			if ($old['supplier'] != $new['supplier']) {
				$changed = true;
				updateChangeLog($id, "Supplier", $old['supplier'], $new['supplier']);
			}
			if ($old['enduser'] != $new['enduser']) {
				$changed = true;
				updateChangeLog($id, "End User", $old['enduser'], $new['enduser']);
			}
			if ($old['containslicenses'] != $new['containslicenses']) {
				$changed = true;
				updateChangeLog($id, "Order Contains Licenses", ($old['containslicenses'] == '1' ? 'Yes' : 'No'), ($new['containslicenses'] == '1' ? 'Yes' : 'No'));
			}
			if ($old['products'] != $new['products']) {
				$changed = true;
				updateChangeLog($id, "Products", $old['products'], $new['products']);
			}
			if ($old['cost'] != $new['cost']) {
				$changed = true;
				updateChangeLog($id, "Cost", $old['cost'], $new['cost']);
			}
			if ($old['status'] != $new['status']) {
				if ($new['status'] == "Complete" && $new['containslicenses']) {
					$redirect = '?p=licenses&a=new&id='.$id;
				}
				$changed = true;
				updateChangeLog($id, "Status", $old['status'], $new['status']);
			}
			if ($old['nominalcode'] != $new['nominalcode']) {
				$changed = true;
				updateChangeLog($id, "Nominal Code", $old['nominalcode'], $new['nominalcode']);
			}
			if ($old['comments'] != $new['comments']) {
				$changed = true;
				updateChangeLog($id, "Comments", (empty($old['comments'])) ? 'None' : $old['comments'], (empty($new['comments'])) ? 'None' : $new['comments']);
			}
			
			if ($changed == true) {
				$query = mysql_query("UPDATE ".$SQL['DATABASE'].".orders SET
										`orderno` = '".$new['orderno']."',
										`reference` = '".$new['reference']."',
										`orderedfor` = '".$new['orderedfor']."',
										`authby` = '".$new['authby']."',
										`supplier` = '".$new['supplier']."',
										`enduser` = '".$new['enduser']."',
										`containslicenses` = '".$new['containslicenses']."',
										`products` = '".$new['products']."',
										`cost` = '".$new['cost']."',
										`status` = '".$new['status']."',
										`completed` = '".$new['completed']."',
										`cancelled` = '".$new['cancelled']."',
										`nominalcode` = '".$new['nominalcode']."',
										`comments` = '".$new['comments']."',
										`confirmed` = '0'
										WHERE `orders`.`id` = '".$id."'");
				header('Location: '.(dirname($_SERVER['SCRIPT_NAME']) != '/' ? dirname($_SERVER['SCRIPT_NAME']) : '').'/'.$redirect);
			} elseif ($changed == false && $_POST['id']) {
				showError('Nothing has changed', true);
				return;
			}
			return;
		}
	}
	
	$authorizeQuery = mysql_query("SELECT friendlyName FROM ".$SQL['DATABASE'].".ordersCanAuthorize ORDER BY friendlyName ASC");
	$statusQuery = mysql_query("SELECT friendlyName FROM ".$SQL['DATABASE'].".orderStatuses ORDER BY friendlyName asc");
	$suppliersQuery = mysql_query("SELECT friendlyName FROM ".$SQL['DATABASE'].".orderSuppliers ORDER BY friendlyName asc");

	if (!empty($errorMsg)) {
		echo '<p><span style="color: red;">'.$errorMsg.'</span></p>';
	}
	?>
	<form method="post" action="?p=orders&a=edit&t=confirm"><input type="hidden" name="id" value="<?php echo $display['id'];?>">

	<label for="order_no">Order number: </label><input type="text" name="order_no" value="<?php echo $display['orderno'];?>"><br/>
	<label for="reference">Reference: </label><input type="text" name="reference" value="<?php echo $display['reference'];?>"><br/>
	<label for="ordered_for">Ordered for: </label><input type="text" name="ordered_for" value="<?php echo $display['orderedfor'];?>"><br/>
	<label for="auth_by">Authorized by: </label><select name="auth_by">
	<option value="none">n/a</option><?php
		while ($auth = mysql_fetch_assoc($authorizeQuery)) {
			echo '<option value="'.$auth['friendlyName'].'"';
			echo ($display['authby'] == $auth['friendlyName']) ? ' SELECTED' : '';
			echo '>'.$auth['friendlyName'].'</option>';
		}
	?></select><br/><br>
	<label for="supplier">Supplier: </label><select name="supplier"><?php
		while ($supplier = mysql_fetch_assoc($suppliersQuery)) {
			echo '<option value="'.$supplier['friendlyName'].'"';
			echo ($display['supplier'] == $supplier['friendlyName']) ? ' SELECTED' : '';
			echo '>'.$supplier['friendlyName'].'</option>';
		}
	?>
	<option value="other">Other supplier ... (Please detail)</option>
	</select><br/>
	<label for="supplier_other"><i>Other supplier</i>: </label><input type="text" name="supplier_other"><span class="infoText">Only use this when "Supplier" is set to other</span><br/><br>
	<label for="end_user">End user: </label><input type="text" name="end_user" value="<?php echo $display['enduser'];?>"><br/>
	<label for="licenses" style="vertical-align: top;">Order contains licenses: </label><input type="checkbox" name="licenses" value="1"<?php if ($display['containslicenses']) { echo ' CHECKED';} ?>><span class="infoText"><-- Tick this if the order contains licensed software</span><br/>
	<label for="cost">Total Cost: </label><input type="text" name="cost" value="<?php echo $display['cost'];?>"><span class="infoText">Without &pound; symbol</span><br/>
	<label for="status">Order status: </label><select name="status">
		<option value="Complete">Complete</option>
		<option value="Cancelled">Cancelled</option><?php
		while ($status = mysql_fetch_assoc($statusQuery)) {
			echo '<option value="'.$status['friendlyName'].'"';
			echo ($display['status'] == $status['friendlyName']) ? ' SELECTED' : '';
			echo '>'.$status['friendlyName'].'</option>';
		}
	?></select><br/>
	<label for="nominal_code">Nominal code: </label><input type="text" name="nominal_code" value="<?php echo $display['nominalcode'];?>"><br/>
	<label for="ordered" style="vertical-align: top;">Order details: </label><textarea name="ordered" rows="15" cols="80"><?php echo $display['products'];?></textarea><br/>
	<label for="comments" style="vertical-align: top;">Comments: </label><textarea name="comments" rows="15" cols="80"><?php echo $display['comments'];?></textarea><br/>

	
	<p><input class="button" type="submit" value="Edit"></p>
	</form>
	<?php	
}

function updateChangeLog($orderID, $field, $old, $new) {
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

function doFindOrder() {
	global $SQL, $config;
	
	if (isset($_POST['query'])) {
		$queryString = (!empty($_POST['query']) ? $_POST['query'] : '%');
		$state = $_POST['state'];
		$queryType = $_POST['queryType'];
	} elseif (isset($_GET['query'])) {
		$queryString = $_GET['query'];
		$state = $_GET['state'];
		$queryType = $_GET['queryType'];
	}
	
	if (!$state) { $state = 'progress'; }
	
	if ($queryString) {
		if ($state == 'complete') {
			$stateString = 'Completed';
			$complete = 1;
			$cancelled = 0;
		} elseif ($state == 'cancelled') {
			$stateString = 'Cancelled';
			$complete = 0;
			$cancelled = 1;
		} elseif ($state == 'progress') {
			$stateString = 'In progress';
			$complete = 0;
			$cancelled = 0;
		} else {
			//Dedeclare this in case someone has given us a bogus value
			$stateString = 'All';
			$complete = '%';
			$cancelled = '%';	
		}
	}
	
	//Check that we have not been given a bogus value
	if ($queryType != 'match' && $queryType != 'dmatch') { $queryType = 'match'; }
	
	?>
	<form method="post" action="?p=orders&a=find">
	<label for="query">Search for: </label><input type="text" name="query" value="<?php echo $queryString; ?>">
	<span class="infoText">% is considered a wildcard</span>
	
	<br/>
	
	<label for="all">Show all</label><input type="radio" id="all" name="state" value="all"<?php echo ($state == 'all') ? ' CHECKED' : ''; ?>>
	<label for="progress">Show in progress</label><input type="radio" name="state" id="progress" value="progress"<?php echo ($state == 'progress') ? ' CHECKED' : ''; ?>>
	<label for="complete">Show completed</label><input type="radio" name="state" id="complete" value="complete"<?php echo ($state == 'complete') ? ' CHECKED' : ''; ?>>
	<label for="cancelled">Show cancelled</label><input type="radio" name="state" id="cancelled" value="cancelled"<?php echo ($state == 'cancelled') ? ' CHECKED' : ''; ?>>
	
	<p>&nbsp;</p>

	<label for="queryType">Query type: </label><select name="queryType">
		<option value="match"<?php echo ($queryType == 'match' ? ' SELECTED' : '');?>>Match</option>
		<option value="dmatch"<?php echo ($queryType == 'dmatch' ? ' SELECTED' : '');?>>Does not match</option>
	</select><br>
	<span class="infoText">"Match" will list all results that match above criteria, "Does not match" will list everything else</span>
	
	<p><input class="button" type="submit" value="Search"></p>
	</form>
	<?php

	$linkURL = '?p=orders&a=find&query='.urlencode($queryString).'&state='.urlencode($state).'&queryType='.urlencode($queryType);
	if ($_POST) {
		header('Location: '.(dirname($_SERVER['SCRIPT_NAME']) != '/' ? dirname($_SERVER['SCRIPT_NAME']) : '').'/'.$linkURL);
	}
	if (isset($queryString)) {
		$queryString = mysql_real_escape_string($queryString);
		$state = mysql_real_escape_string($state);
		//TODO: Does not match
		$query = mysql_query("SELECT * FROM ".$SQL['DATABASE'].".orders WHERE (orderno LIKE '".$queryString."' OR reference LIKE '".$queryString."' OR enteredby LIKE '".$queryString."' OR orderedfor LIKE '".$queryString."' OR authby LIKE '".$queryString."' OR supplier LIKE '".$queryString."' OR enduser LIKE '".$queryString."' OR products LIKE '".$queryString."' OR status LIKE '".$queryString."' OR nominalcode LIKE '".$queryString."' OR comments LIKE '".$queryString."') AND completed LIKE '".$complete."' AND cancelled LIKE '".$cancelled."' ORDER BY date DESC LIMIT 0,9999");
		
		$resultCount = mysql_num_rows($query);
		echo '<p>Found '.$resultCount.' result(s) for <b>'.$stateString.'</b> - <a href="'.$linkURL.'">Link this query</a> (for Bookmarks or sharing)</p>';
		
		echo '<table id="linkresults">';
		echo '<tr id="header">';
		echo '<td>Order Number</td><td>Reference</td><td>Date</td><td>Ordered for</td><td>Supplier</td><td>End User</td><td>Status</td><td>Ordered By</td>';
		echo '</tr>';
		//TODO: Page Results
		
		if ($resultCount == 0) {
			echo '<tr class="noHover"><td colspan="8">No Results to display</td></tr>';
		} else {
			$i = "colour1";
			while ($info = mysql_fetch_assoc($query)) {
				$colour = $i;
				if ($info['cancelled'] == 1) { $colour = "colour3"; }
				if ($info['completed'] == 1) { $colour = "colour4"; }
				if ($config['linkType'] == 'inline') {
					echo '<tr class="'.$colour.'" onclick="javascript:window.location=\'?p=orders&a=view&id='.$info['id'].'\';">';
				} elseif ($config['linkType'] == 'popout') {
					echo '<tr class="'.$colour.'" onclick="javascript:popout(\'?p=orders&a=view&id='.$info['id'].'\');">';
				}
				echo '<td>'.$info['orderno'].'</td><td>'.$info['reference'].'</td><td>'.date("d/m/Y @ H:i:s", $info['date']).'</td><td>'.$info['orderedfor'].'</td><td>'.$info['supplier'].'</td><td>'.$info['enduser'].'</td><td>'.$info['status'].'</td><td>'.$info['enteredby'].'</td>';
				echo '</tr>';
				
				$i = ($i == "colour1") ? "colour2" : "colour1";
			}
		}
		echo '</table>';
	}
}

function doViewOrder() {
	global $SQL, $config;
	if (empty($_GET['id'])) {
		echo '<p>There was an error getting the information to view, please go back and try again.</p>';
		return;
	}
	$_GET['id'] = mysql_real_escape_string($_GET['id']);
	
	$query = mysql_query("SELECT * FROM ".$SQL['DATABASE'].".orders WHERE id = '".$_GET['id']."' LIMIT 0,1");
	if (mysql_num_rows($query) == 0) {
		echo '<p>This ID is not in the Database!</p>';
		return;
	}
	$info = mysql_fetch_assoc($query);
	?>
	<div><b>Order Number:</b> <?php echo $info['orderno']; ?></div>
	<div>&nbsp;</div>
	<div><b>Reference:</b> <?php echo $info['reference']; ?></div>
	<div>&nbsp;</div>
	<div><b>Date Ordered:</b> <?php echo date("d/m/Y @ H:i:s", $info['date']); ?></div>
	<div>&nbsp;</div>
	<div><b>Ordered For:</b> <?php echo $info['orderedfor']; ?></div>
	<div>&nbsp;</div>
	<div><b>Authorized By:</b> <?php echo $info['authby']; ?></div>
	<div>&nbsp;</div>
	<div><b>Supplier:</b> <?php echo $info['supplier']; ?></div>
	<div>&nbsp;</div>
	<div><b>End user:</b> <?php echo $info['enduser']; ?></div>
	<div>&nbsp;</div>
	<div><b>Cost:</b> &pound;<?php echo number_format($info['cost'],2); ?></div>
	<div>&nbsp;</div>
	<div><b>Order Status:</b> <?php echo $info['status']; ?></div>
	<div>&nbsp;</div>
	<div><b>Nominal Code:</b> <?php echo $info['nominalcode']; ?></div>
	<div>&nbsp;</div>
	<div><b>Order Details:</b> <p><pre><?php echo $info['products']; ?></pre></p></div>
	<div><b>Comments:</b> <p><pre><?php echo $info['comments']; ?></pre></p></div>
	<div>&nbsp;</div>
	
	<?php
		if ($info['status'] != "Cancelled" && $info['status'] != "Complete") {
			?>
				<form method="post" action="?p=orders&a=edit&id=<?php echo $info['id']; ?>">
				<p><input type="submit" value="Edit this entry"></p>
				</form>
			<?php
		}
	?>
	<div>&nbsp;</div>
	<div><b>Order Change Log:</b><table id="results">
		<tr id="header">
		<td>Date</td><td>By</td><td>Field Changed</td><td>Old Value</td><td>New Value</td>
		</tr>
		<?php
			$query = mysql_query("SELECT * FROM ".$SQL['DATABASE'].".orderChangeLog WHERE orderid = '".$_GET['id']."' ORDER by date DESC LIMIT 0,9999");
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
				echo '<tr class="'.$colour.'"><td style="width: 200px;">'.date("d/m/Y @ H:i:s", $chglog['date']).'</td><td style="width: 200px;">'.$chglog['changedby'].'</td><td>'.$chglog['field'].'</td><td>'.$chglog['old'].'</td><td>'.$chglog['new'].'</td></tr>';
				$i = ($i == "colour1") ? "colour2" : "colour1";
			}
		?>
		
	</table>
	</div>
	<?php
}

/*function niy() {
	echo '<div style="margin-top: 200px;text-align: center;">';
	echo '<img src="images/error.png">';
	echo '<h2>Not Implemented Yet</h2>';
	echo '</div>';
}*/
?>
