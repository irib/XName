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


// headers 
include 'includes/header.php';

// zone numbers
include 'includes/currentzones.php';

// login & logs
include 'includes/login.php';

// end left column

// ********************************************************
// MODIFY THIS TO CHANGE DESIGN
print $html->globaltablemiddle();
// ********************************************************

// main content

$title='Create a new zone';

if($user->authenticated == 0){
	$content = 'you must log in before creating new zone.
	';
}else{
	if((isset($_REQUEST) && !isset($_REQUEST['zonenamenew'])) ||
		(!isset($_REQUEST) && !isset($zonenamenew))){
	$content ='
<form action="' . $PHP_SELF . '" method="post">
			<input type="hidden" name="idsession" value="' . $user->idsession .
			'">
			<table border="0" width="100%">
			<tr><td align="right">
			zone: </td><td><input type="text" name="zonenamenew">
			</td></tr>
			<tr><td align="right">type of zone:</td>
			<td><input type=radio name="zonetypenew" value="P" checked>primary  
			<input type=radio name="zonetypenew" value="S">secondary</td></tr>
			<tr><td colspan="2" align="center"><input type="submit"
			value="Create"></td></tr>
			</table>
</form>
';
	}else{
	// $zonenamenew is set
		if(isset($_REQUEST)){
			$zonenamenew = $_REQUEST['zonenamenew'];
			$zonetypenew = $_REQUEST['zonetypenew'];
		}
		$content = "";
		$error = 0;
		$missing = "";
	
		if(!notnull($zonenamenew)){
			$missing .= ' zone,';
		}
		if(!notnull($zonetypenew)){
			$missing .= ' zone type,';
		}
	
		if(notnull($missing)){
			$error = 1;
			$missing = substr($missing,0, -1);
			$content .= '<font color="red">Error, missing fields:'.$missing.'</font><br />';
		}
	
	
		if(!$error){
			if(!checkDomain($zonenamenew)){
				$error = 1;
				$content .= '<font color="red">Error: bad zone name</font><br />';
			}else{
				if(preg_match("/^(.*)\.$/",$zonenamenew,$newzonename)){
					$zonenamenew = $newzonename[1];
				}
				$newzone = new Zone($db,'','',$config);
				$list = $newzone->subExists($zonenamenew,$user->userid);
				if($list == 0){
					$error = 1;
					$content .= '<font color="red">Error: ' . $newzone->error .
					'</font><br />';
				}else{
					if(count($list) != 0){
						if(count($list) == 1){
						$content .= '<font color="red">Error: 
						a zone linked with this one already exists, and is not
						managed by you: <br /> ';
						}else{
						$content .= '<font color="red">Error: 
						zone linked with this one already exist, and are not
						managed by you: <br /> ';
						}
						$content .= implode("<br />",$list) .'<br />
						</font><br />';
						$error = 1;
					}
				}
			}
		} // end no error after empty checks
	


		if(!$error){
			// ****************************************
			// *            Create new zone           *
			// ****************************************
			
			$newzone->zoneCreate($zonenamenew,$zonetypenew,$user->userid);
			if($newzone->error){
				$content .= '<font color="red">Error: '.$newzone->error .'</font>';
			}else{
				// send email & print message
				$content .= '<p />Zone ' . $zonenamenew . ' has been successfully
				registered on ' . $config->sitename . ' DNS Server.<p /> 
				You can now use the <a href="modify.php' . $link . 
				'&zonename=' . $zonenamenew . '&zonetype=' . $zonetypenew . '">
				modification interface</a> to configure it.
				';
			} // zone created successfully	
	
		}else{ // error, print form again
	$content .='
<form action="' . $PHP_SELF . '" method="post">
			<input type="hidden" name="idsession" value="' . $user->idsession .
			'">
			<table border="0" width="100%">
			<tr><td align="right">
			zone: </td><td><input type="text" name="zonenamenew"
			value="'.$zonenamenew.'">
			</td></tr>
			<tr><td align="right">type of zone:</td>
			<td><input type=radio name="zonetypenew" value="P"';
			if($zonetypenew=='P'){
				$content .=' checked';
			}
			$content .='>primary  
			<input type=radio name="zonetypenew" value="S"';
			if($zonetypenew=='S'){
				$content .= ' checked';
			}
			$content .= '>secondary</td></tr>
			<tr><td colspan="2" align="center"><input type="submit"
			value="Create"></td></tr>
			</table>
</form>
';
		}
	

	} // end else $zonenamenew not null
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
