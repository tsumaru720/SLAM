<?php

function getPageTitle() {
	return 'User Preferences';
}

function doHeader() {
	global $activePage, $activeMenu, $contentOnly, $errorMsg;
	$activePage = 'preferences';

	if ($_SESSION['newPassword']) {
		$contentOnly = true;
		echo '<link rel="stylesheet" type="text/css" href="css/center.css">';
		if (!$_GET['t']) {
			$errorMsg = 'You must choose a new password before you can proceed';
		}
	}
	
	echo '<link rel="stylesheet" type="text/css" href="css/form.css">';

	if ($_GET['a'] == "password") {
		$activeMenu = 'password';
	} else {
		//Default action
		$activeMenu = 'preferences';
	}
	
	return;
}

function getMenu() {
return array(
	'Preferences' => array(
		'url' => '?p=preferences',
		'alias' => 'preferences',
	),
	'Change Password' => array(
		'url' => '?p=preferences&amp;a=password',
		'alias' => 'password',
	),
);

}

function getBody() {
	
	userPreferences();
}

function userPreferences() {
	global $SQL, $errorMsg;
	
	if ($_GET['a'] == 'confirm') {
		foreach ($_POST as $key => $value) {
			$query = mysql_query("SELECT * FROM ".$SQL['DATABASE'].".configuration WHERE name = '".mysql_real_escape_string($key)."'");
			if ($info = mysql_fetch_assoc($query)) {
				$prefQuery = mysql_query("SELECT * FROM ".$SQL['DATABASE'].".userPreferences WHERE name = '".mysql_real_escape_string($key)."' AND userID = '".$_SESSION['id']."'");
				$resultCount = mysql_num_rows($prefQuery);
				if ($info['value'] != $value) {
					if ($resultCount > 0) {
						mysql_query("UPDATE ".$SQL['DATABASE'].".userPreferences SET `value` = '".mysql_real_escape_string($value)."' WHERE name = '".mysql_real_escape_string($key)."' AND userID = '".$_SESSION['id']."'");
					} else {
						mysql_query("INSERT INTO ".$SQL['DATABASE'].".userPreferences (
									`id` ,
									`userID` ,
									`name` ,
									`value`
									)
									VALUES (
									NULL , '".$_SESSION['id']."', '".mysql_real_escape_string($key)."', '".mysql_real_escape_string($value)."'
									)");
					}
				} else {
					if ($resultCount > 0) {
						mysql_query("DELETE FROM ".$SQL['DATABASE'].".userPreferences WHERE name = '".mysql_real_escape_string($key)."' AND userID = '".$_SESSION['id']."'");
					}
				}
			} else {
				echo '<p>Invalid Database Key</p>';
				return;
			}
		}
		echo '<p>Saved</p>';
	} elseif ($_GET['a'] == 'password') {
		if ($_GET['t'] == 'confirm' && !$errorMsg) {
			$query = mysql_query("SELECT username, passwordHash FROM ".$SQL['DATABASE'].".userAccounts WHERE id = '".$_SESSION['id']."'");
			$info = mysql_fetch_assoc($query);
			
			$passwordHash = md5(strlen($info['username'].$_POST['currentpass']).$info['username'].$_POST['currentpass']);
			
			if ($passwordHash != $info['passwordHash']) {
				showError('Incorrect Password!', true);
				return;
			}
			if ($_POST['pword'] != $_POST['cpword']) {
				showError('Passwords do not match', true);
				return;
			}
			if (strlen($_POST['pword']) < 5) {
				showError('Password is too short', true);
				return;
			}
			$passwordHash = md5(strlen($info['username'].$_POST['pword']).$info['username'].$_POST['pword']);
			if ($passwordHash == $info['passwordHash']) {
				showError('Please choose a different password', true);
				return;
			}
			
			mysql_query("UPDATE ".$SQL['DATABASE'].".userAccounts SET `passwordHash` = '".$passwordHash."' WHERE `userAccounts`.`id` = ".$_SESSION['id']);
			if ($_SESSION['newPassword']) {
				unset($_SESSION['newPassword']);
				mysql_query("UPDATE ".$SQL['DATABASE'].".userAccounts SET `forcePasswordChange` = '0' WHERE `userAccounts`.`id` = ".$_SESSION['id']);
				header('Location: '.(dirname($_SERVER['SCRIPT_NAME']) != '/' ? dirname($_SERVER['SCRIPT_NAME']) : '').'/');
			} else {
				header('Location: '.(dirname($_SERVER['SCRIPT_NAME']) != '/' ? dirname($_SERVER['SCRIPT_NAME']) : '').'/?p=logout');
			}
			return;
		}
		
		if (!empty($errorMsg)) {
			echo '<span style="color: red;">'.$errorMsg.'</span>';
			echo '<p>&nbsp;</p>';
		}	
		
		?>
		<div class="form">
		<form method="post" action="?p=preferences&a=password&t=confirm" autocomplete="off">
		<label for="currentpass">Current Password: </label><input type="password" name="currentpass" autofocus="autofocus"><br/><br/>
		<label for="pword">New Password: </label><input type="password" name="pword"><br/>
		<label for="cpword">Confirm Password: </label><input type="password" name="cpword"><br/><br/>

		<input class="button" type="submit" name="action" value="Change Password"></form></div>
		<?php
		return;
	}
	$query = mysql_query("SELECT config.name,config.friendlyName,config.value AS defaultValue,config.values,userPreferences.value AS currentValue FROM ".$SQL['DATABASE'].".configuration AS config LEFT JOIN (SELECT * FROM ".$SQL['DATABASE'].".userPreferences WHERE userPreferences.userID = ".$_SESSION['id'].") userPreferences ON config.name = userPreferences.name WHERE config.userPref = 1");

	?>
	<form method="post" action="?p=preferences&a=confirm">
	<?php
		while ($info = mysql_fetch_assoc($query)) {
			echo '<label style="width: 250px;" for="'.$info['name'].'">'.$info['friendlyName'].': </label>';
			$displayValue = ($info['currentValue'] ? $info['currentValue'] : $info['defaultValue']);
			if (!empty($info['values'])) {
				echo '<select name="'.$info['name'].'">';
				foreach (explode(',',$info['values']) as $value) {
					echo '<option value="'.$value.'"'.($displayValue == $value ? ' SELECTED' : '').'>'.$value.'</option>';
				}
				echo '</select>';
			} else {
				echo '<input type="text" name="'.$info['name'].'" value="'.$displayValue.'">';
			}
			echo '<span class="infoText">Default <span style="color: red;">'.$info['defaultValue'].'</span></span><br/>';
		}
	?>
	<input class="button" type="submit" value="Save">
	<?php
}

function showError($error, $fatal = false) {
	global $errorMsg;
	$errorMsg = $error;
	getBody();
	if ($fatal == true) {
		die();
	}
}

?>
