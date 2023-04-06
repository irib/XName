<?
if($user->authenticated != 1){
	// login box
	$title = "Login";
	$content ="";
	if($user->error){
		$content = "<font color=\"red\">" . $user->error . "</font><br />\n";
	}	

	$content .= '
	Log in, or <a href="createuser.php" class="linkcolor">create a new
	user</a><p />
	<form method="post" action="index.php">login: <br /><div align=center><input
	type="text" name="login" /></div><br />
	password: <br /><div align=center><input type="password" name="password"
	/></div><br />
	<div align=center><input type=submit value="Log me in !" /><p>
	<a href="password.php" class="linkcolor">Forgot your password ?</a>
	</p>
	</div>
	</form>
	';
	print $html->box($title,$content);
	
}else{ // if authenticated, 

		// print pref box
	
	$title = $user->login;
	$content = '<div align="center">
	<a href="user.php' . $link . '" class="linkcolor">Change 
	your preferences</a><p />
	<a href="index.php' . $link . '&logout=1">Logout</a>
	</div>
	';
	
	print $html->box($title,$content);
	
	
	$title = "Log Legend";
	$content = '<div align="center"><table border="0">
	<tr><td class="loghighlightINFORMATION" align="center">Information</td></tr>
	<tr><td class="loghighlightWARNING" align="center">Warning</td></tr>
	<tr><td class="loghighlightERROR" align="center">Error</td></tr>
	<tr><td class="loghighlightUNKNOWN" align="center">Unknown</td></tr>		
	</table></div>';
	print $html->box($title,$content);		

		// list all other zones for same email address
	$allzones = $user->listallzones();
	if(!notnull($user->error)){
		$content ='<table border="0" width="100%">';
		while($otherzone= array_pop($allzones)){
			// TODO : NEW ZONE
			$newzone = new Zone($db,$otherzone[0],$otherzone[1],$config);
			$status = $newzone->zonestatus();
			switch($status) {
				case 'I':
					$class='INFORMATION';
					break;
				case 'W':
					$class='WARNING';
					break;
				case 'E':
					$class='ERROR';
					break;
				default:
					$class='UNKNOWN';
			}
			$content .= '<tr><td><a href="modify.php'
			.$link.'&zonename=' . $newzone->zonename . '&zonetype=' .
			$newzone->zonetype . '" class="linkcolor">' .
			 $newzone->zonename . '</a> (' . $newzone->zonetype . ')</td><td
			 class="loghighlight' . $class . '" align="center"><a href="logwindow.php'
			 .$link .'&zonename=' .$newzone->zonename . '&zonetype=' .
			$newzone->zonetype . '" class="linkcolor"
			onclick="window.open(\'logwindow.php'
			 .$link .'&zonename=' .$newzone->zonename . '&zonetype=' .
			$newzone->zonetype .
		
'\',\'M\',\'toolbar=no,location=no,directories=no,status=yes,alwaysraised=yes,dependant=yes,resizable=yes,scrollbars=yes,menubar=no,width=640,height=480\');
return false">'.
			 $status . '</a></td></tr>';
		}
		$content .= '</table>';
	}else{
		$content = $user->error;
	}
	$title = '<a href="index.php' . $link . '" class="boxtitle">All your zones</a>';
	print $html->box($title,$content);
}

?>
