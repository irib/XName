<?
/*
	This file is part of XName.org project
	See	http://www.xname.org/ for details
	
	License: GPLv2
	See LICENSE file, or http://www.gnu.org/copyleft/gpl.html
	
	Author(s): Yann Hirou <hirou@xname.org>

*/

	// validate email address, using $id.
	// delete from  dns_waitingreply
	
// headers 
include 'includes/header.php';

// zone numbers
include 'includes/currentzones.php';


// ***************************************************

$title = 'Email validation';

if((isset($_REQUEST) && notnull($_REQUEST['id'])) || 
	(!isset($_REQUEST) && notnull($id))){
	if(isset($_REQUEST)){
		$id = $_REQUEST['id'];
	}
	if($user->validateIDEmail($id)){
	
		$content = 'Your email is now flagged as valid.<br />
		you can now log in on <a href="index.php">main page</a>.
	';
	}else{
		$content = 'An error occured: ' . $user->error ;
	}
}else{
	$content = 'Wrong access method.';
}

// end left column

// ********************************************************
// MODIFY THIS TO CHANGE DESIGN
// ********************************************************
print $html->globaltablemiddle();
// ********************************************************

// main content

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
