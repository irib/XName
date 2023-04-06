<?

/*
	This file is part of XName.org project
	See	http://www.xname.org/ for details
	
	License: GPLv2
	See LICENSE file, or http://www.gnu.org/copyleft/gpl.html
	
	Author(s): Yann Hirou <hirou@xname.org>

*/

// WARNING : we suppose that all supplied parameters
// have already been MySQL-escaped


/**
 * Class containing global functions regarding zones
 *
 *@access public
 */
class Zone { 
	var $db;
	var $config;
	var $error;
	var $zonename;
	var $zonetype;
	var $userid;
	var $zoneid;
	
	// instanciation
	// if $zonename or $idsession, match against DB
	// to log in. fill in $authenticated, generate $idsession
	/**
	 * Class constructor
	 *
	 *@param string $dbase database currently used
	 *@param string $zonename name of zone, may be empty
	 *@param string $zonetype type of zone ('M'aster or 'S'lave)
	 *@param object Config $config Config object
	 */
	Function Zone($dbase,$zonename,$zonetype,$config){
		$this->db = $dbase; 
		$this->error="";
		$this->config = $config;
		
		if(notnull($zonename)){
			if($this->Exists($zonename,$zonetype)){
				$this->zonename=$zonename;
				$this->zonetype=$zonetype;
				$this->retrieveID($zonename,$zonetype);
				if(notnull($this->error)){
					return 0;
				}
			}else{ // does not exist
				if(notnull($this->error)){
					return 0;
				}else{
					// No authentication
					$this->error="Bad zone";
					return 0;
				}
			}
		}
	}
	
	
// Function Exists($zonename,$zonetype)
// 		try to authenticate against primary or secondary
// 		internal use only
	/**
	 * Check if zone already exists
	 *
	 *@access private
	 *@param string $zonename name of zone
	 *@param string $zonetype type of zone ('M'aster or 'S'lave)
	 *@return int 1 if true, 0 if false or error 
	 */
	Function Exists($zonename,$zonetype){
		$this->error="";
		
		// because XName has only 1 DNS, only primary OR secondary
//		$query = "SELECT count(*) FROM dns_zone
//		WHERE zone='$zonename' AND zonetype='$zonetype'";
		$query = "SELECT count(*) FROM dns_zone
		WHERE zone='$zonename'";
		$res = $this->db->query($query);
		$line = $this->db->fetch_row($res);
		if($this->db->error()){
			$this->error="Trouble with DB";
			return 0;
		}

		if($line[0] == 0){
			return 0;
		}else{
			return 1;
		}
	}
	
//	Function subExists($zonename,$userid)
// check if zone is sub-zone of an existing one
// or if there is already a sub zone of this one

	/**
	 * Check if part of current zone is already registered
	 *
	 * Check if the current zone is a sub-zone of an
	 * existing one, or if a subzone of this one is
	 * already registered.
	 *
	 *@access private
	 *@param string $zonename name of zone
	 *@param int $userid user ID
	 *@return array list of zones having links with this one, or 0 if error
	 */
	Function subExists($zonename,$userid){
		$this->error="";
		// sub zone of an existing one ?
		$upper = split('\.',$zonename);
		reset($upper);
		$tocompare = "";
		$list = array();
		while($tld = array_pop($upper)){
			if($tocompare == ""){
				$tocompare = $tld;
			}else{
				$tocompare = $tld . "." . $tocompare;
			}
			$query = "SELECT zone from dns_zone WHERE 
			zone='" . $tocompare . "' AND userid!='" . $userid . "'";
			$res = $this->db->query($query);
			if($this->db->error()){
				$this->error="Trouble with DB";
				return 0;
			}
			while($line = $this->db->fetch_row($res)){
				array_push($list,$line[0]);			
			}
		}
		
		// already a sub zone of this one ?
		$query = "SELECT zone FROM dns_zone WHERE
		zone like '%." . $zonename . "' AND userid!='" . $userid . "'";
		$res = $this->db->query($query);
		if($this->db->error()){
			$this->error="Trouble with DB";
			return 0;
		}
		while($line = $this->db->fetch_row($res)){
			array_push($list,$line[0]);			
		}

		return $list;
	}

//	Function retrieveID($zonename,$zonetype)
	/**
	 * Retrieve ID of current zone in $this->zoneid
	 *
	 *@access public
	 *@param string $zonename name of zone
	 *@param string $zonetype type of zone ('M'aster or 'S'lave)
	 *@return int 0 if error or no such zone, 1 if ID found
	 */
	Function retrieveID($zonename,$zonetype){
		$this->error="";
		$query = "SELECT id FROM dns_zone WHERE 
		zone='" . $zonename . "' AND zonetype='" . $zonetype . "'";
		$res = $this->db->query($query);
		$line = $this->db->fetch_row($res);
		if($this->db->error()){
			$this->error="Trouble with DB";
			return 0;
		}

		if($line[0] == 0){
			return 0;
		}else{
			$this->zoneid = $line[0];
			return 1;
		}
	}

// Function zoneCreate($zonename,$zonetype,$userid)
	/**
	 * Insert new zone in dns_zone table 
	 *
	 *@access public
	 *@param string $zonename zone name
	 *@param string $zonetype zone type ('M'aster or 'S'lave)
	 *@param int $userid user ID
	 *@return int 1 if success, 0 if trouble
	 */
	Function zoneCreate($zonename,$zonetype,$userid){
		$this->error="";
		// check if already exists or not
		if(!$this->Exists($zonename,$zonetype)){
			// does not exist already ==> OK
			$query = "INSERT INTO dns_zone (zone,zonetype,userid)
			VALUES ('".$zonename."','".$zonetype."','".$userid."')";
			$res = $this->db->query($query);
			if($this->db->error()){
				$this->error = "Trouble with DB";
				return 0;
			}else{
				return 1;
			}
			
		}else{
			$this->error="Zone already exists";
			return 0;
		}	
	}



// Function zoneDelete($zonename, $zonetype)
// 		delete primary or secondary
// 		internal use only
// TODO : use zoneid instead of zonename & zonetype
	/**
	 * Delete zone and records from all tables
	 *
	 *@access public
	 *@param string $zonename zone name
	 *@param string $zonetype zone type ('M'aster or 'S'lave)
	 *@return int 1 if success, 0 if trouble
	 */
	Function zoneDelete($zonename,$zonetype){
		$this->error="";
		if(!$this->Exists($zonename,$zonetype)){
			$this->error="Bad login/pass/type";
			return 0;
		}else{
			// Delete from :
			// dns_zone, dns_conf*, dns_modified, dns_record,
			// dns_recovery, dns_waitingreply
			// insert into dns_deleted
			$todelete = array('dns_zone','dns_modified',
			'dns_record','dns_recovery','dns_waitingreply');
			if($zonetype == 'M'){
				array_push($todelete, 'dns_confprimary');
			}else{
				array_push($todelete, 'dns_confsecondary');
			}
			reset($todelete);
			
			while($item = array_pop($todelete)){
				$query = "DELETE FROM " . $item . " WHERE 
				zone='" . $zonename . "'";
				$this->db->query($query);
				if($this->db->error()){
					$this->error = "Trouble with DB";
					return 0;
				}else{
					return 1;
				}
			}
		}
	}


// Function zoneLogs($class1,$class2)
// 		print logs regarding zone
// 		in a table, with class $class
// 		alternate color between lines - done with class1 & class2
	/**
	 * Return HTML table with all logs regarding zone
	 *
	 * Lines are alternatively colored using class parameter
	 * for <tr> and <td>. classes have to be defined in CSS file,
	 * as <classname>INFORMATION,<classname>WARNING,<classname>ERROR
	 *
 	 *@access public
	 *@param string $class1 <classname> 
	 *@param string $class2 <alternateclassname>
	 *@return string html code of rows
	 */
	Function zoneLogs($class1,$class2){
		$this->error="";
		$result = "";
		$query = "SELECT count(*) FROM dns_log WHERE zoneid='" .
		$this->zoneid . "'";
		$res=$this->db->query($query);
		$line=$this->db->fetch_row($res);
		if($line[0] == 0){
			return "No logs available for this zone.";		
		}
		$query = "SELECT date,content,status 
		FROM dns_log WHERE zoneid='" . $this->zoneid . "' ORDER BY date DESC";
		$res=$this->db->query($query);
		$class=$class2;
		while($line=$this->db->fetch_row($res)){
			if($this->db->error()){
				$this->error = "Trouble with DB";
				return 0;
			}else{
				if($class==$class2){
					$class=$class1;
				}else{
					$class=$class2;
				}
				$classadd = "INFORMATION";
				if($line[2] == 'E'){
					$classadd="ERROR";
				}else{
					if($line[2] == 'W'){
						$classadd="WARNING";
					}
				}
				// out only hour:min
				$timestamp = $line[0];
				$hour = substr($timestamp,-6,2);
				$min = substr($timestamp,-4,2);
				$result .= '<tr class="' . $class . '"><td class="' . $class . '">' . $hour . ":" .
				$min .
				'</td><td class="' . $class . '">' .
				$line[1] . '</td><td class="' . $class . $classadd . 
				'">' . $line[2] . "</td></tr>\n";
			}
		}
		return $result;
	}

//	Function zoneStatus()
// 		Returns global status of zone : I,W,E or U(nknown)
	/**
	 * Returns status of zone: I(nformation), W(arning), E(rror), or U(nknown)
	 *
	 *@access public
	 *@return string I W E or U or 0 if trouble
	 */
	Function zoneStatus(){
		$this->error="";
		$i=0;
		$query = "SELECT status FROM 
		dns_log WHERE zoneid='" . $this->zoneid . "' 
		GROUP BY status";
		$res=$this->db->query($query);
		while($line=$this->db->fetch_row($res)){
			if($this->db->error()){
				$this->error = "Trouble with DB";
				return 0;
			}else{
				switch($line[0]) {
					case 'E':
						return 'E';
						break;
					case 'W':
						return 'W';
						break;
					default:
						$i=1;
				}
			}
		}
		if($i){
			return 'I';
		}else{
			return 'U';
		}
	}
	
	
	
//	Function RetrieveUser()
	/**
	 * Retrieve user ID of zone owner
	 *
	 *@access public
	 *@return int user ID or 0 if trouble
	 */
	Function RetrieveUser(){
		$this->error="";
		if($this->userid != 0){
			return $this->userid;
		}
		$query = "SELECT userid FROM dns_zone 
		WHERE id='" . $this->zoneid . "'";
		$res=$this->db->query($query);
		$line=$this->db->fetch_row($res);
		if($this->db->error()){
			$this->error="Trouble with DB";
			return 0;
		}else{
			$this->userid=$line[0];
			return $this->userid;
		}		
	}
}

?>
