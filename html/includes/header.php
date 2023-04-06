<?
require "libs/xname.php";

$config = new Config();

$html = new Html();

print $html->header($config->sitename);

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
$db = new Db();

$user = new User($login,$password,$idsession);

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
}else{
	$link="?";
}

print $html->subheader($link);


if($user->error){
	print "<font color=\"red\">" . $user->error . "</font>\n";
}


print $html->globaltableleft();
?>
