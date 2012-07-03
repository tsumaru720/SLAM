<?php

require_once('apiCommon.php');

if ($_GET['token']) {
	$isValid = checkToken($_GET['host'], $_GET['token']);

	if (!$isValid) {
		apiError('INVALID_TOKEN', 'The token you have provided appears to be invalid. Please check your tolerance and secret.', true);
	} else {
		$args = getPayload($_REQUEST, $_GET['token']);
		$query = mysql_query("SELECT id, name, comments FROM ".$SQL['DATABASE'].".computers WHERE serial = '".mysql_real_escape_string($args['serial'])."'");
		
		if (mysql_num_rows($query) > 0) {
			//Exists
			$computerInfo = mysql_fetch_assoc($query);
			if (strtoupper($computerInfo['name']) != strtoupper($args['host'])) {
				apiError('SERIAL_EXISTS', 'This serial currently has a different name. Need to rename.', true);
			} else {
				echo apiOut('ok', '', 'Successfully updated '.$computerInfo['name']);
				$query = mysql_query("UPDATE ".$SQL['DATABASE'].".computers SET
					`modifiedby` = 'API',
					`modified` = '".time()."',
					`comments` = '".$computerInfo['comments']."\n\n**API Update** Reinstalled ".date("d/m/Y", time())." at ".date("H:i", time())."',
					`active` = '1'
					WHERE id = '".$computerInfo['id']."'");
					updateComputerChangeLog($computerInfo['id'], "Status", '', 'Updated via API');
			}
		} else {
			//Not in Database - Add it
			echo apiOut('ok', '', 'Successfully Added '.$computerInfo['name']);
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
						'".$args['serial']."', 
						'".$args['host']."', 
						'2', 
						'5.5', 
						'API', 
						'API', 
						'".time()."', 
						'".time()."', 
						'**Added By API - ".date("d/m/Y", time())." at ".date("H:i", time())."**', 
						'1'
					)");
		}
	}
}

ob_end_flush();
?>