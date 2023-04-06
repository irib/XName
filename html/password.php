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

// headers 
include 'includes/header.php';

// zone numbers
include 'includes/currentzones.php';

// end left column

// ********************************************************
// MODIFY THIS TO CHANGE DESIGN
// ********************************************************
print $html->globaltablemiddle();
// ********************************************************

// main content

$title = "Password recovery";
if((isset($_REQUEST) && !isset($_REQUEST['id']) && !isset($_REQUEST['account']))
	|| (!isset($_REQUEST) && !isset($id) && !isset($account))){
	$content = '
	You have lost your password ? <br />
	Fill in the following field, and an email will be sent to you,
	containing a recovery-URL to visit.<p />
	
	<form action="' . $PHP_SELF . '" method="post">
	<table border="0">
	<tr><td align="right">Login name:</td><td><input type="text" name="account"
	/></td></tr>
	<tr><td align="right"><b>or</b> one of your zone name:</td><td><input
	type="text" name="zonename" /> <input type="radio" name="zonetype"
	value="P">Primary <input type="radio" name="zonetype" value="S">Secondary</td></tr>
		<tr><td colspan="2" align="center"><input type="submit" 
	value="Recover password" /></td></tr>
	</table>
	</form>';
}else{
	if((isset($_REQUEST) && (notnull($_REQUEST['account']) || 
		notnull($_REQUEST['zonename']))) || 
		(!isset($_REQUEST) && (notnull($account) || notnull($zonename)))){
		$error = 0;
		$content = '';
		if((isset($_REQUEST) && notnull($_REQUEST['zonename'])) ||
			(!isset($_REQUEST) && notnull($zonename))){
			if(isset($_REQUEST)){
				$zonename = $_REQUEST['zonename'];
			}
			$zonename = addslashes($zonename);
			
			if((isset($_REQUEST) && !notnull($_REQUEST['zonetype'])) ||
				(!isset($_REQUEST) && !notnull($zonetype))){
				$content = '<font color="red">Error: you did not specify
				zone type</font>';
				$error = 1;
			}else{
				if(isset($_REQUEST)){
					$zonetype = $_REQUEST['zonetype'];
				}
				$zonetype=addslashes($zonetype);
				$zone=new Zone($db,$zonename,$zonetype,$config);
				if(notnull($zone->error)){
					$content = '<font color="red">Error: ' . $zone->error;
					$error=1;
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
					$error = 1;
					$content .= '<font color="red">No such login name</font>';
				}
			}
		}
		
		if(!$error){
			// generate sessionid
			$id = $user->generateIDRecovery();
			if($user->error){
				$content .= $user->error;
			}else{
				$user->storeIDRecovery($account,$id);
				if($user->error){
					$content .= '<font color="red">' . $user->error . '</font>';
				}else{
					// send email
					$mailbody = '
This is an automatic email.

You have requested a password recovery for your account on ' . $config->sitename . '.

Go on following temporary page, it will be given to you.
warning : this page can be accessed only once.

' . $config->mainurl . 'password.php?id=' . $id . '

-- 
' . $config->emailsignature . '
';
					$email = $user->getEmail($account);
					if(!$email){
						$content .= '<font color="red">Error: your account
						does not seems to have a valid email address.</font><br
						/>';
						$content .= 'Recovery mail not sent.';	
					}else{
						if(mailer($config->tousersource,$email,
						$config->sitename . " password recovery","",$mailbody)){
							$content .= '
							Recorvery mail was successfully sent to your
							email address. <br />
							It contains an URL pointing to a page that will
							reveal you your password.<p />
							<b>WARNING: use this recovery URL as soon as
							possible, as it is send to you in clear text.</b><br
							/>
							For security reasons, this URL can be used only
							once.';
						}else{
							$content .= '<font color="red">Error: an error occured
							sending you recovering email, try again
							later.</font><br />';
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
					$password = $user->Retrievepassword();
					$content = '
					Your login is: <div class="boxheader">' 
					. $user->retrieveLogin($user->userid) . '</div><p />
					Your password is: <div class="boxheader">' 
					. $password . '</div><p />
					You can now go on <a href="index.php">main page</a> 
					and login';
					// reset user
					$user->login="";
				}else{
					$content = 'Bad ID, maybe did you already use this recovery
					URL';
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
					$content = '<font color="red">Error: you did not specify
					zone type</font>';
					$error = 1;
				}else{
					if(isset($_REQUEST)){
						$zonetype=$_REQUEST['zonetype'];
					}
					$zonetype = addslashes($zonetype);
					$zone=new Zone($db,$zonename,$zonetype);
					if(notnull($zone->error)){
						$content = '<font color="red">Error: ' . $zone->error;
					}else{
						$userid = $zone->RetrieveUser();
					}
				}
			}else{
				// nothing entered
				$content = '<font color="red">Error: you did not enter login
				name nor zone name</font>';
				$error = 1;
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
