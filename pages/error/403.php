<?php

function getPageTitle() {
	return '403 - Forbidden';
}

function doHeader() {
	echo '<link rel="stylesheet" type="text/css" href="css/contentCenter.css">';
}

function getBody() {
	echo '<img src="images/error.png">';
	echo '<h2>Forbidden</h2>';

}
?>
