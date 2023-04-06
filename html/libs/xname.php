<?
/*
	This file is part of XName.org project
	See	http://www.xname.org/ for details
	
	License: GPLv2
	See LICENSE file, or http://www.gnu.org/copyleft/gpl.html
	
	Author(s): Yann Hirou <hirou@xname.org>

*/

include 'libs/html.php';
include 'libs/config.php';
include 'libs/db.php';
include 'libs/user.php';
include 'libs/zone.php';
include 'libs/primary.php';
include 'libs/secondary.php';

// **********************************************************
// Utilities 


// function notnull($string)
// 		returns true or false if string is null or not
/**
 * Check if a string is null or empty
 *
 *@param string $string string to test
 *@return int 0 if string null or empty, 1 if not
 */
function notnull($string){
	if(!isset($string)){
		$result = 0;
	}else{
		if(!strcmp("", $string)){
			$result = 0;
		}else{
			if($string == '0'){
				$result = 0;
			}else{
				$result = 1;
			}
		}
	}

	return $result;
}



// function mailer($from, $to, $subject, $headers, $message)
// 		send email
/**
 * Send an email
 *
 *@param string $from sender email address
 *@param string $to recipient email address
 *@param string $subject subject of email
 *@param string $headers additional headers 
 *@param string $message body of email
 *@return int 1 if success, 0 if error
 */
function mailer($from, $to, $subject, $headers, $message){
	// TODO : verify mail by sending it directly, after 
	// a connexion on MX
	// has to be done using vrfyEmail()
	
	// returns 0 if fails, 1 if succeed
	$mailcontent = "From: $from
To: $to
Subject: $subject
$headers

$message
";
	if($fd = popen("/usr/lib/sendmail -t","w")){
		fwrite($fd, $mailcontent);
		if(pclose($fd)){
			return 0;
		}
		return 1;
	}else{
		return 0;
	}
}




// function randomID()
/**
 * Generate random ID to be used for sessionID & recovery ID
 *
 *@return int random number
 */
function randomID(){
        $datetime = md5(date("Y-m-d H:i:s"));
        $ip = md5(getenv("REMOTE_ADDR"));
        $session = md5($datetime . $ip);
	return $session;
}

// **********************************************************
// DB utilities

// function countSecondary()
/**
 * Count number of secondary zones currently hosted
 *
 *@return int number of zones or N/A in case of error
 */
function countSecondary(){
		global $db;
	$query = "SELECT count(*) FROM dns_confsecondary";
	$res = $db->query($query);
	$line = $db->fetch_row($res);
	if($db->error()){
		return "N/A";
	}else{
		return $line[0];
	}
}


// function countPrimary()
/**
 * Count number of primary zones currently hosted
 *
 *@return int number of zones or N/A in case of error
 */
function countPrimary(){
	global $db;
	$query = "SELECT count(*) FROM dns_confprimary";
	$res = $db->query($query);
	$line = $db->fetch_row($res);
	if($db->error()){
		return "N/A";
	}else{
		return $line[0];
	}
}


// **********************************************************
// Availability of services
// ToBeDone

// function av_sslInterface()
function av_sslInterface(){
}

// function av_mailInterface()
function av_mailInterface(){
}

// function av_db(){
function av_db(){
}

// function av_primaryRegistration()
function av_primaryRegistration(){
}

// function av_secondaryRegistration()
function av_secondaryRegistration(){
}

// function av_primaryModification()
function av_primaryModification(){
}

// function av_secondaryModification()
function av_secondaryModification(){
}

// function av_logViewer()
function av_logViewer(){
}

// **********************************************************
// Checkers

// function checkIP($string)
/**
 * Check if IP sounds good - 4 positive numbers <= 255 separated by dots
 *
 *@param string $string IP address to check
 *@return int 0 or 1 if bad or valid IP
 */
function checkIP($string){
	if((strspn($string, "0123456789.") != strlen($string)) || 
	(count(explode('.' ,$string)) != 4)){
		$result = 0;
	}else{
		list($octet1,$octet2,$octet3,$octet4) = explode('.' ,$string);
		if(($octet1 > 255)||($octet2 > 255)||($octet3 > 255)||($octet4 > 255)){
			$result = 0;
		}else{
			$result = 1;
		}
	}
	
	return $result;
}


// function checkDomain($string)
/**
 * Check if  name has only valid char, without dot as 1st char
 *
 *@param string $string zone name to be checked
 *@return int 1 if valid, 0 else
 */
function checkDomain($string){
	$string = strtolower($string);
	// only specified char AND only one . (no sub-zones)
	if((strspn($string, "0123456789abcdefghijklmnopqrstuvwxyz-.") !=
	strlen($string)) || (strpos('0'.$string,".") == FALSE)||
	(strpos('0'.$string,".") == 1)){
		$result = 0;
	}else{
		$result = 1;
	}
	return $result;
}

// function checkZone($string)
/**
 * Check if zone name has only valid char, without dot as 1st char
 * Zone name must have only [a-z] char at the end.
 *@param string $string zone name to be checked
 *@return int 1 if valid, 0 else
 */
function checkZone($string){
	$string = strtolower($string);
	// only specified char AND only one . (no sub-zones)
	if((strspn($string, "0123456789abcdefghijklmnopqrstuvwxyz-.") !=
	strlen($string)) || (strpos('0'.$string,".") == FALSE)||
	(strpos('0'.$string,".") == 1) || !preg_match("/[a-z]$/i",$string)){
		$result = 0;
	}else{
		$result = 1;
	}
	return $result;
}


// function checkName($string)
/**
 * Check if name has only valid char
 *
 *@param string $string name to be checked
 *@return int 1 if valid, 0 else
 */
function checkName($string){
	$string = strtolower($string);
	// only specified char 
	if(strspn($string, "0123456789abcdefghijklmnopqrstuvwxyz-*") !=
	strlen($string)){
		$result = 0;
	}else{
		$result = 1;
	}
	return $result;
}

// function checkPrimary($string)
/**
 * Check if given string is a list of IP addresses or not, ';' separated
 *
 *@param string $string string to be checked
 *@return 1 if valid, 0 else
 */
function checkPrimary($string){
	$string = str_replace(" ", "", $string);
	$primarylist = explode(';',$string);
	$result = 0;
	while(list($key,$value) = each($primarylist)){
		$result += !checkIP($value);
	}
	if($result > 0){
		$result = 0;
	}else{
		$result = 1;
	}
	return $result;
}

// function checkEmail($string)
/**
 * Check if email looks valid or not  
 *
 *@param string $string email to check
 *@return int 1 if valid, 0 else
 */
function checkEmail($string){
	$result = 1;
	if(!ereg("^.+@.+\\..+$", $string)) $result = 0;
	return $result;
}

// function vrfyEmail($string)
// 		look for MX, check accept domain
/**
 * Verify email by looking for MX record for domain
 *
 *@param string $string email to check
 *@return string 1 if valid, "No valid MX record found" else
 */
function vrfyEmail($string){
	if(!getmxrr(substr(strstr($string,'@'),1),$mxhosts)){
		// no valid MX record
		return "No valid MX record found";
	}else{
		return 1;
	}
}


// function checkDig($server,$zone)
// 		try a zone transfer from $server for $zone
/**
 * Try a dig and return result
 *
 *@param string $server name of the DNS server to dig on
 *@param string $zone zone name to dig
 *@return string effective status or "connection timed out" or "unknown problem"
 */
function checkDig($server,$zone){	
	global $config;
	$result = `$config->bindig soa '$zone' @'$server'`;

	// check if status:*
	// return *
	// if "connection timed out" return "connection timed out"
	
	if(ereg("status: ([[:alnum:]]+),",$result,$status)){
		return $status[1];
	}else{
		if(ereg("connection timed out",$result)){
			return "connection timed out";
		}else{
			return "unknown problem";
		}
	}
}

// **********************************************************


// function DigSerial($server,$zone)
// retrieve serial for zone on server
/**
 * Retrieve serial of a zone on specified server using 'host'
 *
 *@param string $server server to query
 *@param string $zone zone name to query for
 *@return string serial number of zone or "not availabl"
 */
function DigSerial($server,$zone){
	global $config;
	$result = `$config->binhost -t soa '$zone' '$server'`;
	if(ereg("try again",$result)){
		return $result;
	}else{
// modified 02/05/2002 to match host v9.2.1
		preg_match("/.*SOA [^\s\t]+ [^\s\t]+ ([^\s\t]+)/",$result,$serial);
		if($serial[1]){
			return $serial[1];
		}else{
			return 'not available';
		}
	}
}


// function zoneDig($server,$zone)
/**
 * Do an axfr dig of a zone
 *
 *@param string $server server to dig
 *@param string $zone zone to dig
 *@return string dig result
 */ 
function zoneDig($server,$zone){
	global $config;
	$result = `$config->bindig axfr '$zone' @'$server'`;
	return $result;
}


// function retrieveArgs($name, $httpvars)
/**
 * Retrieve arguments from an associative array where 
 * arguments names are incremental - for example foo1,foo2,foo3
 * and return an array with all values
 *
 *@param string $name non-incremental part of var name (example: foo in foo1)
 *@param array $httpvars associative array to be parsed - example $HTTP_GET_VARS 
 *@return array array of values
 */
function retrieveArgs($name, $httpvars){
	$result = array();

	$nbmax = count($httpvars);
	// parse all http vars 

	for($i=1; $i <= $nbmax; $i++){
		if(isset($httpvars[$name . $i])){
			$value = $httpvars[$name . $i];
			$value=addslashes($value);
			array_push($result, $value);
		}
	}
	return $result;
}

// *******************************************************
// function diffDate($date)
	// returns time between now and date YYYY MM DD HH mm ss
	// in sec.
/**
 * Returns time between given date and now in seconds
 *
 *@param string $date date, format YYYYMMDDHHmm
 *@return int number of seconds
 */
function diffDate($date){
	// $date : YYYY MM DD HH mm ss
	$year = strftime("%Y");
	$month = strftime("%m");
	
	$day = strftime("%d");
	$hour = strftime("%H");
	$min = strftime("%M");
	if($min < 10){
		$min = "0" . $min;
	}
	$sec = strftime("%S");
	$now=$year.$month.$day.$hour.$min.$sec;
	$nowts=mktime($hour,$min,$sec,$month,$day,$year);

	$year=substr($date,0,4);
	$month=substr($date,4,2);
	$day=substr($date,6,2);
	$hour=substr($date,8,2);
	$min=substr($date,10,2);
	$sec=substr($date,12,2);
	$datets=mktime($hour,$min,$sec,$month,$day,$year);

	return $nowts - $datets;
}

// function nowDate()
/**
 * returns current date formated YYYYMMDDHHmm
 *
 *@return int current date formated YYYYMMDDHHmm
 */
	function nowDate(){
	// $date : YYYY MM DD HH mm
	$year = strftime("%Y");
	$month = strftime("%m");
	
	$day = strftime("%d");
	$hour = strftime("%H");
	$min = strftime("%M");
	if($min < 10){
		$min = "0" . $min;
	}
	$sec = strftime("%S");
	$now=$year.$month.$day.$hour.$min.$sec;
	return $now;
	
	}
	
	
// Function timestampToDate($timestamp)
	/**
	 * Returns epoch timestamp formated into YYYYMMDDHHmmss
	 *
	 *@return int timestamp formated into YYYYMMDDHHmmss
	 */
	Function timestampToDate($timestamp){
		$datearray = getdate($timestamp);
		$year = $datearray['year'];
		$month = $datearray['mon'];
		$day = $datearray['mday'];
		$hour = $datearray['hours'];
		$min = $datearray['minutes'];
		$sec = $datearray['seconds'];
		if($day < 10){
			$day = "0" . $day;
		}
		if($month < 10){
			$month = "0" . $month;
		}
		if($hour < 10){
			$hour = "0" . $hour;
		}
		if($min < 10){
			$min = "0" . $min;
		}
		if($sec < 10){
			$sec = "0" . $sec;
		}
		
		$result = $year.$month.$day.$hour.$min.$sec;

		return $result;
	}
	
	
// *******************************************************
//	Function getSerial($previous)
/**
 * build zone serial number based on previous one, with a format YYYYMMDDxx
 *
 *@param int $previous previous serial number, may be empty
 *@return generated serial number
 */
	Function getSerial($previous){
		// if previous not null, construct against previous
		// else, construct YYYYMMDD01

		$year = strftime("%Y");
		$month = strftime("%m");
	
		$day = strftime("%d");

		$current = $year . $month . $day;
		if(!notnull($previous) || ($current != substr($previous,0,8))){
			$serial = $current . '01';			
		}else{
			$previousnbr=substr($previous,8,strlen($previous) - 8);
			$previousnbr++;
			if(strlen($previousnbr) < 2){
				$previousnbr = '0' . $previousnbr;
			}
			$serial = $current . $previousnbr;
		}
		return $serial;
	}
?>
