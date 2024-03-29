<?
 /*
	This file is part of XName.org project
	See	http://www.xname.org/ for details
	
	License: GPLv2
	See LICENSE file, or http://www.gnu.org/copyleft/gpl.html
	
	Author(s): Yann Hirou <hirou@xname.org>

*/

 // send password recovery by email
 
 // args : $id (in email), $account

$page_title="str_password_recovery_title";
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

// main content

$title = $l['str_password_recovery_title'];
if((isset($_REQUEST) && !isset($_REQUEST['id']) && !isset($_REQUEST['account']))
	|| (!isset($_REQUEST) && !isset($id) && !isset($account))){
	$content = $l['str_lost_pwd_fill_in_fields_to_recover_password'] . '<p />
	
	<form action="' .  $_SERVER["PHP_SELF"] . '" method="post">
	<input type="hidden" name="language" value="' . $lang .'">
	<table border="0">
	<tr><td align="right">' . $l['str_login'] . '</td><td><input type="text" name="account"
	/></td></tr>
	<tr><td align="right" valign="top">' . $l['str_or_one_of_your_zones']  . ':</td>
		<td><input
	type="text" name="zonename" /> <br /><input type="radio" name="zonetype"
	value="P">' . $l['str_primary'] . 
	' <input type="radio" name="zonetype" value="S">' . 
	$l['str_secondary']	. '</td></tr>
		<tr><td colspan="2" align="center"><input type="submit" 
	value="' . $l['str_recover_password_button'] . '" /></td></tr>
	</table>
	</form>';
}else{
	$content = '';
	if((isset($_REQUEST) && (notnull($_REQUEST['account']) || 
		notnull($_REQUEST['zonename']))) || 
		(!isset($_REQUEST) && (notnull($account) || notnull($zonename)))){
		$localerror = 0;
		if((isset($_REQUEST) && notnull($_REQUEST['zonename'])) ||
			(!isset($_REQUEST) && notnull($zonename))){
			if(isset($_REQUEST)){
				$zonename = $_REQUEST['zonename'];
			}
			$zonename = addslashes($zonename);
			
			if((isset($_REQUEST) && !notnull($_REQUEST['zonetype'])) ||
				(!isset($_REQUEST) && !notnull($zonetype))){
				$content .= $html->generic_error . 
							$l['str_you_did_not_specify_zonetype'] .
							$html->generic_error_end;
				$localerror = 1;
			}else{
				if(isset($_REQUEST)){
					$zonetype = $_REQUEST['zonetype'];
				}
				$zonetype=addslashes($zonetype);
				$zone=new Zone($zonename,$zonetype);
				if(notnull($zone->error)){
					$content .= $html->generic_error . $zone->error .
								$html->generic_error_end;
					$localerror=1;
				}else{
					$userid = $zone->RetrieveUser();
					$account = $user->RetrieveLogin($userid);
				}
			}
		}else{
			if((isset($_REQUEST) && notnull($_REQUEST['account'])) ||
				(!isset($_REQUEST) && notnull($account))){
				if(isset($_REQUEST)){
					$account = $_REQUEST['account'];
				}
				$account = addslashes($account);
				if(!$user->Exists($account)){
					$localerror = 1;
					$content .= $html->generic_error . 
								$l['str_bad_login_name'] . 
								$html->generic_error_end;
				}
			}
		}
		
		if(!$localerror){
			// generate sessionid
			$id = $user->generateIDRecovery();
			if($user->error){
				$content .= $user->error;
			}else{
				$user->storeIDRecovery($account,$id);
				if($user->error){
					$content .= $html->generic_error . $user->error . 
								$html->generic_error_end;
				}else{
					// send email
					include ('includes/password_sendmail.php');
					$email = $user->getEmail($account);
					if(!$email){
						$content .=  $html->generic_error . 
									$l['str_email_not_sent'] .  
									$html->generic_error_end;
					}else{
						if(mailer($config->tousersource,$email,
						$config->sitename . " " . $l['str_password_recovery']
									,"",$mailbody)){
							$content .= $l['str_recovery_mail_sent'];
						}else{
							$content .= $html->generic_error . 
								$l['str_errors_occured_during_recovery_mail_sending'] . 
							$html->generic_error_end . '<br />';
						}
					}
				}
			}
		}
		
	}else{
		if((isset($_REQUEST) && isset($_REQUEST['id'])) ||
			(!isset($_REQUEST) && isset($id))){
				if(isset($_REQUEST)){
					$id = $_REQUEST['id'];
				}
				$id=addslashes($id);
				if($user->validateIDRecovery($id)){
					// id OK, validate
					$password = $user->GenerateRandomPassword(8);
					$user->updatePassword($password);
					$content .= '
					' . $l['str_password_recovery_login_is'] . 
					': <div class="boxheader">' 
					. $user->retrieveLogin($user->userid) . '</div><p />' .
					$l['str_your_password_is'] . ': <div class="boxheader">' 
					. $password . '</div><p />
					' . sprintf($l['str_you_can_now_use_the_x_main_interface_x_to_log_in'],
					'<a href="index.php">','</a>');
					// reset user
					$user->login="";
				}else{
					$content .= $l['str_bad_password_id'];
				}
		}else{
			if((isset($_REQUEST) && notnull($_REQUEST['zonename'])) ||
				(!isset($_REQUEST) && notnull($zonename))){
				if(isset($_REQUEST)){
					$zonename = $_REQUEST['zonename'];
				}
				$zonename = addslashes($zonename);
				
				if((isset($_REQUEST) && !notnull($_REQUEST['zonetype'])) || 
					(!isset($_REQUEST) && !notnull($zonetype))){
					$content .= $html->generic_error .
								$l['str_you_did_not_specify_zonetype'] .
								$html->generic_error_end;
					$localerror = 1;
				}else{
					if(isset($_REQUEST)){
						$zonetype=$_REQUEST['zonetype'];
					}
					$zonetype = addslashes($zonetype);
					$zone=new Zone($zonename,$zonetype);
					if(notnull($zone->error)){
						$content .= $html->generic_error . 
									$zone->error.
								 $html->generic_error_end;
					}else{
						$userid = $zone->RetrieveUser();
					}
				}
			}else{
				// nothing entered
				$content .= $html->generic_error . 
				 			$l['str_you_did_not_enter_login_nor_zonename'] .
				 			$html->generic_error_end;
				$localerror = 1;
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
