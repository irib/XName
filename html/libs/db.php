<?

/*
	This file is part of XName.org project
	See	http://www.xname.org/ for details
	
	License: GPLv2
	See LICENSE file, or http://www.gnu.org/copyleft/gpl.html
	
	Author(s): Yann Hirou <hirou@xname.org>

*/



/*
 *	Generic interface for database access
 * 	some code taken from daCode project http://www.dacode.org,
 *	originally from Fabien Seisen <seisen@linuxfr.org>
 */

// uncomment this if not in the php.ini file
//  dl('mysql.so');

/**
 * Generic interface for DB access. Currently supports mysql only
 *
 *@access public
 */
class Db {
  var $dbh, $sh;
  var $result;
  var $config;
  
  /**
   * Class constructor. Connects to DB 
   *
   *@access public
   *@param object Config $config Config object
   *@return object DB database object
   */
  Function Db($config) {
    if($config->dbpersistent){
      $this->dbh = $this->pconnect($config->dbhost . ":" . $config->dbport, $config->dbuser, $config->dbpass, $config->dbname);
    }else{
      $this->dbh = $this->connect($config->dbhost, $config->dbuser, $config->dbpass, $config->dbname);
    }
  	$this->config = $config;
    return $this->dbh;
  }
  
  /**
   * Do simple connect
   *
   *@access private
   *@param string $host hostname or IP of DB host
   *@param string $user username for db access
   *@param string $pass password for db access
   *@param string $db database name
   *@return object Db database handler
   */
  Function connect($host, $user, $pass, $db){
    $this->sh = mysql_connect($host, $user, $pass);
    $res = mysql_select_db($db, $this->sh);
    return $res;
  }

  /**
   * Do permanent connect
   *
   *@access private
   *@param string $host hostname or IP of DB host
   *@param string $user username for db access
   *@param string $pass password for db access
   *@param string $db database name
   *@return object Db database handler
   */
  Function pconnect($host, $user, $pass, $db){
    $this->sh = mysql_pconnect($host, $user, $pass);
    $res = mysql_select_db($db, $this->sh);
    return $res;
  }
  
  /**
   * Pass query to DB
   *
   *@access public
   *@param string $string QUERY 
   *@return object query handler
   */
  Function query($string){
    $this->result = mysql_query($string, $this->sh);
    return $this->result;
  }
  
  /**
   * Fetch next row from query handler
   *
   *@access public
   *@param object Query $res query handler
   *@return array next result row
   */
  Function fetch_row($res){
    if($res){
      return  mysql_fetch_row($this->result);
    }else{
      return 0;
    }
  }	

  /**
   * Returns number of affected rows by given query handler
   *
   *@access public
   *@param object Query $res query handler
   *@return int number of affected rows
   */
  Function affected_rows($res){
  	if($res){
		return mysql_affected_rows($res);
	}else{
		return 0;
	}
  }
  
  /**
   * Free current db handlers - query & results
   *
   *@access public
   *@return int 0
   */
  Function free(){
//	   return mysql_free_result($this->result);
	return 0;
  }


  /**
   * Check if an error occured, & take action
   *
   *@access public
   *@return int 1 if error, 0 else
   */
  Function error(){
    if(mysql_errno()){
      mailer($this->config->emailfrom,$this->config->emailto,'XName: Error
	  MYSQL','',mysql_errno() . ": " . mysql_error() . "\n");
	  return 1;
    }else{
		return 0;
	}
  }
  
}


?>
