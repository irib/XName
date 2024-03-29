<?

/*
	This file is part of XName.org project
	See	http://www.xname.org/ for details
	
	License: GPLv2
	See LICENSE file, or http://www.gnu.org/copyleft/gpl.html
	
	Author(s): Yann Hirou <hirou@xname.org>

*/

$page_title = "str_index_title";
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

// **********************************
// 	MODIFY ALL TEXT HERE
// **********************************
if(file_exists('includes/strings/' . $lang . '/index_content.php')){
	include('includes/strings/' . $lang . '/index_content.php');
}else{
	include('includes/strings/' . $lang . '/index_content_default.php');
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
