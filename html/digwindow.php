<?
/*
	This file is part of XName.org project
	See	http://www.xname.org/ for details
	
	License: GPLv2
	See LICENSE file, or http://www.gnu.org/copyleft/gpl.html
	
	Author(s): Yann Hirou <hirou@xname.org>

*/

// ********************************************************
// Nothing to be changed in this file regarding design
// ********************************************************

require 'libs/xname.php';

$config = new Config();

$html = new Html($config);

print $html->header('Dig zone');


// protect variables for db usage
if(isset($_REQUEST) && isset($_REQUEST['idsession'])){
	$idsession=$_REQUEST['idsession'];
}
if(isset($idsession)){
	$idsession=addslashes($idsession);
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

$db = new Db($config);

$user = new User($db,$login,$password,$idsession);

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
		$server = $_REQUEST['server'];		
	}
	$zone = new Zone($db,$zonename,$zonetype,$config);
	if($zone->error){
	print "<font color=\"red\">" . $user->error . "</font>\n";
	}else{
		$title = "Zone content for " . $zone->zonename . " on $server";
		$content = '
		<table border="0" width="100%"><pre>' .
		zoneDig($server,$zonename) . 
		'</pre></table>';
	
		print $html->box($title,$content);
	}
}

// print close "window"

print $html->footer();
?>
