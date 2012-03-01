<?php
require_once(dirname(__FILE__)."/../../lib/functions/ldapConnect.php");
require_once(dirname(__FILE__)."/../../lib/functions/redirect.php");
require_once(dirname(__FILE__)."/../../lib/functions/memberOfAccessGroup.php");

if (!defined('LDAP_INVALID_CREDENTIALS')) {
    define('LDAP_INVALID_CREDENTIALS', 0x31);
}

function initPage() {
    global $GENERAL;

    if ($_SESSION['authenticated']) {
        redirect($GENERAL['URL_HOME']);
    }
}

function doHeader() {
    ?><link rel="stylesheet" type="text/css" href="css/center.css">
    <link rel="stylesheet" type="text/css" href="css/login.css">
<?php
}

function getPageTitle() {
    return 'Login';
}

function doLogin() {
    global $LDAP;
    
	$results[0]['displayname'][0] = "Mott, Simon";
    $LDAP_RESOURCE = ldapConnect($LDAP['SERVER'], $LDAP['PORT']);
	ldap_set_option($LDAP_RESOURCE, LDAP_OPT_PROTOCOL_VERSION, 3);
	ldap_set_option($LDAP_RESOURCE, LDAP_OPT_REFERRALS, 0);
    $LDAP_BIND = @ldap_bind($LDAP_RESOURCE, $_POST['username'].'@'.$LDAP['SERVER'], $_POST['password']);

    $DISPLAY_MESSAGE = checkForBindError($LDAP_RESOURCE);
    /*if (!$DISPLAY_MESSAGE) {
			//Logged in successfully
			$search = ldap_search($LDAP_RESOURCE, $LDAP['DOMAIN'], '(&(objectclass=person)(samaccountname='.$_POST['username'].'))', array('displayname'), 0, 1000);
			
			$results = ldap_get_entries($LDAP_RESOURCE, $search);
			if (ldap_count_entries($LDAP_RESOURCE, $search) == 0) {
				return "Please check your account";
			}
			
			$USER_DN = $results[0]['dn'];
			if (!memberOfAccessGroup($LDAP_RESOURCE, $LDAP['DOMAIN'], $USER_DN, $LDAP['ACCESS_GROUP_DN'])) {
				return "You do not have permission to access this resource.";
			}
			if (memberOfAccessGroup($LDAP_RESOURCE, $LDAP['DOMAIN'], $USER_DN, $LDAP['ADMIN_GROUP_DN'])) {
				$_SESSION['isadmin'] = true;
			}*/
			
			$_SESSION['authenticated'] = true;
			$_SESSION['displayname'] = $results[0]['displayname'][0];
			$_SESSION['dn'] = $results[0]['dn'];
			$_SESSION['isadmin'] = true;
			return true;
    /*} else {
		return $DISPLAY_MESSAGE;
	}*/
}

function showPageBody() {
	global $LDAP;
    if (!empty($_POST)) {
        $authed = doLogin();
        if ($authed === true) {
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            $DISPLAY_MESSAGE = $authed;
        }
    }
    ?>
    <div id="container">
        <div id="body">
            <div id="loginForm">
                <form action="?p=login" method="post">
                    <fieldset><legend>Login - <?php echo $LDAP['SERVER']; ?></legend>
                        <br/>
                        <?php
                        if ($DISPLAY_MESSAGE) {
                            echo "<span class='errorText'>".$DISPLAY_MESSAGE."</span><br/><br/>\n\n";
                        }
                        ?><label for="username">Username: </label><input type="text" name="username"/><br/>
                        <label for="password">Password: </label><input type="password" name="password"/><br/>
                        <input type="submit" value="Login"/>
                    </fieldset>
                </form>
        </div>
    </div>
    <?php
}

function checkForBindError($LDAP_RESOURCE) {
    $LDAP_ERROR = ldap_errno($LDAP_RESOURCE);
    if ($LDAP_ERROR == 0) {
        //Success
        return;
    }

    if ($LDAP_ERROR == -1) {
        $DISPLAY_MESSAGE = "Unable to contact LDAP server.<br>Please check your server settings in the config.";
    } elseif ($LDAP_ERROR == LDAP_INVALID_CREDENTIALS) {
        $DISPLAY_MESSAGE = "Please check your credentials and try again.";
    } else {
        $DISPLAY_MESSAGE = ldap_err2str($LDAP_ERROR);
    }
    return $DISPLAY_MESSAGE;
}

?>



