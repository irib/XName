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

$html = new Html();

$db = new Db($config);

if(isset($_REQUEST)){
	if(isset($_REQUEST['language'])){
		$lang = $_REQUEST['language'];
	}else{
		$lang = $config->defaultlanguage;
	}
}else{
	if(isset($language)){
		$lang=$language;
	}else{
		$lang = $config->defaultlanguage;
	}
}
include 'includes/strings/' . $lang . '/strings.php';
$html->initialize();
$user = new User($login,$password,$idsession);
$lang = $config->defaultlanguage;
// overwrite default strings
if(isset($user->lang)){
	$lang = $user->lang;
}else{
	if(isset($_REQUEST)){
		if(isset($_REQUEST['language'])){
			$lang = $_REQUEST['language'];
		}else{
			$lang = $config->defaultlanguage;
		}
	}else{
		if(isset($language)){
			$lang=$language;
		}
	}
}

// verify if language exists ! 
if(!is_dir('includes/strings/' . $lang)){
	$lang = $config->defaultlanguage;
}

include 'includes/strings/' . $lang . '/strings.php';

// reinitialize with definitive right language
$html->reinitialize();

print $html->header($l['str_dig_zone_title']);

if(!notnull($idsession)){
	$idsession=$user->idsession;
}

if((isset($_REQUEST['logout']) && $_REQUEST['logout']) || (isset($logout) &&
$logout)){
	$user->logout($idsession);
}

if(notnull($idsession)){
	$link="?idsession=" . $idsession . "&language=" . $lang;
}else{
	$link="?language=" .$lang;
}

if($user->error){
	print $html->generic_error . $user->error . $html->generic_error_end;
}

if($user->authenticated==1){
	if(isset($_REQUEST)){
		$zonename = $_REQUEST['zonename'];
		$zonetype = $_REQUEST['zonetype'];
		$server = $_REQUEST['server'];		
	}
	$zone = new Zone($zonename,$zonetype);
	if($zone->error){
	print $html->generic_error . $zone->error . $html->generic_error_end;
	}else{
		if($zone->RetrieveUser() != $user->userid){
			print $html->generic_error . $l['str_you_dont_own_this_zone'] 
			 	. $html->generic_error_end;
		}else{
			$title = sprintf($l['str_zone_content_for_x_on_server_x'],
						$zone->zonename,$server);
			$content = '
			<table border="0" width="100%"><pre>' .
			zoneDig($server,$zonename) . 
			'</pre></table>';
	
			print $html->box($title,$content);
		}
	}
}

// print close "window"

print $html->footer();
?>
