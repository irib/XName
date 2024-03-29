<?
/*
	This file is part of XName.org project
	See	http://www.xname.org/ for details
	
	License: GPLv2
	See LICENSE file, or http://www.gnu.org/copyleft/gpl.html
	
	Author(s): Yann Hirou <hirou@xname.org>

*/

  // modify user parameters

$page_title="str_user_preferences";  
// headers 
include 'includes/header.php';

if(file_exists("includes/left_side.php")) {
        include "includes/left_side.php";
}else{
        include "includes/left_side_default.php";
}


// end left column

// ********************************************************
// MODIFY THIS TO CHANGE DESIGN
// ********************************************************
print $html->globaltablemiddle();
// ********************************************************

$title = $l['str_user_preferences'];
$localerror=0;
// main content
if($user->authenticated != 1){
	$content = $l['str_must_log_before_editing_pref'];
}else{
	// print login, email, change password
	// valid or not
	if((isset($_REQUEST) && !isset($_REQUEST['modify'])) ||
		(!isset($_REQUEST) && !$modify)){
		$content = '
		<form action="' .  $_SERVER["PHP_SELF"] . '" method="post">
		<input type="hidden" name="modify" value="1">
		' . $hiddenfields . '
		<table border="0" width="100%">
		<tr><td align="right">' . $l['str_login'] . ': </td><td><div class="boxheader">' . $user->login .
		'</div></td></tr>
		<tr><td align="right">' . $l['str_you_can_change_your_login'] . ':</td><td><input type="text"
		name="newlogin"></td></tr>
		';
		if(!$config->usergroups || $usergrouprights == 'A'){
			$content .= '<tr><td colspan="2">' . 
						$html->generic_warning . 
						 $l['str_changing_email_warning'] . 
						 $html->generic_warning_end . '</td></tr>
			<tr><td align="right">' . $l['str_your_valid_email'] . ':</td><td><input type=text name="email" value="' . 
			$user->Retrievemail() . '"></td></tr>
			';
		}
		$content .= '<tr><td colspan="2">' . 
			$l['str_type_your_password_to_change_it'] . '</td></tr>
		<tr><td align="right">' . $l['str_current_password'] . ':</td><td><input type="password"
		name="oldpass"></td></tr>
		<tr><td align="right">' . $l['str_new_password'] . ':</td><td><input type="password"
		name="passnew"></td></tr>
		<tr><td align="right">' . $l['str_confirm_password'] . ':</td><td><input type="password"
		name="confirmpassnew"></td></tr>
		';
		if($config->advancedinterface){
			$content .= '<tr><td align="right">' . $l['str_advanced_interface']  . 
			'<br />(' . $l['str_advanced_interface_details'] . ')</td>
			<td><input type=checkbox name="advanced"';
			if($user->advanced){
				$content .= ' checked';
			}
			$content .='></td></tr>
			';
		}
		if($config->ipv6interface){
			$content .= '<tr><td align="right">' . 
			$l['str_ipv6_interface'] . '<br />(' . 
			$l['str_ipv6_interface_details'] . ')</td>
			<td><input type=checkbox name="ipv6"';
			if($user->ipv6){
				$content .= ' checked';
			}
			$content .='></td></tr>
			';
		}
		if($config->txtrecords){
			$content .= '<tr><td align="right">' . 
			$l['str_txt_records'] . '<br />(' . 
			$l['str_txt_records_details'] . ')</td>
			<td><input type=checkbox name="txtrecords"';
			if($user->txtrecords){
				$content .= ' checked';
			}
			$content .='></td></tr>
			';
		}
		
		$content .= '<tr><td align="right">' . 
		$l['str_number_of_rows_per_record'] . ':</td>
		<td><input type=text name="nbrows" value="' . $user->nbrows . '" size="3"></td></tr>
		';
		
		$content .= '<tr><td align="right">' . 
					$l['str_language']  . ':</td>
					<td><select name="newlang">';
		// select current available langs
		$list = GetDirList('includes/strings');
		reset($list);
		while($newlang = array_shift($list)){
			if(!strcmp($newlang,$lang)){
				$content .= '<option value="' . $lang . '" selected>' . $lang . '</option>';
			}else{
				$content .= '<option value="' . $newlang . '">' . $newlang . '</option>';			
			}
		}
		
		$content .= '</select></td></tr>
		<tr><td colspan="2" align="center">
		<input type="submit" value="' . $l['str_modify_button'] . '"></td></tr>
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
			$content .= $l['str_changing_login_name'] . '... ';
			if(!checkName($newlogin)){
				$localerror = 1;
				$content .= $html->generic_error .  
							$l['str_bad_login_name'] .
							$html->generic_error_end . '<br />';
			}else{
				if($user->Exists($newlogin)){
					$content .= $html->generic_error .
								$l['str_login_already_exists'] . 
								$html->generic_error_end . 
								'<br />';
					$localerror = 1;
				}else{
					if($user->changeLogin($newlogin)){
						$content .= $l['str_ok'] . '<br />';
					}else{
						$localerror = 1;
						$content .= $html->generic_error . $user->error .
						$html->generic_error_end . '<br />';
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
				$content .= $l['str_changing_email'] . '... ';
				if(!checkEmail($email)){
					$localerror = 1;
					$content .= $html->generic_error .
					 			$l['str_bad_email_syntax']  . 
								$html->generic_error_end . '
								<br />';
				}else{
					$result = vrfyEmail($email);
					if($result != 1){
						$localerror =1;
						$content .= $html->generic_error . $result . 
									$html->generic_error_end . '<br />';
					}
				}
				if(!$localerror){
					$email= addslashes($email);
					if(!$user->changemail($email)){
						$localerror = 1;
						$content .= $html->generic_error . $user->error . 
									$html->generic_error_end . '<br />';
					}else{

						// send email
						// send mail to validate email

						// generate random ID 
						$randomid= $user->generateIDEmail();
			
						// send mail

						include('includes/user_sendmail.php');
						
						// insert ID in DB
						if(!$user->storeIDEmail($user->userid,$email,$randomid)){
							$content .= $user->error;
						}else{
				
							if(mailer($config->tousersource,$email, 
								$config->sitename .
								" " . $l['str_email_validation'],"",$mailbody)){

								$content .= $l['str_ok'] . '<p />' .
								$l['str_email_validation_mail_sent'] . '<p />
								';
							}else{
								$content .= 
								sprintf($l['str_email_validation_error_occured_plz_vrfy_that_x_is_working_x'],
										$email,'<a	href="mailto:' . $config->contactemail . '"
								class="linkcolor">' . $config->contactemail . '</a>');
							}
				
						} // end storeIDEmail
					} // end changemail
				} // end no error
			} // end mail modified
		} // end usergroupright == A
		
		if($config->advancedinterface){
			if((isset($_REQUEST) && $_REQUEST['advanced']) ||
				(!isset($_REQUEST) && $advanced)){
				$advanced = 1;
			}else{ 
				$advanced = 0;
			}
		}else{ // end advancedinterface set
			$advanced = 0;
		}
			
		if($config->ipv6interface){
			if((isset($_REQUEST) && $_REQUEST['ipv6']) ||
				(!isset($_REQUEST) && $ipv6)){
				$ipv6 = 1;
			}else{ 
				$ipv6 = 0;
			}
		}else{ // end ipv6interface set
			$ipv6=0;
		}
		if($config->txtrecords){
			if((isset($_REQUEST) && $_REQUEST['txtrecords']) ||
				(!isset($_REQUEST) && $txtrecords)){
				$txtrecords = 1;
			}else{ 
				$txtrecords = 0;
			}
		}else{ // end ipv6interface set
			$txtrecords=0;
		}

		if(isset($_REQUEST) && $_REQUEST['nbrows']){
			$nbrows = addslashes($_REQUEST['nbrows']);
		}else{
			if(!isset($_REQUEST) && $nbrows){
				// nothing to be done
			}else{
				$nbrows = $config->defaultnbrows;
			}
		}
		if(isset($_REQUEST) && $_REQUEST['newlang']){
			$newlang = addslashes($_REQUEST['newlang']);
		}else{
			if(!isset($_REQUEST) && $newlang){
			// nothing to be done
			}else{
				$newlang = $config->defaultlang;
			}
		}
		$user->changeFlags($advanced,$ipv6,$txtrecords,$nbrows,addslashes($newlang));
		
		if(!$localerror){
			if((isset($_REQUEST) && $_REQUEST['oldpass']) ||
				(!isset($_REQUEST) && $oldpass)){
				$content .= $l['str_changing_password'] . '... ';
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
						$localerror = 1;
						$content .= $html->generic_error . 
						 			$l['str_new_passwords_dont_match'] .
						 			$html->generic_error_end . '<br />';
					}else{
						// update user
						$passnew = addslashes($passnew);
						$user->UpdatePassword($passnew);
						if(!$user->error){
							$content .= $l['str_ok'] . '<br />';
						}
					}
				}else{
					$localerror = 1;
					$content .= $html->generic_error . 
					 			$l['str_bad_current_password'] . 
					 			$html->generic_error_end . '</br />';
				}
			}
		} // end no error

		if($user->error){
			$localerror = 1;
			$content .= $html->generic_error . $user->error . 
						$html->generic_error_end . '<br />';
		}
		
		if($localerror){
			// rollback
			$content .= $l['str_some_errors_occured'];
		}else{
			$content .= $l['str_parameters_successfully_updated'];
			if($email != $user->Retrievemail()){
				$content .= '<br />' . $html->generic_warning . 
							$l['str_email_changed_warning'] . '<br />' . 
							sprintf($l['str_if_x_is_not_the_right_one'],$email) . 
							$html->generic_warning_end;
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

if(file_exists("includes/right_side.php")) {
        include "includes/right_side.php";
}else{
        include "includes/right_side_default.php";
}

// ********************************************************
// MODIFY THIS TO CHANGE DESIGN
// ********************************************************
print $html->globaltableend();
// ********************************************************


print $html->footer();

?>
