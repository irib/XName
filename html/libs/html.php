<?
/*
	This file is part of XName.org project
	See	http://www.xname.org/ for details
	
	License: GPLv2
	See LICENSE file, or http://www.gnu.org/copyleft/gpl.html
	
	Author(s): Yann Hirou <hirou@xname.org>

*/


// Class HTML
// all HTML code has to be here

// WARNING: modify ALL or you will have XName.org site !
/**
 * Class for all design HTML code - DESIGN STUFF ONLY, no real code there
 *
 *@access public
 */
Class Html{
	var $fontred = ' <font color="red">';
	var $fontend = '</font>';
	var $generic_error = ' <font color="red">';
	var $generic_error_end = '</font>';	
	var $generic_warning = ' <font color="red">';
	var $generic_warning_end = '</font><br />';	
	var $tmp1, $tmp2;
	/**
	 * Class constructor
	 *
	 *@access public
	 */
	function Html(){
		return $this;
	}

	function initialize(){
		global $l;
		$this->tmp1 = $this->generic_error;
		$this->tmp2 = $this->generic_warning;
		$this->generic_error .=  $l['str_error'] . ': ';
		$this->generic_warning .= $l['str_warning'] . ': ';
		return 1;
	}

	function reinitialize(){
		global $l;
		$this->generic_error = $this->tmp1 .  $l['str_error'] . ': ';
		$this->generic_warning = $this->tmp2 . $l['str_warning'] . ': ';
		return 1;
	}

	
//	function header($title)
//		returns header with $title
	/**
	 * Top of each page
	 *
	 *@access public
	 *@param string $title Title of the page
	 *@return string HTML code
	 */
	function header($title){
	global $config,
		$l;
	Header("Content-Type: text/html; charset=" . $l['str_content_type']);
	$result ='
	<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=' . $l['str_content_type'] . '" />
	<title>' . $config->sitename . ' - ' . $title . '</title>
	<link rel="stylesheet" type="text/css" href="' . $config->cssurl . '" />
</head>
<body bgcolor="#ffffff">
	';
	
		return $result;
	}
	
//	function subheader($link)
//		returns subheader with $link query-string added to all local URLs
//		(used to pass sessionID)
	/**
	 * Sub-top of each page
	 *
	 *@access public
	 *@param string $link link to be added to all local URLS (used to pass sessionID)
	 *@return string HTML code
	 */
	function subheader($link){
		global $lang,$config;
		global $l;
		$result = '<!-- header -->

<table width="100%" border="0"  class="headtitle">
		<tr class="headtitle"><td class="header">
			<a href="index.php'.$link.'" class="linkcolor">' .
			$config->sitename . '</a>
		</td></tr>
		<tr class="headtitle"><td class="linkline">
		<a href="zones.php'.$link.'" class="linkcolor">' .
		$l['str_html_view_zones'] . '</a> |
		<a href="createzone.php'.$link.'" class="linkcolor">' .
		$l['str_html_create_zone'] . '</a> |
		<a href="modify.php'.$link.'" class="linkcolor">' .
		$l['str_html_modify_zone'] . '</a> |
		<a href="deletezone.php'.$link.'" class="linkcolor">' .
		$l['str_html_delete_zone'] . '</a> |
		<a href="' . $config->mainurl . '" class="linkcolor">' .
		$l['str_html_go_on_xname'] . '!</a>		
		</td></tr>
		</table>
		<!-- end header -->
		';
		return $result;
	}
	
//	function globaltableleft()
//		returns left part of the global table
	/**
	 * left part of the global table
	 *
	 *@access public
	 *@return string HTML code
	 */
	function globaltableleft(){
	$result = '
	<!-- global table -->
<table border="0" width="100%">
<tr><td width="20%" valign="top"> <!-- left column -->
';
		return $result;
	}
	
//	function globaltablemiddle()
//		returns middle part of the global table
	/**
	 * middle part of the global table
	 *
	 *@access public
	 *@return string HTML code
	 */
	function globaltablemiddle(){
	$result = '
	</td><td width="60%" valign="top"><!-- middle column -->
	';
	return $result;
	}
	
//	function globaltableright()
//		returns right part of the global table
	/**
	 * right part of the global table
	 *
	 *@access public
	 *@return string HTML code
	 */
	function globaltableright(){
	$result ='

</td>
<td width="20%" valign="top"> <!-- right column -->
';
	return $result;
	}
	
//	function globaltableend()
//		returns end of the global table
	/**
	 * end of the global table
	 *
	 *@access public
	 *@return string HTML code
	 */
	function globaltableend(){
		$result='
		</td></tr>
</table> <!-- end global table -->
';
		return $result;
	}
	
	
//	function footer()
//		returns footer
	/**
	 * global footer
	 *
	 *@access public
	 *@return string HTML code
	 */
	function footer(){
		global $db;	
		$result = "<p /> SQL:" . $db->totaltime . '

</body>
</html>

		';

		return $result;
	}
	
//	function box($title,$content)
//		returns designed box with title & content
	/**
	 * designed box with title & content
	 *
	 *@access public
	 *@param string $title title of the box, may be HTML
	 *@param string $content content of the box, may be HTML
	 *@return string HTML code
	 */
	function box($title,$content){
		$result = '
		<!-- box beginning "' . $title . '" -->
		<table border="0" width="100%">
		<tr class="boxtitle"><td class="boxtitle">' . $title . '</td></tr>
		<tr class="boxtext"><td class="boxtext">' . $content . '</td></tr>
		</table>
		<!-- box end "$title" -->
		';
		return $result;
	}
	
}
?>
