<?php
session_start();

/*$requestURI = substr($_SERVER['REQUEST_URI'],1);
$queryString = preg_split('/\?/',$requestURI);
$getParams = preg_split('/\//',$queryString[0]);*/

/*if (empty($getParams[0])) {
    $getParams[0] = 'index';
}*/

if (empty($_GET['p'])) {
    $_GET['p'] = 'index';
}

$pageName = urldecode($_GET['p']);
$page = 'lib/pages/'.$pageName.'.php';

require_once(dirname(__FILE__)."/lib/functions/checkLogin.php");
if (file_exists($page)) {
	if (!checkLogin()) {
        $pageName = "login";
        $page = dirname(__FILE__).'/lib/pages/'.$pageName.'.php';
    }
} else {
    $page = dirname(__FILE__)."/lib/pages/errors/http_404.php";
}

if (file_exists(dirname(__FILE__)."/conf/config.php")) {
    require_once(dirname(__FILE__)."/conf/config.php");
	if (substr($GENERAL['KB_PATH'], -1) != '/') { 
		$GENERAL['KB_PATH'] = $GENERAL['KB_PATH'].'/';
	}
} else {
    $page = dirname(__FILE__)."/lib/pages/errors/no_config.php";
}

include($page);

if (function_exists('initPage')) {
    initPage();
}


function buildNavigationLine($category, $menuItems) {
	$output = '[ ';
	$totalItems = count($menuItems);
	$i = 1;
	foreach ($menuItems as $name => $url) {
		if ($name == $category) {
			$output .= '<b>'.$name.'</b>';
		} else {
			$output .= '<a href="'.$url.'">'.$name.'</a>';
		}
		if ($i == $totalItems) {
			$output .= ' ]';
		} else {
			$output .= ' | ';
		}
		$i++;
	}
	return $output;
}

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">  
<html>
    <head>
    <?php
        $pageTitle = function_exists('getPageTitle') ? getPageTitle() : 'Asset Tracking';
        echo '<title>',$pageTitle,'</title>',"\n";
    ?>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" >
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <?php
    if (function_exists('doHeader')) {
        doHeader();
    }
    ?>
    </head>

<body>

<h1 id="header"><b>SLAM</b> <span style="font-weight: none; font-size: 10px;">(<i>S</i>oftware <i>L</i>icensing <i>A</i>llocation <i>M</i>anager)</span>
</h1>
<?php

	if (checkLogin()) {
		$navigationHeaders = array('Dashboard' => '/',
									'Computers' => '?p=computer',
									'Licenses' => '?p=license',
									'Orders' => '?p=order',
									'Knowledge Base' => '?p=kb',
									'Admin' => '?p=admin',
									'Reports' => '?p=report',
									'Logout' => '?p=logout'
							);
		$computerItems = array('Add Computer' => '?p=computer&a=new',
								'Search' => '?p=computer&a=find'
							);
		$licenseItems = array('New License' => '?p=license&a=new',
								'Search' => '?p=license&a=find'
							);
		$adminItems = array('Configuration' => '?p=admin&a=config',
							'Manage Locations' => '?p=admin&a=locations',
							'Manage Products' => '?p=admin&a=products',
							'Can Authorize Orders' => '?p=admin&a=auth',
							'Order Statuses' => '?p=admin&a=status',
							'Order Suppliers' => '?p=admin&a=suppliers'
							);
							
		$reportItems = array('Maintenance' => '?p=report&a=maintenance',
							'Licenses' => '?p=report&a=licenses',
							);
							
		$orderItems = array('New Order' => '?p=order&a=new',
							'Search' => '?p=order&a=find'
							);
		$kbItems = array('Add Entry' => '?p=kb&a=new',
							'Search' => '?p=kb&a=find'
							);
							
		echo '<div class="nav">'.buildNavigationLine($PAGE_CATEGORY, $navigationHeaders).'</div>';
		
		if ($PAGE_CATEGORY == 'Computers' && isset($computerItems)) {
			echo '<div>&nbsp;</div>';
			echo '<div class="nav2">'.buildNavigationLine($PAGE_SUB_CATEGORY, $computerItems).'</div>';
		} elseif ($PAGE_CATEGORY == 'Licenses' && isset($licenseItems)) {
			echo '<div>&nbsp;</div>';
			echo '<div class="nav2">'.buildNavigationLine($PAGE_SUB_CATEGORY, $licenseItems).'</div>';
		} elseif ($PAGE_CATEGORY == 'Orders' && isset($orderItems)) {
			echo '<div>&nbsp;</div>';
			echo '<div class="nav2">'.buildNavigationLine($PAGE_SUB_CATEGORY, $orderItems).'</div>';
		} elseif ($PAGE_CATEGORY == 'Knowledge Base' && isset($kbItems)) {
			echo '<div>&nbsp;</div>';
			echo '<div class="nav2">'.buildNavigationLine($PAGE_SUB_CATEGORY, $kbItems).'</div>';
		} elseif ($PAGE_CATEGORY == 'Admin' && isset($adminItems)) {
			echo '<div>&nbsp;</div>';
			echo '<div class="nav2">'.buildNavigationLine($PAGE_SUB_CATEGORY, $adminItems).'</div>';
		} elseif ($PAGE_CATEGORY == 'Reports' && isset($reportItems)) {
			echo '<div>&nbsp;</div>';
			echo '<div class="nav2">'.buildNavigationLine($PAGE_SUB_CATEGORY, $reportItems).'</div>';
		}
		echo '<div class="navspacer">&nbsp;</div>';
	}
	?>
<?php
    if (function_exists('showPageBody')) {
        showPageBody();
    }
?>
</body>
</html>
