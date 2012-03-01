<?php
require_once(dirname(__FILE__)."/../../lib/functions/mysqlConnect.php");
require_once(dirname(__FILE__)."/../../lib/functions/redirect.php");

function initPage() {
	global $PAGE_CATEGORY, $PAGE_SUB_CATEGORY, $SQL;
	$PAGE_CATEGORY = "Knowledge Base";
	if ($_GET['a'] == "new") {
		$PAGE_SUB_CATEGORY = "Add Entry";
	} elseif ($_GET['a'] == "find") {
		$PAGE_SUB_CATEGORY = "Search";
	}
	
	mysqlConnect($SQL['HOST'], $SQL['USERNAME'], $SQL['PASSWORD'], $SQL['PORT']);
}

function doHeader() {
  ?><link rel="stylesheet" type="text/css" href="css/form.css">
	<link rel="stylesheet" type="text/css" href="css/resultsTable.css">
	<script src="script/popout.js"></script>
<?php
}

function getPageTitle() {
  return 'Knowledge Base';
}

function showPageBody() {
	if ($_GET['a'] == "new") {
		doNewEntry();
	} elseif ($_GET['a'] == "find") {
		doFindEntry();
	} elseif ($_GET['a'] == "view") {
		doViewEntry();
	} elseif ($_GET['a'] == "upload") {
		doUpload();
	} else {
		redirect('?p=kb&a=find');
	}
}

function doNewEntry() {
	global $SQL;
	mysqlConnect($SQL['HOST'], $SQL['USERNAME'], $SQL['PASSWORD'], $SQL['PORT']);
	
	if ($_GET['t'] == "confirm") {
		foreach ($_POST as $key => $value) {
			$_POST[$key] = mysql_real_escape_string($value);
		}
		if (strlen($_POST['title']) == 0) {
			echo 'You have not provided a title, please go back and try again';
			return;
		}
		$query = mysql_query("INSERT INTO ".$SQL['DATABASE'].".kbList (
							`id` ,
							`createdby` ,
							`modifiedby` ,
							`date` ,
							`modified` ,
							`title` ,
							`comments`
							)
							VALUES (
							NULL ,
							'".$_SESSION['displayname']."', 
							'".$_SESSION['displayname']."', 
							'".time()."', 
							'".time()."', 
							'".$_POST['title']."', 
							'".$_POST['comments']."'
							)");
		$newID = mysql_insert_id();
		$_POST['tags'] = str_replace(", ", ",", $_POST['tags']);
		foreach (explode(",", $_POST['tags']) as $tag) {
			if (!empty($tag)) {
				$query = mysql_query("INSERT INTO ".$SQL['DATABASE'].".kbTags (
									`id`, 
									`kbid`,
									`tag`
									)
									VALUES (
									NULL, 
									'".$newID."',
									'".$tag."'
									)");
			}
		}
		redirect('?p=kb&a=view&id='.$newID);
	} else {	
		?>
		&nbsp;<br>
		You will have the opportunity to upload files such as documents/images at a later stage<BR><BR>
		<form method="post" action="?p=kb&a=new&t=confirm">
		<div>Entry Title: </div><input type="text" name="title" size="40"><br/>
		<div>Tags:</div><input type="text" name="tags" size="40"><b> - Please separate with a comma. For example "focus, bluezone"</b><br/>
		<div>Comments: </div><textarea name="comments" rows="10" cols="35"></textarea><br/>
	
		
		<p><input type="submit" value="Add"></p>
		</form>
		<?php
	}
}

function doFindEntry() {
	global $SQL;
	
	if (!empty($_POST['query'])) {
		$queryString = mysql_real_escape_string($_POST['query']);
	} elseif (isset($_GET['query'])) {
		$queryString = mysql_real_escape_string($_GET['query']);
	} elseif (!empty($_POST)) {
		$queryString = '%';
	}
	?>
	&nbsp;Use % for wildcard searching<br><br>
	<form name="search" method="post" action="?p=kb&a=find">
	<div>Search for: </div><input type="text" name="query" value="<?php echo $queryString; ?>"><br/>
	<br/>
	<p><input type="submit" value="Search"</p>
	</form>
	<?php
	
	if (isset($queryString)) {
		$query = mysql_query("SELECT kbList.id, kbList.title, kbList.modifiedby, kbList.modified, count(distinct kbFiles.filename) FROM ".$SQL['DATABASE'].".kbList as kbList LEFT JOIN ".$SQL['DATABASE'].".kbFiles as kbFiles ON kbFiles.kbid = kbList.id, ".$SQL['DATABASE'].".kbTags as kbTags WHERE (kbList.title LIKE '".$queryString."' OR kbList.comments LIKE '".$queryString."' OR kbTags.tag LIKE '".$queryString."' OR kbFiles.filename LIKE '".$queryString."' OR kbFiles.index LIKE '".$queryString."') AND kbTags.kbid = kbList.id GROUP BY kbList.id ORDER BY kbList.date DESC");
		echo '<p>Found '.mysql_num_rows($query).' result(s) in <b>Generic</b></p>';
		echo '<table id="linkresults">';
		echo '<tr id="headers">';
		echo '<td>Title</td><td>Modified on</td><td>Modified by</td><td>No. of Attachments</td>';
		echo '</tr>';
		
		$i = "colour1";
		while ($info = mysql_fetch_assoc($query)) {
			$colour = $i;
			echo '<tr class="'.$colour.'" onclick="javascript:popout(\'?p=kb&a=view&id='.$info['id'].'\');">';
			//echo '<tr class="'.$colour.'">';
			//<a style="display: block; width: 100%; height: 100%;" href="?p=kb&a=view&id='.$info['id'].'">
			echo '<td style="text-align: left;">'.$info['title'].'</td><td>'.date("d/m/Y @ H:i:s", $info['modified']).'</td><td>'.$info['modifiedby'].'</td><td>'.$info['count(distinct kbFiles.filename)'].'</td>';
			echo '</tr>';
			$i = ($i == "colour1") ? "colour2" : "colour1";
		}
		echo '</table>';
	}
}

function doViewEntry() {
	global $SQL;
	if (empty($_GET['id'])) {
		echo 'There was an error getting the information to view, please go back and try again.';
	} else {
		$_GET['id'] = mysql_real_escape_string($_GET['id']);
	}
	$query = mysql_query("SELECT * FROM ".$SQL['DATABASE'].".kbList WHERE id = '".$_GET['id']."'");
	if (mysql_num_rows($query) == 0) {
		echo '<p>This ID is not in the Database!</p>';
		return;
	}
	$info = mysql_fetch_assoc($query);
	$query = mysql_query("SELECT * FROM ".$SQL['DATABASE'].".kbTags WHERE kbid = '".$_GET['id']."'");
	echo '<h2 style="text-decoration: underline;">'.$info['title'].'</h2>';
	echo 'Created: '.date("d/m/Y @ H:i:s", $info['date']).' by '.$info['createdby'].'<br>Modified: '.date("d/m/Y @ H:i:s", $info['modified']).' by '.$info['modifiedby'].'';
	echo '<br>';
	echo 'Tags: ';
	while ($tag = mysql_fetch_assoc($query)) {
		$tagstr .= '<a href="?p=kb&a=find&query='.urlencode($tag['tag']).'">'.$tag['tag'].'</a>, ';
	}
	if (mysql_num_rows($query) == 0) {
		echo 'No Tags in this entry';
		return;
	}
	$tagstr = substr($tagstr, 0, -2);
	echo $tagstr;
	echo '<h3>Comments</h3>';
	if (strlen($info['comments']) > 0) {
		echo '<pre>'.$info['comments'].'</pre>';
	} else {
		echo '<pre>No comments added to this entry, please see attached file(s)</pre>';
	}
	
	echo '<h3>Attachments</h3>';
	$query = mysql_query("SELECT * FROM ".$SQL['DATABASE'].".kbFiles WHERE kbid = '".$_GET['id']."' ORDER by kbFiles.date DESC");
	$count = mysql_num_rows($query);
	
	echo '<table id="results" style="width: 800px;">';
	echo '<tr id="headers">
			<td>Name</td><td style="width: 200px;">Created</td><td style="width: 200px;">Author</td><td style="width: 100px;">Size</td>
			</tr>';
	$i = "colour1";
	if ($count > 0) {
		while ($fileinfo = mysql_fetch_assoc($query)) {
			$colour = $i;
			echo '<tr class="'.$colour.'">';
			echo '<td><a style="text-align: left; display: block;" href="kb/'.urlencode($fileinfo['urlfilename']).'">'.getIconForType($fileinfo['type']).' '.$fileinfo['filename'].'</a></td><td>'.date("d/m/Y @ H:i:s", $fileinfo['date']).'</td><td>'.$fileinfo['createdby'].'</td><td>'.humanify($fileinfo['size']).'</td>';
			echo '</tr>';
			$i = ($i == "colour1") ? "colour2" : "colour1";
		}
	} else {
		echo '<tr><td colspan=4>No attachments found</td></tr>';
	}
	echo '</table>';
	echo '<form method="post" action="?p=kb&a=upload&id='.$_GET['id'].'">';
	echo '<input type="submit" value="Attach new file...">';
	echo '</form>';
}

function getIconForType($type) {
	return;
}

function doUpload(){
	global $GENERAL, $SQL;
	
	$error_types = array(
		1=>'The file exceeds the php.ini upload_max_filesize directive',
		'The file exceeds the MAX_FILE_SIZE directive',
		'The file was only partially uploaded.',
		'No file was uploaded.',
		6=>'Missing a temporary folder.',
		'Failed to write file to disk.',
		'A PHP extension stopped the file upload.'
		); 
	
	if ($_GET['t'] == 'do') {
		echo '<pre>';
		$_POST['id'] == mysql_real_escape_string($_POST['id']);
		$errors = 0;
		foreach ($_FILES as $file) {
			if ($file['error'] > 0) {
				if ($file['error'] != UPLOAD_ERR_NO_FILE) {
					$errors++;
					echo 'Failed to upload '.$file['name'].' - '.$error_types[$file['error']];
					echo "\n";
					continue;
				}
			} else {
				$originalName = basename($file['name']);
				$pathInfo = pathinfo($originalName);
				$extension = '';
				if ($pathInfo['extension']) { $extension = $pathInfo['extension']; }
				$newName = md5($_POST['id'].$originalName.time()).'.'.$extension;
				$path = $GENERAL['KB_PATH'].$newName;
				move_uploaded_file($file['tmp_name'], $path);
				$query = mysql_query("INSERT INTO ".$SQL['DATABASE'].".kbFiles (
									`id`, 
									`kbid`,
									`filename`,
									`urlfilename`,
									`path`,
									`size`,
									`type`,
									`date`,
									`createdby`,
									`index`
									)
									VALUES (
									NULL, 
									'".$_POST['id']."',
									'".$originalName."',
									'".$newName."',
									'".$path."',
									'".$file['size']."',
									'".$extension."',
									'".time()."',
									'".$_SESSION['displayname']."',
									''
									)");
			}			
		}
		echo '</pre>';
		if ($errors == 0) {
			redirect('?p=kb&a=view&id='.$_POST['id']);
		}
	} else {
		$fileCount = (is_numeric($_POST['count'])) ? $_POST['count'] : 1;
		?>
		<form method="post" action="?p=kb&a=upload">
		<?php
			if (is_numeric($_GET['id'])) {
				$id = $_GET['id'];
				echo '<input type="hidden" name="id" value="'.$_GET['id'].'">';
			} elseif (is_numeric($_POST['id'])) {
				$id = $_POST['id'];
				echo '<input type="hidden" name="id" value="'.$_POST['id'].'">';
			} else {
				echo 'Invalid ID. Please go back and try again.';
				return;
			}
			$id = mysql_real_escape_string($id);
		?>
		<div>Number of attachments: </div><input type="text" name="count" size="1" value="<?php echo $fileCount; ?>"><br/>
		<p><div>&nbsp;</div><input type="submit" value="Update"></form></p>
		<hr>
		<form enctype="multipart/form-data" method="post" action="?p=kb&a=upload&t=do">
		<input type="hidden" name="id" value="<?php echo $_GET['id'];?>">
		<div>&nbsp;</div><br><br>
		<?php
			for ($i = 1; $i <= $fileCount; $i++) {
				echo '<div>Attachment #'.$i.'</div><input type="file" name="file'.$i.'" size="40"><br/>';
			}
		?>
		<p><div>&nbsp;</div><input type="submit" value="Attach"></form></p>
		</form>
		<?php
	}
}

function humanify($size) {
	define("ONE_GIGABYTE", 1073741824);
	define("ONE_MEGABYTE", 1048576);
	define("ONE_KILOBYTE", 1024);

	if ($size >= ONE_GIGABYTE) {
		return number_format($size / ONE_GIGABYTE, 1).' GB';
	} elseif ($size >= ONE_MEGABYTE) {
		return number_format($size / ONE_MEGABYTE, 1).' MB';
	} elseif ($size >= ONE_KILOBYTE) {
		return number_format($size / ONE_KILOBYTE, 1).' KB';
	} else {
		return $size.' Bytes';
	}
}
?>

