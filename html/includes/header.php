<?
require "libs/xname.php";

$config = new Config();

$html = new Html($config);

print $html->header('Free DNS Hosting Service');

// protect variables for db usage
if(isset($_REQUEST)){
	if(isset($_GET['idsession'])){
		$idsession=addslashes($_GET['idsession']);
	}
	if(isset($_REQUEST['login'])){
		$login=addslashes($_REQUEST['login']);
	}
	if(isset($_REQUEST['password'])){
		$password=addslashes($_REQUEST['password']);
	}
}else{
	if(isset($idsession)){
		$idsession=addslashes($idsession);
	}
	if(isset($login)){
		$login=addslashes($login);
	}
	if(isset($password)){
		$password=addslashes($password);
	}
}
$db = new Db($config);

$user = new User($db,$login,$password,$idsession);

if(!notnull($idsession)){
	$idsession=$user->idsession;
}

if($_REQUEST['logout'] || $logout){
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
