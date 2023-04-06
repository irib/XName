<?
/*
	This file is part of XName.org project
	See	http://www.xname.org/ for details
	
	License: GPLv2
	See LICENSE file, or http://www.gnu.org/copyleft/gpl.html
	
	Author(s): Yann Hirou <hirou@xname.org>

*/

// ********************************************************
// Nothing to be changed here regarding design
// ********************************************************


require 'libs/xname.php';

$config = new Config();

$html = new Html();

print $html->header('Log viewer');


// protect variables for db usage
if(isset($_REQUEST) && isset($_REQUEST['idsession'])){
	$idsession=$_REQUEST['idsession'];
}
if(isset($idsession)){
	$idsession = addslashes($idsession);
}

if(isset($_REQUEST) && isset($_REQUEST['login'])){
	$login=$_REQUEST['login'];
}
if(isset($login)){
	$login = addslashes($login);
}

if(isset($_REQUEST) && isset($_REQUEST['password'])){
	$password=$_REQUEST['password'];
}
if(isset($password)){
	$password = addslashes($password);
}

$db = new Db();

$user = new User($login,$password,$idsession);

if(!notnull($idsession)){
	$idsession=$user->idsession;
}

if((isset($_REQUEST) && $_REQUEST['logout']) ||
	(!isset($_REQUEST) && $logout == 1)){
	$user->logout($idsession);
}

if(isset($idsession)){
	$link="?idsession=" . $idsession;
}else{
	$link="";
}

if($user->error){
	print "<font color=\"red\">" . $user->error . "</font>\n";
}

if($user->authenticated==1){

	if(isset($_REQUEST)){
		$zonename = $_REQUEST['zonename'];
		$zonetype = $_REQUEST['zonetype'];
	}
	$zonename = addslashes($zonename);
	$zonetype = addslashes($zonetype);
	$zone = new Zone($zonename,$zonetype);
	if($zone->error){
		print "<font color=\"red\">" . $user->error . "</font>\n";
	}else{
		$title = "Last logs for " . $zone->zonename;
		$content = "";
		// if $deleteall, delete & insert a "deleted" line in logs
		// maybe only admin should be able to delete logs... ?
		if((isset($_REQUEST) && $_REQUEST['deleteall']) ||
			(!isset($_REQUEST) && $deleteall == 1)){
			if($zone->zoneLogDelete()){
				$content = '<font color="red">' . $zone->error . '</font>';
			}
		}
		
		$content .= '
		<table border="0" width="100%">' .
		$zone->zoneLogs("loghighlight","loglowlight") . 
		'</table>
		<div align="center">
		<form action="' . $PHP_SELF . '" method="get">
		<input type="hidden" name="deleteall" value="1">
		<input type="hidden" name="idsession" value="' . $idsession . '">
		<input type="hidden" name="zonename" value="' . $zonename . '">
		<input type="hidden" name="zonetype" value="' . $zonetype . '">		
		<input type="submit" name="deletebutton" value="Delete all logs">
		</form></div>';
	
		print $html->box($title,$content);
	}
}

// print close "window"

print $html->footer();
?>
