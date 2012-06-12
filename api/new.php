<?php

require_once('apiCommon.php');

if ($_GET['token']) {
	$isValid = checkToken($_GET['host'], $_GET['token']);
	if (!$isValid) {
		apiError('INVALID TOKEN', 'The token you have provided appears to be invalid. Please check your tolerance and secret.', true);
	} else {
		$args = getPayload($_REQUEST, $_GET['token']);
		var_dump($args);
	}
}

ob_end_flush();
?>