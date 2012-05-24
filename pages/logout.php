<?php

function getPageTitle() {
	return;
}

function doHeader() {
	//Nothing
	return;
}

function getBody() {
	foreach ($_SESSION as $key => $value) {
		unset($_SESSION[$key]);
	}
	header('Location: '.(dirname($_SERVER['SCRIPT_NAME']) != '/' ? dirname($_SERVER['SCRIPT_NAME']) : '').'/');
}
?>
