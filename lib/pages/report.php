<?php
require_once(dirname(__FILE__)."/../../lib/functions/mysqlConnect.php");
require_once(dirname(__FILE__)."/../../lib/functions/redirect.php");

function initPage() {
	global $PAGE_CATEGORY, $PAGE_SUB_CATEGORY, $SQL;
	$PAGE_CATEGORY = "Reports";
	
	if ($_GET['a'] == "maintenance") {
		$PAGE_SUB_CATEGORY = "Maintenance";
	} elseif ($_GET['a'] == "licenses") {
		$PAGE_SUB_CATEGORY = "Licenses";
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
  return 'Reports';
}

function showPageBody() {
	if ($_GET['a'] == "maintenance") {
		doMaintenanceReport();
	} elseif ($_GET['a'] == "licenses") {
		doLicenseReport();
	}
}

function doMaintenanceReport() {
	global $SQL;
	
	$query = mysql_query("SELECT locations.id, friendlyName, corporate, name, serial, maintenanceFee FROM ".$SQL['DATABASE'].".locations LEFT JOIN ".$SQL['DATABASE'].".computers ON locations.id = computers.location AND computers.active = 1 WHERE hidden = '0' AND name IS NOT NULL ORDER BY friendlyName ASC");
	?>
	<table id="results" style="width:500px;">
	<tr id="headers">
	<td>PC Name</td><td>Serial</td><td>Maintenance Cost</td>
	</tr>
	<?php
	
		$lastLocation = '';
		$i = "colour2";
		
		$totalComputers = 0;
		$totalChargableComputers = 0;
		$grandTotal = 0;
		
		while ($info = mysql_fetch_assoc($query)) {
			if ($info['maintenanceFee'] > 0) {
				if ($info['friendlyName'] != $lastLocation) {
					if ($compAtLocCount > 0) {
						maintenanceTableFooter($compAtLocCount, $totalLocCost);
						$i = "colour2";
					}
					maintenanceTableHeader($info['friendlyName'].' - '.(($info['corporate'] == 1) ? 'Corporate' : 'Franchise'));
					$compAtLocCount = 0;
					$totalLocCost = 0;
				}
				$colour = $i;
				echo '<tr class="'.$colour.'"><td>'.$info['name'].'</td><td>'.$info['serial'].'</td><td>&pound;'.number_format($info['maintenanceFee'], 2).'</td></tr>';
				$i = ($i == "colour1") ? "colour2" : "colour1";
				
				$compAtLocCount++;
				$totalLocCost = $totalLocCost + $info['maintenanceFee'];
				$lastLocation = $info['friendlyName'];
				echo '</tr>';
				
				$totalComputers++;
				$totalChargableComputers++; $grandTotal = $grandTotal + $info['maintenanceFee'];
			}
		}
		
		if ($compAtLocCount > 0) {
			maintenanceTableFooter($compAtLocCount, $totalLocCost);
		}
	?>
	<tr style="background-color: #999999; font-weight: bold;"><td colspan=3 style="text-align: left;">PC Total: <?php echo number_format($totalComputers,0); ?></td></tr>
	<tr style="background-color: #999999; font-weight: bold;"><td colspan=3 style="text-align: left;">PC Total (Chargable): <?php echo number_format($totalChargableComputers,0); ?></td></tr>
	<tr style="background-color: #999999; font-weight: bold;"><td colspan=3 style="text-align: left;">Monthly Grand Total: &pound;<?php echo number_format($grandTotal,2); ?></td></tr>
	<tr style="background-color: #999999; font-weight: bold;"><td colspan=3 style="text-align: left;">Annual: &pound;<?php echo number_format(($grandTotal*12),2); ?></td></tr>
	</table>
	<div>Report Generated: <b><?php echo date("l jS F Y \a\\t H:i:s", time()); ?></b></div>
	<?php
}

function maintenanceTableHeader($friendlyName) {
	echo '<tr><td colspan=3 style="background-color: #bbbbbb;"><b>'.$friendlyName.'</b></td></tr>';
}

function maintenanceTableFooter($compAtLocCount, $totalLocCost) {
	echo '<tr><td colspan=2 style="background-color: #bbbbbb;"><b>Total Computers: '.$compAtLocCount.'</b></td><td style="background-color: #bbbbbb;"><b>Cost: &pound;'.number_format($totalLocCost,2).'</b></td></tr>';
}

function doLicenseReport() {
	echo 'Not yet implemented';
}

/*function doNavHeader($type, $subCategory) {
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
}*/
?>

