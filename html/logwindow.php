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

$html = new Html($config);

print $html->header('Log viewer');


// protect variables for db usage
if(isset($idsession)){
	$idsession=addslashes($idsession);
}
if(isset($login)){
	$login=addslashes($login);
}
if(isset($password)){
	$password=addslashes($password);
}

$db = new Db($config);

$user = new User($db,$login,$password,$idsession);

if(!notnull($idsession)){
	$idsession=$user->idsession;
}

if($logout){
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
	$zonename = addslashes($zonename);
	$zonetype = addslashes($zonetype);
	$zone = new Zone($db,$zonename,$zonetype,$config);
	if($zone->error){
	print "<font color=\"red\">" . $user->error . "</font>\n";
	}else{
		$title = "Last logs for " . $zone->zonename;
		$content = '
		<table border="0" width="100%">' .
		$zone->zoneLogs("loghighlight","loglowlight") . 
		'</table>';
	
		print $html->box($title,$content);
	}
}

// print close "window"

print $html->footer();
?>
