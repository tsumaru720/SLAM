<?php
require_once(dirname(__FILE__)."/../../lib/functions/ldapConnect.php");
require_once(dirname(__FILE__)."/../../lib/functions/checkLDAPError.php");
require_once(dirname(__FILE__)."/../../lib/functions/redirect.php");
require_once(dirname(__FILE__)."/../../lib/functions/memberOfAccessGroup.php");

function initPage() {
	global $PAGE_CATEGORY;
	$PAGE_CATEGORY = "Dashboard";
}

function doHeader() {

}

function getPageTitle() {
    return 'Dashboard';
}

function showPageBody() {
    global $LDAP;
    ?>
    <p>Welcome <?php echo htmlentities($_SESSION['displayname']); ?></p>

    <?php
}
?>

