<?php

function getPageTitle() {
	return 'Log in';
}

function doHeader() {
	global $contentOnly;

	if ($_SESSION['authenticated']) {
		header('Location: '.(dirname($_SERVER['SCRIPT_NAME']) != '/' ? dirname($_SERVER['SCRIPT_NAME']) : '').'/');
	}
	$contentOnly = true;
	echo '<link rel="stylesheet" type="text/css" href="css/center.css">';
	echo '<link rel="stylesheet" type="text/css" href="css/form.css">';
}

function getBody() {
	global $errorMsg, $config;

	if (!empty($_GET['a']) && $_GET['a'] == 'check' && empty($errorMsg)) {

		if (strtolower($_POST['username']) == 'administrator') {
			//Use built in database for administrator user, regardless of what is configured
			$config['authType'] = 'slam';
		}
		$auth = checkAuthentication($config['authType'], strtolower($_POST['username']), $_POST['password']);
		//header('Location: '.dirname($_SERVER['SCRIPT_NAME']).'/');
		header('Location: '.$_SERVER['HTTP_REFERER']);
	} else {
		if (!empty($errorMsg)) {
			echo '<span style="color: red;">'.$errorMsg.'</span>';
			echo '<p>&nbsp;</p>';
		}
		echo '<p>';
		showForm($config['authType']);
		echo '</p>';
	}
}

function checkAuthentication($authType, $username, $password) {
	if ($authType == 'slam') {
		global $SQL;
		if (empty($username)) {
			showError('Invalid Credentials', true);
			return false;
		}
		$password = md5(strlen($username.$password).$username.$password);

		$query = mysql_query("SELECT * FROM ".$SQL['DATABASE'].".userAccounts WHERE username = '".mysql_real_escape_string($username)."' AND passwordHash = '".$password."' LIMIT 0,1;");
		if ($user = mysql_fetch_assoc($query)) {
			if (filter_var($user['enabled'], FILTER_VALIDATE_BOOLEAN)) {
				$_SESSION['authenticated'] = true;
				$_SESSION['displayName'] = $user['displayName'];
				$_SESSION['id'] = $user['id'];
				$_SESSION['isAdmin'] = filter_var($user['isAdmin'], FILTER_VALIDATE_BOOLEAN);
				return true;
			} else {
				showError('Account Locked', true);
				return false;
			}
		} else {
			showError('Invalid Credentials', true);
			return false;
		}
	} else {
		//Unsupported authentication module
		//Try to re-auth using built in database.
		//If credentials are invalid, script is halted elsewhere.
		return checkAuthentication('slam', $username, $password);
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

function showForm($authType) {
	if ($authType == 'slam') {
		echo '<p>Authenticate with SLAM</p>';
	} else {
		echo '<p>Authenticate</p>';
	}
?>
	<p>&nbsp;</p>
	<div class="form">
	<form action="?a=check" method="post">
		<label for="username">Username: </label><input type="text" name="username"<?php echo (!empty($_POST['username']) ? ' value="'.$_POST['username'].'"' : '');?>/><br/>
		<label for="password">Password: </label><input type="password" name="password"/><br/>
		<input style="margin-left: 125px;" type="submit" value="Login"/>
	</form>
	</div>
<?php
}
?>
