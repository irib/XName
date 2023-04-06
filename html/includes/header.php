<?
require "libs/xname.php";

$config = new Config();

// protect variables for db usage
if(isset($_REQUEST)){
	if(isset($_REQUEST['idsession'])){
		$idsession=addslashes($_REQUEST['idsession']);
	}else{
		$idsession='';
	}
	if(isset($_REQUEST['login'])){
		$login=addslashes($_REQUEST['login']);
	}else{
		$login='';
	}
	if(isset($_REQUEST['password'])){
		$password=addslashes($_REQUEST['password']);
	}else{
		$password='';
	}
	if(isset($_REQUEST['logout'])){
		$logout=$_REQUEST['logout'];
	}else{
		$logout=0;
	}
}else{
	if(isset($idsession)){
		$idsession=addslashes($idsession);
	}else{
		$idsession='';
	}
	if(isset($login)){
		$login=addslashes($login);
	}else{
		$login='';
	}
	if(isset($password)){
		$password=addslashes($password);
	}else{
		$password='';
	}
	if(!isset($logout)){
		$logout=0;
	}
	
}

$html = new Html();




$db = new Db();
$deflangused = 0;
if(isset($_REQUEST)){
	if(isset($_REQUEST['language']) && notnull($_REQUEST['language'])){
		$lang = $_REQUEST['language'];
	}else{
		if(isset($_REQUEST['newlang']) && notnull(isset($_REQUEST['newlang']))){
			$lang = $_REQUEST['newlang'];
		}else{
			$lang = $config->defaultlanguage;
			$deflangused = 1;
		}
	}
}else{
	if(isset($language) && notnull($language)){
		$lang=$language;
	}else{
		if(isset($newlang) && notnull($newlang)){
			$lang = $newlang;
		}else{
			$lang = $config->defaultlanguage;
			$deflangused = 1;
		}
	}
}
include 'includes/strings/' . $lang . '/strings.php';
$html->initialize();
$user = new User($login,$password,$idsession);

// use $idsession in all urls, including first page
if(!notnull($idsession) && $user->authenticated){
	header("Location: " . $SERVER['PHP_SELF'] . "?idsession=" . $user->idsession);
}

// overwrite default strings
if ($deflangused){
if(isset($user->lang)){
	$lang = $user->lang;
}else{
	if(isset($_REQUEST) && isset($_REQUEST['language'])){
		$lang = $_REQUEST['language'];
	}else if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])){
		$lang = ereg_replace(",.*", "", $_SERVER['HTTP_ACCEPT_LANGUAGE']);
	}else{
		if(isset($language)){
			$lang=$language;
		}
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

print $html->header($l[$page_title]);

if($config->usergroups){
	include 'libs/group.php';
	$group = new Group($user->userid);
	if($config->userlogs){
		include 'libs/userlogs.php';
		$userlogs=new UserLogs($group->groupid,$user->userid);
	}
}

	
if(!notnull($idsession)){
	$idsession=$user->idsession;
}

if((isset($_REQUEST['logout']) && $_REQUEST['logout']) || (isset($logout) &&
$logout)){
	$user->logout($idsession);
}

if(notnull($idsession)){
	$link="?idsession=" . $idsession;
	$hiddenfields = '<input type="hidden" name="idsession" value="' . $idsession . '">';
	// add language only if different 
	if(strcmp($lang,$user->lang)){
		$link .= "&language=" . $lang;
		$hiddenfields .= '<input type="hidden" name="language" value="' . $lang . '">';

	}
}else{
	$link="?language=" .$lang;
}

print $html->subheader($link);


if($user->error){
	print $html->generic_error . $user->error . $html->generic_error_end;
}


print $html->globaltableleft();
?>
