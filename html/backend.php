<?
require "libs/xname.php";

$config = new Config();

// protect variables for db usage
if((isset($_REQUEST)){
	if(isset($_REQUEST['idsession'])){
		$idsession=$_REQUEST['idsession'];
	}
	if(isset($_REQUEST['login'])){
		$login=$_REQUEST['login'];
	}
	if(isset($_REQUEST['password'])){
		$password=$_REQUEST['password'];
	}
}

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

if(!$user->error && $user->authenticated==1){
	$allzones = $user->listallzones();
	if(!notnull($user->error)){
		$numberofzones = count($allzones);
		// print number of zones
		print "NbZones: $numberofzones\n";
		$Izones = 0;
		$Wzones = 0;
		$Ezones = 0;
		while($otherzone= array_pop($allzones)){
			$newzone = new Zone($db,$otherzone[0],$otherzone[1]);
			$status = $newzone->zonestatus();
			switch($status) {
				case 'I':
					$Izones++;
					break;
				case 'W':
					$Wzones++;
					break;
				case 'E':
					$Ezones++;
					break;
			}
		}


	// print number of I zones
	print "NbI: $Izones\n";
	// print number of W zones
	print "NbW: $Wzones\n";	
	// print number of E zones
	print "NbE: $Ezones\n";

	}else{
		print "ERROR User";
	}

}else{
	print "ERROR login - $login & $password";
}

?>
