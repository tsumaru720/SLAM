<?php

function getPageTitle() {
	return 'Dashboard';
}

function doHeader() {
	global $activePage, $activeMenu;
	$activePage = 'dashboard';
	return;
}

function getMenu() {
return array(
	'Reports' => array(
		'url' => '#',
		'alias' => 'reports',
	),
	'Export Data' => array(
		'url' => '#',
		'alias' => 'export',
	),
);

}

function getBody() {
	echo 'Hello '.$_SESSION['displayName'];
	echo '<pre>';
	var_dump($_SESSION);
	var_dump(ini_get( 'session.gc_maxlifetime'));
	
	echo '</pre>';
}
?>
