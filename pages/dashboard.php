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
	global $config;
	echo 'Hello '.$_SESSION['firstName'];
	echo '<pre>';

	var_dump($_SESSION);
	echo '';
	var_dump($config);
	echo '</pre>';
}
?>
