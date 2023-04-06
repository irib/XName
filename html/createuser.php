<?
/*
	This file is part of XName.org project
	See	http://www.xname.org/ for details
	
	License: GPLv2
	See LICENSE file, or http://www.gnu.org/copyleft/gpl.html
	
	Author(s): Yann Hirou <hirou@xname.org>

*/

// create a new user
// parameters :
// -void
// - $loginnew,$passwordnew,$confirmpasswordnew, $emailnew

// headers 
include 'includes/header.php';


if(isset($_REQUEST) && isset($_REQUEST['loginnew'])){
	$loginnew=$_REQUEST['loginnew'];
}
if(isset($loginnew)){
	$loginnew = addslashes($loginnew);
}

if(isset($_REQUEST) && isset($_REQUEST['passwordnew'])){
	$passwordnew=$_REQUEST['passwordnew'];
}
if(isset($passwordnew)){
	$passwordnew = addslashes($passwordnew);
}

if(isset($_REQUEST) && isset($_REQUEST['confirmpasswordnew'])){
	$confirmpasswordnew=$_REQUEST['confirmpasswordnew'];
}
if(isset($confirmpasswordnew)){
	$confirmpasswordnew = addslashes($confirmpasswordnew);
}

if(isset($_REQUEST) && isset($_REQUEST['email'])){
	$email=$_REQUEST['email'];
}
if(isset($email)){
	$email = addslashes($email);
}

// zone numbers
include 'includes/currentzones.php';


// end left column


// ********************************************************
// MODIFY THIS TO CHANGE DESIGN
// ********************************************************
print $html->globaltablemiddle();
// ********************************************************

if($config->public){
// main content

	$title='Create a new user';

	if(!isset($loginnew)){
	$content ='
	<form action="' . $PHP_SELF . '" method="post">
			<table border="0" width="100%">
			<tr><td align="right">
			login: </td><td><input type="text" name="loginnew">
			</td></tr>
			<tr><td align=right>
			your <font color="red">valid</font> email: </td>
			<td><input type="text" name="email">
			</td></tr>
			<tr><td align="right">
			new password: </td>
			<td><input type="password" name="passwordnew">
			</td></tr>
			<tr><td align="right">
			confirm password: </td>
			<td><input type="password" name="confirmpasswordnew">
			</td></tr>
			';
			if($config->advancedinterface){
				$content .= '<tr><td align="right">Advanced interface<br />(for SOA params, TTL, etc..)</td>
				<td><input type=checkbox name="advanced"></td></tr>
				';
			}
			
			$content .= '<tr><td colspan="2">I have read and I understand ' . $config->sitename . ' disclaimer,
			 available at 
<a href="disclaimer.php" 
onclick="window.open(\'disclaimer.php\',\'M\',\'toolbar=no,location=no,directories=no,status=no,alwaysraised=yes,dependant=yes,menubar=no,width=640,height=480\');
return false">' . $config->mainurl . 'disclaimer.php</a>
			 <input type="checkbox" name="ihaveread" value="1"></td></tr>
			<tr><td colspan="2" align="center"><input type="submit"
			value="Create my user"></td></tr>
			</table>
</form>
';
	}else{ // !isset($loginnew)
	// $loginnew is set ==> check & save & mail
		$content = "";

		$error = 0;
	
		$missing = "";
	
		if(!notnull($loginnew)){
			$missing .= ' login,';
		}
		if(!notnull($passwordnew)){
			$missing .= ' password,';
		}
		if(!notnull($confirmpasswordnew)){
			$missing .= ' confirm password,';
		}
		if(!notnull($email)){
			$missing .= ' email,';
		}
		if((isset($_REQUEST) && $_REQUEST['ihaveread'] != 1) || (!isset($_REQUEST)
		&& $ihaveread != 1)){
			$missing .= ' I have read the disclaimer,';
		}
	
		if(notnull($missing)){
			$error = 1;
			$missing = substr($missing,0, -1);
			$content .= '<font color="red">Error, missing fields:'.$missing.'</font><br />';
		}
	
	
		if(!$error){
			if(!checkName($loginnew)){
				$error = 1;
				$content .= '<font color="red">Error: bad login name</font><br />';
			}
		
			if(!checkEmail($email)){
				$error = 1;
				$content .= '<font color="red">Error: bad email syntax. Be careful,
			dot \'.\' before the \'@\' is not allowed in DNS
			configuration</font>
			<br />';
			}else{
				$result = vrfyEmail($email);
				if($result != 1){
					$error =1;
					$content .= '<font color="red">Error:' . $result . '</font><br />';
				}
			}
	
			if($passwordnew != $confirmpasswordnew){
				$error = 1;
				$content .= '<font color="red">Error: passwords don\'t
			match</font><br />';
			}
		} // end no error after empty checks
	


		if(!$error){
		// ****************************************
		// *            Create new user           *
		// ****************************************
			$newuser = new User('','','');
			$newuser->userCreate($loginnew,$passwordnew,$email);
			if($newuser->error){
				$content .= '<font color="red">Error: '.$newuser->error .'</font>';
			}else{
			// if advanced interface, save status
			if($config->advancedinterface){
				if((isset($_REQUEST) && $_REQUEST['advanced']) ||
					(!isset($_REQUEST) && $advanced)){
					 $newuser->changeAdvanced("1");
				}else{ 
					$newuser->changeAdvanced("0");
				}
			} // end advancedinterface set
			
			// send email & print message
			// send mail to validate email

			// generate random ID 
				$randomid= $newuser->generateIDEmail();
			
			// send mail
			// print what happened

				$mailbody = '
This is an automatic email.

You have registered a new user on ' . $config->sitename . '.
This mail is send to you to validate your email address, ' . $email .'.

Please go on 
' . $config->mainurl . 'validate.php?id=' . $randomid . '

Your account can not be used unless you have validated your email address.

Regards,

-- 
' . $config->emailsignature . '
';

			// insert ID in DB
				if(!$newuser->storeIDEmail($newuser->userid,$email,$randomid)){
					$content .= $newuser->error;
				}else{
			
					if(mailer($config->emailfrom,$email,$config->sitename . " email validation","",$mailbody)){
							$content .= 'A mail was succesfully sent to you, to validate your
					email address. Just reply to it to activate your account, or
					follow embedded link.
					
					Once it is validated, log in on <a href="/">main page</a> to
					add zones.<p />
					
					';
					}else{
						$content .= 'An error occured when sending you an email.
					Please verify that your email address ' .$email. ' is working
					properly. In doubt, you can contact us at <a
					href="mailto:' . $config->contactemail . '" 
					class="linkcolor">' . $config->contactemail . '</a>.
					';
					}
			
				}
		
			} // user created successfully
	
		}else{ // error, print form again
			$content .='
<form action="' . $PHP_SELF . '" method="post">
			<table border="0" width="100%">
			<tr><td align="right">
			Login: </td><td><input type="text" name="loginnew"
			value="'.$loginnew.'">
			</td></tr>
			<tr><td align=right>
			your <font color="red">valid</font> email: </td>
			<td><input type="text" name="email" value="'.$email.'">
			</td></tr>
			<tr><td align="right">
			new password for this zone: </td>
			<td><input type="password" name="passwordnew">
			</td></tr>
			<tr><td align="right">
			confirm password: </td>
			<td><input type="password" name="confirmpasswordnew">
			</td></tr>
			<tr><td colspan="2">I have read and I understand ' . $config->sitename . '
			disclaimer,<br /> available at 
<a href="disclaimer.php" 
onclick="window.open(\'disclaimer.php\',\'M\',\'toolbar=no,location=no,directories=no,status=no,alwaysraised=yes,dependant=yes,menubar=no,width=640,height=480\');
return false">' . $config->mainurl . 'disclaimer.php</a>
			 <input type="checkbox" name="ihaveread" value="1"></td></tr>
			<tr><td colspan="2" align="center"><input type="submit"
			value="Create my user"></td></tr>
			</table>
</form>
';
		}
	
	} // end else $loginnew not null

}else{ // end $config->public
	$title="ERROR";
	$content="This is not a public server";
}
print $html->box($title,$content);


// ********************************************************
// MODIFY THIS TO CHANGE DESIGN
// ********************************************************
print $html->globaltableright();
// ********************************************************

// contact 
include 'includes/contact.php';


// ********************************************************
// MODIFY THIS TO CHANGE DESIGN
// ********************************************************
print $html->globaltableend();
// ********************************************************


print $html->footer();

?>
