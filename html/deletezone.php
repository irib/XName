<?
/*
	This file is part of XName.org project
	See	http://www.xname.org/ for details
	
	License: GPLv2
	See LICENSE file, or http://www.gnu.org/copyleft/gpl.html
	
	Author(s): Yann Hirou <hirou@xname.org>

*/

// delete zone
// parameters 
// - void
// - zonename,zonetype

// headers 
include 'includes/header.php';


if(isset($zonename)){
	$zonename=addslashes($zonename);
}
if(isset($zonetype)){
	$zonetype=addslashes($zonetype);
}

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

$title='Delete zone';

if($user->authenticated == 0){
	$content = 'you must log in before deleting zone.
	';
}else{
	if(!isset($zonename)){

		$zonelist = $user->listallzones();
		
		if(!notnull($user->error)){
			$content =  '<div class="boxheader">choose a zone to delete</div>';
			while($otherzone= array_pop($zonelist)){
				$newzone = new Zone($db,$otherzone[0],$otherzone[1],$config);
				$content .= '<a href="'
				.$link.'&zonename=' . $newzone->zonename . '&zonetype=' .
				$newzone->zonetype . '" class="linkcolor">' .
				 $newzone->zonename . '</a> (' . $newzone->zonetype . ')<br />';
			}
		}else{
			$content = $user->error;
		}

	}else{ // zonename is set ==> confirm & delete
		$zone = new Zone($db,$zonename,$zonetype,$config);

		if($zone->error){
			$content = '<font color="red">Error: ' . $zone->error . '</font>';
		}else{
			if($zone->Retrieveuser() != $user->userid){
				$content = '<font color="red">Error: zone ' . $zone->zonename . 
				' (';
				if($zone->zonetype == 'P'){
					$content .= 'Primary';
				}else{
					$content .= 'Secondary';
				}
				$content .= ') is not owned by you.</font>';
			}else{

				if(!isset($confirm)){
				// ==> print confirm screen
					$content = '
					<div class="boxheader">Confirmation</div>
					Do you confirm you want to delete zone ' . $zone->zonename . '
					(';
					if($zone->zonetype == 'P'){
						$content .= 'Primary';
					}else{
						$content .= 'Secondary';
					}
					$content .= ') from ' . $config->sitename . ' ?
				 	<div align="center">
					<form action="' . $PHP_SELF . '" method="POST">
					<input type="hidden" name="idsession" value="' . $user->idsession
					. '">
					<input type="hidden" name="zonename" value="' .
					$zone->zonename . 
					'">
					<input type="hidden" name="zonetype" value="' . $zone->zonetype . 
					'">
					<input type="hidden" name="confirm" value="1">
					<input type="submit" value="Yes, please delete '
					 . $zone->zonename . 
					'(';
					if($zone->zonetype == 'P'){
						$content .= 'Primary';
					}else{
						$content .= 'Secondary';
					}
					$content .= ') from ' . $config->sitename . '">
					</form>
					<form action="index.php">
					<input type="hidden" name="idsession" value="' . $user->idsession
					. '">
					<input type="submit" value="No, do not delete"></form>
					</div>
					';
				}else{ // not confirmed
					// delete
					// delete from dns_zone, dns_conf$zonetype, dns_log, dns_modified,
					// dns_record
					// TODO : log deletion
					$error = 0;
			
					$content = 'Deleting '  . $zone->zonename . 
						'(';
					if($zone->zonetype == 'P'){
						$content .= 'Primary';
					}else{
						$content .= 'Secondary';
					}
					$content .= ') from ' . $config->sitename . '...<br />';
					$query = "DELETE FROM dns_zone WHERE id='" . $zone->zoneid . "'";
					$res = $db->query($query);
					if($db->error()){
						$error = 1;
						$content .= '<font color="red">Error: Trouble with DB</font>';
					}
					$query = "DELETE FROM dns_conf";
					if($zone->zonetype == 'P'){
						$query .= 'primary';
					}else{
						$query .= 'secondary';
					}
					 $query .= " WHERE zoneid='" . $zone->zoneid . "'";
					$res = $db->query($query);
					if($db->error()){
						$error = 1;
						$content .= '<font color="red">Error: Trouble with DB</font>';
					}
					$query = "DELETE FROM dns_log WHERE zoneid='" . $zone->zoneid . "'";
					$res = $db->query($query);
					if($db->error()){
						$error = 1;
						$content .= '<font color="red">Error: Trouble with
						DB</font>';
					}
					$query = "DELETE FROM dns_modified WHERE zoneid='" . $zone->zoneid . "'";
					$res = $db->query($query);
					if($db->error()){
						$error = 1;
						$content .= '<font color="red">Error: Trouble with
						DB</font>';
					}
					if($zone->zonetype=='P'){
						$query = "DELETE FROM dns_record WHERE zoneid='" . $zone->zoneid . "'";
						$res = $db->query($query);
						if($db->error()){
							$error = 1;
							$content .= '<font color="red">Error: Trouble with
							DB</font>';
						}
					}		
		
					if(!$error){
					// insert into dns_deleted (to delete file....)
						$query = "INSERT INTO dns_deleted (zonename,zonetype,userid) values ('" .
						$zone->zonename . "','" . $zone->zonetype . "','" . $user->userid . "')";			
						$res = $db->query($query);
						if($db->error()){
							$error = 1;
							$content .= '<font color="red">Error: Trouble with
							DB</font>'.
							$query;
						}
					}
	
					if($error){

						$content .= '<p>Errors occured during deletion. Please try 
				again later.<br /> 
				If problem persists, <a href="mailto:' . $config->contactemail . '">contact
				us</a>.';
					}else{
						$content .= 'Zone successfully deleted. ';
					} 
				}
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
