<?php

function getPageTitle() {
	return '404 - Page Not Found';
}

function doHeader() {
	echo '<link rel="stylesheet" type="text/css" href="css/contentCenter.css">';
}

function getBody() {
	echo '<img src="images/error.png">';
	echo '<h2>Page Not Found</h2>';

}
?>
