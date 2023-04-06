<?
/*
	This file is part of XName.org project
	See	http://www.xname.org/ for details
	
	License: GPLv2
	See LICENSE file, or http://www.gnu.org/copyleft/gpl.html
	
	Author(s): Yann Hirou <hirou@xname.org>

*/

	// modify.php
	// require user to be already logged-in, it means
	// parameters are $idsession or $zonename and $password
	
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

// main content

if($user->authenticated==1){
	if(isset($_REQUEST) && isset($_REQUEST['zonename'])){
		$zonename=$_REQUEST['zonename'];
		$zonetype=$_REQUEST['zonetype'];
	}
	if(notnull($zonename)){
		$zonename = addslashes($zonename);
		$zonetype = addslashes($zonetype);
		$zone = new Zone($zonename,$zonetype);
		if($zone->error){
			$content = '<font color="red">Error: ' . $zone->error . '</font>';
		}else{
			// verify that $zone is owned by user or group
			if((!$config->usergroups &&
				$zone->RetrieveUser() != $user->userid) ||
				($config->usergroups && 
				$zone->RetrieveUser() != $group->groupid)){
				$content = '<font color="red">Error: you can not manage zone ' . $zone->zonename . " (" . 
				$zone->zonetype . ")</font>";
			}else{

				$content = '<table border="0" width="100%" class="top">
				<tr class="top"><td class="top"><div align=center>Current zone: ' . $zone->zonename . '
				</div></td></tr></table>
				';


				$title = $zone->zonename;

				if($zone->zonetype=='P'){
					$title .= ' Primary';
					if(isset($_REQUEST)){
						$xferip = $_REQUEST['xferip'];
						$defaultttl = $_REQUEST['defaultttl'];
						$soarefresh = $_REQUEST['soarefresh'];
						$soaretry = $_REQUEST['soaretry'];
						$soaexpire = $_REQUEST['soaexpire'];
						$soaminimum = $_REQUEST['soaminimum'];
					}
					$xferip=addslashes($xferip);
					$defaultttl=addslashes($defaultttl);
					$soarefresh=addslashes($soarefresh);
					$soaretry=addslashes($soaretry);
					$soaexpire=addslashes($soaexpire);
					$soaminimum=addslashes($soaminimum);
					if(isset($_REQUEST)){
						$params=array($_REQUEST,$xferip,$defaultttl,
								$soarefresh,$soaretry,$soaexpire,$soaminimum);
					}else{
						$params=array($HTTP_POST_VARS,$xferip,$defaultttl,
								$soarefresh,$soaretry,$soaexpire,$soaminimum);
					}
						$currentzone = new Primary($zone->zonename,
						$zone->zonetype,$user);
				}else{
					if($zone->zonetype=='S'){
						$title .= ' Secondary';
						if(isset($_REQUEST)){
							$primary = $_REQUEST['primary'];
							$xfer = $_REQUEST['xfer'];
							$xferip = $_REQUEST['xferip'];
						}
						$primary=addslashes($primary);
						$xfer=addslashes($xfer);
						$xferip=addslashes($xferip);
						$params=array($primary,$xfer,$xferip);
						$currentzone = new Secondary($zone->zonename,
							$zone->zonetype,$user);
					}
				}
				if(isset($_REQUEST)){
					$modified = $_REQUEST['modified'];
				}
				if($modified == 1){
					if($config->usergroups && ($usergrouprights == 'R')){ 
					// if usergroups, zone is owned by
					// group and current user has no creation rights
						$content .= '<font color="red">Error: You are not allowed
						by your group administrator to create/write zones.</font>';
					}else{
						$content .= $currentzone->printModified($params);
						// logs
						if($config->usergroups){ 
							if($config->userlogs){
								if(!$currentzone->error){
									if($currentzone->zonetype == 'P'){
										$userlogs->addLogs($currentzone->zoneid,
										"Modification of " .
										$currentzone->zonename . " (" .
										$currentzone->zonetype . "). New serial: " . 
										$currentzone->serial);
									}else{
										$userlogs->addLogs($currentzone->zoneid,
										"Modification of " .
										$currentzone->zonename . " (" .
										$currentzone->zonetype . ").");
									}
									
								}else{
									$userlogs->addLogs($currentzone->zoneid,
									"Trouble during modification of " .
									$currentzone->zonename . " (" .
									$currentzone->zonetype . 
									"): " . $currentzone->error);
								}							
								if($userlogs->error){
									$content .= '<font color="red">Error logging action: '.$userlogs->error .
									'</font>';
								}
							}
						}
					} // end usergrouprights != R		
				}else{
					if($config->usergroups && ($usergrouprights == 'R')){ 
						// if usergroups, zone is owned by
						// group and current user has no creation rights
						$content = '<font color="red">Warning: You are not allowed
						by your group administrator to create/write zones,
						validation of this form will be inactive.</font>';
					}else{
						$content = "";
					}
					// to let user access advanced interface, even if not
					// in its preferences.
					if((isset($_REQUEST) && $_REQUEST['advanced']) ||
						(!isset($_REQUEST) && $advanced)){
						$advanced = 1;
					}else{
						$advanced = $user->advanced;
					}
					
					$content .= $currentzone->printModifyForm($advanced);
				}
				if($config->usergroups){
					if($config->userlogs){
						// $usergrouprights was set in includes/login.php
						if(($usergrouprights == 'R') || ($usergrouprights =='W')){
							$content .= '<p />Warning, as member of a group, your action
							will be logged.';
						}
					}	
				}
			}
		}
	}else{
		$title =  "choose a zone to modify";
	
		if($config->usergroups){
			$allzones = $group->listallzones();
			$user->error=$group->error;			
		}else{
			$allzones = $user->listallzones();
		}
	
		if(!notnull($user->error)){
			$content =  '<div class="boxheader">choose a zone to modify</div>';
			$content .='<table border="0" width="100%">';
			while($otherzone= array_pop($allzones)){
				$newzone = new Zone($otherzone[0],$otherzone[1]);
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
				$content .= '<tr><td><a href="' . $PHP_SELF 
				.$link.'&zonename=' . $newzone->zonename . '&zonetype=' .
				$newzone->zonetype . '" class="linkcolor">' .
				 $newzone->zonename . '</a> (' . $newzone->zonetype . ')</td><td
				 class="loghighlight' . $class . '" align="center"><a href="logwindow.php'
			 .$link .'&zonename=' .$newzone->zonename . '&zonetype=' .
			$newzone->zonetype . '" class="linkcolor" onclick="window.open(\'logwindow.php'
			 .$link .'&zonename=' .$newzone->zonename . '&zonetype=' .
			$newzone->zonetype .
		
'\',\'Logs\',\'toolbar=no,location=no,directories=no,status=yes,alwaysraised=yes,dependant=yes,resizable=yes,scrollbars=yes,menubar=no,width=640,height=480\');
return false">'.
				 $status . '</a></td></tr>';
			}

			$content .= '</table>';
		}else{
			$content = $user->error;
		}
	}
}else{
	$title = 'Modify zone';
	$content = 'Please log in before';
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
