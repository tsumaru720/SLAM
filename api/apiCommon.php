<?php	
ob_start();

if (!file_exists(dirname(__FILE__).'/../config.php')) {
	apiError('NO_CONFIG', 'No SQL Configuration file was found.', true);
} else {
	require_once(dirname(__FILE__).'/../config.php');
	$SQL['RESOURCE'] = mysql_connect($SQL['HOST'].':'.$SQL['PORT'], $SQL['USERNAME'], $SQL['PASSWORD']) or die(mysql_error());
	$query = mysql_query("SELECT name, value FROM ".$SQL['DATABASE'].".apiConfiguration");
	while ($sqlConfig = mysql_fetch_assoc($query)) {
		$config[$sqlConfig['name']] = $sqlConfig['value'];
	}
	
	if (empty($config['secret'])) {
		apiError('NO_SECRET', 'No Token/Encryption secret is defined.', true);
	}
	if ($config['tolerance'] < 1 || $config['tolerance'] > 60) {
		//No time is set for token validity or is too large. Use 10 instead
		$config['tolerance'] = 10;
	}
	
}

function getPayload($args, $key) {
	//TODO: Encryption of some kind
	return $args;
}

function checkToken($host, $token) {
	global $config;
	
	if (empty($host)) { return false; }
	
	$epoch = time();
	$mod = $epoch % $config['tolerance'];
	$epoch = $epoch - $mod;
	
	$tempToken = md5($epoch.$config['tolerance'].$host.$config['secret']);
	$previousToken = md5(($epoch - $config['tolerance']).$config['tolerance'].$host.$config['secret']);
	
	if (($token != $tempToken) && ($token != $previousToken)) {
		//Invalid Token
		return false;
	} else {
		//Valid Token
		return true;
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
		'API',
		'".$field."',
		'".$old."',
		'".$new."',
		'".time()."')");
}

function apiError($short, $desc, $fatal = false) {
	global $apiError;

	$apiError = null;
	$apiError = apiOut('error', $short, $desc);

	if ($fatal == true) {
		ob_end_clean();
		echo $apiError;
		die();
	}
}

function apiOut($status, $type, $message) {
	$out['status'] = $status;
	$out['type'] = $type;
	$out['message'] = $message;
	return json_encode($out);
}

?>
