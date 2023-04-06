<?
/*
	This file is part of XName.org project
	See	http://www.xname.org/ for details
	
	License: GPLv2
	See LICENSE file, or http://www.gnu.org/copyleft/gpl.html
	
	Author(s): Yann Hirou <hirou@xname.org>

*/

  // modify user parameters
  
// headers 
include 'includes/header.php';

// zone numbers
include 'includes/currentzones.php';

// login & logs
include 'includes/login.php';


// end left column

// ********************************************************
// MODIFY THIS TO CHANGE DESIGN
// ********************************************************
print $html->globaltablemiddle();
// ********************************************************

$title = "User Preferences";
// main content
if($user->authenticated != 1){
	$content = "You must log in before editing user preferences";
}else{
	// print login, email, change password
	// valid or not
	if((isset($_REQUEST) && !$_REQUEST['modify']) ||
		(!isset($_REQUEST) && !$modify)){
		$content = '
		<form action="' . $PHP_SELF . '" method="post">
		<input type="hidden" name="modify" value="1">
		<input type="hidden" name="idsession" value="' . $user->idsession . '">
		<table border="0" width="100%">
		<tr><td align="right">login: </td><td><div class="boxheader">' . $user->login .
		'</div></td></tr>
		<tr><td align="right">You can change your login:</td><td><input type="text"
		name="newlogin"></td></tr>
		';
		if(!$config->usergroups || $usergrouprights == 'A'){
			$content .= '<tr><td colspan="2"><font color="red">warning:</font> changing your email address will
			prevent you to log in until you have validated it. Be sure to provide
			a <b>valid</b> email address or you will not be able to access your account
			anymore.</td></tr>
			<tr><td align="right">your <font color="red">valid</font> email:</td><td><input type=text name="email" value="' . 
			$user->Retrievemail() . '"></td></tr>
			';
		}
		$content .= '<tr><td colspan="2">you need to type your current password only if you wish
		to change it</td></tr>
		<tr><td align="right">current password:</td><td><input type="password"
		name="oldpass"></td></tr>
		<tr><td align="right">new password:</td><td><input type="password"
		name="passnew"></td></tr>
		<tr><td align="right">confirm password:</td><td><input type="password"
		name="confirmpassnew"></td></tr>
		';
		if($config->advancedinterface){
			$content .= '<tr><td align="right">Advanced interface<br />(for SOA params, TTL, etc..)</td>
			<td><input type=checkbox name="advanced"';
			if($user->advanced){
				$content .= ' checked';
			}
			$content .='></td></tr>
			';
		}
		$content .= '<tr><td colspan="2" align="center"><input type="submit" value="Modify"></td></tr>
		</table>
		</form>
		';
	}else{
		$content = "";
		// check if newlogin already exists or not
		if((isset($_REQUEST) && notnull($_REQUEST['newlogin'])) ||
			(!isset($_REQUEST) && notnull($newlogin))){
			if(isset($_REQUEST)){
				$newlogin = $_REQUEST['newlogin'];
			}
			$newlogin=addslashes($newlogin);
			$content .= 'Changing your login name... ';
			if(!checkName($newlogin)){
				$error = 1;
				$content .= '<font color="red">Error: bad login name</font><br />';
			}else{
				if($user->Exists($newlogin)){
					$content .= '<font color="red">Error, login already exists</font><br />';
					$error = 1;
				}else{
					if($user->changeLogin($newlogin)){
						$content .= 'OK<br />';
					}else{
						$error = 1;
						$content .= '<font color="red">Error: ' . $user->error .
						'</font><br />';
					}
				}
			}
		} // end if (newlogin)
		
		
		// check if mail modified or not
		// if modified ==> valid=0
		// only for admin users
		if(!$config->usergroups || $usergrouprights == 'A'){
			if(isset($_REQUEST)){
				$email = $_REQUEST['email'];
			}
			if($email != $user->Retrievemail()){
				// mail modified
				// check & warn if bad
				$content .= 'Modifying email... ';
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
				if(!$error){
					$email= addslashes($email);
					if(!$user->changemail($email)){
						$error = 1;
						$content .= '<font color="red">Error:' . $user->error . '</font><br />';
					}else{

						// send email
						// send mail to validate email

						// generate random ID 
						$randomid= $user->generateIDEmail();
			
						// send mail

						$mailbody = '
This is an automatic email.

You have modified your email address on ' . $config->sitename . '.
This mail is send to you to validate your email address, ' . $email .'.

Please go on 
' . $config->mainurl . 'validate.php?id=' . $randomid . '

Your account can not be used unless you have validated your email address.

Regards,

-- 
' . $config->emailsignature . '


';

						// insert ID in DB
						if(!$user->storeIDEmail($user->userid,$email,$randomid)){
							$content .= $user->error;
						}else{
				
							if(mailer($config->tousersource,$email, 
								$config->sitename .
								" email validation","",$mailbody)){

								$content .= 'OK. <p />A mail was succesfully sent to you, to validate your
								email address. Just follow embedded link to activate your
								account again.<p />
								';
							}else{
								$content .= 'An error occured when sending you an email.
								Please verify that your email address ' .$email. ' is working
								properly. In doubt, you can contact us at <a
								href="mailto:' . $config->contactemail . '"
								class="linkcolor">' . $config->contactemail . '</a>.
								';
							}
				
						} // end storeIDEmail
					} // end changemail
				} // end no error
			} // end mail modified
		} // end usergroupright == A
		
		if($config->advancedinterface){
			if((isset($_REQUEST) && $_REQUEST['advanced']) ||
				(!isset($_REQUEST) && $advanced)){
				 $user->changeAdvanced("1");
			}else{ 
				$user->changeAdvanced("0");
			}
		} // end advancedinterface set
		
		if(!$error){
			if((isset($_REQUEST) && $_REQUEST['oldpass']) ||
				(!isset($_REQUEST) && $oldpass)){
				$content .= 'Modifying password... ';
				// check if old = current
				if(isset($_REQUEST)){
					$oldpass = $_REQUEST['oldpass'];
				}
				$oldpass = addslashes($oldpass);
				if(md5($oldpass) == $user->Retrievepassword()){
					// check if new = confirmnew
					if(isset($_REQUEST)){
						$passnew = $_REQUEST['passnew'];
						$confirmpassnew = $_REQUEST['confirmpassnew'];
					}
					if($passnew != $confirmpassnew){
						$error = 1;
						$content .= '<font color="red">Error: new passwords do not
						match.</font><br />';
					}else{
						// update user
						$passnew = addslashes($passnew);
						$user->UpdatePassword($passnew);
						if(!$user->error){
							$content .= 'OK<br />';
						}
					}
				}else{
					$error = 1;
					$content .= '<font color="red">Error: bad current
					password.</font></br />';
				}
			}
		} // end no error

		if($user->error){
			$error = 1;
			$content .= '<font color="red">Error: ' . $user->error . '</font><br
			/>';
		}
		
		if($error){
			// rollback
			$content .= 'Some errors occured, not all modification have been done.';
		}else{
			$content .= 'Your parameters were successfully updated.';
			if($email != $user->Retrievemail()){
				$content .= '<br /><font color="red">warning</font> you have
				changed your email address. An email has been sent to you to
				validate your new email address. Unless you validate this email
				address, you will not be able to log on anymore.<br />
				If <b>' . $email . '</b> is not the right one, go back and modify
				it before logging out.';
			}
		}
	}
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
