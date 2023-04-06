<?
/*
	This file is part of XName.org project
	See	http://www.xname.org/ for details
	
	License: GPLv2
	See LICENSE file, or http://www.gnu.org/copyleft/gpl.html
	
	Author(s): Yann Hirou <hirou@xname.org>

*/

// create a new zone
// parameters : 
// - void
// - zonenamenew zonetypenew

$page_title = "str_create_new_zone";
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
print $html->globaltablemiddle();
// ********************************************************

// main content

$title=$l['str_create_new_zone'];
if($user->authenticated == 0){
	$content = $l['str_must_log_before_creating_new_zone'];
}else{
	if($config->usergroups && ($usergrouprights == 'R')){ 
		// if usergroups, zone is owned by
		// group and current user has no creation rights
		$content = $html->generic_error .  
					$l['str_not_allowed_by_group_admin_to_create_write_zones'] .
					 $html->generic_error_end;
	}else{

		if((isset($_REQUEST) && !isset($_REQUEST['zonenamenew'])) ||
			(!isset($_REQUEST) && !isset($zonenamenew))){
			$content ='
	<form action="' .  $_SERVER["PHP_SELF"] . '" method="post">
				' . $hiddenfields . '
				<table border="0" width="100%">
				<tr><td align="right">
				' . $l['str_zone'] . ': </td><td><input type="text" name="zonenamenew"
				value="'.$zonenamenew.'">
				</td></tr>
				<tr><td align="right">' . $l['str_zonetype'] . ':</td>
				<td nowrap><input type=radio name="zonetypenew" value="P"';
				if($zonetypenew=='P'){
					$content .=' checked';
				}
				$content .='>' . $l['str_primary'] . '  
				<input type=radio name="zonetypenew" value="S"';
				if($zonetypenew=='S'){
					$content .= ' checked';
				}
				$content .= '>' . $l['str_secondary'] . '</td></tr>
				<tr><td align="right">' . $l['str_using_following_zone_as_template'] 
							. '</td>
				<td><select name="template">
				<option value="">' . $l['str_none'] . '</option>
				';
			
			if($config->usergroups){
				$allzones = $group->listallzones();
				$user->error=$group->error;			
			}else{
				$allzones = $user->listallzones();
			}
	
			if(!notnull($user->error)){
				while($otherzone= array_pop($allzones)){
					$newzone = new Zone($otherzone[0],$otherzone[1],$otherzone[2]);
					$content .= '<option value="'.$newzone->zonename.'('.$newzone->zonetype.')">'.
								$newzone->zonename.' (' .
								$newzone->zonetype.')</option>';
				}
			}
			$content .='
			</select>
			</td></tr>
			<td>' . sprintf($l['str_use_server_for_import_x'],$config->webserverip) . 
			'</td><td valign="center"><input type="text" name="serverimport">
			</td></tr>
			<tr><td colspan="2" align="center"><input type="submit"
			value="' . $l['str_create'] . '"></td></tr>
			</table>
</form>
';
		}else{
		// $zonenamenew is set
			if(isset($_REQUEST)){
				$zonenamenew = $_REQUEST['zonenamenew'];
				$zonetypenew = $_REQUEST['zonetypenew'];
				if(isset($_REQUEST['template'])){
					$template = $_REQUEST['template'];
				}else{
					$template = "";
				}
				if(isset($_REQUEST['serverimport'])){
					$serverimport = $_REQUEST['serverimport'];
				}else{
					$serverimport = "";
				}
			}
			$content = "";
			$localerror = 0;
			$missing = "";
		
			if(!notnull($zonenamenew)){
				$missing .= " " . $l['str_zone'] . ",";
			}
			if(!notnull($zonetypenew)){
				$missing .= " " . $l['str_zonetype'] . ",";
			}
		
			if(notnull($missing)){
				$localerror = 1;
				$missing = substr($missing,0, -1);
				$content .= $html->fontred . sprintf($l['str_error_missing_fields'],
							$missing) . $html->fontend . '<br />';
			}
	
	
			if(!$localerror){
				if(!checkZone($zonenamenew)){
					$localerror = 1;
					$content .= $html->generic_error . 
								sprintf($l['str_bad_zone_name_x'],
									$zonenamenew) .
								$html->generic_error_end . '<br />';
				}else{
					if(preg_match("/^(.*)\.$/",$zonenamenew,$newzonename)){
						$zonenamenew = $newzonename[1];
					}
					$newzone = new Zone('','');
					if($config->usergroups){ 
						// if usergroups, zone is owned by
						// group and not individuals
						$list = $newzone->subExists($zonenamenew,$group->groupid);
					}else{
						$list = $newzone->subExists($zonenamenew,$user->userid);
					}
					if($list == 0){
						$localerror = 1;
						$content .= $html->generic_error . $newzone->error .
									$html->generic_error_end . '<br />';
					}else{
						if(count($list) != 0){
							if(count($list) == 1){
								$content .= $html->generic_error . 
								$l['str_zone_linked_exists_and_not_manageable']  . 
								'<br /> ';
							}else{
								$content .= $html->generic_error . 
								$l['str_zones_linked_exist_and_not_manageable']  . 
								'<br /> ';
							}
							$content .= implode("<br />",$list) .'<br />' .
							$html->generic_error_end . '<br />';
							$localerror = 1;
						}
					}
				}
			} // end no error after empty checks
	


			if(!$localerror){
				// ****************************************
				// *            Create new zone           *
				// ****************************************
				if(notnull($template) && strcmp($zonetypenew,
				substr($template,-2,1))){
					$content .= $html->generic_warning .
						$l['str_template_type_missmatch_noone_will_be_used'] .
						$html->generic_warning_end;
					$template = $l['str_none'];
				}else{
					if(!notnull($template)){
						$template = $l['str_none'];
					}
				}
				// import zone content
				if(notnull($serverimport)){
					if(strcmp($zonetypenew, 'P')){
						$content .= $html->generic_warning .
							$l['str_no_serverimport'] .
							$html->generic_warning_end;
						$serverimport="";
					}else{
						// check if serverimport is IP or NS name
						if(!( checkIP($serverimport) || checkDomain($serverimport) )){
							$l['str_bad_serverimport_name'];
							$serverimport="";
						} 
					}
				}
				
				if($config->usergroups){ 
					// if usergroups, zone is owned by
					// group and not individuals
					if($usergrouprights != 'R'){
						$newzonereturn = $newzone->zoneCreate($zonenamenew,
							$zonetypenew,$template,$serverimport,$group->groupid);
						// logs
						if(!$newzone->error){
							if($config->userlogs){
								$userlogs->addLogs($newzone->zoneid,
									sprintf($l['str_creation_of_x_x'],
										$zonenamenew,$zonetypenew));
								if($userlogs->error){
									$content .= $html->generic_error . 
												sprintf($l['str_logging_action_x'],
												$userlogs->error) .
												$html->generic_error_end;
								}
							}
						}
					}else{ // user is read only
						$content .= $html->generic_error . 
									$l['str_not_allowed_by_group_admin_to_create_write_zones']
									. $html->generic_error_end;
						$localerror=1;
					}
				}else{
					$newzonereturn = $newzone->zoneCreate($zonenamenew,$zonetypenew,$template,$serverimport,$user->userid);
				}
				if(($newzone->error && !$newzonereturn) || $localerror){
					if(!$localerror){
						$content .= $html->generic_error .
									$newzone->error .
									$html->generic_error_end;;
					}
				}else{
					if(strcmp($template,$l['str_none'])){
						$content .= '<p />' . 
								sprintf($l['str_using_zone_x_as_template'],$template);
						if($newzone->error){
							$content .= "<p />" . 
										$html->generic_warning . 
										$l['str_errors_occured_during_tmpl_usage_check_content']
										. $html->generic_warning_end;
						}
					}
					// send email & print message
					$content .= '<p />' .
						sprintf($l['str_zone_x_successfully_registered_on_x_server'],
							 $zonenamenew,$config->sitename) . '<p /> 
					';
					if(strcmp($template,$l['str_none'])){
						$content .=
							sprintf($l['str_you_can_now_use_the_x_modif_interface_x_to_configure'],
									'<a href="modify.php' . $link . 
								'&zonename=' . $zonenamenew . '&zonetype=' .
								$zonetypenew .'">','</a>');
					}else{
						$content .=
							sprintf($l['str_you_can_now_use_the_x_modif_interface_x_to_verify'],
									'<a href="modify.php' . $link . 
								'&zonename=' . $zonenamenew . '&zonetype=' .
								$zonetypenew .'">','</a>');
					}
				} // zone created successfully	
	
			}else{ // error, print form again
				$content .='
	<form action="' .  $_SERVER["PHP_SELF"] . '" method="post">
				' . $hiddenfields . '
				<table border="0" width="100%">
				<tr><td align="right">
				' . $l['str_zone'] . ': </td><td><input type="text" name="zonenamenew"
				value="'.$zonenamenew.'">
				</td></tr>
				<tr><td align="right">' . $l['str_zonetype'] . ':</td>
				<td><input type=radio name="zonetypenew" value="P"';
				if($zonetypenew=='P'){
					$content .=' checked';
				}
				$content .='>' . $l['str_primary'] . '  
				<input type=radio name="zonetypenew" value="S"';
				if($zonetypenew=='S'){
					$content .= ' checked';
				}
				$content .= '>' . $l['str_secondary'] . '</td></tr>
				<tr><td align="right">' . $l['str_using_following_zone_as_template'] 
							. '</td>
				<td><select name="template">
				<option value="">' . $l['str_none'] . '</option>
				';
				if($config->usergroups){
					$allzones = $group->listallzones();
					$user->error=$group->error;			
				}else{
					$allzones = $user->listallzones();
				}
	
				if(!notnull($user->error)){
					while($otherzone= array_pop($allzones)){
						$newzone = new Zone($otherzone[0],$otherzone[1],$otherzone[2]);
						$content .= '<option value="'.$newzone->zonename.'('.$newzone->zonetype.')">'.
								$newzone->zonename.' (' .
								$newzone->zonetype.')</option>';
					}
				}
				$content .='
				</select>
				</td></tr>
				<td>' . sprintf($l['str_use_server_for_import_x'],$config->webserverip) . 
				'</td><td valign="center"><input type="text" name="serverimport">
				</td></tr>
				<tr><td colspan="2" align="center"><input type="submit"
				value="' . $l['str_create'] . '"></td></tr>
				</table>
				</form>
				';	
			}
	

		} // end else $zonenamenew not null

		if($config->usergroups){
			if($config->userlogs){
				// $usergrouprights was set in includes/login.php
				if(($usergrouprights == 'R') || ($usergrouprights =='W')){
					$content .= '<p />' . $html->generic_warning . 
					' ' . $l['str_as_member_of_group_action_logged'] .
					$html->generic_warning_end;
				}
			}	
		}
	} // end usergroups && usergrouprights != R
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
