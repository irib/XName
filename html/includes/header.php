<?
require "libs/xname.php";

$config = new Config();

$html = new Html($config);

print $html->header('Free DNS Hosting Service');


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
