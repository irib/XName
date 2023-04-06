<?
/*
	This file is part of XName.org project
	See	http://www.xname.org/ for details
	
	License: GPLv2
	See LICENSE file, or http://www.gnu.org/copyleft/gpl.html
	
	Author(s): Yann Hirou <hirou@xname.org>

*/

	// Class Secondary
	// All functions for secondary manipulation

/**
 * All functions for secondary manipulation
 *
 *@access public
 */	
class Secondary extends Zone {

	var $masters;
	var $xfer;
	var $serial;
	var $creation;
	var $user;
	
	// Instanciation
	/**
	 * Class constructor - initialize all secondary data from DB
	 *
	 *@access public
	 *@param object Db $db DB
	 *@param string $zonename name of zone
	 *@param string $zonetype type of zone (necessary secondary...)
	 *@param object User $user Current user
	 *@param object Config $config Config object
	 */
	 
	Function Secondary($db,$zonename,$zonetype,$user,$config){
		$this->Zone($db,$zonename,$zonetype,$config);
		
		$query = "SELECT masters,xfer,serial
		FROM dns_confsecondary WHERE zoneid='" . $this->zoneid . "'";
		$res = $this->db->query($query);
		$line = $this->db->fetch_row($res);

		if($this->db->error()){
			$this->error="Trouble with DB";
			return 0;
		}

		if(!isset($line[0])){
			$this->creation = 1;
		}else{
			$this->creation = 0;
		}
		$this->masters = $line[0];
		$this->xfer = $line[1];
		$this->serial = $line[2];
		$this->user = $user;

	}


//	Function printModifyForm()
	/**
	 * Print HTML form pre-filled with current data
	 *
	 *@access public
	 *@return string HTML form pre-filled
	 */
	Function printModifyForm(){
		$this->error="";
		$result = "";

		$result .= "<strong>Be sure that the nameserver is 
		authoritative for your zone</strong><br>\n";
		
		$result .= '
		<form method="POST">
			<input type="hidden" name="modified" value="1">
			<input type="hidden" name="idsession" value="' . $this->user->idsession .
			'">
			<input type="hidden" name="zonename" value="' . 
			$this->zonename . '">
			<input type="hidden" name="zonetype" value="' . 
			$this->zonetype . '">
			<table border="0">
			<tr><td align="right" class="boxheader">
			<div align="right">zone : </div></td><td class="boxheader">' . $this->zonename . '
			</td></tr>
			<tr><td align="right">
			primary name server IP(s) (use \';\' as separator)  : </td>
			<td><input type="text" name="primary"
			value="' . $this->masters . '">
			</td></tr>

			<tr><td align="right">
			allow transfers from :</td><td>
			<input type="radio" name="xfer" value="all" ';
			$notothers = 0;
			$xferip="";
			if($this->xfer == 'any'){
				$result .= 'checked';
				$notothers = 1;
			}
			$result .= '>All
			<input type="radio" name="xfer" value="master" ';
			if($this->xfer == $this->masters){
				$result .= 'checked';
				$notothers = 1;
			}
			$result .= '>Master only
			</td></tr>
			<tr><td align="right">
			&nbsp;</td><td><input type="radio" name="xfer" value="others" ';
			if($notothers == 0){
				$result .= 'checked';
				if(strpos('.' . $this->xfer,$this->masters) == 1){
					$xferip = substr($this->xfer,strlen($this->masters)+1);
				}else{
					$xferip = $this->xfer;
				}
			}
			$result .= '>Master and (IP separated by \';\'): 
			<input type="text" name="xferip" value="' . $xferip . '">
			</td></tr>

			
			<tr><td colspan="2" align="center"><input type="submit"
			value="Modify"></td></tr>
			</table>
		</form>
		';		

		return $result;
	}

//	Function PrintModified($params)
	/**
	 * Take params send by "printmodifyform", do integrity 
	 * checks,checkDig & call $this->updateDb
	 *
	 *@access public
	 *@param array $params array of params: primary, xfer & xferIP
	 *@return string HTML output
	 */
	Function PrintModified($params){
		list($primary,$xfer,$xferip)=$params;
		$content = "";
		
		if(!notnull($primary)){
			$error = 1;
			$content .= '<font color="red">Error: you must provide a primary DNS
			server</font><br />';
		}
		
		// if primary modified ==> try to dig
		if($primary != $this->masters){
			// check primary integrity 
			if(!checkPrimary($primary)){
				$error = 1;
				$content .= '<font color=red>Error: your primary Name server should be an IP address<br>
				If you want to use 2 primary NS, separe IP addresses with
				\';\'</font><br />';
			}else{
				// remove last ';' if present
				if(substr($primary, -1) == ';'){
					$primary = substr($primary, 0, -1);
				}
				// split $primary into IPs
				$server = split(';',$primary);
				reset($server);
				while($ipserver = array_pop($server)){
					$dig = checkDig($ipserver,$this->zonename);
					if($dig != 'NOERROR'){
						switch($dig){
						case "NOERROR":
							$msg = "no error";
							break;
						case "FORMERR":
							$msg = "format error";
							break;
						case "SERVFAIL":
							$msg = "server failed";
							break;
						case "NXDOMAIN":
							$msg = "no such domain name";
							break;
						case "NOTIMP":
							$msg = "not implemented";
							break;
						case "REFUSED":
							$msg = "refused";
							break;
						case "YXDOMAIN":
							$msg = "domain name exists";
							break;
						case "YXRRSET":
							$msg = "rrset exists";
							break;
						case "NXRRSET":
							$msg = "rrset doesn't exist";
							break;
						case "NOTAUTH":
							$msg = "not authoritative";
							break;
						case "NOTZONE":
							$msg = "Not in zone";
							break;
						case "BADSIG":
							$msg = "bad signature";
							break;
						case "BADKEY":
							$msg = "bad key";
							break;
						case "BADTIME":
							$msg = "bad time";
							break;
						}
						
						$content .= '<font color="red">Warning: trying to dig from ' 
						. $ipserver . ' returned status "' . $dig . '"';
						if(notnull($msg)){
							$content .= ' (' . $msg . ')';
						}
						$content .= '.</font> This is a non-blocking warning, it will
						not prevent your zone to be registered - but correct
						this error or ' . $this->config->sitename . ' will not be able to serve your zone
						correctly.<br />';
					}
				}
			}
		}
		
		
		// check xferip
		if(notnull($xferip)){
			if(!checkPrimary($xferip)){
				$error = 1;
				$content .= '<font color="red">Error: invalid (list of) IP address(es) of servers allowed to
				do transfers. If you want to add several IPs, separe them with
				\';\'.</font><br />';
			}
			$xfer='others';
		}else{
			if($xfer=='others'){
				$xfer='master';
			}
		}

		
		switch($xfer){
			case "all":
				$xferip = 'any';
				break;
			case "master":
				$xferip=$primary;
				break;
			case "others":
				// remove last ';' if present
				if(substr($xferip, -1) == ';'){
					$xferip = substr($xferip, 0, -1);
				}
				
				// suppress duplicate entry of $primary if already in $xferip
				$xferarray = split(';',$xferip);
				$xferiparray=array();
				reset($xferarray);
				while($xferitem=array_pop($xferarray)){
					if($xferitem != $primary){
						array_push($xferiparray,$xferitem);
					}
				}
				$xferip = implode(";",$xferiparray);
				$xferip=$primary . ';' . $xferip;
				break;
			default:
				$xferip="any";
			}
		
		
		if(!$error){
			// updatedb
			if(!$this->updateDb($primary,$xferip)){		
				$content .= $this->error;
			}else{
				// instert un dns_modified to be generated & reloaded
				$query = "select count(*) from dns_modified where zoneid='" .
				$this->zoneid  . "'";
				$res = $this->db->query($query);
				$line = $this->db->fetch_row($res);
				if($line[0] == 0){
					$query = "INSERT INTO dns_modified (zoneid) values ('" . $this->zoneid . "')";
					$res = $this->db->query($query);
				}
					if($this->db->error()){
						$result .= '<p><font color="red">Error: Trouble with DB</font>
						Your zone will not be available at next reload. Please come back 
						later to modify it again.</p>';
					}else{
	
					$content .= '
					<p />
					<div class=boxheader>Zone successfully modified on
					' . $this->config->sitename . '.</div>
					<p />
				
					Be sure to :<p />
					- add following line to your zone file (with trailing dots) :<p />
					<pre>
' . $this->zonename . '.	IN	NS	' . $this->config->nsname . '.
				</pre>
				<p />
				
				- modify your DNS configuration file as follow, to allow
				transfer to ' . $this->config->nsname . ' (if you run your primary name server
				with bind):
				<p />
				<pre>
// 
// modify this sample configuration to fit your
// needs
//
zone "' . $this->zonename . '" {
	type master;
	file "' . $this->zonename . '";
	allow-transfer {
		' . $this->config->nsaddress . ';
	};
};
</pre>
					<p />
					- Delegate zone ' . $this->zonename . ' to your primary 
					name server and ' . $this->config->nsname . '.
				
					<p />
				
					As configuration is reloaded on our name server once per hour,
					this new configuration will be active within one hour. 
					You should receive an email informing you about this reload.
					<p />

					';
				}
			}
		}else{
			// $error 
			// nothing has been modified, go back and solve troubles
			
			// or print form again
		
		}
		return $content;

	}



//	Function updateDb($primary,$xferip)
	/**
	 * Update DB with new secondary parameters
	 *
	 *@access public
	 *@param string $primary IP(s) of primary name server(s)
	 *@param string $xferip IP(s) allowed to do zone transfers
	 *@return int 1 if success, 0 if error
	 */
	Function updateDb($primary,$xferip){

		// 27/03/02 not possible to change email address in this script

		// dns_confsecondary		
		if($this->creation==0){
			$query = "UPDATE dns_confsecondary SET masters='" . 
			$primary . "', xfer='" . $xferip . "' WHERE zoneid='" . 
			$this->zoneid . "'";
		}else{
			$query = "INSERT INTO dns_confsecondary (zoneid,masters,xfer)
			VALUES('" . $this->zoneid . "','" . $primary . "','" . $xferip . "')";
		}
		$res = $this->db->query($query);
		if($this->db->error()){
			$this->error="Trouble with DB";
			return 0;
		}
		
		return 1;
	}

}
?>
