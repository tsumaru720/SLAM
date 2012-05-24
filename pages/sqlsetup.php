<?php

function getPageTitle() {
	return 'MySQL Initial Setup';
}

function doHeader() {
	global $contentOnly, $SQL;

	if (!empty($SQL['HOST'])) {
		header('Location: '.dirname($_SERVER['SCRIPT_NAME']).'/');
	}
	$contentOnly = true;
	echo '<link rel="stylesheet" type="text/css" href="css/center.css">';
	echo '<link rel="stylesheet" type="text/css" href="css/form.css">';
}

function getBody() {
	global $errorMsg;
	$_GET = sanitize($_GET);

	if (!empty($_GET['a']) && $_GET['a'] == 'check' && empty($errorMsg)) {
		$_POST = sanitize($_POST);
		$resource = @mysql_connect($_POST['hostname'].':'.$_POST['port'], $_POST['dbuser'], $_POST['dbpass']);
		if (!$resource) {
			showError('You have provided incorrect details, please check and try again.');
			return;
		}
		$db = @mysql_select_db($_POST['dbname'], $resource);
		if (!$db) {
			showError('You have provided incorrect details, please check and try again.');
			return;
		}
		//TODO: Insert DB structure if databse doesnt exist
		generateConfig();

		header('Location: '.dirname($_SERVER['SCRIPT_NAME']).'/');
	} else {
		if (empty($errorMsg)) {
			echo 'You do not have any MySQL settings for this application. Please complete the form below:';
		} else {
			echo '<span style="color: red;">'.$errorMsg.'</span>';
		}
		echo '<p>&nbsp;</p>';
		echo '<p>';
		showForm();
		echo '</p>';
	}
}

function generateConfig() {

	$configPath = dirname(__FILE__)."/../config.php";

	$fh = @fopen($configPath, 'w') or showError("Can't create config.php. Check permissions", true);
	$line = "<?php\n";
	$line .= "\n";
	$line .= "\$SQL['HOST'] = '".$_POST['hostname']."';\n";
	$line .= "\$SQL['PORT'] = '".$_POST['port']."';\n";
	$line .= "\$SQL['DATABASE'] = '".$_POST['dbname']."';\n";
	$line .= "\$SQL['USERNAME'] = '".$_POST['dbuser']."';\n";
	$line .= "\$SQL['PASSWORD'] = '".$_POST['dbpass']."';\n";
	$line .= "\n";
	$line .= "?>";
	fwrite($fh, $line);
	fclose($fh);
}

function showError($error, $fatal = false) {
	global $errorMsg;
	$errorMsg = $error;
	getBody();
	if ($fatal == true) {
		die();
	}
}

function showForm() {
?>
	<div class="form">
	<form action="?a=check" method="post">
		<label for="hostname">MySQL Server: </label><input type="text" name="hostname"<?php echo (!empty($_POST['hostname']) ? ' value="'.$_POST['hostname'].'"' : '');?>/><br/>
		<label for="port">Server Port: </label><input type="text" name="port" value="3306" size="4"<?php echo (!empty($_POST['port']) ? ' value="'.$_POST['port'].'"' : '');?>/><br/>
		<p>&nbsp;</p>
		<label for="dbname">Database Name: </label><input type="text" name="dbname"<?php echo (!empty($_POST['dbname']) ? ' value="'.$_POST['dbname'].'"' : '');?>/><br/>
		<label for="dbuser">Username: </label><input type="text" name="dbuser"<?php echo (!empty($_POST['dbuser']) ? ' value="'.$_POST['dbuser'].'"' : '');?>/><br/>
		<label for="dbpass">Password: </label><input type="password" name="dbpass"<?php echo (!empty($_POST['dbpass']) ? ' value="'.$_POST['dbpass'].'"' : '');?>/><br/>
		<input style="margin-left: 125px;" type="submit" value="Save"/>
	</form>
	</div>
<?php
}
?>
