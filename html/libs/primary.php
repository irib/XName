<?

/*
	This file is part of XName.org project
	See	http://www.xname.org/ for details
	
	License: GPLv2
	See LICENSE file, or http://www.gnu.org/copyleft/gpl.html
	
	Author(s): Yann Hirou <hirou@xname.org>

*/

// Class Primary
// 	All functions for primary manipulations
/**
 * Class for all functions for primary manipulation
 *
 *@access public
 */
class Primary extends Zone {
	var $creation;
	var $serial;
	var $refresh;
	var $retry;
	var $expiry;
	var $minimum;
	var $defaultttl;
	var $xfer;
	var $user;

	var $mx;
	var $mxttl;
	var $ns;
	var $nsttl;
	var $a;
	var $attl;
	var $aip;
	var $cname;
	var $cnamettl;
	var $dname;
	var $dnamettl;
	var $a6;
	var $a6ttl;
	var $aaaa;
	var $aaaattl;
	var $subns;
	var $subnsttl;
	var $subnsa;

	
	// instanciation
	/**
	 * Class constructor & data retrieval (use of Retrieve[Multi]Record)
	 *
	 *@access public
	 *@param string $zonename zone name
	 *@param string $zonetype zone type (must be 'M'aster)
	 *@param string $user class member user for current user
	 */
	Function Primary($zonename,$zonetype,$user){
		global $db;
		$this->Zone($zonename,$zonetype);

		// fill in vars
		$query = "SELECT serial, refresh, retry, expiry, minimum, defaultttl, xfer
		FROM dns_confprimary WHERE zoneid='" . $this->zoneid . "'";
		$res = $db->query($query);
		$line = $db->fetch_row($res);
		if($db->error()){
			$this->error="Trouble with DB";
			return 0;
		}
		if(!isset($line[1])){
			$this->creation = 1;
		}else{
			$this->creation = 0;
		}

		// set default SOA values
		$this->serial = $line[0];
		if($line[1]){
			$this->refresh = $line[1];
		}else{
			$this->refresh = 10800;
		}
		if($line[2]){
			$this->retry = $line[2];
		}else{
			$this->retry = 3600; 
		}
		if($line[3]){
			$this->expiry = $line[3];
		}else{
			$this->expiry = 604800;
		}
		if($line[4]){
			$this->minimum = $line[4];
		}else{
			$this->minimum = 10800;
		}
		if($line[5]){
			$this->defaultttl = $line[5];
		}else{
			$this->defaultttl = 86400;
		}
		
		$this->xfer = $line[6];
		$this->user=$user;
		
		// initialize arrays
		$this->ns = array();
		$this->nsttl = array();
		$this->mx = array();
		$this->mxttl = array();
		$this->dname = array();
		$this->dnamettl = array();
		$this->a = array();
		$this->attl = array();
		$this->aip = array();
		$this->cname = array();
		$this->cnamettl = array();
		$this->a6 = array();
		$this->a6ttl = array();
		$this->aaaa = array();
		$this->aaaattl = array();
		$this->subns = array();
		$this->subnsttl = array();
		$this->subnsa = array();
		
		// fill in with records
		$this->RetrieveRecords('NS',$this->ns,$this->nsttl);
		$this->RetrieveRecords('MX',$this->mx,$this->mxttl);
		$this->RetrieveRecords('DNAME',$this->dname,$this->dnamettl);
		$this->RetrieveMultiRecords('A',$this->a,$this->aip,$this->attl);
		$this->RetrieveRecords('CNAME',$this->cname,$this->cnamettl);
		$this->RetrieveRecords('A6',$this->a6,$this->a6ttl);
		$this->RetrieveRecords('AAAA',$this->aaaa,$this->aaaattl);
		$this->RetrieveMultiRecords('SUBNS',$this->subns,$this->subnsa,$this->subnsttl);
	}
	


// *******************************************************
	
	//	Function printModifyForm($advanced)
	/**
	 * returns a pre-filled form to modify primary records
	 *
	 *@access public
	 *@param int $advanced 0 or 1 if advanced interface needed or not
	 *@return string HTML pre-filled form
	 */
	Function printModifyForm($advanced){
		global $config;
		$this->error="";
		$result = '';
		
			$deletecount = 0;
			// TODO use zoneid instead of zonename & zonetype
			$result .= '<form method="POST">
			<input type="hidden" name="idsession"
			 value="' . $this->user->idsession . '">
			 <input type="hidden" name="zonename"
			 value="' . $this->zonename . '">
			 <input type="hidden" name="zonetype"
			 value="' . $this->zonetype . '">
			 
			<input type="hidden" name="modified" value="1">
			';
			// if advanced, say it to modified - in case
			// of temporary use of advanced interface, not in
			// user prefs.
			if($advanced){ 
				$result .= '<input type="hidden" name="advanced" value="1">
				';
			}
			
			
			if($advanced){
				// print global params ($TTL)
				$result .= '
				<div class="boxheader">Global params</div>
				<table border="0" width="100%">
				<tr><td colspan="2">The time to live is primarily used by
				resolver when they cache RRs. The TTL describes how long a RR
				can be cached before it should be discarded</td></tr>
				<tr><td align="right">Default TTL</td><td><input type="text"
				name="defaultttl" value="' . $this->defaultttl . '"></td></tr>
				</table>
				<p />
				';
				// print SOA params
				$result .= '
				<div class="boxheader">SOA Parameters</div>
				<table border="0" width="100%">
				<tr><td colspan="2">The refresh interval tells the slave how
				often to check that its data are up to date.
				<tr><td align="right">Refresh period</td><td><input type="text"
				name="soarefresh" value="' .
				$this->refresh . '"></td></tr>
				<tr><td colspan="2">If the slave fails to reach the master name
				server after the refresh period, then it starts trying to
				connect every "retry" seconds.</td></tr>
				<tr><td align="right">Retry interval</td><td><input type="text"
				name="soaretry" value="' .
				$this->retry . '"></td></tr>
				<tr><td colspan="2">If the slave fails to  contact the master
				server for "expire" seconds, the slave expires its data.
				Expiration time should always be much larger than the retry and
				refresh intervals.
				<tr><td align="right">Expire time</td><td><input type="text"
				name="soaexpire" value="' .
				$this->expiry . '"></td></tr>
				<tr><td colspan="2">Negative caching TTL controls how long other
				servers will cache no-such-domain (NXDOMAIN) responses from this
				server. The maximum time for negative caching is 3hours (10800s).</td></tr>
				<tr><td align="right">negative caching TTL</td><td><input type="text"
				name="soaminimum" value="' .
				$this->minimum . '"></td></tr>
				
				
				</table>
				<p />';
			}
			
			$result .= '
			<div class="boxheader">Name Server (NS) records</div>
			<table border="0">
				<tr><td colspan="3">NS records are names (and not IP addresses). You have to use the full
				qualified name of the computer, with the trailing dot at the end (ex:
				' . $config->nsname . '.).</td></tr>
				';
			
			$xnamepresent = 0;
			$keys = array_keys($this->ns);
			while($key = array_shift($keys)){
				$result .= '<tr>
				<td align="right">NS</td><td>' . $key . '</td>
				';
				if($advanced){
					$result .= '<td align="right">TTL: </td>
					<td>' . $this->nsttl[$key] . '</td>';
				}
				$result .= '<td>';
					if(strcmp($key, $config->nsname . '.')){
						$deletecount++;
						$result .= '<input type="radio" name="delete' .
						 $deletecount .
					'" value="ns(' . $key . ')">Delete';
					}else{
						$xnamepresent=1;
					}
					$result .= "</td></tr>\n";
			}
			if(!$xnamepresent){
				$result .= '
				<tr>
				<td align="right">Mandatory NS:</td><td><input type="hidden"
				name="ns1" value="' . $config->nsname . '.">' . $config->nsname . '.</td>
				';
				if($advanced){
					$result .= '<td align="right">TTL: </td>
					<td><input type="text" name="nsttl1" size="6" value="default"></td>';
				}
				$result .= '<td></td></tr>
				';
			}
			$result .= '
				<tr>
				<td align="right">New NS (1): </td><td><input type="text"
				name="ns2"></td>';
			if($advanced){
				$result .= '<td align="right">TTL: </td>
				<td><input type="text" name="nsttl2" size="6" value="default"></td>
				';
			}
			$result .= '<td></td></tr>
				<tr>
				<td align="right">New NS (2): </td><td><input type="text"
				 name="ns3"></td>';
			if($advanced){
				$result .= '<td align="right">TTL: </td>
				<td><input type="text" name="nsttl3" size="6" value="default"></td>
				';
			}
			$result .= '<td></td></tr>
			</table>

			<p>
			<div class="boxheader">Mail Exchanger (MX) records</div>
			<table border="0">
				<tr><td colspan="4">MX records are names (and not IP addresses). 
				You have to use the full
				qualified name of the computer, with the trailing dot at the end
				 (ex:
				mail.' . $config->domainname . '.). You also have to specify a preference number. 
				If you have many MX, the
				default one will be the one with the lower preference number.
				</td></tr>
			';

			$counter=0;
			$keys = array_keys($this->mx);
			while($key = array_shift($keys)){			
				$deletecount++;
				$result .= '<tr><td align="right">MX: </td>
						<td>Pref: ' . $this->mx[$key] . '</td>
						<td>' . $key . '</td>';
				if($advanced){
					$result .= '<td align="right">TTL: </td>
					<td>' . $this->mxttl[$key] . '</td>
					';
				}
				$result .= '
						<td><input type="radio" name="delete' . $deletecount .
						'" value="mx(' . $key . ')">Delete</td></tr>
				';
				$counter++;
			}	
			
			$result .= '
				<tr><td align="right">New MX (1): </td>
						<td>Pref: <input type="text" size="3" maxlength="3"
						 name="pref1"></td>
						<td><input type="text" name="mx1"></td>';
			if($advanced){
				$result .= '<td align="right">TTL: </td>
				<td><input type="text" name="mxttl1" size="6" value="default"></td>
				';
			}
			$result .= '<td></td></tr>
				<tr><td align=right>New MX (2): </td>
						<td>Pref: <input type="text" size="3" maxlength="3"
						 name="pref2"></td>
						<td><input type="text" name="mx2"></td>';
			if($advanced){
				$result .= '<td align="right">TTL: </td>
				<td><input type="text" name="mxttl2" size="6" value="default"></td>
				';
			}
			$result .= '<td></td></tr>
			</table>

			<p>
			<div class="boxheader">Address (A) records</div>
			<table border="0">
				<tr><td colspan="4">A records are association of a name and an IP address. The name
				is unqualified, i.e. what you want to have before ' . $this->zonename . ', 
				like www for www.' . $config->domainname . ' except for the name of the domain itself
				which is qualified, i.e. ' . $config->domainname . '.<br />
				If you want to add an A record for the zone itself, use fully qualified zone name 
				(with a trailing dot) as name.<br />
				If you want to do Round Robin on A records, enter different records with same name 
				but different IPs.
				</td></tr>
			';

			$counter=0;
			while($this->a[$counter]){
				$deletecount++;
				// if advanced, print TTL fields
				$result .= '<tr><td align="right">A: </td>
						<td>Name:  ' . $this->a[$counter] . '</td>
						<td> IP: ' . $this->aip[$counter] . '</td>';
				if($advanced){
					$result .= '<td align="right">TTL: </td>
					<td>' . $this->attl[$counter] . '</td>
					';
				}
				$result .= '
						<td><input type="radio" name="delete' . $deletecount .
						'" value="a(' . $this->a[$counter] . '/' .
						$this->aip[$counter] . ')">Delete</td></tr>
				';
				$counter ++;
			}	

			$counter=0;
			$keys = array_keys($this->a);
			while($key = array_shift($keys)){
				$deletecount++;
				$counter++;
			}	
			
			$result .= '
				<tr><td align="right">New A (1): </td>
						<td>Name <input type="text" name="aname1"></td>
						<td>IP <input type="text" name="a1"></td>';
			if($advanced){
				$result .= '<td align="right">TTL: </td>
				<td><input type="text" name="attl1" size="6" value="default"></td>
				';
			}
			$result .= '<td></td></tr>
				<tr><td align="right">New A (2): </td>
						<td>Name <input type="text" name="aname2"></td>
						<td>IP <input type="text" name="a2"></td>';
			if($advanced){
				$result .= '<td align="right">TTL: </td>
				<td><input type="text" name="attl2" size="6" value="default"></td>
				';
			}
			$result .= '<td></td></tr>

			</table>

			<p>
			<div class="boxheader">Canonical Name (CNAME) records</div>
			<table border="0">
				<tr><td colspan="3">CNAME records are alias name definitions.<br>
				For example, if there is already an A record pointing on IP 10.1.1.1,
				any new record pointing to 10.1.1.1 should be a CNAME record pointing to the 
				name used in the A record.<br>
				It is usefull if you want tou have many names for the same IP address.
				</td></tr>
							';

			$counter=0;
			$keys = array_keys($this->cname);
			while($key = array_shift($keys)){
				$deletecount++;
				$result .= '<tr><td align="right">CNAME: </td>
						<td>Alias: ' . $key . '</td>
						<td> Name: ' . $this->cname[$key] . '</td>';
				if($advanced){
					$result .= '<td align="right">TTL: </td>
					<td>' . $this->cnamettl[$key] . '</td>
					';
				}
				$result .= '
						<td><input type="radio" name="delete' . $deletecount . 
						'" value="cname(' . $key . ')">Delete</td></tr>
				';
			}	
			
			$result .= '
				<tr><td align="right">New CNAME (1): </td><td>Alias <input
				 type="text" size="10" name="cname1"></td>
						<td>Name (A record) <input type="text" name="cnamea1">
						</td>';
			if($advanced){
				$result .= '<td align="right">TTL: </td>
				<td><input type="text" name="cnamettl1" size="6" value="default"></td>
				';
			}
			$result .= '<td></td></tr>
				<tr><td align="right">New CNAME (2): </td><td>Alias 
				<input type="text" size="10" name="cname2"></td>
						<td>Name (A record) <input type="text" name="cnamea2">
						</td>';
			if($advanced){
				$result .= '<td align="right">TTL: </td>
				<td><input type="text" name="cnamettl2" size="6" value="default"></td>
				';
			}
			$result .= '<td></td></tr>
			</table>




			<p>
			<div class="boxheader">Sub Zones</div>
			<table border="0">
				<tr><td colspan="3">
				You can define sub-zones, delegated to any name server. Be sure that
				these zones are defined on the nameserver you choose.
				<br />
				For example, if you want to have a new zone myzone.mydomain.com, you can
				create a new zone on ' . $config->sitename . ' "myzone.mydomain.org", and configure it as
				you wish.<br />
				Your new zone name is <b>necessary</b> under ' . $this->zonename . '., it 
				can not have a dot \'.\' in it. <br />
				Nameservers must be fully qualified name, ending with a dot \'.\'
				</td></tr>
				';

			$counter=0;
			while($this->subns[$counter]){
				$deletecount++;
				$result .= '<tr><td align="right">zone: </td>
						<td>Zone: ' . $this->subns[$counter] . '</td>
						<td> NS: ' . $this->subnsa[$counter] . '</td>
						';
				if($advanced){
					$result .= '<td align="right">TTL: </td>
					<td>' . $this->subnsttl[$counter] . '</td>
					';
				}
				$result .= '<td><input type="radio" name="delete' . $deletecount . 
						'" value="subns(' . $this->subns[$counter] . '/' . 
						$this->subnsa[$counter] . ')">Delete</td></tr>
				';
				$counter ++;
			}	
			
			$result .= '
				<tr><td align="right">New sub zone (1): </td><td>name <input
				 type="text" size="10" name="subns1"></td>
						<td>NS <input type="text" name="subnsa1">
						</td>';
			if($advanced){
				$result .= '<td align="right">TTL: </td>
				<td><input type="text" name="subnsttl1" size="6" value="default"></td>
				';
			}
			$result .= '<td></td></tr>
				<tr><td align="right">New sub zone (2): </td><td>name 
				<input type="text" size="10" name="subns2"></td>
						<td>NS <input type="text" name="subnsa2">
						</td>';
			if($advanced){
				$result .= '<td align="right">TTL: </td>
				<td><input type="text" name="subnsttl2" size="6" value="default"></td>
				';
			}
			$result .= '<td></td></tr>
			</table>


			<p>
			<div class="boxheader">Computers allowed to do zone transfers</div>
			<table border="0">
				<tr><td width="20">&nbsp;</td><td colspan="2">To protect your zone, you can specify which computers are
				allowed to request a zone transfer, giving access to all zone content. <br>
				Usually allowed computers are secondary name servers, 
				and sometimes administrator\'s
				computer.<br>
				You can specify multiple IP addresses, separated by semicolons
				(\';\').
				</td></tr>
				<tr><td width="20">&nbsp;</td><td align="left" colspan="2">IP addresses allowed to transfer zones (leave empty for everyone)
				<input type="text" name="xferip" value="';
				if($this->xfer=="any"){
					$result .= '';
				}else{
					$result .= $this->xfer;
				}
				$result .= '"></td></tr>
			</table>



';

$result .= '
			<p>
			<input type="hidden" name="valid" value="1">
			<div align="center">
			<input type="submit" value="Generate zone configuration">
			<input type="reset">
			</form>
		';
		
		return $result;
	}


// *******************************************************	
//	Function PrintModified($params)
	/**
	 * Process params from primarymodifyform() form:
	 * for each record type execute addTYPERecord, execute updateSOA 
	 * and outputs result & config file
	 *
	 *@access public
	 *@param array $params contains $VARS ($HTTP_GET_VARS or POST), $xferip and SOA params
	 *@return string HTML result
	 */
	Function PrintModified($params){
		global $db;
		global $config;
		list($VARS,$xferip,$defaultttl,$soarefresh,$soaretry,$soaexpire,$soaminimum)=$params;

		$this->error="";
		$result = '';

		$delete = retrieveArgs("delete", $VARS);
		$ns = retrieveArgs("ns", $VARS);
		$nsttl = retrieveArgs("nsttl",$VARS);
		$mx = retrieveArgs("mx", $VARS);
		$mxttl = retrieveArgs("mxttl",$VARS);
		$pref = retrieveArgs("pref", $VARS);
		$aname = retrieveArgs("aname", $VARS);
		$a = retrieveArgs("a", $VARS);
		$attl = retrieveArgs("attl",$VARS);
		$cname = retrieveArgs("cname", $VARS);
		$cnamea = retrieveArgs("cnamea", $VARS);
		$cnamettl = retrieveArgs("cnamettl",$VARS);
		$subns = retrieveArgs("subns", $VARS);
		$subnsa = retrieveArgs("subnsa", $VARS);
		$subnsttl = retrieveArgs("subnsttl",$VARS);
		
		$result .= $this->Delete($delete);
		$result .= $this->AddNSRecord($ns,$nsttl);
		$result .= $this->AddMXRecord($mx,$pref,$mxttl);
		$result .= $this->AddARecord($a,$aname,$attl);
		$result .= $this->AddCNAMERecord($cname,$cnamea,$cnamettl);
		$result .= $this->AddSUBNSRecord($subns,$subnsa,$subnsttl);
		
		if($this->UpdateSOA($xferip,$defaultttl,$soarefresh,$soaretry,$soaexpire,$soaminimum) == 0){
			$result .= '<font color="red">Error: ' . $this->error . '</font><br />';		
		}else{
			$result .= '
			New serial: 
			' . $this->serial . "<p />";
		
			// check for errors
			// - generate zone file in /tmp/zonename
			$this->generateConfigFile();
			// - do named-checkzone $zonename /tmp/zonename and return result
			$checker = "$config->binnamedcheckzone " . $this->zonename . " /tmp/" . $this->zonename.".".
			$this->zonetype;
			$check = `$checker`;
			// if ok
			 if(preg_match("/OK/", $check)){
			// if($check == "OK\n"){
				$result .= 'Your zone successfully passed our internal configuration tests.
				It should be active within one hour. You will receive an email informing you
				about its activation.<p />
				For your information,
				here is the generated configuration: 
				<p align="center"><table border="0" bgcolor="#ffffff"><tr><td> 
				<pre>
				';
				// Print /tmp/zonename
				$fd = fopen("/tmp/" . $this->zonename.".".$this->zonetype,"r");
				$result .= fread($fd, filesize("/tmp/" . $this->zonename.".".$this->zonetype));
				fclose($fd);
				$result .= "</pre>
				</td></tr></table>
				</p>&nbsp;<p />";
				unlink("/tmp/" . $this->zonename.".".$this->zonetype);
				// flag as 'M'odified to be generated & reloaded
				$query = "UPDATE dns_zone SET 
					status='M' WHERE id='" . $this->zoneid . "'";
				$res = $db->query($query);
				if($db->error()){
					$result .= '<p><font color="red">Error: Trouble with DB</font>
					Your zone will not be available at next reload. Please come back 
					later to modify it again.</p>';
				}
				
			}else{
				$result .= 'Error occured when checking your configuration.<br />
				This zone will <b>not</b> be loaded until errors are corrected.<br />
				For your information, here is check result: 
				<p />
				<pre>' . $check . '</pre>
				If you think it is an engine error, please <a
				href="mailto:' . $config->contactemail . '"
				>contact administrator</a>.
				<p />
				For your information, trouble occured when checking following file:
				<p align="center"><table border="0" bgcolor="#ffffff"><tr><td> 
				<pre>
				';
				// Print /tmp/zonename
				$fd = fopen("/tmp/" . $this->zonename.".".$this->zonetype,"r");
				$result .= fread($fd, filesize("/tmp/" . $this->zonename.".".$this->zonetype));
				fclose($fd);
				$result .= "</pre>
				</td></tr></table>
				</p>&nbsp;<p />";
			}
		}	
		return $result;
	}
	

// *******************************************************	

//	Function Delete($delete)
	/**
	 * Takes list of items to be deleted, and process them
	 *
	 *@access public
	 *@param array $delete list of items cname(alias), a(name), ns(name), etc..
	 *@return string text of result (Deleting XXX record... Ok<br />)
	 */
	Function Delete($delete){
		global $db;
		$result = '';
		
		// for each delete entry, delete item cname(alias), a(name), ns(name),
		// mx(name)


		while(list($key,$value) = each($delete)){
			if($value != ""){
				$newvalue = preg_replace("/^.*\(([^\)]+)\)/","\\1", $value);
				$newvalue = preg_replace("/\./", "\.", $newvalue);
				
				// name of item to be deleted: 
				preg_match("/^(.*)\(/",$value,$item);
				$item = $item[1];
				
				switch($item){
					case "cname":
						// www		IN		CNAME		toto.
						$query = "DELETE FROM dns_record 
								WHERE zoneid='" . $this->zoneid . "'
								AND type='CNAME' AND val1='" . $newvalue . "'";
						$result .= "Deleting CNAME record " .
						stripslashes($newvalue) . "...";
						break;
					
					 
					case "a":
						// www		IN		A			IP
						preg_match("/^(.*)\/(.*)/",$newvalue,$item);
						$val1 = $item[1];
						$val2 = $item[2];
						$query = "DELETE FROM dns_record 
								WHERE zoneid='" . $this->zoneid . "'
								AND type='A' AND val1='" . $val1 . "' 
								AND val2='" . $val2 . "'";
						$result .= "Deleting A record " .
						stripslashes($newvalue) . "...";
						break;
					
					case "ns":
						// 		IN		NS		name
						$query = "DELETE FROM dns_record 
							WHERE zoneid='" . $this->zoneid . "'
							AND type='NS' AND val1='" . $newvalue . "'";
						$result .= "Deleting NS record " . 
						stripslashes($newvalue) . "...";
						break;

					case "mx":
						// * 		IN		MX		pref		name
						$query = "DELETE FROM dns_record 
						WHERE zoneid='" . $this->zoneid . "'
						AND type='MX' AND val1='" . $newvalue . "'";
						$result .= "Deleting MX record " .
						stripslashes($newvalue) . "...";
						break;
					case "subns":
						// newzone	IN		NS		ns.name
						preg_match("/^(.*)\/(.*)/",$newvalue,$item);
						$val1 = $item[1];
						$val2 = $item[2];
						
						$query = "DELETE FROM dns_record
						WHERE zoneid='" . $this->zoneid . "'
						AND type='SUBNS' AND val1='" . $val1 . "'
						AND val2='" . $val2 . "'";
						$result .= "Deleting sub-zone " .
						stripslashes($newvalue) . "...";
						break;
				}
			}
			$res = $db->query($query);
			if($db->error()){
				$this->error="Trouble with DB";
				$result .= ' <font color="red">Trouble with DB</font><br />';
			}else{
				$result .= " OK<br />\n";
			}
		}
		return $result;
	}


// *******************************************************

//	Function AddMXRecord($mx,$pref,$ttl)
	/**
	 * Add an MX record to the current zone
	 *
	 *@access private
	 *@param string $mx name of MX 
	 *@param int $pref preference value for this MX
	 *@param int $ttl ttl value for this record
	 *@return string text of result (Adding MX Record... Ok)
	 */
	Function AddMXRecord($mx,$pref,$ttl){
		global $db;
		$result = '';
		// for each MX, add MX entry
		$i = 0;
		while(list($key,$value) = each($mx)){
			// value = name
			if($value != ""){
				if(!(checkDomain($value) || checkName($value))){
					// check if matching A record exists ? NOT OUR JOB
					$result .= '<font color="red">Error: bad MX name ' . 
					stripslashes($value) . "</font><br />\n";
					$this->error = "Data error";
				}else{
					// if checkName, add zone.
					if(checkName($value)){
						$value .= "." . $this->zonename;
					}
					// if no trailing ".", add one. 
					if(strrpos($value, ".") != strlen($value) -1){
						$value .= ".";
					}
				
					// pref[$i] has to be an integer
					if(preg_match("/[^\d]/", $pref[$i])){
						$result .= '<font color="red">Error: preference for 
						MX ' . 	stripslashes($value) . ' has to be an
						integer</font><br />';
						$this->error = "Data error";
					}else{
						if($pref[$i] == ""){
							$pref[$i] = 0;
						}
	
						// Check if record already exists
						$query = "SELECT count(*) FROM dns_record WHERE 
						zoneid='" . $this->zoneid . "' AND type='MX' 
						AND val1='" . $value . "'";
						$res = $db->query($query);
						$line = $db->fetch_row($res);
						if($line[0] == 0){
							$result .= "Adding MX record " . 
							stripslashes($value) . "...";
							if(!notnull($ttl[$i])){
								$ttlval = "default";
							}else{
								$ttlval = $ttl[$i];
							}
							$query = "INSERT INTO dns_record (zoneid, type, val1, val2,ttl) 
								VALUES ('" . $this->zoneid . "', 'MX', '" 
								. $value . "', '" . $pref[$i] . "','" . $ttlval . "')";
							$db->query($query);
							if($db->error()){
								$result .= ' <font color="red">Trouble with
								DB</font><br />';
								$this->error = "Trouble with DB";
							}else{
								$result .= " OK<br />\n";
							}
						}else{ // record already exists
							$result .= "Warning: MX record " 
							. stripslashes($value) . " already
							exists - not overwritten<br />\n";
						}
					}
				}
			}
			$i++;
		}
		return $result;
	}



// *******************************************************

//	Function AddNSRecord($ns,$ttl)
	/**
	 * Add an NS record to the current zone
	 *
	 *@access private
	 *@param string $ns name of NS
	 *@param int $ttl ttl value for this record
	 *@return string text of result (Adding NS Record... Ok)
	 */
	Function AddNSRecord($ns,$ttl){
		global $db;
		$result = '';
		// for each NS, add NS entry
		while(list($key,$value) = each($ns)){
			// value = name
			if($value != ""){
				if(!checkDomain($value)){
					$result .= '<font color="red">Error: bad NS name ' . 
					stripslashes($value) . '</font><br />';
					$this->error = "Data error";
				}else{
					// if no trailing ".", add one
					if(strrpos($value, ".") != strlen($value) -1){
						$value .= ".";
					}
					
					// Check if record already exists
					$query = "SELECT count(*) FROM dns_record WHERE 
					zoneid='" . $this->zoneid . 
					"' AND type='NS' AND val1='" . $value . "'";
					$res = $db->query($query);
					$line = $db->fetch_row($res);
					if($line[0] == 0){
						$result .= "Adding NS record " .
						stripslashes($value) . "...";
						if(!notnull($ttl[$key])){
							$ttlval = "default";
						}else{
							$ttlval = $ttl[$key];
						}
						$query = "INSERT INTO dns_record (zoneid, type, val1,ttl) 
							VALUES ('" . $this->zoneid . "', 'NS', '" 
							. $value . "','" . $ttlval . "')";
						$db->query($query);
						if($db->error()){
							$result .= ' <font color="red">Trouble with
							 DB</font><br />';
							$this->error = "Trouble with DB";
						}else{
							$result .= " OK<br />\n";
						}
					}else{
						$result .= "Warning: NS record " . 
						stripslashes($value) . " already
						exists - not overwritten<br />\n";
					}
				}
			}
		}
		return $result;
	}


// *******************************************************

//	Function AddARecord($a,$aname,$ttl)
	/**
	 * Add an A record to the current zone
	 *
	 *@access private
	 *@param string $a ip of A record
	 *@param string $aname name of A record
	 *@param int $ttl ttl value for this record
	 *@return string text of result (Adding A Record... Ok)
	 */
	Function AddARecord($a,$aname,$ttl){
		global $db;
		$result = '';
		// for each A, add A entry
		$i = 0;
		while(list($key,$value) = each($aname)){
			if($value != ""){
				if(! (checkName($value) || ($value == $this->zonename.'.'))){
					$result .= '<font color="red">Error: bad A record ' . 
					stripslashes($value) . "</font><br />\n";
					$this->error = "Data error";
				}else{
					// a[$i] has to be an ip address
					if($a[$i] == ""){
						$result .= '<font color="red">Error: No IP address for ' .
						stripslashes($value) . "</font><br />\n";
						$this->error = "Data error";
					}else{
						if(!checkIP($a[$i])){
							print '<font color="red">Error: ' . 
							stripslashes($value) . " IP has to be an IP address
							</font><br />\n";
							$this->error = "Data error";
						}else{
							// Check if record already exists
							$query = "SELECT count(*) FROM dns_record WHERE 
							zoneid='" . $this->zoneid . "' AND type='A' 
							AND val1='" . $value . "'";
							$res = $db->query($query);
							$line = $db->fetch_row($res);
							if($line[0] == 0){
								// check if CNAME record not already exists
								$query = "SELECT count(*) FROM dns_record WHERE 
								zoneid='" . $this->zoneid . "' AND type='CNAME' 
								AND val1='" . $value . "'";
								$res = $db->query($query);
								$line = $db->fetch_row($res);
								if($line[0] == 0){
									$result .= "Adding A record " . 
									stripslashes($value) . "...";
									if(!notnull($ttl[$i])){
										$ttlval = "default";
									}else{
										$ttlval = $ttl[$i];
									}
									$query = "INSERT INTO dns_record (zoneid, type, val1, val2,ttl) 
									VALUES ('" . $this->zoneid . "', 
									'A', '" . $value . "', '" . $a[$i] . "','" . $ttlval . "')";
									$db->query($query);
									if($db->error()){
										$result .= " <font color=red>Trouble with
										DB</font><br />\n";
										$this->error = "Trouble with DB";
									}else{
										$result .= " OK<br />\n";
									}	
								}else{ // end check CNAME
									$result .= "Warning: CNAME record for " . 
									stripslashes($value) . "
									already exists - not overwritten<br />\n";
								}
							}else{ // end check A
								$result .= "Warning: A record for " . 
								stripslashes($value) . " already
								exists";
								
								// check if already same IP or not. If yes, do not
								// change anything 
								// if no, warn & assume it is round robin.
								$query .= " AND val2='" . $a[$i] . "'";
								$res = $db->query($query);
								$line = $db->fetch_row($res);
								if($line[0] == 0){
									$result .= ', but with a different value. Assuming you
									wish to have multiple records for the same target (Round
									Robin) ';
									$result .= "Adding A record " . 
									stripslashes($value) . "...";
									if(!notnull($ttl[$i])){
										$ttlval = "default";
									}else{
										$ttlval = $ttl[$i];
									}									
									$query = "INSERT INTO dns_record (zoneid, type, val1, val2,ttl) 
									VALUES ('" . $this->zoneid . "', 
									'A', '" . $value . "', '" . $a[$i] . "','" . $ttlval . "')
									";
									$db->query($query);
									if($db->error()){
										$result .= " <font color=red>Trouble with
										DB</font><br />\n";
										$this->error = "Trouble with DB";
									}else{
										$result .= " OK<br />\n";
									}	

								}else{
									$result .= ' with the same IP - not overwritten<br />';
								}
							}
						}
					}
				}
			}
			$i++;
		}
		return $result;
	}
	
	
	
		
// *******************************************************

//	Function AddCNAMERecord($cname,$cnamea,$ttl)
	/**
	 * Add an CNAME record to the current zone
	 *
	 *@access private
	 *@param string $cname name of CNAME record
	 *@param string $cnamea record pointed by this CNAME record
	 *@param int $ttl ttl value for this record
	 *@return string text of result (Adding CNAME Record... Ok)
	 */
	Function AddCNAMERecord($cname,$cnamea,$ttl){
		global $db;
		// for each CNAME, add CNAME entry
		$i = 0;
		while(list($key,$value) = each($cname)){
			if($value != ""){	
				if(!checkName($value) || checkIP($cnamea[$i])){
					$result .= '<font color="red">Error: Bad CNAME record ' .
					stripslashes($value) . "</font><br />\n";
					$this->error = "Data error";
				}else{
					if($cnamea[$i] ==""){
						$result .= '<font color="red">Error: No record for ' . 
						stripslashes($value) . "</font><br />\n";
						$this->error = 1;
					}else{
						// Check if record already exists
						$query = "SELECT count(*) FROM dns_record WHERE 
						zoneid='" . $this->zoneid . "' AND type='CNAME' 
						AND val1='" . $value . "'";
						$res = $db->query($query);
						$line = $db->fetch_row($res);
						if($line[0] == 0){
							// check if A record don't already exist
							$query = "SELECT count(*) FROM dns_record WHERE 
							zoneid='" . $this->zoneid . "' AND type='A' 
							AND val1='" . $value . "'";
							$res = $db->query($query);
							$line = $db->fetch_row($res);
							if($line[0] == 0){
								$result .= "Adding CNAME record " . 
								stripslashes($value) . "...";
								if(!notnull($ttl[$i])){
									$ttlval = "default";
								}else{
									$ttlval = $ttl[$i];
								}								
								$query = "INSERT INTO dns_record (zoneid, type, val1, val2,ttl) 
								VALUES ('" . $this->zoneid . "', 'CNAME', '"
								 . $value . "', '" . $cnamea[$i] . "','" . $ttlval . "')
								";
								$db->query($query);
								if($db->error()){
									$result .= ' <font color="red">Trouble with
									DB</font><br />';
									$this->error = "Trouble with DB";
								}else{
									$result .= " OK<br />\n";	
								}
							}else{ // A record present
								$result .= "Warning: A record " .
								stripslashes($value) . " already
								exists - not overwritten<br />\n";
							}							
						}else{
							$result .= "Warning: CNAME $value already
							exists - not overwritten<br />\n";
						}
					}
				}
			}
			$i++;
		}
		return $result;
	}

// *******************************************************

//	Function AddSUBNSRecord($subns,$subnsa,$ttl)
	/**
	 * Add a zone delegation to the current zone
	 *
	 *@access private
	 *@param string $subns name of subzone
	 *@param string $subnsa name of NS server
	 *@param int $ttl ttl value for this record
	 *@return string text of result (Adding zone NS Record... Ok)
	 */
	Function AddSUBNSRecord($subns,$subnsa,$ttl){
		global $db;
		// for each SUBNS, add NS entry
		$i = 0;
		while(list($key,$value) = each($subns)){
			if($value != ""){	
				if(!checkName($value)){
					$result .= '<font color="red">Error: Bad Zone name ' .
					stripslashes($value) . "</font><br />\n";
					$this->error = "Data error";
				}else{
					if($subnsa[$i] ==""){
						$result .= '<font color="red">Error: No NS record for ' . 
						stripslashes($value) . "</font><br />\n";
						$this->error = 1;
					}else{
						// Check if record already exists
						// if yes, no problem - multiple different NS possible
						$result .= "Adding zone NS record " . 
						stripslashes($value) . "...";
						$query = "SELECT count(*) FROM dns_record 
						WHERE zoneid='" . $this->zoneid . "' AND type='SUBNS' 
						AND val1='" . $value . "' AND val2='" . $subnsa[$i] . "'";
						$res=$db->query($query);
						$line = $db->fetch_row($res);
						if($db->error()){
							$result .= ' <font color="red">Trouble with
							DB</font><br />';
							$this->error = "Trouble with DB";
						}else{
							if($line[0]==0){
								if(!notnull($ttl[$i])){
									$ttlval = "default";
								}else{
									$ttlval = $ttl[$i];
								}							
								$query = "INSERT INTO dns_record (zoneid, type, val1, val2,ttl) 
								VALUES ('" . $this->zoneid . "', 'SUBNS', '"
								 . $value . "', '" . $subnsa[$i] . "','" . $ttlval . "')
								";
								$db->query($query);
								if($db->error()){
									$result .= ' <font color="red">Trouble with
									DB</font><br />';
									$this->error = "Trouble with DB";
								}else{
									$result .= " OK<br />\n";	
								}
							}else{
								$result .= "Record already exists<br />";
							}
						}
					}
				}
			}
			$i++;
		}
		return $result;
	}



// *******************************************************
//	Function UpdateSOA($xferip,$defaultttl,$soarefresh,$soaretry,$soaexpire,$soaminimum)
	/**
	 * Update SOA of current zone
	 *
	 *@access private
	 *@param string $xferip IP(s) allowed to do zone transfers
	 *@param int $defaultttl default TTL to be used
	 *@param int $soarefresh refresh interval
	 *@param int $soaretry retry interval
	 *@param int $soaexpire expire interval
	 *@param int $soaminimum negative TTL
	 *@return string 1 if success, 0 if DB error, string of error else
	 */
	Function UpdateSOA($xferip,$defaultttl,
						$soarefresh,$soaretry,$soaexpire,$soaminimum){
		global $db;
		$result ="";

		if(!notnull($defaultttl)){
			$defaultttl = 86400;
		}
		if(!notnull($soarefresh)){
			$soarefresh = 10800;
		}
		if(!notnull($soaretry)){
			$soaretry = 3600;
		}
		if(!notnull($soaexpire)){
			$soaexpire = 604800;
		}
		if(!notnull($soaminimum)){
			$soaminimum = 10800;
		}
		if(notnull($xferip)){
			if(!checkPrimary($xferip)){
				$error = 1;
				$result .= '<font color="red">Error: invalid (list of) IP address(es) of servers allowed to
				do transfers. If you want to add several IPs, separe them with
				\';\'.</font><br />';
			}
		}else{
			$xferip='any';
		}
	
		if(!$error){
	
			// dns_confprimary
			// upgrade serial
			
			$this->serial = getSerial($this->serial);
			if($this->creation==0){
				$query = "UPDATE dns_confprimary SET serial='" . $this->serial . "',
				xfer='" . $xferip . "', refresh='" . $soarefresh . "',
				retry='" . $soaretry . "', expiry='" . $soaexpire . "',
				minimum='" . $soaminimum . "', defaultttl='" . $defaultttl . "'
				WHERE zoneid='" . $this->zoneid . "'";
			}else{
				$query = "INSERT INTO dns_confprimary (zoneid,serial,xfer,refresh,
						retry,expiry,minimum,defaultttl)
				VALUES ('" . $this->zoneid . "','" . $this->serial . "','" . $xferip . "'
				,'" . $soarefresh . "','" . $soaretry . "','" . $soaexpire . "','" .
				$soaminimum . "','" . $defaultttl . "')";
			}
			$res = $db->query($query);
			if($db->error()){
				$this->error="Trouble with DB";
				return 0;
			}
			return 1;
		}else{
			return 0;
		}
		return $result;
		
	}





// *******************************************************	
//	Function RetrieveRecords($type,&$arraytofill,&$ttltofill)
	/**
	 * Fill in given array with all records of type $type for current zone
	 *
	 *@access private
	 *@param string $type type of record to be retrieved
	 *@param array &$arraytofill reference of array to be filled with records
	 *@param array &$ttltofill reference of array to be filled with ttl
	 *@return int 1 if success, 0 if error
	 */
	Function RetrieveRecords($type,&$arraytofill,&$ttltofill){
		global $db;
		$this->error='';
		$query = "SELECT val1, val2, ttl
			FROM dns_record 
			WHERE zoneid='" . $this->zoneid . "'
			AND type='" . $type . "'";
		$res =  $db->query($query);
		$arraytofill = array();
		$ttltofill = array();
		while($line = $db->fetch_row($res)){
			if($db->error()){
				$this->error="Trouble with DB";
				return 0;
			}
			$arraytofill[$line[0]]=$line[1];
			$ttltofill[$line[0]] = $line[2];
		}
		return 1;
	}

// *******************************************************	
//	Function RetrieveMultiRecords($type,&$array1,&$array2,&$ttltofill)
	/**
	 * Same as RetrieveRecords, but used when a type of record might 
	 * have multiple similar entries (A for round robin, NS, etc...)
	 * Results are stored in two separate arrays
	 *
	 *@access private
	 *@param string $type type of record to be retrieved
	 *@param array &$array1tofill reference of array to be filled with first record element
	 *@param array &$array2tofill reference of array to be filled with second record element
	 *@param array &$ttltofill reference of array to be filled with ttl
	 *@return int 1 if success, 0 if error
	 */
	Function RetrieveMultiRecords($type,&$array1tofill,&$array2tofill,&$ttltofill){
		global $db;
		$this->error='';
		$query = "SELECT val1, val2, ttl
			FROM dns_record 
			WHERE zoneid='" . $this->zoneid . "'
			AND type='" . $type . "'";
		$res =  $db->query($query);
		$array1tofill = array();
		$array2tofill = array();
		$ttltofill = array();
		$i=0;
		while($line = $db->fetch_row($res)){
			if($db->error()){
				$this->error="Trouble with DB";
				return 0;
			}
			$array1tofill[$i]=$line[0];
			$array2tofill[$i]=$line[1];
			$ttltofill[$i]=$line[2];
			$i++;
		}
	}


// *******************************************************	
//	Function generateConfigFile()
	/**
	 * Generate a temporary config file in /tmp
	 *
	 *@access private
	 *@return int 1
	 */
	Function generateConfigFile(){
		global $config;
		// reinitialize every records after add/delete/modify
		// fill in with records
		$this->RetrieveRecords('NS',$this->ns,$this->nsttl);
		$this->RetrieveRecords('MX',$this->mx,$this->mxttl);
		$this->RetrieveRecords('DNAME',$this->dname,$this->dnamettl);
		$this->RetrieveMultiRecords('A',$this->a,$this->aip,$this->attl);
		$this->RetrieveRecords('CNAME',$this->cname,$this->cnamettl);
		$this->RetrieveRecords('A6',$this->a6,$this->a6ttl);
		$this->RetrieveRecords('AAAA',$this->aaaa,$this->aaaattl);
		$this->RetrieveMultiRecords('SUBNS',$this->subns,$this->subnsa,$this->subnsttl);


		// select SOA items
		$fd = fopen("/tmp/" . $this->zonename . "." . $this->zonetype,"w");
		fputs($fd, "\n\$TTL " . $this->defaultttl . " ; default TTL
" . $this->zonename . ".\t\tIN\tSOA\t" . $config->nsname . ".\t");
		$mail = ereg_replace("@",".",$this->user->Retrievemail());
		fputs($fd, $mail . ". (");
		fputs($fd,"\n\t\t\t\t" . $this->serial . "\t; serial");
		fputs($fd,"\n\t\t\t\t" . $this->refresh . "\t; refresh period");
		fputs($fd,"\n\t\t\t\t" . $this->retry . "\t; retry interval");
		fputs($fd,"\n\t\t\t\t" . $this->expiry . "\t; expire time");
		fputs($fd,"\n\t\t\t\t" . $this->minimum . "\t; negative TTL");
		fputs($fd,"\n\t\t\t)");
	
		// retrieve & print NS
		$keys = array_keys($this->ns);
		while($key = array_shift($keys)){
			if($this->nsttl[$key] != "default"){
				fputs($fd,"\n\t\t" . $this->nsttl[$key] . "\tIN\t\tNS\t\t" . $key);
			}else{
				fputs($fd,"\n\t\t\tIN\t\tNS\t\t" . $key);
			}
		}

		// retrieve & print MX
		$keys = array_keys($this->mx);
		while($key = array_shift($keys)){
			if($this->mxttl[$key] != "default"){
				fputs($fd, "\n\t\t" . $this->mxttl[$key] . "\tIN\t\tMX\t" . $this->mx[$key] . "\t" . $key);
			}else{
				fputs($fd, "\n\t\t\tIN\t\tMX\t" . $this->mx[$key] . "\t" . $key);
			}
		}
		
		fputs($fd,"\n\n\$ORIGIN " . $this->zonename . ".");

		// retrieve & print A
		$counter = 0;
		while($this->a[$counter]){
			if($this->attl[$counter] != "default"){
				fputs($fd,"\n" . $this->a[$counter] . "\t\t" . $this->attl[$counter] . 
					"\tIN\t\tA\t\t" . $this->aip[$counter]);
			}else{
				fputs($fd,"\n" . $this->a[$counter] . "\t\t\tIN\t\tA\t\t" . $this->aip[$counter]);
			}
			$counter++;
		}

		// retrieve & print CNAME
		$keys = array_keys($this->cname);
		while($key = array_shift($keys)){
			if($this->cnamettl[$key] != "default"){
				fputs($fd,"\n" . $key . "\t\t" . $this->cnamettl[$key] . 
					"\tIN\t\tCNAME\t\t" . $this->cname[$key]);
			}else{
				fputs($fd,"\n" . $key . "\t\t\tIN\t\tCNAME\t\t" . $this->cname[$key]);
			}
		}
		
		// retrieve & print SUBNS
		$counter = 0;
		while($this->subns[$counter]){
			if($this->subnsttl[$counter] != "default"){
				fputs($fd,"\n" . $this->subns[$counter] . "\t\t" . 
					$this->subnsttl[$counter] . "\tIN\t\tNS\t\t" . $this->subnsa[$counter]);
			}else{
				fputs($fd,"\n" . $this->subns[$counter] . "\t\t\tIN\t\tNS\t\t" . $this->subnsa[$counter]);
			}
			$counter++;
		}
		
		fputs($fd,"\n\n");
		fclose($fd);
		return 1;
	}
}
?>
