<?

/*
	This file is part of XName.org project
	See	http://www.xname.org/ for details
	
	License: GPLv2
	See LICENSE file, or http://www.gnu.org/copyleft/gpl.html
	
	Author(s): Yann Hirou <hirou@xname.org>

*/

 // class User
 // general functions regarding users & logins

// WARNING : we suppose that all supplied parameters
// have already been MySQL-escaped

/**
 * general functions regarding users & logins
 *
 *@access public
 */
class User {

	var $db;
	var $login;
	var $email;
	var $authenticated;
	var $idsession;
	var $password;
	var $valid;
	var $userid;
	
	// Instanciation
	// if $login or $idsession, match against DB
	// to log in. fill in $authenticated, generate $idsession
	/**
	 * Class constructor
	 *
	 *@access public
	 *@param string $dbase database to use
	 *@param string $login XName login, may be null
	 *@param string $password XName password
	 *@param string $sessionID current session ID, if user already logged in
	 */
	Function User($dbase, $login, $password, $sessionID){
		$this->db = $dbase;
		$this->error="";
		$this->idsession=0; // initialization
		$this->authenticated=0;
		$this->email="";
		$this->valid=0;
		$this->userid=0;
		$this->cleanId('dns_session','date');
		$this->cleanId('dns_recovery','insertdate');
		if(notnull($login)){
			if($this->Login($login,$password)){
				// authentication OK
				// generate ID
				$id = $this->generateIDSession();
				if($this->db->error()){
					$this->error="Trouble with DB";
					return 0;
				}
				
				$this->authenticated=1;
				$this->login=$login;
				$this->password=$password;
				// save in DB
				$query = "INSERT INTO dns_session 
				(sessionID,userid) VALUES ('" . $id . "','" .
				$this->userid . "')";
				$res = $this->db->query($query);
				if($this->db->error()){
					$this->error="Trouble with DB";
					return 0;
				}
				$this->idsession=$id;
			}else{ // bad login
				if(notnull($this->error)){
					return 0;
				}else{
					// No authentication
					$this->error="Bad Login";
					return 0;
				}
			}
		}else{ // end if not null login
			if(notnull($sessionID)){
				// retrieve $login, $password from DB
				// check if session not expired (30mn)
				$this->checkidsession($sessionID);				
				if(notnull($this->error)){
					return 0;
				}
				// reinitialize date in DB 
				// to allow browsing user more than 30 mn... 
				$query = "UPDATE dns_session SET userid='" . $this->userid . "'
				WHERE sessionID='" . $sessionID . "'";
				$this->db->query($query);
				$this->authenticated=1;

				// retrieve username
				$query = "SELECT login FROm dns_user WHERE 
				id='" . $this->userid . "'";
				$res = $this->db->query($query);
				if($this->db->error()){
					$this->error="Trouble with DB";
					return 0;
				}
				$line = $this->db->fetch_row($res);
				$this->login = $line[0];
				 
			}else{
				// nothing entered...
				// do nothing.
			}
		} 
	}
	


//	function cleanId($table,$fieldname)
	/**
	 * Delete IDs from given table if they are older than 30 mn
	 *
	 *@access private
	 *@param string $table Table to clean
	 *@param string $fieldname field from table containing timestamp
	 *@return int 1 if success, 0 if error
	 */
	function cleanId($table,$fieldname){
		$this->error="";
		$date = nowDate() - (30*60);
		$query = "DELETE FROM " . $table  . " WHERE
		" . $fieldname . " < '" . $date . "'";
		$res = $this->db->query($query);
		if($this->db->error()){
			$this->error="Trouble with DB";
			return 0;
		}
		return 1;	
	}
	
	// ********************************************************

	// 	Function checkidsession($idsession)
	/**
	 * Check if session ID is valid, not expired, & update timestamp to now
	 *
	 *@access private
	 *@param string $idsession Session ID to validate
	 *@return int 0 if error, else return 1
	 */
	Function checkidsession($idsession){
		$query = "SELECT userid,date FROM dns_session
		WHERE sessionID='" . $idsession . "'";
		$res = $this->db->query($query);
		$line = $this->db->fetch_row($res);
		if($this->db->error()){
			$this->error="Trouble with DB";
			return 0;
		}
		$date = $line[1];

		if($date){
			// check if $date - now <= 30mn

			if(diffDate($date) > 30*60){

				// session expired
				$this->error="Session expired";
				// delete session 
				$query = "DELETE FROM dns_session WHERE sessionID='" .
				$idsession . "'";
				$this->db->query($query);
				return 0;	
			}
			
			$year = strftime("%Y");
			$month = strftime("%m");
	
			$day = strftime("%d");
			$hour = strftime("%H");
			$min = strftime("%M");
			$sec = strftime("%S");
			$now=$year.$month.$day.$hour.$min.$sec;
	
			// update DB with new date
			$query = "UPDATE dns_session SET date='" . $now . "'
			WHERE sessionID='" . $idsession . "'";
			$this->db->query($query);
			
			$this->userid=$line[0];
			$this->idsession=$idsession;
		}else{ // date empty == no such id in DB
			$this->error="Session expired";
			return 0;	
		}	
		return 1;
	}



	// ********************************************************
		
// Function Login($login, $password)
// 		internal use only
	/**
	 * Try to log in user, after calling $this->Exists to check if user exists
	 *
	 *@access private
	 *@param string $login login
	 *@param string $password password
	 *@return int 1 if success, 0 if error or not present
	 */
	Function Login($login,$password){
		$this->error="";
		if(!$this->Exists($login)){
			return 0;
		}else{
			if(!$this->valid){
				$this->error="Login already exists, but email address has not
				 been validated by user";			
				return 0;
			}else{
				$query = "SELECT id FROM dns_user
				WHERE login='$login' AND password='$password'";
				$res = $this->db->query($query);
				$line = $this->db->fetch_row($res);
				if($this->db->error()){
					$this->error="Trouble with DB";
					return 0;
				}
		
				if($line[0] == 0){
					return 0;
				}else{
					$this->userid=$line[0];
					return 1;
				}
			}
		}
	}

	// ********************************************************


//	Function Exists($login)
	/**
	 * Check if user exists or not
	 *
	 *@access private
	 *@param string $login login to check
	 *@return int 1 if present, 0 else - or on error
	 */
	Function Exists($login){
		$this->error="";
		$query = "SELECT valid FROM dns_user WHERE
			login='$login'";
		$res = $this->db->query($query);
		$line = $this->db->fetch_row($res);
		if($this->db->error()){
			$this->error="Trouble with DB";
			return 0;
		}
		
		if(!isset($line[0])){
			return 0;
		}else{
			$this->valid=$line[0];
			return 1;
		}	
	}

// 	Function generateIDSession ()
	/**
	 * Call randomID() recursively until an ID not already in DB is found
	 *
	 *@access public
	 *@return string ID
	 */
	Function generateIDSession (){
		$this->error="";
		$result = randomID();
		
		// check if id already in DB or not
		$query = "SELECT count(*) FROM dns_session
		WHERE sessionID='" . $result . "'";
		$res = $this->db->query($query);
		$line = $this->db->fetch_row($res);
		if($this->db->error()){
			$this->error="Trouble with DB";
			return 0;
		}
		if($line[0] != 0){
			$result = $this->generateIDSession();
		}
		return $result;
	}
	


	
	// ********************************************************
	// 	Function logout($idsession)
	/**
	 * Log out user by deleting entry from dns_session & reseting user vars
	 *
	 *@access public
	 *@param string $idsession session ID to reset
	 *@return int 1 if success, 0 if error 
	 */
	Function logout($idsession){
		if($idsession==0){
			$idsession=$this->idsession;
		}
		$query = "DELETE FROM dns_session WHERE sessionID='" . $idsession . "'";
		$res=$this->db->query($query);
		if($this->db->error()){
			$this->error="Trouble with DB";
			return 0;
		}
		$this->authenticated=0;
		$this->login="";
		$this->password="";
		$this->idsession="";
		$this->userid=0;
		return 1;
	}

// Function userCreate($login,$password,$email)
	/**
	 * Create new user with given login, pass & email
	 *
	 *@access public
	 *@param string $login login 
	 *@param string $password password
	 *@param string $email email
	 *@return int 1 if success, 0 if error
	 */
	Function userCreate($login,$password,$email){
		$this->error="";
		// check if already exists or not
		if(!$this->Login($login,$password)){
			if(!$this->error){
				// does not exist already ==> OK
				$query = "INSERT INTO dns_user (login,email,password)
				VALUES ('".$login."','".$email."','".$password."')";
				$res = $this->db->query($query);
				if($this->db->error()){
					$this->error = "Trouble with DB";
					return 0;
				}else{
					$query = "SELECT id FROM dns_user WHERE login='" . $login .
					"'";
					$res = $this->db->query($query);
					$line = $this->db->fetch_row($res);
					if($this->db->error()){
						$this->error = "Trouble with DB";
						return 0;
					}else{
						$this->userid=$line[0];	
						return 1;
					}
				}
			}
		}else{
			$this->error="Login already exists";
			return 0;
		}
	}


//	Function changeLogin($login)
	/**
	 * Change login name for current user
	 *
	 *@access public
	 *@param string $login login
	 *@return int 1 if success, 0 if error
	 */
	Function changeLogin($login){
		$this->error="";
		$query = "UPDATE dns_user SET login='" . $login . "' 
		WHERE id='" . $this->userid . "'";
		$res = $this->db->query($query);
		if($this->db->error()){
			$this->error = "Trouble with DB";
			return 0;
		}		
		return 1;
	}
	
//	Function updatePassword($password)
	/**
	 * Change password for current user
	 *
	 *@access public
	 *@param string $password password
	 *@return int 1 if success, 0 if error
	 */
	Function updatePassword($password){
		$this->error="";
		$query = "UPDATE dns_user SET password='" . $password . "'
		WHERE id='" . $this->userid . "'";
		$res = $this->db->query($query);
		if($this->db->error()){
			$this->error = "Trouble with DB";
			return 0;
		}else{
			return 1;
		}	
	}
	
// Function listallzones()
	/**
	 * list all zones owned by same user
	 *
	 *@access public
	 *@return array array of all zones/zonestypes owned by user or 0 if error
	 */	
	Function listallzones(){
		// warning: be sure to validate user before using this function
		$this->error="";

		$query = "SELECT zone, zonetype FROM dns_zone
		WHERE userid='" . $this->userid . "'";
		$res = $this->db->query($query);
		if($this->db->error()){
			$this->error="Trouble with DB";
			return 0;
		}else{
			$result = array();
			while($line = $this->db->fetch_row($res)){
				array_push($result,$line);
			}
			return $result;
		}
	}

// 	Function Retrievemail()
	/**
	 * Return email from current user
	 *
	 *@access public
	 *@return string email address or 0 if error
	 */
	Function Retrievemail(){
		$this->error="";
		if(notnull($this->email)){
			return $this->email;
		}
		$query = "SELECT email FROM dns_user 
		WHERE id='" . $this->userid . "'";

		$res=$this->db->query($query);
		$line=$this->db->fetch_row($res);
		if($this->db->error()){
			$this->error="Trouble with DB";
			return 0;
		}else{
			$this->email=$line[0];
			return $this->email;
		}		
	}

//	Function getEmail()
	// used to retrieve email with login without loging in
	// sample : recovery
	/**
	 * Return email from specified user, even if not logged in
	 *
	 *@access public
	 *@param string $login login to retrieve mail for
	 *@return string email address or 0 if error
	 */
	Function getEmail($login){
		$this->error='';

		$query = "SELECT email FROM dns_user 
		WHERE login='" . $login . "'";

		$res=$this->db->query($query);
		$line=$this->db->fetch_row($res);
		if($this->db->error()){
			$this->error="Trouble with DB";
			return 0;
		}else{
			return $line[0];
		}
	}

//	Function Changemail($email)
	/**
	 * Change email address for current user
	 *
	 *@access public
	 *@param string $email new email address
	 *@return int 1 if success, 0 if error
	 */
	Function Changemail($email){
		$this->error="";
		$query = "UPDATE dns_user SET email='" . $email . "',
		valid='0' WHERE id='" . $this->userid . "'";
		$res=$this->db->query($query);
		if($this->db->error()){
			$this->error="Trouble with DB";
			return 0;
		}else{
			return 1;
		}		
	}


// 	Function Retrievepassword()
	/**
	 * Return password for current user
	 *
	 *@access public
	 *@return string current password or 0 if error
	 */	 
	Function Retrievepassword(){
		$this->error="";
		if(notnull($this->password)){
			return $this->password;
		}
		$query = "SELECT password FROM dns_user 
		WHERE id='" . $this->userid . "'";

		$res=$this->db->query($query);
		$line=$this->db->fetch_row($res);
		if($this->db->error()){
			$this->error="Trouble with DB";
			return 0;
		}else{
			$this->password=$line[0];
			return $this->password;
		}		
	}


//	Function generateIDEmail()
	/**
	 * Return generated ID for dns_waitingreply not already present (recursive)
	 *
	 *@access public
	 *@return string ID generated or 0 if error
	 */
	Function generateIDEmail(){
		$this->error="";
		$result = randomID();
		
		// check if id already in DB or not
		$query = "SELECT count(*) FROM dns_waitingreply
		WHERE id='" . $result . "'";
		$res = $this->db->query($query);
		$line = $this->db->fetch_row($res);
		if($this->db->error()){
			$this->error="Trouble with DB";
			return 0;
		}
		if($line[0] != 0){
			$result = $this->generateIDEmail();
		}
		return $result;	
	}
	
//	Function storeIDEmail($userid,$email,$id)
	/**
	 * store userid, email and new ID (generated with generateIDEmail) 
	 * in dns_waitingreply, to wait for validation of new email address
	 *
	 *@param string $userid user ID
	 *@param string $email user email address
	 *@param string $id unique ID for dns_waitingreply 
	 *@return int 1 if success, 0 if error
	 */
	Function storeIDEmail($userid,$email,$id){
		$this->error="";
		// check if present or not !
		$query = "DELETE FROM dns_waitingreply WHERE userid='" . $userid . "'";
		$res = $this->db->query($query);
		
		$query = "INSERT INTO dns_waitingreply (userid,email,id) 
		VALUES ('" . $userid . "','" . $email . "','" . $id . "')";
		$res = $this->db->query($query);
		if($this->db->error()){
			$this->error="Trouble with DB";
			return 0;
		}
		return 1;
	}
	
//	Function validateIDEmail($id)
	/**
	 * Validate email corresponding to given ID (implies that user
	 * have received email with validating ID)
	 *
	 *@access public
	 *@param string $id ID
	 *@return int 1 if success, 0 if error
	 */
	Function validateIDEmail($id){
		// TODO : valid for limited time
		$this->error="";
		$query = "SELECT userid FROM dns_waitingreply 
		WHERE id='" . $id . "'";
		$res = $this->db->query($query);
		$line = $this->db->fetch_row($res);
		if($this->db->error()){
			$this->error="Trouble with DB";
			return 0;
		}
		if(!notnull($line[0])){
			$this->error="No such ID";
			return 0;
		}
		$userid = $line[0];
		
		$query = "DELETE FROM dns_waitingreply WHERE 
		userid='" . $userid . "'";
		$res = $this->db->query($query);
		if($this->db->error()){
			$this->error="Trouble with DB";
			return 0;
		}
		// update & set dns_user.valid to 1
		$query = "UPDATE dns_user SET valid='1' WHERE
		id='" . $userid . "'";
		$res = $this->db->query($query);
		if($this->db->error()){
			$this->error="Trouble with DB";
			return 0;
		}
		return 1;
	}


//	Function generateIDRecovery()
	/**
	 * Generate unique ID for account recovery
	 *
	 *@access public
	 *@return string ID if success, 0 if error
	 */
	Function generateIDRecovery(){
		$this->error="";
		$result = randomID();
		
		// check if id already in DB or not
		$query = "SELECT count(*) FROM dns_recovery
		WHERE id='" . $result . "'";
		$res = $this->db->query($query);
		$line = $this->db->fetch_row($res);
		if($this->db->error()){
			$this->error="Trouble with DB";
			return 0;
		}
		if($line[0] != 0){
			$result = $this->generateIDRecovery();
		}
		return $result;	
	}
	

//	Function storeIDRecovery($login,$id)
	/**
	 * store login and new ID (generated with generateIDRecovery) 
	 * in dns_recovery, to wait for request of lost password
	 *
	 *@access public
	 *@param string $login login
	 *@param string $id generated ID to store
	 *@return int 1 if success, 0 if error
	 */
	Function storeIDRecovery($login,$id){
		$this->error="";
		// retrieve user ID
		$query = "SELECT id FROM dns_user WHERE login='" . $login . "'";
		$res = $this->db->query($query);
		$line=$this->db->fetch_row($res);
		if($this->db->error()){
			$this->error="Trouble with DB";
			return 0;
		}
		$userid = $line[0];
		
		// if $login already present, delete id
		$query = "DELETE FROM dns_recovery WHERE userid='" . $userid . "'";
		$res = $this->db->query($query);
		if($this->db->error()){
			$this->error="Trouble with DB";
			return 0;
		}		
		$query = "INSERT INTO dns_recovery (userid,id) 
		VALUES ('" . $userid . "','" . $id . "')";
		$res = $this->db->query($query);
		if($this->db->error()){
			$this->error="Trouble with DB";
			return 0;
		}
		return 1;
	}


// 	Function validateIDRecovery($id)
	/**
	 * Validate ID from dns_recovery, and modify current userid 
	 * to the one from dns_recovery
	 *
	 *@access public
	 *@param string $id ID
	 *@return int 1 if success, 0 if error
	 */
	Function validateIDRecovery($id){
		// TODO : valid for limited time
		$this->error="";
		$query = "SELECT userid FROM dns_recovery 
		WHERE id='" . $id . "'";
		$res = $this->db->query($query);
		$line = $this->db->fetch_row($res);
		if($this->db->error()){
			$this->error="Trouble with DB";
			return 0;
		}
		if(!notnull($line[0])){
			$this->error="No such ID";
			return 0;
		}
		$userid = $line[0];
		
		$query = "DELETE FROM dns_recovery WHERE 
		userid='" . $userid . "'";
		$res = $this->db->query($query);
		if($this->db->error()){
			$this->error="Trouble with DB";
			return 0;
		}
		$this->userid=$userid;
		return 1;	
	}



// 	Function RetrieveLogin($id)
	/**
	 * Return login matching given userid
	 *
	 *@access public
	 *@param string $id user ID
	 *@return string login or 0 if error
	 */
	Function RetrieveLogin($id){
		$this->error="";
		$query = "SELECT login FROM dns_user 
		WHERE id='" . $id . "'";

		$res=$this->db->query($query);
		$line=$this->db->fetch_row($res);
		if($this->db->error()){
			$this->error="Trouble with DB";
			return 0;
		}else{
			return $line[0];
		}		
	}

}
?>
