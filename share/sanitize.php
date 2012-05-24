<?php

function sanitize($arg) {
	foreach ($arg as $key => $value) {
		$arg[$key] = mysql_real_escape_string($value);
	}
	return $arg;
}

?>