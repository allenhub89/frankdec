<?php

##################################
# Parse config file and set vars #
##################################

$ini = parse_ini_file("../config.ini.php", true);
$admin = $ini['login']['admin'];
$def_path = $ini['login']['default'];
$subdirectories = $ini['filepaths']['subdirectories'];

session_start();

if(!($_SESSION['user_name'] == $admin || $_SESSION['user_name'] == 'toast'))
{
  header('Location: $def_path');
}

$logfilepath = "/var/log/apache2/error.log";

$fh = fopen($logfilepath, 'r');
if($fh){
	$pageText = fread($logfilepath, filesize($logfilepath));

	#converts newlines to <br>
	echo nl2br($pageText);
} 
else
{
	echo "If this is empty, fRNAkenstein doesn't have access to the log file";
}
?>
