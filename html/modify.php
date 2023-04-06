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

	if(notnull($zonename)){
		$zonename=addslashes($zonename);
		$zonetype=addslashes($zonetype);
		$zone = new Zone($db,$zonename,$zonetype,$config);
		if($zone->error){
			print "Error: " . $zone->error;
		}else{
			// verify that $zone is owned by $user
			if($zone->RetrieveUser() != $user->userid){
				print "Error: zone " . $zone->zonename . " (" . 
				$zone->zonetype . ") is not owned by you .";
				print $zone->Retrieveuser() ."!=". $user->userid;
			}else{

				print '<table border="0" width="100%" class="top">
			<tr class="top"><td class="top"><div align=center>Current zone: ' . $zone->zonename . '
			</div></td></tr></table>
			';


				$title = $zone->zonename;

				if($zone->zonetype=='P'){
					$title .= ' Primary';
					$azone=addslashes($azone);
					$xferip=addslashes($xferip);

					$params=array($HTTP_POST_VARS,$azone,$xferip);
					$currentzone = new
				
Primary($db,$zone->zonename,$zone->zonetype,$user,$config);
				}else{
					if($zone->zonetype=='S'){
						$title .= ' Secondary';
						$primary=addslashes($primary);
						$xfer=addslashes($xfer);
						$xferip=addslashes($xferip);
						$params=array($primary,$xfer,$xferip);
						$currentzone = new
Secondary($db,$zone->zonename,$zone->zonetype,$user,$config);
					}
				}
	
				if($modified == 1){
					$content = $currentzone->printModified($params);
				}else{
					$content = $currentzone->printModifyForm();
				}
			}
		}
	}else{
		$title =  "choose a zone to modify";
		
		$zonelist = $user->listallzones();
		
		if(!notnull($user->error)){
			$content =  '<div class="boxheader"choose a zone to modify</div>';
			$content .='<table border="0" width="100%">';
			while($otherzone= array_pop($zonelist)){
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
				$content .= '<tr><td><a href="' . $PHP_SELF 
				.$link.'&zonename=' . $newzone->zonename . '&zonetype=' .
				$newzone->zonetype . '" class="linkcolor">' .
				 $newzone->zonename . '</a> (' . $newzone->zonetype . ')</td><td
				 class="loghighlight' . $class . '"><a href="logwindow.php'
				 .$link .'&zonename=' .$newzone->zonename . '&zonetype=' .
				$newzone->zonetype . '" class="linkcolor">'.
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
