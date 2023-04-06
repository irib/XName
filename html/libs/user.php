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

	var $login;
	var $email;
	var $authenticated;
	var $idsession;
	var $password;
	var $valid;
	var $userid;
	var $grouprights;
	var $advanced;
	var $ipv6;
	var $txtrecords;
	var $nbrows;
	var $lang;
	var $isadmin;
	
	// Instanciation
	// if $login or $idsession, match against DB
	// to log in. fill in $authenticated, generate $idsession
	/**
	 * Class constructor
	 *
	 *@access public
	 *@param string $login XName login, may be null
	 *@param string $password XName password
	 *@param string $sessionID current session ID, if user already logged in
	 */
	Function User($login, $password, $sessionID){
		$this->error="";
		$this->idsession=0; // initialization
		$this->authenticated=0;
		$this->email="";
		$this->valid=0;
		$this->userid=0;
		$this->grouprights="";
		$this->isadmin=0;
		$this->cleanId('dns_session','date');
		$this->cleanId('dns_recovery','insertdate');
		global $db,$l;
		
		if(notnull($login)){
			if($this->Login($login,$password)){
				// authentication OK
				// generate ID
				$id = $this->generateIDSession();
				if($db->error()){
					$this->error=$l['str_trouble_with_db'];
					return 0;
				}
				$this->authenticated=1;
				$this->login=$login;
				$this->password=$password;
				// save in DB
				$query = "INSERT INTO dns_session 
				(sessionID,userid) VALUES ('" . $id . "','" .
				$this->userid . "')";
				$res = $db->query($query);
				if($db->error()){
					$this->error=$l['str_trouble_with_db'];
					return 0;
				}
				$this->idsession=$id;
			}else{ // bad login
				if(notnull($this->error)){
					return 0;
				}else{
					// No authentication
					$this->error=$l['str_bad_login_name'];
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
				$this->authenticated=1;

				// retrieve username
				$query = "SELECT login FROM dns_user WHERE 
				id='" . $this->userid . "'";
				$res = $db->query($query);
				if($db->error()){
					$this->error=$l['str_trouble_with_db'];
					return 0;
				}
				$line = $db->fetch_row($res);
				$this->login = $line[0];
				 
			}else{
				// nothing entered...
				// do nothing.
				return 0;
			}
		} // end else if not null login 

		// retrieve advanced param
		if(!$this->RetrieveFlags()){
			return 0;
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
		global $db,$l;
		
		$this->error="";
		$date = dateToTimestamp(nowDate());
		$date -= 30*60;
		$date = timestampToDate($date);
		$query = "DELETE FROM " . $table  . " WHERE
		" . $fieldname . " < " . $date;
		$res = $db->query($query);
		if($db->error()){
			$this->error=$l['str_trouble_with_db'];
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
		global $db,$l;
		
		$query = "SELECT userid,date FROM dns_session
		WHERE sessionID='" . $idsession . "'";
		$res = $db->query($query);
		$line = $db->fetch_row($res);
		if($db->error()){
			$this->error=$l['str_trouble_with_db'];
			return 0;
		}
		$date = $line[1];

		if($date){
			// check if $date - now <= 30mn

			if(diffDate($date) > 30*60){

				// session expired
				// delete session 
				$query = "DELETE FROM dns_session WHERE sessionID='" .
				$idsession . "'";
				$db->query($query);
				$this->error=$l['str_session_expired'];
				return 0;	
			}
			
			// update DB with new date
			$query = "UPDATE dns_session SET date=now()
			WHERE sessionID='" . $idsession . "'";
			$db->query($query);
			
			$this->userid=$line[0];
			$this->idsession=$idsession;
		}else{ // date empty == no such id in DB
			$this->error=$l['str_session_expired'];
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
		global $db,$l;
		
		$this->error="";
		if(!$this->Exists($login)){
			return 0;
		}else{
			if(!$this->valid($login)){
				$this->error=$l['str_login_not_activated'];			
				return 0;
			}else{
				$password = md5($password);
				$query = "SELECT id FROM dns_user
				WHERE login='$login' AND password='$password'";
				$res = $db->query($query);
				$line = $db->fetch_row($res);
				if($db->error()){
					$this->error=$l['str_trouble_with_db'];
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
		global $db,$l;
		$this->error="";
		$query = "SELECT count(*) FROM dns_user WHERE
			login='$login'";
		$res = $db->query($query);
		$line = $db->fetch_row($res);
		if($db->error()){
			$this->error=$l['str_trouble_with_db'];
			return 0;
		}
		return $line[0];
	}


// Function Valid($login)
	/** 
	 * Check if given login is flagged as valid or not
	 *@access private
	 *@param string $login login to check
	 *@return int 1 if valid, 0 else
	 */
	Function Valid($login){
		global $db,$l;
		$this->error="";
		$query = "SELECT count(*) FROM dns_user 
					WHERE login='" . $login . "' AND valid='1'";
		$res = $db->query($query);
		$line = $db->fetch_row($res);
		if($db->error()){
			$this->error=$l['str_trouble_with_db'];
			return 0;
		}
		$this->valid=$line[0];
		return $line[0];
	}
	
	
// 	Function generateIDSession ()
	/**
	 * Call randomID() recursively until an ID not already in DB is found
	 *
	 *@access public
	 *@return string ID
	 */
	Function generateIDSession (){
		global $db,$l;
		$this->error="";
		$result = randomID();
		
		// check if id already in DB or not
		$query = "SELECT count(*) FROM dns_session
		WHERE sessionID='" . $result . "'";
		$res = $db->query($query);
		$line = $db->fetch_row($res);
		if($db->error()){
			$this->error=$l['str_trouble_with_db'];
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
		global $db,$l;
		if($idsession==0){
			$idsession=$this->idsession;
		}
		$query = "DELETE FROM dns_session WHERE sessionID='" . $idsession . "'";
		$res=$db->query($query);
		if($db->error()){
			$this->error=$l['str_trouble_with_db'];
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
		global $db,$l;
		global $config;
		$this->error="";
		// check if already exists or not
		if(!$this->Exists($login)){
			if(!$this->error){
				// does not exist already ==> OK
				$password = md5($password);
				if($config->usergroups){
					$query = "INSERT INTO dns_user
					(login,email,password,groupright)
					VALUES ('".$login."','".$email."','".$password."','A')";
				}else{
					$query = "INSERT INTO dns_user (login,email,password)
					VALUES ('".$login."','".$email."','".$password."')";
				}
				$res = $db->query($query);
				if($db->error()){
					$this->error = $l['str_trouble_with_db'];
					return 0;
				}else{
					$query = "SELECT id FROM dns_user WHERE login='" . $login .
					"'";
					$res = $db->query($query);
					$line = $db->fetch_row($res);
					if($db->error()){
						$this->error = $l['str_trouble_with_db'];
						return 0;
					}else{
						$this->userid=$line[0];	
						if($config->usergroups){
							$query="UPDATE dns_user SET groupid='" .
							$this->userid . "' WHERE id='" . $this->userid .
							"'";
							$res = $db->query($query);
							if($db->error()){
								$this->error = $l['str_trouble_with_db'];
								return 0;
							}							
						}
						return 1;
					}
				}
			}
		}else{
			$this->error=$l['str_login_already_exists'];
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
		global $db,$l;
		$this->error="";
		$query = "UPDATE dns_user SET login='" . $login . "' 
		WHERE id='" . $this->userid . "'";
		$res = $db->query($query);
		if($db->error()){
			$this->error = $l['str_trouble_with_db'];
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
		global $db,$l;
		$this->error="";
		$password = md5($password);
		$query = "UPDATE dns_user SET password='" . $password . "'
		WHERE id='" . $this->userid . "'";
		$res = $db->query($query);
		if($db->error()){
			$this->error = $l['str_trouble_with_db'];
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
		global $db,$l;
		// warning: be sure to validate user before using this function
		$this->error="";

		$query = "SELECT zone, zonetype, id FROM dns_zone
		WHERE userid='" . $this->userid . "' AND status!='D' ORDER BY zone DESC";
		$res = $db->query($query);
		if($db->error()){
			$this->error=$l['str_trouble_with_db'];
			return 0;
		}else{
			$result = array();
			while($line = $db->fetch_row($res)){
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
		global $db,$l;
		$this->error="";
		if(notnull($this->email)){
			return $this->email;
		}
		$query = "SELECT email FROM dns_user 
		WHERE id='" . $this->userid . "'";

		$res=$db->query($query);
		$line=$db->fetch_row($res);
		if($db->error()){
			$this->error=$l['str_trouble_with_db'];
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
		global $db,$l;
		$this->error='';

		$query = "SELECT email FROM dns_user 
		WHERE login='" . $login . "'";

		$res=$db->query($query);
		$line=$db->fetch_row($res);
		if($db->error()){
			$this->error=$l['str_trouble_with_db'];
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
		global $db,$l;
		$this->error="";
		$query = "UPDATE dns_user SET email='" . $email . "',
		valid='0' WHERE id='" . $this->userid . "'";
		$res=$db->query($query);
		if($db->error()){
			$this->error=$l['str_trouble_with_db'];
			return 0;
		}else{
			return 1;
		}		
	}


// Function RetrieveFlags()
	/**
	 * Returns flags for current user. 
	 *
	 *@access private
	 *@return int 0 if error, 1 if success
	 */
	 Function RetrieveFlags(){
	 	global $db,$l;
	 	$this->error="";
		$query = "SELECT advanced,ipv6,txtrecords,nbrows,lang FROM dns_user
					WHERE id='" . $this->userid . "'";
					
		$res=$db->query($query,1);
		$line=$db->fetch_row($res);
		if($db->error()){
			$this->error=$l['str_trouble_with_db'];
			return 0;
		}else{
			$this->advanced=$line[0];
			$this->ipv6=$line[1];
			$this->txtrecords=$line[2];
			$this->nbrows=$line[3];
			$this->lang=$line[4];
			// check if admin
			$query = "SELECT count(*) from dns_admin where userid='" . $this->userid . "'";
			$res=$db->query($query,1);
                	$line=$db->fetch_row($res);
			if($db->error()){
	                        $this->error=$l['str_trouble_with_db'];
        	                return 0;
                	}else{
				if($line[0]){
					$this->isadmin = 1;
				}
				return 1;
			}
		}
	 }
	 
// Function changeFlags($param)
	/**
	 * Change Flags parameters for current user
	 *
	 *@access public
	 *@param $advanced int flag to be set (0 or 1)
	 *@param $ipv6 int flag to be set (0 or 1)
	 *@param $txtrecords int flag to be set (0 or 1)
	 *@param $nbrows int number of raw to be printed
	 *@param $lang string language to be used for this user	 
	 *@return int 1 if success, 0 if error
	 */
	Function changeFlags($advanced,$ipv6,$txtrecords,$nbrows,$lang){
		global $db,$l;
		$this->error="";
		$query = "UPDATE dns_user SET advanced='" . $advanced . "',
						ipv6='" . $ipv6 . "',
						txtrecords='" . $txtrecords . "',
						nbrows='" . $nbrows . "',
						lang='" . $lang . "'
						WHERE id='" . $this->userid . "'";
		$res=$db->query($query);
		if($db->error()){
			$this->error=$l['str_trouble_with_db'];
			return 0;
		}else{
			$this->advanced=1;
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
		global $db,$l;
		$this->error="";
		if(notnull($this->password)){
			return $this->password;
		}
		$query = "SELECT password FROM dns_user 
		WHERE id='" . $this->userid . "'";

		$res=$db->query($query);
		$line=$db->fetch_row($res);
		if($db->error()){
			$this->error=$l['str_trouble_with_db'];
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
		global $db,$l;
		$this->error="";
		$result = randomID();
		
		// check if id already in DB or not
		$query = "SELECT count(*) FROM dns_waitingreply
		WHERE id='" . $result . "'";
		$res = $db->query($query);
		$line = $db->fetch_row($res);
		if($db->error()){
			$this->error=$l['str_trouble_with_db'];
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
		global $db,$l;
		$this->error="";
		// check if present or not !
		$query = "DELETE FROM dns_waitingreply WHERE userid='" . $userid . "'";
		$res = $db->query($query);
		
		$query = "INSERT INTO dns_waitingreply (userid,email,id) 
		VALUES ('" . $userid . "','" . $email . "','" . $id . "')";
		$res = $db->query($query);
		if($db->error()){
			$this->error=$l['str_trouble_with_db'];
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
		global $db,$l;
		// TODO : valid for limited time
		$this->error="";
		$query = "SELECT userid FROM dns_waitingreply 
		WHERE id='" . $id . "'";
		$res = $db->query($query);
		$line = $db->fetch_row($res);
		if($db->error()){
			$this->error=$l['str_trouble_with_db'];
			return 0;
		}
		if(!notnull($line[0])){
			$this->error=$l['str_no_such_id'] ;
			return 0;
		}
		$userid = $line[0];
		
		$query = "DELETE FROM dns_waitingreply WHERE 
		userid='" . $userid . "'";
		$res = $db->query($query);
		if($db->error()){
			$this->error=$l['str_trouble_with_db'];
			return 0;
		}
		// update & set dns_user.valid to 1
		$query = "UPDATE dns_user SET valid='1' WHERE
		id='" . $userid . "'";
		$res = $db->query($query);
		if($db->error()){
			$this->error=$l['str_trouble_with_db'];
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
		global $db,$l;
		$this->error="";
		$result = randomID();
		
		// check if id already in DB or not
		$query = "SELECT count(*) FROM dns_recovery
		WHERE id='" . $result . "'";
		$res = $db->query($query);
		$line = $db->fetch_row($res);
		if($db->error()){
			$this->error=$l['str_trouble_with_db'];
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
		global $db,$l;
		$this->error="";
		// retrieve user ID
		$query = "SELECT id FROM dns_user WHERE login='" . $login . "'";
		$res = $db->query($query);
		$line=$db->fetch_row($res);
		if($db->error()){
			$this->error=$l['str_trouble_with_db'];
			return 0;
		}
		$userid = $line[0];
		
		// if $login already present, delete id
		$query = "DELETE FROM dns_recovery WHERE userid='" . $userid . "'";
		$res = $db->query($query);
		if($db->error()){
			$this->error=$l['str_trouble_with_db'];
			return 0;
		}		
		$query = "INSERT INTO dns_recovery (userid,id) 
		VALUES ('" . $userid . "','" . $id . "')";
		$res = $db->query($query);
		if($db->error()){
			$this->error=$l['str_trouble_with_db'];
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
		global $db,$l;
		// TODO : valid for limited time
		$this->error="";
		$query = "SELECT userid FROM dns_recovery 
		WHERE id='" . $id . "'";
		$res = $db->query($query);
		$line = $db->fetch_row($res);
		if($db->error()){
			$this->error=$l['str_trouble_with_db'];
			return 0;
		}
		if(!notnull($line[0])){
			$this->error=$l['str_no_such_id'];
			return 0;
		}
		$userid = $line[0];
		
		$query = "DELETE FROM dns_recovery WHERE 
		userid='" . $userid . "'";
		$res = $db->query($query);
		if($db->error()){
			$this->error=$l['str_trouble_with_db'];
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
		global $db,$l;
		$this->error="";
		$query = "SELECT login FROM dns_user 
		WHERE id='" . $id . "'";

		$res=$db->query($query);
		$line=$db->fetch_row($res);
		if($db->error()){
			$this->error=$l['str_trouble_with_db'];
			return 0;
		}else{
			return $line[0];
		}		
	}


// Function deleteUser()
	/**
	 * Remove user from DB
	 *
	 *@access public
	 *@return int 1 if success, 0 if fail
	 */
	Function deleteUser(){
		global $db,$l;
		$this->error="";
		$query = "DELETE FROM dns_user WHERE id='" . $this->userid . "'";
		$res=$db->query($query);
		if($db->error()){
			$this->error=$l['str_trouble_with_db'];
			return 0;
		}else{
			$this->logout();
			return 1;
		}
	}


//	Function GenerateRandomPassword($length)
	/**
	* Return a random password of $length length
	*
	*@access public
	*@param int $length, desired password length
	*@return string password
	*/

	Function generateRandomPassword($length){
                $u_alpha = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z");
                $l_alpha = array("a","b","c","d","e","f","g","h","i","j","k","l","m","n","o","p","q","r","s","t","u","v","w");
                $numeric = array("0","1","2","3","4","5","6","7","8","9");
                $sel = mt_rand(0, 1);
                if ($sel == 0){
                        $pass = $u_alpha[mt_rand(0, (count($u_alpha) - 1))];
                }else{
                        $pass = $l_alpha[mt_rand(0, (count($l_alpha) - 1))];
				}
                for ($i=0;$i<$length;$i++){
                        $sel = mt_rand(0, 2);
                        if ($sel == 0){
                                $pass .= $u_alpha[mt_rand(0, (count($u_alpha) - 1))];
                        }else if ($sel == 1){
                                $pass .= $l_alpha[mt_rand(0, (count($l_alpha) - 1))];
                        }else{
                                $pass .= $numeric[mt_rand(0, (count($numeric) - 1))];
                        }
                }
		return $pass;
	}

}
?>
