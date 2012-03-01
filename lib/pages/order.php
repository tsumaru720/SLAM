<?php
require_once(dirname(__FILE__)."/../../lib/functions/mysqlConnect.php");
require_once(dirname(__FILE__)."/../../lib/functions/redirect.php");

function initPage() {
	global $PAGE_CATEGORY, $PAGE_SUB_CATEGORY, $SQL;
	$PAGE_CATEGORY = "Orders";
	if ($_GET['a'] == "new") {
		$PAGE_SUB_CATEGORY = "New Order";
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
  return 'Order Management';
}

function showPageBody() {
	if ($_GET['a'] == "new") {
		doNewOrder();
	} elseif ($_GET['a'] == "find") {
		doFindOrder();
	} elseif ($_GET['a'] == "view") {
		doViewOrder();
	} elseif ($_GET['a'] == "edit") {
		doEditOrder();
	} else {
		redirect('?p=order&a=find');
	}
	
}

function doViewOrder() {
	global $SQL;
	if (empty($_GET['id'])) {
		echo 'There was an error getting the information to view, please go back and try again.';
	} else {
		$_GET['id'] = mysql_real_escape_string($_GET['id']);
	}
	
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
	<div><b>Order Details:</b> <p><pre><?php echo $info['products']; ?></pre></p></div>
	
	<div><b>Cost:</b> &pound;<?php echo number_format($info['cost'],2); ?></div>
	<div>&nbsp;</div>
	<div><b>Order Status:</b> <?php echo $info['status']; ?></div>
	<div>&nbsp;</div>
	<div><b>Nominal Code:</b> <?php echo $info['nominalcode']; ?></div>
	<div>&nbsp;</div>
	<div><b>Comments:</b> <p><pre><?php echo $info['comments']; ?></pre></p></div>
	<div>&nbsp;</div>
	
	<?php
		if ($info['status'] != "Cancelled" && $info['status'] != "Complete") {
			?>
				<form method="post" action="?p=order&a=edit&id=<?php echo $info['id']; ?>">
				<p><input type="submit" value="Edit this entry"></p>
				</form>
			<?php
		}
	?>
	<div>&nbsp;</div>
	<div><b>Order Change Log:</b><table id="results">
		<tr id="headers">
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

function doEditOrder() {
	global $SQL;
	if (!empty($_POST['id'])) {
		$id = mysql_real_escape_string($_POST['id']);
	} elseif (isset($_GET['id']) && !empty($_GET['id'])) {
		$id = mysql_real_escape_string($_GET['id']);
	} else {
		echo 'There was an error getting the information to view, please go back and try again.';
	}
	
	$query = mysql_query("SELECT * FROM ".$SQL['DATABASE'].".orders WHERE id = '".$id."' LIMIT 0,1");
	if (mysql_num_rows($query) == 0) {
		echo '<p>This ID is not in the Database!</p>';
		return;
	}
	
	$info = mysql_fetch_assoc($query);
	
	if ($_GET['t'] == 'confirm') {
		foreach ($_POST as $key => $value) {
			$_POST[$key] = mysql_real_escape_string($value);
		}
		if (!empty($_POST['order_no'])) { $_POST['order_no'] = strtoupper($_POST['order_no']); }
		if (!empty($_POST['reference'])) { $_POST['reference'] = strtoupper($_POST['reference']); }
		if ($_POST['supplier'] == 'other') {
			if (!empty($_POST['supplier_other'])) {
				$_POST['supplier'] = $_POST['supplier_other'];
				$query = mysql_query("SELECT friendlyName FROM ".$SQL['DATABASE'].".orderSuppliers WHERE friendlyName = '".$_POST['supplier']."' LIMIT 0,1");
				if (mysql_num_rows($query) == 0) {
					$query = mysql_query("INSERT INTO ".$SQL['DATABASE'].".orderSuppliers (
						`id` ,
						`friendlyName`
						)
						VALUES (
						NULL , '".$_POST['supplier']."'
					)");
				}
			} else {
				echo '<p>Please ensure you provide valid supplier name</p>';
				return;
			}
		}
		if (!is_numeric($_POST['cost'])) {
			echo '<p>Please ensure you provide valid cost</p>';
			return;
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
		
		$changed = false;
		$redirect = '?p=order&a=view&id='.$id;
		
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
			if (!is_bool($new['containslicenses'])) {
				echo '<p>"Contains Licenses" was not recognised</p>';
				return;
			} else {
				$changed = true;
				updateChangeLog($id, "Order Contains Licenses", ($old['containslicenses'] == '1' ? 'Yes' : 'No'), ($new['containslicenses'] == '1' ? 'Yes' : 'No'));
			}
		}
		if ($old['products'] != $new['products']) {
			if (empty($new['products'])) {
				echo '<p>Your order must have detail of what is being ordered</p>';
				return;
			} else {
				$changed = true;
				updateChangeLog($id, "Products", $old['products'], $new['products']);
			}
		}
		if ($old['cost'] != $new['cost']) {
			if (!is_numeric($new['cost'])) {
				echo '<p>Cost is invalid</p>';
				return;
			} else {
				$changed = true;
				updateChangeLog($id, "Cost", $old['cost'], $new['cost']);
			}
		}
		if ($old['status'] != $new['status']) {
			if ($new['status'] == "Complete" && $new['containslicenses']) {
				$redirect = '?p=license&a=new&id='.$id;
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
			redirect($redirect);
		} elseif ($changed == false && $_POST['id']) {
			echo '<p><b>Nothing has changed!</b></p>';
		}
		return;
	}
	
	$authorizeQuery = mysql_query("SELECT friendlyName FROM ".$SQL['DATABASE'].".ordersCanAuthorize ORDER BY friendlyName ASC LIMIT 0,9999");
	$statusQuery = mysql_query("SELECT friendlyName FROM ".$SQL['DATABASE'].".orderStatuses ORDER BY friendlyName asc LIMIT 0,9999");
	$suppliersQuery = mysql_query("SELECT friendlyName FROM ".$SQL['DATABASE'].".orderSuppliers ORDER BY friendlyName asc LIMIT 0,9999");
	?>
	&nbsp;<br>
	<form method="post" action="?p=order&a=edit&t=confirm"><input type="hidden" name="id" value="<?php echo $info['id'];?>">
	<div>Order Number: </div><input type="text" name="order_no" value="<?php echo $info['orderno'];?>"><br/>
	<div>Reference:</div><input type="text" name="reference" value="<?php echo $info['reference'];?>"><br/>
	<div>Ordered For:</div><input type="text" name="ordered_for" value="<?php echo $info['orderedfor'];?>"><br/>
	<div>Authorized By: </div><select name="auth_by">
	<option value="none">n/a</option><?php
		while ($auth = mysql_fetch_assoc($authorizeQuery)) {
			echo '<option value="'.$auth['friendlyName'].'"';
			echo ($info['authby'] == $auth['friendlyName']) ? ' SELECTED' : '';
			echo '>'.$auth['friendlyName'].'</option>';
		}
	?></select><br/>
	<div>Supplier: </div><select name="supplier"><?php
		while ($supplier = mysql_fetch_assoc($suppliersQuery)) {
			echo '<option value="'.$supplier['friendlyName'].'"';
			echo ($info['supplier'] == $supplier['friendlyName']) ? ' SELECTED' : '';
			echo '>'.$supplier['friendlyName'].'</option>';
		}
	?>
	<option value="other">Other supplier ... (Please detail)</option>
	</select><br/>
	<div><i>Other supplier: </i></div><input type="text" name="supplier_other"><br/>
	<div>End user:</div><input type="text" name="end_user" value="<?php echo $info['enduser'];?>"><br/>
	<div>Order contains Licenses: </div><input type="checkbox" name="licenses" value="1"<?php if ($info['containslicenses']) { echo ' CHECKED';} ?>><br/><br/>
	<div>Order Details: </div><textarea name="ordered" rows="5" cols="25"><?php echo $info['products'];?></textarea><br/>
	<div>Cost (Without &pound; symbol):</div><input type="text" name="cost" value="<?php echo $info['cost'];?>"><br/>
	<div>Order Status: </div><select name="status">
		<option value="Complete">Complete</option>
		<option value="Cancelled">Cancelled</option><?php
		while ($status = mysql_fetch_assoc($statusQuery)) {
			echo '<option value="'.$status['friendlyName'].'"';
			echo ($info['status'] == $status['friendlyName']) ? ' SELECTED' : '';
			echo '>'.$status['friendlyName'].'</option>';
		}
	?></select><br/>
	<div>Nominal Code:</div><input type="text" name="nominal_code" value="<?php echo $info['nominalcode'];?>"><br/>
	<div>Comments: </div><textarea name="comments" rows="5" cols="25"><?php echo $info['comments'];?></textarea><br/>

	
	<p><input type="submit" value="Edit"></p>
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
		'".$_SESSION['displayname']."',
		'".$field."',
		'".$old."',
		'".$new."',
		'".time()."')");
		
}

function doNewOrder() {
	global $SQL;
	
	if ($_GET['t'] == "confirm") {
		foreach ($_POST as $key => $value) {
			$_POST[$key] = mysql_real_escape_string($value);
		}
		if (!empty($_POST['order_no'])) { $_POST['order_no'] = strtoupper($_POST['order_no']); }
		if (!empty($_POST['reference'])) { $_POST['reference'] = strtoupper($_POST['reference']); }
		if ($_POST['supplier'] == 'other') {
			if (!empty($_POST['supplier_other'])) {
				$_POST['supplier'] = $_POST['supplier_other'];
				$query = mysql_query("SELECT friendlyName FROM ".$SQL['DATABASE'].".orderSuppliers WHERE friendlyName = '".$_POST['supplier']."' LIMIT 0,1");				$query = mysql_query("SELECT friendlyName FROM ".$SQL['DATABASE'].".orderSuppliers WHERE friendlyName = '".$_POST['supplier']."' LIMIT 0,1");
				if (mysql_num_rows($query) == 0) {
					$query = mysql_query("INSERT INTO ".$SQL['DATABASE'].".orderSuppliers (
						`id` ,
						`friendlyName`
						)
						VALUES (
						NULL , '".$_POST['supplier']."'
					)");
				}
			} else {
				echo '<p>Please ensure you provide valid supplier name</p>';
				return;
			}
		}
		if (!is_numeric($_POST['cost'])) {
			echo '<p>Please ensure you provide valid cost</p>';
			return;
		}
		$complete = 0;
		$cancelled = 0;

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
								'".$_SESSION['displayname']."',
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
		echo '<p><b><a href="?p=order&a=view&id='.mysql_insert_id().'">Your Order</a></b> has successfully been added to the database</p>';
	} else {
		$authorizeQuery = mysql_query("SELECT friendlyName FROM ".$SQL['DATABASE'].".ordersCanAuthorize ORDER BY friendlyName ASC LIMIT 0,9999");
		$statusQuery = mysql_query("SELECT friendlyName FROM ".$SQL['DATABASE'].".orderStatuses ORDER BY friendlyName asc LIMIT 0,9999");
		$suppliersQuery = mysql_query("SELECT friendlyName FROM ".$SQL['DATABASE'].".orderSuppliers ORDER BY friendlyName asc LIMIT 0,9999");
		?>
		&nbsp;<br>
		<form method="post" action="?p=order&a=new&t=confirm">
		<div>Order Number: </div><input type="text" name="order_no"><br/>
		<div>Reference:</div><input type="text" name="reference"><br/>
		<div>Ordered For:</div><input type="text" name="ordered_for"><br/>
		<div>Authorized By: </div><select name="auth_by">
		<option value="none">n/a</option><?php
			while ($auth = mysql_fetch_assoc($authorizeQuery)) {
				echo '<option value="'.$auth['friendlyName'].'">'.$auth['friendlyName'].'</option>';
			}
		?></select><br/>
		<div>Supplier: </div><select name="supplier"><?php
			while ($supplier = mysql_fetch_assoc($suppliersQuery)) {
				echo '<option value="'.$supplier['friendlyName'].'">'.$supplier['friendlyName'].'</option>';
			}
		?>
		<option value="other">Other supplier ... (Please detail)</option>
		</select><br/>
		<div><i>Other supplier: </i></div><input type="text" name="supplier_other"><br/>
		<div>End user:</div><input type="text" name="end_user"><br/>
		<div>Order contains Licenses: </div><input type="checkbox" name="licenses" value="1"><br/><br/>
		<div>Order Details: </div><textarea name="ordered" rows="5" cols="25"></textarea><br/>
		<div>Cost (Without &pound; symbol):</div><input type="text" name="cost"><br/>
		<div>Order Status: </div><select name="status"><?php
			while ($status = mysql_fetch_assoc($statusQuery)) {
				echo '<option value="'.$status['friendlyName'].'">'.$status['friendlyName'].'</option>';
			}
		?></select><br/>
		<div>Nominal Code:</div><input type="text" name="nominal_code"><br/>
		<div>Comments: </div><textarea name="comments" rows="5" cols="25"></textarea><br/>

		
		<p><input type="submit" value="Add"></p>
		</form>
		<?php
	}
}

function doFindOrder() {
	global $SQL;
	
	$state = 'progress';
	if (!empty($_POST['query'])) {
		$queryString = mysql_real_escape_string($_POST['query']);
		$state = mysql_real_escape_string($_POST['state']);
	} elseif (isset($_GET['query'])) {
		$queryString = mysql_real_escape_string($_GET['query']);
		$state = mysql_real_escape_string($_GET['s']);
	} elseif (empty($_POST['query']) && is_string($_POST['query'])) {
		$queryString = mysql_real_escape_string("%");
		$state = mysql_real_escape_string($_POST['state']);
	}
	
	if ($state == 'complete') {
		$complete = 1;
		$cancelled = 0;
	} elseif ($state == 'cancelled') {
		$complete = 0;
		$cancelled = 1;
	} elseif ($state == 'progress') {
		$complete = 0;
		$cancelled = 0;
	} else {
		$complete = '%';
		$cancelled = '%';	
	}
	?>
	&nbsp;Use % for wildcard searching<br><br>
	<form method="post" action="?p=order&a=find">
	<div>Search for: </div><input type="text" name="query" value="<?php echo $queryString; ?>"><br/>
	<div>&nbsp;</div><input type="radio" id="all" name="state" value="all"<?php echo ($state == 'all') ? ' CHECKED' : ''; ?>><label for="all">All</label>
	<input type="radio" name="state" id="progress" value="progress"<?php echo ($state == 'progress') ? ' CHECKED' : ''; ?>><label for="progress">In Progress</label>
	<input type="radio" name="state" id="complete" value="complete"<?php echo ($state == 'complete') ? ' CHECKED' : ''; ?>><label for="complete">Complete</label>
	<input type="radio" name="state" id="cancelled" value="cancelled"<?php echo ($state == 'cancelled') ? ' CHECKED' : ''; ?>><label for="cancelled">Cancelled</label><br/><br/>
	<p><input type="submit" value="Search"></p>
	</form>
	<?php
	/*if ($_POST['changelog'] || $_GET['type'] == "changelog") {
		doChangeLogSearch($queryString, $queryLocation);
	} else {
		doGenericSearch($queryString, $queryLocation);
	}*/
	
	
	if (isset($queryString)) {
		$query = mysql_query("SELECT * FROM ".$SQL['DATABASE'].".orders WHERE (orderno LIKE '".$queryString."' OR reference LIKE '".$queryString."' OR enteredby LIKE '".$queryString."' OR orderedfor LIKE '".$queryString."' OR authby LIKE '".$queryString."' OR supplier LIKE '".$queryString."' OR enduser LIKE '".$queryString."' OR products LIKE '".$queryString."' OR status LIKE '".$queryString."' OR nominalcode LIKE '".$queryString."' OR comments LIKE '".$queryString."') AND completed LIKE '".$complete."' AND cancelled LIKE '".$cancelled."' ORDER BY date DESC LIMIT 0,9999");
		echo '<p>Found '.mysql_num_rows($query).' result(s) in <b>'.$_POST['state'].'</b></p>';
		echo '<table id="linkresults">';
		echo '<tr id="headers">';
		echo '<td>Order Number</td><td>Reference</td><td>Date</td><td>Ordered for</td><td>Supplier</td><td>End User</td><td>Status</td><td>Ordered By</td>';
		echo '</tr>';
		
		$i = "colour1";
		while ($info = mysql_fetch_assoc($query)) {
			$colour = $i;
			if ($info['cancelled'] == 1) { $colour = "colour3"; }
			if ($info['completed'] == 1) { $colour = "colour4"; }
			//echo '<tr class="'.$colour.'" onclick="javascript:window.location=\'?p=order&a=view&id='.$info['id'].'\';">';
			echo '<tr class="'.$colour.'" onclick="javascript:popout(\'?p=order&a=view&id='.$info['id'].'\');">';
			echo '<td>'.$info['orderno'].'</td><td>'.$info['reference'].'</td><td>'.date("d/m/Y @ H:i:s", $info['date']).'</td><td>'.$info['orderedfor'].'</td><td>'.$info['supplier'].'</td><td>'.$info['enduser'].'</td><td>'.$info['status'].'</td><td>'.$info['enteredby'].'</td>';
			echo '</tr>';
			
			$i = ($i == "colour1") ? "colour2" : "colour1";
		}
		echo '</table>';
	}
	
}

?>

