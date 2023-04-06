<?

/*
	This file is part of XName.org project
	See	http://www.xname.org/ for details
	
	License: GPLv2
	See LICENSE file, or http://www.gnu.org/copyleft/gpl.html
	
	Author(s): Yann Hirou <hirou@xname.org>

*/

// headers 
include 'includes/header.php';

// zone numbers
include 'includes/currentzones.php';

// faq
//include 'includes/faq.php';

// login & logs
include 'includes/login.php';



// end left column


// ********************************************************
// MODIFY THIS TO CHANGE DESIGN
// ********************************************************
print $html->globaltablemiddle();
// ********************************************************

// main content

$title = "Free DNS Hosting Service";

if($user->authenticated==1){
	// list all zones, with serials, etc....
	// and form to change email & password for $user

	if($config->usergroups){
		$allzones = $group->listallzones();
		$user->error=$group->error;		
	}else{
		$allzones = $user->listallzones();
	}

	if(!notnull($user->error)){
		$content ='<table border="0" width="100%">
		<tr><td class="boxheader">Zone</td>
		<td class="boxheader">Name Server</td><td class="boxheader">Serial</td>
		<td class="boxheader">View</td><td class="boxheader">Status</td></tr>';
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
			$content .= '<tr><td colspan="3"><a href="modify.php'
			.$link.'&zonename=' . $newzone->zonename . '&zonetype=' .
			$newzone->zonetype . '" class="linkcolor">' .
			 $newzone->zonename . '</a> (' . $newzone->zonetype . ')</td>
			 <td><a href="logwindow.php' 
			 .$link.'&zonename=' . $newzone->zonename . '&zonetype=' .
			$newzone->zonetype . '" class="linkcolor" onclick="window.open(\'logwindow.php'
			 .$link .'&zonename=' .$newzone->zonename . '&zonetype=' .
			$newzone->zonetype .
		
'\',\'M\',\'toolbar=no,location=no,directories=no,status=no,alwaysraised=yes,dependant=yes,resizable=yes,menubar=no,scrollbars=yes,width=640,height=480\');
return false">logs</a></td>
			<td
			 class="loghighlight' . $class . '" align="center"><a href="logwindow.php'
			 .$link .'&zonename=' .$newzone->zonename . '&zonetype=' .
			$newzone->zonetype . '" class="linkcolor" onclick="window.open(\'logwindow.php'
			 .$link .'&zonename=' .$newzone->zonename . '&zonetype=' .
			$newzone->zonetype .
		
'\',\'Logs\',\'toolbar=no,location=no,directories=no,status=yes,alwaysraised=yes,dependant=yes,resizable=yes,scrollbars=yes,menubar=no,width=640,height=480\');
return false">'.
			 $status . '</a></td></tr>';
			// for each retrieve NS & do DigSerial($zone,$server)
			if($newzone->zonetype=='P'){
				$primary = new
			
Primary($newzone->zonename,$newzone->zonetype,$user->userid);
				$keys = array_keys($primary->ns);
				while($nameserver = array_shift($keys)){
					$serial = DigSerial($nameserver,$primary->zonename);
					if($serial == 'not available'){
						$serial = '<font color="red">not available</font>';
					}
					$content .= '
					<tr><td width="20">&nbsp;</td><td>' . $nameserver .
					'</td><td>' . $serial . '</td><td>
					<a href="digwindow.php'
			 .$link .'&zonename=' .$newzone->zonename . '&zonetype=' .
			$newzone->zonetype . '&server=' . $nameserver . '" class="linkcolor" onclick="window.open(\'digwindow.php'
			 .$link .'&zonename=' .$newzone->zonename . '&zonetype=' .
			$newzone->zonetype . '&server=' . $nameserver . 
		
'\',\'M\',\'toolbar=no,location=no,directories=no,status=no,alwaysraised=yes,dependant=yes,menubar=no,resizable=yes,scrollbars=yes,width=640,height=480\');
return false">zone content</a></td></tr>';
				}
			}else{
				// secondary NS
				$secondary = new
			
Secondary($newzone->zonename,$newzone->zonetype,$user->userid);
				$masters = split(';',$secondary->masters);
				// add our NS server to secondary NS servers 
				array_push($masters, $config->nsname . ".");
				while($nameserver = array_pop($masters)){
					$serial = DigSerial($nameserver,$secondary->zonename);
					if($serial == 'not available'){
						$serial = '<font color="red">not available</font>';
					}
					$content .= '
					<tr><td width="20">&nbsp;</td><td>' . $nameserver .
					'</td><td>' . $serial . '</td><td>
					<a href="digwindow.php'
			 .$link .'&zonename=' .$newzone->zonename . '&zonetype=' .
			$newzone->zonetype . '&server=' . $nameserver . '" class="linkcolor" onclick="window.open(\'digwindow.php'
			 .$link .'&zonename=' .$newzone->zonename . '&zonetype=' .
			$newzone->zonetype . '&server=' . $nameserver . 
		
'\',\'M\',\'toolbar=no,location=no,directories=no,status=no,alwaysraised=yes,dependant=yes,menubar=no,resizable=yes,scrollbars=yes,width=640,height=480\');
return false">zone content</a></td></tr>';
					
				}
				
			}
		}
		$content .= '</table>';
	}else{
		$content = $user->error;
	}	
	
	
}else{

	// **********************************
	// 	MODIFY ALL TEXT HERE
	// **********************************
	$content = '
		<div class="boxheader">XName Software</div>
		<table border="0" width="100%">
		<tr><td width="20">&nbsp;</td>
		<td>
		You have successfully (?) installed XName software.<br />
		Be carefull to following points:
		<ul>
		<li> Modify all items in libs/config.php</li>
		<li> If you have mysql errors, check that configured user in config.php exists, 
		and that database name is the same as the one modified in sql/creation.sql</li>
		<li> Modify this text - html/index.php</li>
		<li> Modify all html/*.php to feet your html design (all currently used design functions are
		taken from libs/html.php, feel free to use your owns !). Class HTML is used only in these files 
		and in includes/*.php</li>
		</ul>
		</td></tr>
		</table>
	';
}


// *************************************
//          END OF CONTENT
// *************************************

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
