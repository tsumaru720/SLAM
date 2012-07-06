<?php

ini_set('session.cache_limiter', 'nocache');
session_start();
ob_start();

require_once(dirname(__FILE__)."/share/sanitize.php");

if (!file_exists(dirname(__FILE__).'/config.php')) {
	$page = 'sqlsetup';
} else {
	require_once(dirname(__FILE__).'/config.php');
	$SQL['RESOURCE'] = mysql_connect($SQL['HOST'].':'.$SQL['PORT'], $SQL['USERNAME'], $SQL['PASSWORD']) or die(mysql_error());
	$query = mysql_query("SELECT name, value FROM ".$SQL['DATABASE'].".configuration");
	while ($sqlConfig = mysql_fetch_assoc($query)) {
		$config[$sqlConfig['name']] = $sqlConfig['value'];
	}
	if ($_SESSION['id']) {
		$query = mysql_query("SELECT name, value FROM ".$SQL['DATABASE'].".userPreferences WHERE userID = ".$_SESSION['id']);
		while ($sqlConfig = mysql_fetch_assoc($query)) {
			$config[$sqlConfig['name']] = $sqlConfig['value'];
		}
	}
}

if ((time() - $_SESSION['lastSeen']) >= $config['timeout']) {
	//Force all session information to be cleared.
	//This will ensure the user needs to log on again.
	foreach ($_SESSION as $key => $value) {
		unset($_SESSION[$key]);
	}
	$errorMsg = 'Session timeout';
}

if (empty($_SESSION['authenticated']) && empty($page)) {
	$page = 'login';
}

if ($config['authType'] == 'slam' && is_numeric($_SESSION['id'])) {
	mysql_query("UPDATE  ".$SQL['DATABASE'].".userAccounts SET  `lastSeen` =  '".time()."' WHERE  id = ".$_SESSION['id'].";");
	$_SESSION['lastSeen'] = time();
}

if (empty($page)) {
	if (empty($_GET['p'])) {
		$page = 'dashboard';
	} else {
		$page = str_replace('.','',$_GET['p']);
	}
}

if ($_SESSION['newPassword']) {
	if (!($page == 'preferences' && $_GET['a'] == 'password' && $_GET['t'] == 'confirm')) {
		$page = 'preferences';
		$_GET['a'] = 'password';
	}
}

if (file_exists(dirname(__FILE__).'/pages/'.$page.'.php') && !empty($page)) {

	if ($page == 'admin' && !$_SESSION['isAdmin']) {
		//403 Error.
		require_once(dirname(__FILE__).'/pages/error/403.php');	
	} else {
		require_once(dirname(__FILE__).'/pages/'.$page.'.php');
	}
} else {
	//404 Error.
	require_once(dirname(__FILE__).'/pages/error/404.php');
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">  
<html>
	<head>
<?php if (function_exists('getPagetitle')) { $pageTitle = getPageTitle(); }?>
	<title>SLAM<?php echo (!empty($pageTitle) ? ' - '.getPageTitle() : '');?></title>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" >
	<link rel="stylesheet" type="text/css" href="css/style.css">
<?php if (function_exists('doHeader')) { doHeader(); } ?>

	<script>
		if (window.name == 'popoutWindow') {
			document.write('<link rel="stylesheet" type="text/css" href="css/popout.css">');
		}
	</script>
	</head>
<body>
<?php
if ($contentOnly != true) { ?>
	<div id="mainNavigation">
		<ul class="left">
			<li<?php echo ($activePage == 'dashboard' ? ' class="active"' : ''); ?>><a href="?p=dashboard"><img src="images/dashboard.png"><br>Dashboard</a></li>
			<li<?php echo ($activePage == 'computers' ? ' class="active"' : ''); ?>><a href="?p=computers"><img src="images/computers.png"><br>Computers</a></li>
			<li<?php echo ($activePage == 'printers' ? ' class="active"' : ''); ?>><a href="?p=printers"><img src="images/printers.png"><br>Printers</a></li>
			<li<?php echo ($activePage == 'licenses' ? ' class="active"' : ''); ?>><a href="?p=licenses"><img src="images/licenses.png"><br>Licenses</a></li>
			<li<?php echo ($activePage == 'orders' ? ' class="active"' : ''); ?>><a href="?p=orders"><img src="images/orders.png"><br>Orders</a></li>
			<li<?php echo ($activePage == 'knowledge' ? ' class="active"' : ''); ?>><a href="?p=knowledgebase"><img src="images/knowledge.png"><br>Knowledge Base</a></li>
			<li<?php echo ($activePage == 'preferences' ? ' class="active"' : ''); ?>><a href="?p=preferences"><img src="images/userprefs.png"><br>Preferences</a></li>
	<?php
	if ($_SESSION['isAdmin']) {
	?>
			<li<?php echo ($activePage == 'admin' ? ' class="active"' : ''); ?>><a href="?p=admin"><img src="images/admin.png"><br>Administration</a></li>
	<?php
	}
	?>
			<li id="logout"><a href="?p=logout" title="Logout of '<?php echo $_SESSION['displayName']; ?>'">Logout<?php echo (strlen($_SESSION['firstName']) <= 10 ? ' '.$_SESSION['firstName'] : ''); ?></a></li>
			</ul>
		</div>
	<?php
	if (function_exists('getMenu')) {
		$menuItems = getMenu(); ?>
		<div id="subNavigation">
			<ul>
	<?php
		foreach ($menuItems as $key => $array) { ?>
			<li<?php echo ($activeMenu == $array['alias'] ? ' class="active"' : ''); ?>><a href="<?php echo $array['url']; ?>"><?php echo $key; ?></a></li>	
	<?php
		}
	?>
			</ul>
		</div>

	<?php
	//End of subMenu
	}
	?>

<?php
} else {
	echo '<style>#container { padding: 0; }</style>';
}?>
	<div id="container">
		<div id="body">
			<?php if (function_exists('getBody')) { getBody(); } ?>
		</div>
	</div>
</body>
</html>
<?php
ob_end_flush();
?>