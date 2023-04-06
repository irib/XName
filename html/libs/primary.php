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
	var $txt;
	var $txtttl;
	var $subns;
	var $subnsttl;
	var $subnsa;
	var $delegatefromto;
	var $delegateuser;
	var $delegatettl;

	var $reversezone;
	var $ipv6;
		
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
		global $db,$l;
		$this->Zone($zonename,$zonetype);

		// fill in vars
		$res = $db->query("SELECT serial, refresh, retry, expiry, minimum, defaultttl, xfer
			FROM dns_confprimary WHERE zoneid='" . $this->zoneid . "'");
		$line = $db->fetch_row($res);
		if($db->error()){
			$this->error=$l['str_trouble_with_db'];
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
		if(ereg('.arpa$',$zonename) || ereg('.ip6.int',$zonename)){
			$this->reversezone=1;
		}else{
			$this->reversezone=0;
		}
		// initialize arrays
		$this->ns = array();
		$this->nsttl = array();
		$this->mx = array();
		$this->mxttl = array();
		$this->subns = array();
		$this->subnsttl = array();
		$this->subnsa = array();
		$this->cname = array();
		$this->cnamettl = array();

		if($this->reversezone){
			$this->ptr = array();
			$this->ptrname = array();
			$this->ptrttl = array();
			$this->delegatefromto = array();
			$this->delegateuser = array();
			$this->delegatettl = array();
		}else{
			$this->dname = array();
			$this->dnamettl = array();
			$this->a = array();
			$this->attl = array();
			$this->aip = array();
			$this->a6 = array();
			$this->a6ttl = array();
			$this->a6ip = array();
			$this->aaaa = array();
			$this->aaaattl = array();
			$this->aaaaip = array();
			$this->txt = array();
			$this->txtttl = array();
			$this->txtdata = array();
		}		
		// fill in with records
		$this->RetrieveRecords('NS',$this->ns,$this->nsttl);
		$this->RetrieveRecords('MX',$this->mx,$this->mxttl);
		$this->RetrieveMultiRecords('SUBNS',$this->subns,$this->subnsa,$this->subnsttl);
		$this->RetrieveRecords('CNAME',$this->cname,$this->cnamettl);
		
		if($this->reversezone){
			$this->RetrieveMultiRecords('PTR',$this->ptr,$this->ptrname,$this->ptrttl);
			$this->RetrieveMultiRecords('DELEGATE',$this->delegatefromto,$this->delegateuser,$this->delegatettl);
		}else{
			$this->RetrieveRecords('DNAME',$this->dname,$this->dnamettl);
			$this->RetrieveMultiRecords('A',$this->a,$this->aip,$this->attl);
			$this->RetrieveMultiRecords('A6',$this->a6,$this->a6ip,$this->a6ttl);
			$this->RetrieveMultiRecords('AAAA',$this->aaaa,$this->aaaaip,$this->aaaattl);
			$this->RetrieveMultiRecords('TXT',$this->txt,$this->txtdata,$this->txtttl);
		}
	}


// *******************************************************
	
	//	Function printModifyForm($params)
	/**
	 * returns a pre-filled form to modify primary records
	 *
	 *@access public
	 *@param array $params list of params
	 *@return string HTML pre-filled form
	 */
	Function printModifyForm($params){
		global $config,$lang;
		global  $l;
		global $hiddenfields;

		list($advanced,$ipv6,$nbrows) = $params;
		$this->error="";
		$result = '';
			$deletecount = 0;
			// TODO use zoneid instead of zonename & zonetype
			$result .= '<form method="POST">
			 ' . $hiddenfields . '
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
				<div class="boxheader">' . $l['str_primary_global_params'] . '</div>
				<table border="0" width="100%">
				<tr><td colspan="2">' . $l['str_primary_ttl_explanation'] . '</td></tr>
				<tr><td align="right">' . $l['str_primary_default_ttl'] . '</td>
				<td><input type="text" name="defaultttl" value="' . 
				$this->defaultttl . '"></td></tr>
				</table>
				<p />
				';
				// print SOA params
				$result .= '
				<div class="boxheader">' . $l['str_primary_soa_params'] . '</div>
				<table border="0" width="100%">
				<tr><td colspan="2">' . $l['str_primary_refresh_interval_expl'] . '
				</td>
				<tr><td align="right">' . $l['str_primary_refresh_period'] . '</td>
				<td><input type="text" name="soarefresh" value="' .
				$this->refresh . '"></td></tr>
				<tr><td colspan="2">' . $l['str_primary_retry_interval_expl'] . '
				</td></tr>
				<tr><td align="right">' . $l['str_primary_retry_interval'] . '
				</td><td><input type="text"	name="soaretry" value="' .
				$this->retry . '"></td></tr>
				<tr><td colspan="2">' . $l['str_primary_expire_time_expl'] . '
				</td>
				<tr><td align="right">' . 
				$l['str_primary_expire_time'] . '</td><td><input type="text"
				name="soaexpire" value="' .
				$this->expiry . '"></td></tr>
				<tr><td colspan="2">' . $l['str_primary_negative_caching_expl'] . '
				</td></tr>
				<tr><td align="right">' . $l['str_primary_negative_caching'] . '</td>
				<td><input type="text" name="soaminimum" value="' .
				$this->minimum . '"></td></tr>
				
				
				</table>
				<p />';
			}
		
			// retrieve NS names
			$nsxnames = GetListOfServerNames();
			$nsxnamesmandatory = GetListOfServerNames(1);
			if (count($this->ns) == 0)
				$nsxnamesoptional = array_diff($nsxnames, $nsxnamesmandatory);

			$result .= '
			<div class="boxheader">' . $l['str_primary_name_server_title'] . '</div>
			<table border="0" width="100%">
				<tr><td>' .
				sprintf($l['str_primary_name_server_expl_with_sample_x'],
					$nsxnames[0]) .'</td></tr>
				<tr><td>
				<table align="center" border="0" cellspacing="1"
				cellpadding="2" bgcolor="#000000" width="90%">
				<tr><td bgcolor="#DDDDDD">
				<table border="0" width="100%"><th>' .
				$l['str_primary_name'];
				if($advanced) { $result .= '<th>TTL'; }
				$result .= '<th>' . $l['str_delete'] . '
				';
			
			$usednsxnames = array();
			$keys = array_keys($this->ns);
			while($key = array_shift($keys)){
				$result .= '<tr>
				<td>' . $key . '</td>
				';
				if($advanced){
					$result .= '
					<td>' . $this->PrintTTL($this->nsttl[$key]) . '</td>';
				}
				$result .= '<td>';
				// if ns is mandatory, never delete it
				$keytocompare = substr($key,0,-1);
				if(!in_array($keytocompare,$nsxnamesmandatory)){
					$deletecount++;
					$result .= '<input type="checkbox" name="delete' .
					 $deletecount .
					'" value="ns(' . $key . ')"></td>';
				}else{
					array_push($usednsxnames, $keytocompare);
				}
				$result .= "</td></tr>\n";
			}
			// compare $usednsxnames and $nsxnamesmandatory. If differences, add missing ones.
			$missingns = array_diff($nsxnamesmandatory,$usednsxnames);
			$nscounter=0;
			while($missingnsname = array_pop($missingns)){
				$nscounter++;
				$result .= '
				<tr>
				<td><input type="hidden" name="ns' . $nscounter .'" value="' 
				. $missingnsname . '.">' . $missingnsname . '.</td>
				';
				if($advanced){
					$result .= '
					<td><input type="text" name="nsttl' . $nscounter . 
					'" size="8" value="' . $l['str_primary_default'] . '"></td>';
				}
				$result .= '<td></td></tr>
				';
			}
			$nscounter++;
			for($count=1;$count <= $nbrows;$count++){
				$result .= '
					<tr>
					<td><input type="text" name="ns' . $nscounter . '" value="' .
					$nsxnamesoptional[$count] . '"></td>';
				if($advanced){
					$result .= '
					<td><input type="text" name="nsttl' . $nscounter . 
					'" size="8" value="' . $l['str_primary_default'] . '"></td>
					';
				}
				$nscounter++;
				$result .= '</td></tr>';
			}

			$result .= '
			</table></td></tr></table>
			</td></tr></table>

			<p>
			<div class="boxheader">' . $l['str_primary_mail_exchanger_title'] . '</div>
			<table border="0" width="100%">
				<tr><td>' . 
				sprintf($l['str_primary_mx_expl_with_sample_x'],
					$this->zonename) . '<br />' .
				$l['str_primary_mx_expl_for_pref'] . '
				</td></tr>
				<tr><td>
        <table align="center" border="0" cellspacing="1" cellpadding="2"
        bgcolor="#000000" width="90%">
        <tr><td bgcolor="#DDDDDD">
        <table border="0" width="100%"><th>' . $l['str_primary_mx_pref'] .'
        <th>' . $l['str_primary_name'];
        if($advanced) { $result .= '<th>TTL'; }
        $result .= '<th>' . $l['str_delete'] . '
			  ';

			$counter=0;
			$keys = array_keys($this->mx);
			while($key = array_shift($keys)){			
				$deletecount++;
				$result .= '<tr>
						<td>' . $this->mx[$key] . '</td>
						<td>' . $key . '</td>';
				if($advanced){
					$result .= '
					<td>' . $this->PrintTTL($this->mxttl[$key]) . '</td>
					';
				}
				$result .= '
						<td><input type="checkbox" name="delete' . $deletecount .
						'" value="mx(' . $key . ')"></td></tr>
				';
				$counter++;
			}	
			
			$mxcounter = 0;
			for($count=1;$count <= $nbrows;$count++){
				$mxcounter++;
				$result .= '
					<tr><td><input type="text" size="5" maxlength="5"
							 name="pref' . $mxcounter . '"></td>
							<td><input type="text" name="mx' . $mxcounter . '"></td>';
				if($advanced){
					$result .= '
					<td><input type="text" name="mxttl' . $mxcounter . '" size="8" value="' . 
						$l['str_primary_default'] . '"></td>
					';
				}
				$result .= '<td>&nbsp;</td></tr>';
			}
				
			$result .= '
			</table></td></tr></table>
			</td></tr></table>
			';
			
			if($this->reversezone){
				$result .= '
				<p />
				<div class="boxheader">' . $l['str_primary_ptr_title'] . '</div>
				<table border="0" width="100%">
				<tr><td>' . $l['str_primary_ptr_expl'] . '<br />
				' . $l['str_primary_ptr_sample'] . ': <br />
				<tt>' . $l['str_primary_ptr_sample_content'] . '</tt>
				<br />' . $l['str_primary_ptr_ipv6_note'] . '</td></tr>
				<tr><td>' . 
				sprintf($l['str_primary_ptr_record_modify_a_x'],
				$config->sitename) . '<input type=checkbox
				name="modifya"></td></tr>
				<tr><td><table border="0" width="100%">
				';			
				$counter=0;
				while($this->ptr[$counter]){
					$deletecount++;
					// if advanced, print TTL fields
					$result .= '<tr><td align="right">PTR: </td>
							<td>IP:&nbsp;' . $this->ptr[$counter] . '</td>
							<td>' . $l['str_primary_name'] . ':&nbsp;' . 
							$this->ptrname[$counter] . '</td>';
					if($advanced){
						$result .= '<td align="right">TTL:</td>
						<td>' . $this->PrintTTL($this->ptrttl[$counter]) . '</td>
						';
					}
					$result .= '
							<td><input type="radio" name="delete' . $deletecount .
							'" value="ptr(' . $this->ptr[$counter] . '/' .
							$this->ptrname[$counter] . ')"></td><td>' . $l['str_delete'] . 
							'</td></tr>
					';
					$counter ++;
				}	

				$counter=0;
				$keys = array_keys($this->ptr);
				while($key = array_shift($keys)){
					$deletecount++;
					$counter++;
				}	
			
				$ptrcounter = 0;
				for($count=1;$count <= $nbrows;$count++){
					$ptrcounter++;			
					$result .= '
						<tr><td align="right">' . 
						sprintf($l['str_primary_ptr_new_ptr_x'],$ptrcounter) . 
						': </td>
							<td>' . sprintf($l['str_primary_ptr_ip_under_x'],
							$this->zonename) . '<br><input
							type="text" name="ptr' . $ptrcounter . '"></td>
							<td>' . $l['str_primary_name'] . '<br><input type="text" name="ptrname' .
							$ptrcounter . '"></td>';
					if($advanced){
						$result .= '<td align="right">TTL:</td>
						<td><input type="text" name="ptrttl' . $ptrcounter . '" size="8" value="' . 
							$l['str_primary_default'] . '"></td>
						';
					}
					$result .= '<td></td></tr>';
				}
				
				$result .='
				</table></td></tr></table>
				';
				
				$result .='
				<p>
				<div class="boxheader">' . $l['str_primary_reverse_sub_zones_title'] . '</div>
				<table border="0" width="100%">
				<tr><td>
				' . sprintf($l['str_primary_reverse_sub_zones_delegation_x'],
						$config->sitename) . '
				<br />
				' . sprintf($l['str_primary_reverse_sub_zones_delegation_expl_x_x'],
						$this->zonename, $config->sitename) . '<br />
				' . $l['str_primary_reverse_sub_zones_delegation_how'] . '
				</td></tr>
				<tr><td><table border="0" width="100%">
				';

				$counter=0;
				while($this->delegatefromto[$counter]){
					$deletecount++;
					list($from,$to) = split('-',$this->delegatefromto[$counter]);
					$result .= '<tr><td align="right">' . 
						$l['str_primary_reverse_sub_zone_range'] . ':</td>
								<td>' . $l['str_primary_reverse_sub_zone_range_from']
								. '&nbsp;' . $from . '</td>
							<td>' . $l['str_primary_reverse_sub_zone_range_to'] . '&nbsp;' . $to . '</td>
							<td>'. sprintf($l['str_primary_reverse_sub_zone_delegated_to_user_x'], 
									$this->delegateuser[$counter]) .
							'</td> 
							';
					if($advanced){
						$result .= '<td align="right">TTL: </td>
						<td>' . $this->PrintTTL($this->delegatettl[$counter]) . '</td>
						';
					}
					$result .= '<td><input type="radio" name="delete' . $deletecount . 
							'" value="delegate(' .
							$this->delegatefromto[$counter] . ')"></td><td>' . 
							$l['str_delete'] . '</td></tr>
					';
					$counter ++;
				}	
			
				$subnscounter = 0;
				for($count=1;$count <= $nbrows;$count++){
					$subnscounter++;
					$result .= '
						<tr><td align="right">' . 
						$l['str_primary_reverse_sub_zone_range'] . '&nbsp;(' .
							$subnscounter . '):</td><td>' . 
							$l['str_primary_reverse_sub_zone_range_from'] . 
							'&nbsp;<input type="text" size="3" 
							name="delegatefrom' . $subnscounter . '"></td>
							<td>' . $l['str_primary_reverse_sub_zone_range_to'] . 
							'&nbsp;<input type="text" name="delegateto' . $subnscounter . '" size="3">
								</td><td>' .
								$l['str_primary_reverse_sub_zone_delegated_to_user'] . 
								'&nbsp;<input type="text" name="delegateuser' .
								$subnscounter . '" size="10"></td>';
					if($advanced){
						$result .= '<td align="right">TTL:</td>
						<td><input type="text" name="delegatettl' . $subnscounter . '" size="8" value="' . 
							$l['str_primary_default'] . '"></td>
						';
					}
				}

				$result .= '
				</table></td></tr></table>
				';




			}else{ // not reverse zone
				$result .= '
				<p />
				<div class="boxheader">' . $l['str_primary_a_record_title'] . '</div>
				<table border="0" width="100%">
				<tr><td>' .
				sprintf($l['str_primary_a_record_what_you_want_before_x_x_x'],
					$this->zonename, $this->zonename,
					$this->zonename) . '<br />
				' . $l['str_primary_a_record_expl'] . '
				</td></tr>
				<tr><td>' . 
				sprintf($l['str_primary_a_record_modify_ptr_x'],
				$config->sitename) . '<input type=checkbox
				name="modifyptr"></td></tr>
				<tr><td>
        <table align="center" border="0" cellspacing="1" cellpadding="2"
        bgcolor="#000000" width="90%">
        <tr><td bgcolor="#DDDDDD">
        <table border="0" width="100%">
        <th>' . $l['str_primary_name'] . '<th>IP';
        if($advanced) { $result .= '<th>TTL'; }
        $result .= '<th>' . $l['str_delete'] . '
				';
	
				$counter=0;
				while($this->a[$counter]){
					$deletecount++;
					// if advanced, print TTL fields
					$result .= '<tr>
							<td>' . $this->a[$counter] . '</td>
							<td>' . $this->aip[$counter] . '</td>';
					if($advanced){
						$result .= '<td>' . $this->PrintTTL($this->attl[$counter]) . '</td>
						';
					}
					$result .= '
							<td><input type="checkbox" name="delete' . $deletecount .
							'" value="a(' . $this->a[$counter] . '/' .
							$this->aip[$counter] . ')"></td></tr>
					';
					$counter ++;
				}	

				$counter=0;
				$keys = array_keys($this->a);
				while($key = array_shift($keys)){
					$deletecount++;
					$counter++;
				}	
				$acounter = 0;
				for($count=1;$count <= $nbrows;$count++){
					$acounter++;
					$result .= '
					<tr>
							<td><input type="text" name="aname' . $acounter
							. '"></td>
							<td><input type="text" name="a' . $acounter . '"></td>';
					if($advanced){
						$result .= '<td><input type="text" name="attl' . $acounter . '" size="8" value="' . 
							$l['str_primary_default'] . '"></td>
						';
					}
				
					$result .= '<td>&nbsp;</td></tr>';
				}

				$result .= '
				</table></td></tr></table>
				</td></tr></table>
				';
	
				if($this->user->ipv6){
					$result .= '
					<p />
					<div class="boxheader">' . $l['str_primary_ipv6_record_title'] . 
					'</div>
					<table border="0" width="100%">
					<tr><td>' . 
					sprintf($l['str_primary_ipv6_record_expl_before_x_x_x'],
						$this->zonename,$this->zonename,
						$this->zonename) . '<br />
					' . $l['str_primary_ipv6_record_expl_zone_and_round_robin'] . '
					</td></tr>
					<tr><td>
          <table align="center" border="0" cellspacing="1" cellpadding="2"
          bgcolor="#000000" width="90%">
          <tr><td bgcolor="#DDDDDD">
          <table border="0" width="100%">
          <th>'. $l['str_primary_name'] . '<th>IPv6';
          if ($advanced) { $result .= '<th>TTL'; }
          $result .= '<th>' . $l['str_delete'] . '
					<!-- <tr><td colspan="4">' .
					sprintf($l['str_primary_ipv6_record_modify_reverse_x'],
					$config->sitename) . ' ? <input type=checkbox
					name="modifyptripv6"></td></tr>
					-->';
	
					$counter=0;
					while($this->aaaa[$counter]){
						$deletecount++;
						// if advanced, print TTL fields
						$result .= '<tr>
								<td>' . $this->aaaa[$counter] . '</td>
								<td>' . $this->aaaaip[$counter] . '</td>';
						if($advanced){
							$result .= '<td>' . $this->PrintTTL($this->aaaattl[$counter]) . '</td>
							';
						}
						$result .= '
								<td><input type="checkbox" name="delete' . $deletecount .
								'" value="aaaa(' . $this->aaaa[$counter] . '/' .
								$this->aaaaip[$counter] . ')"></td></tr>
						';
						$counter ++;
					}	

					$counter=0;
					$keys = array_keys($this->aaaa);
					while($key = array_shift($keys)){
						$deletecount++;
						$counter++;
					}	
					$aaaacounter = 0;
					for($count=1;$count <= $nbrows;$count++){
						$aaaacounter++;
						$result .= '
						<tr><td><input type="text" name="aaaaname' . 
								$aaaacounter
								. '"></td>
								<td><input type="text" name="aaaa' . $aaaacounter . '"></td>';
						if($advanced){
							$result .= '
							<td><input type="text" name="aaaattl' . $aaaacounter . '" size="8" value="' . $l['str_primary_default'] . '"></td>
							';
						}
				
						$result .= '<td></td></tr>';
					}

					$result .= '
					</table></td></tr></table>
					</td></tr></table>
					';
				} // end IPv6	
	
				
				
				$result .= '<p />
				<div class="boxheader">' . $l['str_primary_cname_title'] . '</div>
				<table border="0" width="100%">
				<tr><td>' . $l['str_primary_cname_expl'] . '
				</td></tr>
				<tr><td>
        <table align="center" border="0" cellspacing="1" cellpadding="2"
        bgcolor="#000000" width="90%">
        <tr><td bgcolor="#DDDDDD">
        <table border="0" width="100%">
        <th>' . $l['str_primary_cname_alias'] .'
        <th>' . $l['str_primary_cname_name_a_record'];
        if($advanced) { $result .= '<th>TTL'; }
        $result .= '<th>' . $l['str_delete'] . '
							';

				$counter=0;
				$keys = array_keys($this->cname);
				while($key = array_shift($keys)){
					$deletecount++;
					$result .= '<tr>
							<td>' . $key . '</td>
							<td> ' . $this->cname[$key] . '</td>';
					if($advanced){
						$result .= '
						<td>' . $this->PrintTTL($this->cnamettl[$key]) . '</td>
						';
					}
					$result .= '
							<td><input type="checkbox" name="delete' . $deletecount . 
							'" value="cname(' . $key . ')"></td></tr>
					';
				}	
			

				$cnamecounter = 0;
				for($count=1;$count <= $nbrows;$count++){
					$cnamecounter++;
					$result .= '
						<tr>
						<td><input
						 type="text" name="cname' . $cnamecounter . '"></td>
							<td><input 
							type="text" name="cnamea' . $cnamecounter . '">
						</td>';
					if($advanced){
						$result .= '
						<td><input type="text" name="cnamettl' . $cnamecounter . '" 
						size="8" value="' . $l['str_primary_default'] . '"></td>
						';
					}
					$result .= '<td></td></tr>';
				}

				$result .= '
				</table></td></tr></table>
				</td></tr></table>
				';
				
				// END CNAME

				// BEGIN TXT
				if($this->user->txtrecords){
					$result .= '
					<p />
					<div class="boxheader">' . $l['str_primary_txt_record_title'] . 
					'</div>
					<table border="0" width="100%">
					<tr><td>' . 
					sprintf($l['str_primary_txt_record_expl_x_x_x'],
						$this->zonename,$this->zonename,
						$this->zonename) . '
					</td></tr>
					<tr><td>
          <table align="center" border="0" cellspacing="1" cellpadding="2"
          bgcolor="#000000" width="90%">
          <tr><td bgcolor="#DDDDDD">
          <table border="0" width="100%">
          <th>'. $l['str_primary_name'] . '<th>TXT';
          if ($advanced) { $result .= '<th>TTL'; }
          $result .= '<th>' . $l['str_delete'] ;
	
					$counter=0;
					while($this->txt[$counter]){
						$deletecount++;
						// if advanced, print TTL fields
						$result .= '<tr>
								<td>' . $this->txt[$counter] . '</td>
								<td>' . $this->txtdata[$counter] . '</td>';
						if($advanced){
							$result .= '<td>' . $this->PrintTTL($this->txtttl[$counter]) . '</td>
							';
						}
						$result .= '
								<td><input type="checkbox" name="delete' . $deletecount .
								'" value="txt(' . $this->txt[$counter] . '/' .
								htmlentities($this->txtdata[$counter]) . ')"></td></tr>
						';
						$counter ++;
					}	

					$counter=0;
					$keys = array_keys($this->txt);
					while($key = array_shift($keys)){
						$deletecount++;
						$counter++;
					}	
					$txtcounter = 0;
					for($count=1;$count <= $nbrows;$count++){
						$txtcounter++;
						$result .= '
						<tr><td><input type="text" name="txt' . 
								$txtcounter
								. '"></td>
								<td><input type="text" name="txtstring' . $txtcounter . '"></td>';
						if($advanced){
							$result .= '
							<td><input type="text" name="txtttl' . $txtcounter . '" size="8" value="' . $l['str_primary_default'] . '"></td>
							';
						}
				
						$result .= '<td></td></tr>';
					}

					$result .= '
					</table></td></tr></table>
					</td></tr></table>
					';
				} 
				// END TXT

				// BEGIN SUBNS
				
				$result .='
			<p>
			<div class="boxheader">' . $l['str_primary_sub_zones_title'] . '</div>
			<table border="0" width="100%">
				<tr><td>
				' . sprintf($l['str_primary_sub_zones_expl_on_x_x'],$config->sitename,
					$this->zonename) . '
				</td></tr>
				<tr><td>
        <table align="center" border="0" cellspacing="1" cellpadding="2"
        bgcolor="#000000" width="90%">
        <tr><td bgcolor="#DDDDDD">
        <table border="0" width="100%">
        <th>' . $l['str_primary_sub_zones_zone'] .'<th>NS';
        if($advanced) { $result .= '<th>TTL'; }
        $result .= '<th>' . $l['str_delete'] . '
				';

				$counter=0;
				while($this->subns[$counter]){
					$deletecount++;
					$result .= '<tr>
							<td>' . $this->subns[$counter] . '</td>
							<td>' . $this->subnsa[$counter] . '</td>
							';
					if($advanced){
						$result .= '
						<td>' . $this->PrintTTL($this->subnsttl[$counter]) . '</td>
						';
					}
					$result .= '<td><input type="checkbox" name="delete' . $deletecount . 
							'" value="subns(' . $this->subns[$counter] . '/' . 
							$this->subnsa[$counter] . ')"></td></tr>
					';
					$counter ++;
				}	
			
				$subnscounter = 0;
				for($count=1;$count <= $nbrows;$count++){
					$subnscounter++;
					$result .= '
						<tr><td><input
						 type="text" name="subns' . $subnscounter . '"></td>
							<td><input type="text" name="subnsa' . $subnscounter . '">
								</td>';
					if($advanced){
						$result .= '
						<td><input type="text" name="subnsttl' . $subnscounter . '" size="8" value="' . $l['str_primary_default'] . '"></td>
						';
					}
				}

				$result .= '
				</table></td></tr></table>
				</td></tr></table>
				';


			} // end not reverse zone
			


			$result .= '

			<p>
			<div class="boxheader">' . $l['str_primary_allow_transfer_title'] . '</div>
			<table border="0" width="100%">
				<tr><td width="20">&nbsp;</td><td>
				' . $l['str_primary_allow_transfer_expl'] . '
				</td></tr>
				<tr><td width="20">&nbsp;</td><td align="left">
				' . $l['str_primary_allow_transfer_ip_allowed'] . '
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
			<input type="submit" value="' . $l['str_primary_generate_zone_button'] .
			'">
			<input type="reset" value="' . $l['str_primary_reset_form_button'] . '">
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
		global  $html,$l;
	
list($VARS,$xferip,$defaultttl,$soarefresh,$soaretry,$soaexpire,$soaminimum,
								$modifyptr,$modifyptripv6,$modifya)=$params;

		$this->error="";
		$result = '';

		$delete = retrieveArgs("delete", $VARS);
		$ns = retrieveArgs("ns", $VARS);
		$nsttl = retrieveArgs("nsttl",$VARS);
		$mx = retrieveArgs("mx", $VARS);
		$mxttl = retrieveArgs("mxttl",$VARS);
		$pref = retrieveArgs("pref", $VARS);
		$subns = retrieveArgs("subns", $VARS);
		$subnsa = retrieveArgs("subnsa", $VARS);
		$subnsttl = retrieveArgs("subnsttl",$VARS);
		if($this->reversezone){
			$ptr = retrieveArgs("ptr", $VARS);
			$ptrname = retrieveArgs("ptrname", $VARS);
			$ptrttl = retrieveArgs("ptrttl", $VARS);			
			$delegatefrom = retrieveArgs("delegatefrom", $VARS);
			$delegateto = retrieveArgs("delegateto", $VARS);
			$delegatettl = retrieveArgs("delegatettl",$VARS);
			$delegateuser = retrieveArgs("delegateuser",$VARS);
		}else{
			$aaaaname = retrieveArgs("aaaaname", $VARS);
			$aaaa = retrieveArgs("aaaa", $VARS);
			$aaaattl = retrieveArgs("aaaattl",$VARS);

			$aname = retrieveArgs("aname", $VARS);
			$a = retrieveArgs("a", $VARS);
			$attl = retrieveArgs("attl",$VARS);
		}
		$cname = retrieveArgs("cname", $VARS);
		$cnamea = retrieveArgs("cnamea", $VARS);
		$cnamettl = retrieveArgs("cnamettl",$VARS);
		
		$txt = retrieveArgs("txt", $VARS);
		$txtstring = retrieveArgs("txtstring", $VARS);
		$txtttl = retrieveArgs("txtttl",$VARS);

		$result .= $this->Delete($delete,$modifyptr,$modifya);
		$result .= $this->AddNSRecord($ns,$nsttl);
		$result .= $this->AddMXRecord($mx,$pref,$mxttl);
		if($this->reversezone){
			$result .= $this->AddPTRRecord($this->zoneid,$ptr,$ptrname,$ptrttl,$modifya);
			$result .= $this->AddDELEGATERecord($delegatefrom,$delegateto,$delegateuser,$delegatettl);
		}else{
			if($this->user->ipv6){
				$result .= $this->AddAAAARecord($this->zoneid,$aaaa,$aaaaname,$aaaattl,$modifyptripv6);
			}
			$result .= $this->AddARecord($this->zoneid,$a,$aname,$attl,$modifyptr);
			$result .= $this->AddSUBNSRecord($subns,$subnsa,$subnsttl);
		}
		$result .= $this->AddCNAMERecord($cname,$cnamea,$cnamettl);
		$result .= $this->AddTXTRecord($txt,$txtstring,$txtttl);
		
		if($this->UpdateSOA($xferip,$defaultttl,$soarefresh,$soaretry,$soaexpire,$soaminimum) == 0){
			$result .= $html->generic_error . $this->error . 
						$html->generic_error_end . '<br />';		
		}else{
			$result .= sprintf($l['str_primary_new_serial_x'],
				$this->serial) . "<p />";
		
			// check for errors
			// - generate zone file in /tmp/zonename
			if(!$this->generateConfigFile()){
				$result .= $html->generic_error . $this->error . 
					$html->generic_error_end . '<br />';
			}else{

				// - do named-checkzone $zonename /tmp/zonename and return result
				$checker = "$config->binnamedcheckzone ".$this->zonename." ".
					$this->tempZoneFile();
				$check = `$checker`;
				// if ok
				 if(preg_match("/OK/", $check)){
				// if($check == "OK\n"){
					$result .= $l['str_primary_internal_tests_ok'] . '<p />
					' . $l['str_primary_generated_config'] . ': 
					<p align="center"><table border="0" bgcolor="#ffffff"><tr><td> 
					<pre>
					';
					// Print /tmp/zonename
					$fd = fopen($this->tempZoneFile(),"r");
					if ($fd == 0)
					{
						$result .= $html->generic_error . 
									sprintf($l['str_can_not_open_x_for_reading'],
											$this->tempZoneFile()) . 
									$html->generic_error_end;
					}else{
						$result .= fread($fd, filesize($this->tempZoneFile()));
						fclose($fd);
					}
					$result .= "</pre>
					</td></tr></table>
					</p>&nbsp;<p />";
					unlink($this->tempZoneFile());
					$result .= $this->flagModified($this->zoneid);
				}else{
					$result .= $l['str_primary_zone_error_warning'] . ': 
					<p />
					<pre>' . $check . '</pre>
					' . 
					sprintf($l['str_primary_error_if_engine_error_x_contact_admin_x'],
						'<a	href="mailto:' . $config->contactemail . '">',
						'</a>') . '
					<p />
					' . $l['str_primary_trouble_occured_when_checking'] . ':
					<p align="center"><table border="0" bgcolor="#ffffff"><tr><td> 
					<pre>
					';
					// Print /tmp/zonename
					$fd = fopen($this->tempZoneFile(),"r");
					if ($fd == 0)
					{
						$result .= $html->generic_error . 
									sprintf($l['str_can_not_open_x_for_reading'],
											$this->tempZoneFile()) . 
									$html->generic_error_end;
					}else{
						$result .= fread($fd, filesize($this->tempZoneFile()));
						fclose($fd);
					}
					$result .= "</pre>
					</td></tr></table>
					</p>&nbsp;<p />";
				}
			}
		}	
		return $result;
	}
	



// *******************************************************	
	Function DeleteARecord($name,$ip,$reverse){
		global $db;
		global  $html,$config,$l;

		$result = sprintf($l['str_primary_deleting_a_x'],
					stripslashes($name) . "/" . stripslashes($ip)) . "...";
	
		if(notnull($reverse)){
			// look for reverse
			// check if managed by user
			// etc...

			// if reverse IP is managed by current user, update PTR
			// else check if reverse IP delegation exists (ie as CNAME)
			$result .= $l['str_primary_looking_for_reverse'] . "...";
				// construct reverse zone
			$ipsplit = split('\.',stripslashes($ip));
			$reversezone="";
			$firstip=0;
			while($reverseipvalue = array_pop($ipsplit)){
				if($firstip){
					$reversezone .= $reverseipvalue . ".";
				}else{
					$firstip = $reverseipvalue;
				}
			}
			$reversezone .= "in-addr.arpa";
			if($this->Exists($reversezone,'P')){
				$alluserzones = $this->user->listallzones();
				$ismanaged=0;
				while($userzones = array_pop($alluserzones)){
					if(!strcmp($reversezone,$userzones[0])){
						$ismanaged=1;
					}
				}
				if($ismanaged){
					// modification allowed because same owner
					// looking for zoneid
					$result .= " " . $l['str_primary_zone_managed_by_you'];
					$query = "SELECT id FROM dns_zone 
						WHERE zone='" . $reversezone . "' AND zonetype='P'";
					$res = $db->query($query);
					$line = $db->fetch_row($res);
					$newzoneid=$line[0];
					if(strcmp($val1,$this->zonename)){
						$valtodelete = $name . "." . $this->zonename . ".";
					}else{
						$valtodelete = $name;
					}
					$query = "DELETE FROM dns_record 
						WHERE zoneid='" . $newzoneid . "'
						AND type='PTR' AND val1='" . $firstip . "' 
						AND val2='" . $valtodelete . "'";
	
					$res = $db->query($query);
					if($db->error()){
						$this->error=$l['str_trouble_with_db'];
					}else{
						$result .= " " . $this->flagModified($newzoneid);
						$this->updateSerial($newzoneid);
					}
				}else{
					// zone exists, but not managed by current user.
					// check for subzone managed by current user
					$result .= " " . 
						$l['str_primary_main_zone_not_managed_by_you'] . "...";
					$query = "SELECT zone,id FROM dns_zone WHERE
						userid='" . $this->user->userid . "'
						AND zone like '%." . $reversezone . "'";
					$res = $db->query($query);
					$newzoneid = 0;
					while($line = $db->fetch_row($res)){
						$range =array_pop(array_reverse(split('\.',$line[0])));
						list($from,$to) = split('-',$range);
						if(($firstip >= $from) && ($firstip <= $to)){
							$newzoneid=$line[1];
						}
					}
					if($newzoneid){
						if(strcmp($val1,$this->zonename)){
							$valtodelete = $name . "." . $this->zonename . ".";
						}else{
							$valtodelete = $name;
						}
						$query = "DELETE FROM dns_record 
							WHERE zoneid='" . $newzoneid . "'
							AND type='PTR' AND val1='" . $firstip . "' 
							AND val2='" . $valtodelete . "'";
		
						$res = $db->query($query);
						if($db->error()){
							$this->error=$l['str_trouble_with_db'];
						}else{
							$result .= " " . $this->flagModified($newzoneid);
							$this->updateSerial($newzoneid);
						}
					}else{
						// no zone found
						$result .= " " . 
							$l['str_primary_reverse_exists_but_ip_not_manageable'] . "<br />";
					}
											
				}
			}else{
				$result .=
					sprintf($l['str_primary_not_managed_by_x'],
						$config->sitename) . "<br />";
			}
		} // end if updatereverse
		$query = "DELETE FROM dns_record 
			WHERE zoneid='" . $this->zoneid . "'
			AND type='A' AND val1='" . $name . "' 
			AND val2='" . $ip . "'";
		$res = $db->query($query);
		if($db->error()){
			$this->error=$l['str_trouble_with_db'];
			$result .= $html->generic_error . $l['str_trouble_with_db'] .
						$html->generic_error_end 
						. '<br />';
		}else{
			$result .= $l['str_primary_deleting_ok'] . "<br />\n";
		}
		return $result;
	}
	

// *******************************************************	
	Function DeletePTRRecord($ip,$name,$reverse){
		global $db;
		global  $html,$config,$l;

		$result = sprintf($l['str_primary_deleting_ptr_x'],
					stripslashes($ip) . "/" . stripslashes($name)) . "...";
	
		if(notnull($reverse)){
		// if "normal" zone is managed by current user, update A 
		// remove all before first dot, and last char.
			$newzone = substr(substr(strstr(stripslashes($name),'.'),1),0,-1);
			$newa = substr(stripslashes($name),0,strlen(stripslashes($name)) - strlen($newzone) -2);
			// construct new IP
			// zone *.in-addr.arpa or *.ip6.int
			$iplist = split('\.',strrev(
									substr(
										strstr(
											substr(
												strstr(
													strrev(
														$this->zonename
													),
												'.'),
											1),
										'.'),
									1)
								)
							);
			$newip = "";
			$count = 0; // we have to count in case of zub-zones aa.bb.cc.dd-ee
			while($ipitem = array_pop($iplist)){
				$count++;
				if(count < 4){
					$newip .= "." . $ipitem;
				}
			}
			$newip = substr($newip,1) . "." . $ip;
			$result .= sprintf($l['str_primary_looking_for_zone_x'],$newzone). "...";
			if($this->Exists($newzone,'P')){
				$alluserzones = $this->user->listallzones();
				$ismanaged=0;
				while($userzones = array_pop($alluserzones)){
					if(!strcmp($newzone,$userzones[0])){
						$ismanaged=1;
					}
				}
				if($ismanaged){
					// modification allowed because same owner
					// looking for zoneid
					$result .= " " . $l['str_primary_zone_managed_by_you'];
					$query = "SELECT id FROM dns_zone 
						WHERE zone='" . $newzone . "' AND zonetype='P'";
					$res = $db->query($query);
					$line = $db->fetch_row($res);
					$newzoneid=$line[0];
					$query = "DELETE FROM dns_record 
						WHERE zoneid='" . $newzoneid . "'
						AND type='A' AND val1='" . $newa . "' 
						AND val2='" . $newip . "'";
					$res = $db->query($query);
					if($db->error()){
						$this->error=$l['str_trouble_with_db'];
					}else{
						$result .= " " . $this->flagModified($newzoneid);
						$this->updateSerial($newzoneid);
					}
				}else{
					// zone exists, but not managed by current user.
					$result .= " " . 
					$l['str_primary_main_zone_not_managed_by_you'];
				}
			}else{
				$result .=
					sprintf($l['str_primary_not_managed_by_x'],
						$config->sitename) . "<br />";
			}
		}		

		$query = "DELETE FROM dns_record 
			WHERE zoneid='" . $this->zoneid . "'
			AND type='PTR' AND val1='" . $ip . "' 
			AND val2='" . $name . "'";
		$res = $db->query($query);
		if($db->error()){
			$this->error=$l['str_trouble_with_db'];
			$result .= $html->generic_error . $l['str_trouble_with_db'] .
						$html->generic_error_end 
						. '<br />';
		}else{
			$result .= $l['str_primary_deleting_ok'] . "<br />\n";
		}
		return $result;
	}

// *******************************************************
	
	//	Function DeleteMultipleARecords()
	/**
	 * Delete all the A records for a given name in current zone
	 *
	 *@access public
	 *@params name $name of the A records to delete
	 *@return result as a string text
	 */


	Function DeleteMultipleARecords($name){

		global $db,$html,$l;
		
		$query = "DELETE FROM dns_record 
			WHERE zoneid='" . $this->zoneid . "'
			AND type='A' AND val1='" . $name . "'";
						$result .= sprintf($l['str_primary_deleting_a_x'],
						stripslashes($newvalue)) . "...";
		$res = $db->query($query);
		if($db->error()){
			$this->error=$l['str_trouble_with_db'];
			$result .= $html->generic_error . $l['str_trouble_with_db'] .
						$html->generic_error_end 
						. '<br />';
		}else{
			$result .= $l['str_primary_deleting_ok'] . "<br />\n";
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
	Function Delete($delete,$updatereverse,$updatea){
		global $db;
		global  $html,$l;
				
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
						$result .= sprintf($l['str_primary_deleting_cname_x'],
						stripslashes($newvalue)) . "...";
						break;
					
					 
					case "a":
						// www		IN		A			IP
						preg_match("/^(.*)\/(.*)/",$newvalue,$item);
						$val1 = $item[1];
						$val2 = $item[2];
						$result .= $this->DeleteARecord($val1,$val2,$updatereverse);
						$query = "";
						break;


					case "aaaa":
						// www		IN		AAAA			IPv6
						preg_match("/^(.*)\/(.*)/",$newvalue,$item);
						$val1 = $item[1];
						$val2 = $item[2];
						$query = "DELETE FROM dns_record 
								WHERE zoneid='" . $this->zoneid . "'
								AND type='AAAA' AND val1='" . $val1 . "' 
								AND val2='" . $val2 . "'";
						$result .= sprintf($l['str_primary_deleting_aaaa_x'],
						stripslashes($newvalue)) . "...";
						break;

					case "txt":
						// www		IN		TXT			String
						preg_match("/^(.*)\/(.*)/",$newvalue,$item);
						$val1 = $item[1];
						$val2 = $item[2];
						$query = "DELETE FROM dns_record 
								WHERE zoneid='" . $this->zoneid . "'
								AND type='TXT' AND val1='" . $val1 . "' ";
						$result .= sprintf($l['str_primary_deleting_txt_x'],
						stripslashes($newvalue)) . "...";
						break;

					
					
					case "ptr":
						// ip		IN		PTR			name
						preg_match("/^(.*)\/(.*)/",$newvalue,$item);
						$val1 = $item[1];
						$val2 = $item[2];
						$result .= $this->DeletePTRRecord($val1,$val2,$updatea);
						$query = "";
						break;
						
					case "ns":
						// 		IN		NS		name
						$query = "DELETE FROM dns_record 
							WHERE zoneid='" . $this->zoneid . "'
							AND type='NS' AND val1='" . $newvalue . "'";
						$result .= sprintf($l['str_primary_deleting_ns_x'],
						stripslashes($newvalue)) . "...";
						break;

					case "mx":
						// * 		IN		MX		pref		name
						$query = "DELETE FROM dns_record 
						WHERE zoneid='" . $this->zoneid . "'
						AND type='MX' AND val1='" . $newvalue . "'";
						$result .= sprintf($l['str_primary_deleting_mx_x'],
						stripslashes($newvalue)) . "...";
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
						$result .= sprintf($l['str_primary_deleting_sub_zone_x'],
						stripslashes($newvalue)) . "...";
						break;
					case "delegate":
						// $newvalue: XX-YY
						list($from,$to) = split('-',$newvalue);
						// remove CNAMEs
						for($cnamecounter=$from;$cnamecounter<= $to; $cnamecounter++){
							$query = "DELETE FROM dns_record 
								WHERE zoneid='" . $this->zoneid . "'
								AND type='CNAME' AND val1='" . $cnamecounter . "'";
							$res = $db->query($query);
							if($db->error()){
								$this->error=$l['str_trouble_with_db'];
							}
						}
						
						// remove NS
						$query = "DELETE FROM dns_record WHERE zoneid='" . $this->zoneid . "'
								AND type='SUBNS' AND val1='" . $newvalue . "'";
						$res = $db->query($query);
						if($db->error()){
							$this->error=$l['str_trouble_with_db'];
						}
						
						// delete zone
						// use zoneDelete()
						$query = "SELECT userid FROM dns_zone WHERE zone='" 
								. $newvalue . "." . $this->zonename . "' AND zonetype='P'";
						$res = $db->query($query);
						$line=$db->fetch_row($res);
						$zonetodelete = new Zone($newvalue . "." . $this->zonename, 'P','',$line[0]);
						$zonetodelete->zoneDelete();
						
						// delete DELEGATE record
						$query = "DELETE FROM dns_record
									WHERE zoneid='" . $this->zoneid . "'
									AND type='DELEGATE' AND val1='" . $newvalue . "'";
						break;
				}
			}
			if(notnull($query)){
				$res = $db->query($query);
				if($db->error()){
					$this->error=$l['str_trouble_with_db'];
					$result .= $html->generic_error . $l['str_trouble_with_db'] .
								$html->generic_error_end 
								. '<br />';
				}else{
					$result .= $l['str_primary_deleting_ok'] . "<br />\n";
				}
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
		global $db, $html,$l;

		$result = '';
		// for each MX, add MX entry
		$i = 0;
		while(list($key,$value) = each($mx)){
			// value = name
			if($value != ""){
				if(!(checkDomain($value) || checkName($value))){
					// check if matching A record exists ? NOT OUR JOB
					$result .= ' ' . $html->generic_error . ' ' . 
					sprintf($l['str_primary_bad_mx_name'],
					stripslashes($value)) . $html->generic_error_end . "<br />\n";
					$this->error = $l['str_primary_data_error'];
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
						$result .= ' ' . $html->generic_error . ' ' . 
						sprintf($l['str_primary_preference_for_mx_x_has_to_be_int'],
							stripslashes($value)) . 
						$html->generic_error_end . '<br />';
						$this->error = $l['str_primary_data_error'];
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
							$result .= sprintf($l['str_primary_adding_mx_x'],
							stripslashes($value)) . "...";
							$ttlval = $this->DNSTTL($ttl[$i]);
							$query = "INSERT INTO dns_record (zoneid, type, val1, val2,ttl) 
								VALUES ('" . $this->zoneid . "', 'MX', '" 
								. $value . "', '" . $pref[$i] . "','" . $ttlval . "')";
							$db->query($query);
							if($db->error()){
								$result .= ' ' . $html->generic_error . 
								$l['str_trouble_with_db'] . 
								$html->generic_error_end . '<br />';
								$this->error = $l['str_trouble_with_db'];
							}else{
								$result .= $l['str_primary_ok'] . "<br />\n";
							}
						}else{ // record already exists
							$result .= 
								sprintf($l['str_primary_warning_mx_x_exists_not_overwritten'],
									stripslashes($value)) ."<br />\n";
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
		global $db,$html,$l;

		$result = '';
		$i=0;
		// for each NS, add NS entry
		while(list($key,$value) = each($ns)){
			// value = name
			if($value != ""){
				if(!checkDomain($value)){
					$result .= $html->generic_error . " " . 
					sprintf($l['str_primary_bad_ns_x'],
					stripslashes($value)) . $html->generic_error_end . '<br />';
					$this->error = $l['str_primary_data_error'];
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
						$result .= sprintf($l['str_primary_adding_ns_x'],
						stripslashes($value)) . "...";
						$ttlval = $this->DNSTTL($ttl[$i]);
						$query = "INSERT INTO dns_record (zoneid, type, val1,ttl) 
							VALUES ('" . $this->zoneid . "', 'NS', '" 
							. $value . "','" . $ttlval . "')";
						$db->query($query);
						if($db->error()){
							$result .= $html->generic_error .
							$l['str_trouble_with_db'] . $html->generic_error_end .
							'<br />';
							$this->error = $l['str_trouble_with_db'];
						}else{
							$result .= $l['str_primary_ok'] . "<br />\n";
						}
					}else{
						$result .= 
							sprintf($l['str_primary_warning_ns_x_exists_not_overwritten'],
									stripslashes($value)) . "<br />\n";
					}
				}
			}
			$i++;
		}
		return $result;
	}


// *******************************************************

//	Function AddARecord($zoneid,$a,$aname,$ttl,$updatereverse)
	/**
	 * Add an A record to given zone
	 *
	 *@access private
	 *@param int $zoneid id of zone
	 *@param string $a ip of A record
	 *@param string $aname name of A record
	 *@param int $ttl ttl value for this record
	 *@param int $updatereverse flag to update or not reverse zone
	 *@return string text of result (Adding A Record... Ok)
	 */
	Function AddARecord($zoneid,$a,$aname,$ttl,$updatereverse){
		global $db,$html,$config,$l;
		$result = '';
		// for each A, add A entry
		$i = 0;
		while(list($key,$value) = each($aname)){
			if($value != ""){
				if(! (checkAName($value) || ($value == $this->zonename.'.'))){
					$result .= $html->generic_error . " " . 
					sprintf($l['str_primary_bad_a_x'], 
					stripslashes($value)) . $html->generic_error_end . "<br />\n";
					$this->error = $l['str_primary_data_error'];
				}else{
					// a[$i] has to be an ip address
					if($a[$i] == ""){
						$result .= $html->generic_error . " " . 
						 sprintf($l['str_primary_no_ip_for'],
						stripslashes($value)) . $html->generic_error_end . 
						"<br />\n";
						$this->error = $l['str_primary_data_error'];
					}else{
						if(!checkIP($a[$i])){
							$result .= $html->generic_error . " "  . 
							sprintf($l['str_primary_x_ip_has_to_be_ip'],
							stripslashes($value)) . 
							$html->generic_error_end . "<br />\n";
							$this->error = $l['str_primary_data_error'];
						}else{
							// Check if record already exists
							$query = "SELECT count(*) FROM dns_record WHERE 
							zoneid='" . $zoneid . "' AND type='A' 
							AND val1='" . $value . "'";
							$res = $db->query($query);
							$line = $db->fetch_row($res);
							if($line[0] == 0){
								// check if CNAME record not already exists
								$query = "SELECT count(*) FROM dns_record WHERE 
								zoneid='" . $zoneid . "' AND type='CNAME' 
								AND val1='" . $value . "'";
								$res = $db->query($query);
								$line = $db->fetch_row($res);
								if($line[0] == 0){
									$result .= sprintf($l['str_primary_adding_a_x'],
									stripslashes($value)) . "...";
									$ttlval = $this->DNSTTL($ttl[$i]);
									$query = "INSERT INTO dns_record (zoneid, type, val1, val2,ttl) 
									VALUES ('" . $zoneid . "', 
									'A', '" . $value . "', '" . $a[$i] . "','" . $ttlval . "')";
									$db->query($query);
									if($db->error()){
										$result .= 
										$html->generic_error .
										$l['str_trouble_with_db'] .  
										$html->generic_error_end . "<br />\n";
										$this->error = $l['str_trouble_with_db'];
									}else{
										$result .= $l['str_primary_ok'] . "<br />\n";
										
										if($updatereverse){									
											$result .= $this->UpdateReversePTR($a[$i],$value,'A');
										} // end if updatereverse
									} // end "primary OK"	
								}else{ // end check CNAME
									$result .= 
										sprintf($l['str_primary_warning_cname_x_exists_not_overwritten'],
										stripslashes($value)) . "<br />\n";
								}
							}else{ // end check A
								
								// check if already same IP or not. If yes, do not
								// change anything 
								// if no, warn & assume it is round robin.
								$query .= " AND val2='" . $a[$i] . "'";
								$res = $db->query($query);
								$line = $db->fetch_row($res);
								if($line[0] == 0){
									$result .= sprintf($l['str_primary_warning_a_x_exists_with_diff_value'],
													stripslashes($value)) . ' ';
									$result .= sprintf($l['str_primary_adding_a_x'],
									stripslashes($value)) . "...";
									$ttlval = $this->DNSTTL($ttl[$i]);
									$query = "INSERT INTO dns_record (zoneid, type, val1, val2,ttl) 
									VALUES ('" . $zoneid . "', 
									'A', '" . $value . "', '" . $a[$i] . "','" . $ttlval . "')
									";
									$db->query($query);
									if($db->error()){
										$result .= $html->generic_error . $l['str_trouble_with_db'] .
										$html->generic_error_end . "<br />\n";
										$this->error = $l['str_trouble_with_db'];
									}else{
										$result .= $l['str_primary_ok'] . "<br />\n";									
										if($updatereverse){	
											$result .= $this->UpdateReversePTR($a[$i],$value,'A');
										} // end updatereverse
									} // end primary ok

								}else{
									$result .= sprintf($l['str_primary_a_x_with_same_ip'],
									 				stripslashes($value)). '<br />';
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

//	Function AddAAAARecord($aaaa,$aaaaname,$ttl,$updatereverse)
	/**
	 * Add an AAAA record to the current zone
	 *
	 *@access private
	 *@param string $aaaa ipv6 of AAAA record
	 *@param string $aaaaname name of AAAA record
	 *@param int $ttl ttl value for this record
	 *@param int $updatereverse flag to update or not reverse zone
	 *@return string text of result (Adding AAAA Record... Ok)
	 */
	Function AddAAAARecord($zoneid,$aaaa,$aaaaname,$ttl,$updatereverse){
		global $db,$config,$html,$l;

		$result = '';
		// for each AAAA, add AAAA entry
		$i = 0;
		while(list($key,$value) = each($aaaaname)){
			if($value != ""){
				if(! (checkAName($value) || ($value == $this->zonename.'.'))){
					$result .= $html->generic_error .  sprintf($l['str_primary_aaaa_bad_aaaa_x'],
					stripslashes($value)) . $html->generic_error_end . "<br />\n";
					$this->error = $l['str_primary_data_error'];
				}else{
					// a[$i] has to be an ipv6 address
					if($aaaa[$i] == ""){
						$result .= $html->generic_error . sprintf($l['str_primary_no_ipv6_for_x'],
						stripslashes($value)) . $html->generic_error_end . "<br />\n";
						$this->error = $l['str_primary_data_error'];
					}else{
						if(!checkIPv6($aaaa[$i])){
							$result .= $html->generic_error . 
								sprintf($l['str_primary_x_ip_has_to_be_ipv6'],
								stripslashes($value)) . 
								$html->generic_error_end . "<br />\n";
							$this->error = $l['str_primary_data_error'];
						}else{
							// Check if record already exists
							$query = "SELECT count(*) FROM dns_record WHERE 
							zoneid='" . $this->zoneid . "' AND type='AAAA' 
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
									$result .= sprintf($l['str_primary_adding_aaaa_x'], 
									stripslashes($value)) . "...";
									$ttlval = $this->DNSTTL($ttl[$i]);
									$query = "INSERT INTO dns_record (zoneid, type, val1, val2,ttl) 
									VALUES ('" . $this->zoneid . "', 
									'AAAA', '" . $value . "', '" . $aaaa[$i] . "','" . $ttlval . "')";
									$db->query($query);
									if($db->error()){
										$result .= $html->generic_error . $l['str_trouble_with_db'] .
										$html->generic_error_end . "<br />\n";
										$this->error = $l['str_trouble_with_db'];
									}else{
										$result .= " " . $l['str_primary_ok'] . "<br />\n";

										if($updatereverse){
											$result .= $this->UpdateReversePTR($aaaa[$i],$value,'AAAA');
                                                                                } // end if updatereverse
									} // end "primary OK"	
								}else{ // end check CNAME
									$result .= sprintf($l['str_primary_warning_cname_x_exists_not_overwritten'],
									stripslashes($value)) . "<br />\n";
								}
							}else{ // end check AAAA
								
								// check if already same IP or not. If yes, do not
								// change anything 
								// if no, warn & assume it is round robin.
								$query .= " AND val2='" . $aaaa[$i] . "'";
								$res = $db->query($query);
								$line = $db->fetch_row($res);
								if($line[0] == 0){
									$result .=
										sprintf($l['str_primary_warning_aaaa_x_exists_with_diff_value'],
												stripslashes($value)) . ' ';
									$result .= sprintf($l['str_primary_adding_aaaa_x'],
													stripslashes($value)) . "...";
									$ttlval = $this->DNSTTL($ttl[$i]);
									$query = "INSERT INTO dns_record (zoneid, type, val1, val2,ttl) 
									VALUES ('" . $this->zoneid . "', 
									'AAAA', '" . $value . "', '" . $aaaa[$i] . "','" . $ttlval . "')
									";
									$db->query($query);
									if($db->error()){
										$result .= $html->generic_error . $l['str_trouble_with_db'] .
										$html->generic_error_end . "<br />\n";
										$this->error = $l['str_trouble_with_db'];
									}else{
										$result .= $l['str_primary_ok'] . "<br />\n";
										if($updatereverse){
                                                                                        $result .= $this->UpdateReversePTR($aaaa[$i],$value,'AAAA');
                                                                                } // end updatereverse
									}	

								}else{
									$result .= sprintf($l['str_primary_aaaa_x_with_same_ip'],
													stripslashes($value)) . '<br />';
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

//	Function AddPTRRecord($zoneid,$ptr,$ptrname,$ttl,$updatereverse)
	/**
	 * Add a PTR record to the $zoneid zone
	 * $zoneid param added for easiest reverse automatic filling.
	 *
	 *@access private
	 *@param string $zoneid id of zone in which PTR has to be added
	 *@param string $ptr ip of PTR record
	 *@param string $ptrname name of PTR record
	 *@param int $ttl ttl value for this record
	 *@param int $updatereverse to try to update or not matching A record
	 *@return string text of result (Adding PTR Record... Ok)
	 */
	Function AddPTRRecord($zoneid,$ptr,$ptrname,$ttl,$updatereverse){
		global $db, $html,$l;
				
		$result = '';
		// for each PTR, add PTR entry
		$i = 0;
		while(list($key,$value) = each($ptr)){
			if($value != ""){
				if((!$this->user->ipv6 && (ereg("[a-zA-Z]",$value) || ($value > 254))) || ($this->user->ipv6 &&
				!checkIPv6($value))){
					$result .= $html->generic_error . sprintf($l['str_primary_bad_ptr_x'],
					stripslashes($value)) . $html->generic_error_end . "<br />\n";
					$this->error = $l['str_primary_data_error'];
				}else{
					if($ptrname[$i] == ""){
						$result .= $html->generic_error . sprintf($l['str_primary_no_name_for_x'],
						stripslashes($value)) . $html->generic_error_end . "<br />\n";
						$this->error = "Data error";
					}else{
						if(!checkZoneWithDot($ptrname[$i])){
							$result .= $html->generic_error . 
							sprintf($l['str_primary_x_name_has_to_be_fully_qualified_x'],	
								stripslashes($value),$ptrname[$i]) . 
								$html->generic_error_end . "<br />\n";
							$this->error = $l['str_primary_data_error'];
						}else{
							// Check if record already exists
							$query = "SELECT count(*) FROM dns_record WHERE 
							zoneid='" . $zoneid . "' AND type='PTR' 
							AND val1='" . $value . "'";
							$res = $db->query($query);
							$line = $db->fetch_row($res);
							if($line[0] == 0){
								// check if CNAME record not already exists
								$query = "SELECT count(*) FROM dns_record WHERE 
								zoneid='" . $zoneid . "' AND type='CNAME' 
								AND val1='" . $value . "'";
								$res = $db->query($query);
								$line = $db->fetch_row($res);
								if($line[0] == 0){
									$result .= sprintf($l['str_primary_adding_ptr_x'],
									stripslashes($value)) . "...";
									$ttlval = $this->DNSTTL($ttl[$i]);
									$query = "INSERT INTO dns_record (zoneid, type, val1, val2,ttl) 
									VALUES ('" . $zoneid . "', 
									'PTR', '" . $value . "', '" . $ptrname[$i] . "','" . $ttlval . "')";
									$db->query($query);
									if($db->error()){
										$result .= $html->generic_error . 
										$l['str_trouble_with_db'] . $html->generic_error_end . "<br />\n";
										$this->error = $l['str_trouble_with_db'];
									}else{
										$result .= $l['str_primary_ok'] . "<br />\n";
										
										// update associated A record
										if($updatereverse){									
											// if "normal" zone is managed by current user, update A 
											// remove all before first dot, and last char.
											$newzone = substr(substr(strstr($ptrname[$i],'.'),1),0,-1);
											$newa = substr($ptrname[$i],0,strlen($ptrname[$i]) - strlen($newzone) -2);
											// construct new IP
											// zone *.in-addr.arpa or *.ip6.int
											$iplist = split('\.',strrev(
																	substr(
																		strstr(
																			substr(
																				strstr(
																					strrev(
																						$this->zonename
																					),
																				'.'),
																			1),
																		'.'),
																	1)
																)
															);
											$newip = "";
											$count = 0; // we have to count in case of zub-zones aa.bb.cc.dd-ee
											while($ipitem = array_pop($iplist)){
												$count++;
												if(count < 4){
													$newip .= "." . $ipitem;
												}
											}
											$newip = substr($newip,1) . "." . $value;
											$result .= sprintf($l['str_primary_looking_for_zone_x'],$newzone). "...";
											if($this->Exists($newzone,'P')){
												$alluserzones = $this->user->listallzones();
												$ismanaged=0;
												while($userzones = array_pop($alluserzones)){
													if(!strcmp($newzone,$userzones[0])){
														$ismanaged=1;
													}
												}
												if($ismanaged){
													// modification allowed because same owner
													// looking for zoneid
													$result .= " " . $l['str_primary_zone_managed_by_you'];
													$query = "SELECT id FROM dns_zone 
														WHERE zone='" . $newzone . "' AND zonetype='P'";
													$res = $db->query($query);
													$line = $db->fetch_row($res);
													$newzoneid=$line[0];
													$result .= " " . $this->AddARecord($newzoneid,array($newip),array($newa),
														array($l['str_primary_default']),NULL);
													if(!$this->error){
														$result .= " " . $this->flagModified($newzoneid);
														$this->updateSerial($newzoneid);
													}
												}else{
													// zone exists, but not managed by current user.
													$result .= " " . 
													$l['str_primary_main_zone_not_managed_by_you'];
												}
											}else{
												$result .=
													sprintf($l['str_primary_not_managed_by_x'],
														$config->sitename) . "<br />";
									 		}
										} // end update reverse
									} // update OK 
								}else{ // end check CNAME
									$result .= sprintf($l['str_primary_warning_cname_x_exists_not_overwritten'],
									stripslashes($value)) . "<br />\n";
								}
							}else{ // end check A
								
								// check if already same name or not. If yes, do not
								// change anything 
								// if no, warn & assume it is round robin.
								$query .= " AND val2='" . $ptrname[$i] . "'";
								$res = $db->query($query);
								$line = $db->fetch_row($res);
								if($line[0] == 0){
									$result .= sprintf($l['str_primary_warning_ptr_x_exists_with_diff_value'],
												stripslashes($value)) . ' ';
									$result .=  sprintf($l['str_primary_adding_ptr_x'],
									stripslashes($value)) . "...";
									$ttlval = $this->DNSTTL($ttl[$i]);
									$query = "INSERT INTO dns_record (zoneid, type, val1, val2,ttl) 
									VALUES ('" . $zoneid . "', 
									'PTR', '" . $value . "', '" . $ptrname[$i] . "','" . $ttlval . "')
									";
									$db->query($query);
									if($db->error()){
										$result .= $html->generic_error . 
										$l['str_trouble_with_db'] . $html->generic_error_end . "<br />\n";
										$this->error = $l['str_trouble_with_db'];
									}else{
										$result .= $l['str_primary_ok'] . "<br />\n";
										// update associated A record
										if($updatereverse){									
											// if "normal" zone is managed by current user, update A 
											// remove all before first dot, and last char.
											$newzone = substr(substr(strstr($ptrname[$i],'.'),1),0,-1);
											$newa = substr($ptrname[$i],0,strlen($ptrname[$i]) - strlen($newzone) -2);
											// construct new IP
											// zone *.in-addr.arpa or *.ip6.int
											$iplist = split('\.',strrev(
																	substr(
																		strstr(
																			substr(
																				strstr(
																					strrev(
																						$this->zonename
																					),
																				'.'),
																			1),
																		'.'),
																	1)
																)
															);
											$newip = "";
											$count = 0; // we have to count in case of zub-zones aa.bb.cc.dd-ee
											while($ipitem = array_pop($iplist)){
												$count++;
												if(count < 4){
													$newip .= "." . $ipitem;
												}
											}
											$newip = substr($newip,1) . "." . $value;
											$result .= sprintf($l['str_primary_looking_for_zone_x'],$newzone). "...";
											if($this->Exists($newzone,'P')){
												$alluserzones = $this->user->listallzones();
												$ismanaged=0;
												while($userzones = array_pop($alluserzones)){
													if(!strcmp($newzone,$userzones[0])){
														$ismanaged=1;
													}
												}
												if($ismanaged){
													// modification allowed because same owner
													// looking for zoneid
													$result .= " " . $l['str_primary_zone_managed_by_you'];
													$query = "SELECT id FROM dns_zone 
														WHERE zone='" . $newzone . "' AND zonetype='P'";
													$res = $db->query($query);
													$line = $db->fetch_row($res);
													$newzoneid=$line[0];
													$result .= " " . $this->AddARecord($newzoneid,array($newip),array($newa),
														array($l['str_primary_default']),NULL);
													if(!$this->error){
														$result .= " " . $this->flagModified($newzoneid);
														$this->updateSerial($newzoneid);
													}
												}else{
													// zone exists, but not managed by current user.
													$result .= " " . 
													$l['str_primary_main_zone_not_managed_by_you'];
												}
											}else{
												$result .=
													sprintf($l['str_primary_not_managed_by_x'],
														$config->sitename) . "<br />";
									 		}
										} // end update reverse
										
									}	

								}else{
									$result .= sprintf($l['str_primary_warning_ptr_x_already_exists_not_overwritten'],
													stripslashes($value)) . '<br />';
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
		global $db, $html,$l;
				
				// for each CNAME, add CNAME entry
		$i = 0;
		$result = "";
		while(list($key,$value) = each($cname)){
			if($value != ""){	
				if(!checkCName($value) || checkIP($cnamea[$i]) || !(checkDomain($cnamea[$i]) || checkName($cnamea[$i]))){
					$result .= $html->generic_error . sprintf($l['str_primary_bad_cname_x'],
					stripslashes($value)) . $html->generic_error_end . "<br />\n";
					$this->error = $l['str_primary_data_error'];
				}else{
					if($cnamea[$i] ==""){
						$result .= $html->generic_error . sprintf($l['str_primary_no_record'],
						stripslashes($value)) . $html->generic_error_end . "<br />\n";
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
								$result .= sprintf($l['str_primary_adding_cname_x'],
								stripslashes($value)) . "...";
								$ttlval = $this->DNSTTL($ttl[$i]);
								$query = "INSERT INTO dns_record (zoneid, type, val1, val2,ttl) 
								VALUES ('" . $this->zoneid . "', 'CNAME', '"
								 . $value . "', '" . $cnamea[$i] . "','" . $ttlval . "')
								";
								$db->query($query);
								if($db->error()){
									$result .= $html->generic_error . $l['str_trouble_with_db'] . 
									$html->generic_error_end . '<br />';
									$this->error = $l['str_trouble_with_db'];
								}else{
									$result .= $l['str_primary_ok'] . "<br />\n";	
								}
							}else{ // A record present
								$result .= sprintf($l['str_primary_warning_a_x_exists_not_overwritten'],
								stripslashes($value)) . "<br />\n";
							}							
						}else{
							$result .= sprintf($l['str_primary_warning_cname_x_exists_not_overwritten'],
											stripslashes($value)) . "<br />\n";
						}
					}
				}
			}
			$i++;
		}
		return $result;
	}

		
// *******************************************************

//	Function AddTXTRecord($txt,$txtstring,$ttl)
	/**
	 * Add a TXT record to the current zone
	 *
	 *@access private
	 *@param string $txt name of TXT record
	 *@param string $txtstring string pointed by this TXT record
	 *@param int $ttl ttl value for this record
	 *@return string text of result (Adding TXT Record... Ok)
	 */
	Function AddTXTRecord($txt,$txtstring,$ttl){
		global $db, $html,$l;
				
				// for each TXT, add TXT entry
		$i = 0;
		$result = "";
		while(list($key,$value) = each($txt)){
			if($value != ""){	
				if(!checkCName($value)){
				$result .= "VALUE: $value";
					$result .= $html->generic_error . sprintf($l['str_primary_bad_txt_x'],
					stripslashes($value)) . $html->generic_error_end . "<br />\n";
					$this->error = $l['str_primary_data_error'];
				}else{
					if($txtstring[$i] ==""){
						$result .= $html->generic_error . sprintf($l['str_primary_no_record'],
						stripslashes($value)) . $html->generic_error_end . "<br />\n";
						$this->error = 1;
					}else{
						// Check if CNAME record already exists
						$query = "SELECT count(*) FROM dns_record WHERE 
						zoneid='" . $this->zoneid . "' AND type='CNAME' 
						AND val1='" . $value . "'";
						$res = $db->query($query);
						$line = $db->fetch_row($res);
						if($line[0] == 0){
							$result .= sprintf($l['str_primary_adding_txt_x'],
							stripslashes($value)) . "...";
							// suppress all quotes, and add new ones
							$newstring = preg_replace("/\"/","",stripslashes($txtstring[$i]));
							$ttlval = $this->DNSTTL($ttl[$i]);
							$query = "INSERT INTO dns_record (zoneid, type, val1, val2,ttl) 
							VALUES ('" . $this->zoneid . "', 'TXT', '"
							 . $value . "', '\"" . $newstring . "\"','" . $ttlval . "')
							";
							$db->query($query);
							if($db->error()){
								$result .= $html->generic_error . $l['str_trouble_with_db'] . 
								$html->generic_error_end . '<br />';
								$this->error = $l['str_trouble_with_db'];
							}else{
								$result .= $l['str_primary_ok'] . "<br />\n";	
							}
						}else{
							$result .= sprintf($l['str_primary_warning_cname_x_exists_not_overwritten'],
											stripslashes($value)) . "<br />\n";
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
		global $db, $html,$l;

		// for each SUBNS, add NS entry
		$i = 0;
		$result = "";
		while(list($key,$value) = each($subns)){
			if($value != ""){	
				if(!checkName($value)){
					$result .= $html->generic_error . sprintf($l['str_bad_zone_name_x'],
					' ' . stripslashes($value)) . $html->generic_error_end . "<br />\n";
					$this->error = $l['str_primary_data_error'];
				}else{
					if($subnsa[$i] =="" || !(checkDomain($subnsa[$i]) || checkName($subnsa[$i]))){
						if($subnsa[$i] ==""){
							$result .= $html->generic_error . ' ' . sprintf($l['str_primary_no_ns_x'], 
							stripslashes($value)) . $html->generic_error_end . "<br />\n";
						}else{
							$result .= $html->generic_error . ' ' . sprintf($l['str_primary_bad_ns_x'],
                                                        stripslashes($subnsa[$i])) . $html->generic_error_end . "<br />\n";
						}
						$this->error = 1;
					}else{
						// Check if record already exists
						// if yes, no problem - multiple different NS possible
						$result .= sprintf($l['str_primary_adding_zone_ns_x'],
						stripslashes($value)) . "...";
						$query = "SELECT count(*) FROM dns_record 
						WHERE zoneid='" . $this->zoneid . "' AND type='SUBNS' 
						AND val1='" . $value . "' AND val2='" . $subnsa[$i] . "'";
						$res=$db->query($query);
						$line = $db->fetch_row($res);
						if($db->error()){
							$result .= $html->generic_error . $l['str_trouble_with_db'] . 
							$html->generic_error_end . '<br />';
							$this->error = $l['str_trouble_with_db'];
						}else{
							if($line[0]==0){
								$ttlval=$this->DNSTTL($ttl[$i]);
								$query = "INSERT INTO dns_record (zoneid, type, val1, val2,ttl) 
								VALUES ('" . $this->zoneid . "', 'SUBNS', '"
								 . $value . "', '" . $subnsa[$i] . "','" . $ttlval . "')
								";
								$db->query($query);
								if($db->error()){
									$result .= $html->generic_error . $l['str_trouble_with_db'] .
									$html->generic_error_end . '<br />';
									$this->error = $l['str_trouble_with_db'];
								}else{
									$result .= $l['str_primary_ok'] . "<br />\n";	
								}
							}else{
								$result .=sprintf($l['str_primary_warning_ns_x_exists_not_overwritten'],
											stripslashes($value)) . "<br />\n";
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

//	Function AddDELEGATERecord($delegatefrom,$delegateto,$delegateuser,$ttl)
	/**
	 * Add a delegation to the current zone
	 *
	 *@access private
	 *@param string $delegatefrom lower limit of range
	 *@param string $delegateto upper limit of range
	 *@param string $delegateuser user to delegate zone to
	 *@param int $ttl ttl value for this record
	 *@return string text of result (Adding DELEGATE Record... Ok)
	 */
	Function AddDELEGATERecord($delegatefrom,$delegateto,$delegateuser,$ttl){
		global $db,$html,$l;

		$i = 0;
		$result = "";
		while(list($key,$value) = each($delegatefrom)){
			if(notnull($value)){
				$result .= sprintf($l['str_primary_adding_delegate_x'],
				stripslashes($value),$delegateto[$i]) . "...";
				if(!ereg('^[0-9]+$',$value)){
					$result .= $html->generic_error . sprintf($l['str_primary_bad_lower_limit_x'],
					stripslashes($value)) . $html->generic_error_end . "<br />\n";
					$this->error = $l['str_primary_data_error'];
				}else{
					if(!ereg('^[0-9]+$',$delegateto[$i])||$delegateto[$i]>255){
						$result .= $html->generic_error . sprintf($l['str_primary_bad_upper_limit_x'],
						stripslashes($delegateto[$i])) . $html->generic_error_end . "<br />\n";
						$this->error = $l['str_primary_data_error'];
					}else{
						// check if lower if below upper
						if(!($value <= $delegateto[$i])){
							$result .= $html->generic_error . sprintf($l['str_primary_bad_limits_x_x'],
							stripslashes($value),stripslashes($delegateto[$i])) . 
							$html->generic_error_end . "<br />\n";
							$this->error = $l['str_primary_data_error'];		
						}else{
							if(!notnull($delegateuser[$i])){
								$result .= $html->generic_error . 
								$l['str_primary_no_user_for_delegation'] . $html->generic_error_end . 
								'<br />';
								$this->error = $l['str_primary_data_error'];
							}else{
								// check if user is in DB or not
								$query = "SELECT id FROM dns_user WHERE 
											login='" . addslashes($delegateuser[$i]) . "'";
								$res=$db->query($query);
								$line=$db->fetch_row($res);
								if($db->error()){
									$result .= $html->generic_error . $l['str_trouble_with_db'] . 
									$html->generic_error_end . '<br />';
									$this->error = $l['str_trouble_with_db'];
								}else{
									if(!$line[0]){
										$result .= $html->generic_error . 
										sprintf($l['str_primary_delegate_user_x_doesnot_exist'],
											stripslashes($delegateuser[$i])) . 
										$html->generic_error_end . '<br />';
										$this->error = $l['str_primary_data_error'];
									}else{ // user exists
										$newuserid=$line[0];
										// check if items inside this range are already registered or not
										$query = "SELECT val1 FROM dns_record WHERE zoneid='" .
											$this->zoneid . "' AND type='DELEGATE'";
										$res=$db->query($query);
										if($db->error()){
											$result .= $html->generic_error . $l['str_trouble_with_db']
											. $html->generic_error_end . '<br />';
											$this->error = $l['str_trouble_with_db'];
										}else{
											while($line = $db->fetch_row($res)){
												list($from,$to)=split('-',$line[0]);
												if(
												(($from <= $value) && ($to >= $value)) ||
												(($from >= $value) && ($from <= $delegateto[$i]))
												){
													$result .= $html->generic_error . 
													sprintf($l['str_primary_delegate_bad_limits_x_x_overlaps_existing_x_x'],
														stripslashes($value),stripslashes($delegateto[$i]), 
														$from,$to) . 
													$html->generic_error_end . "<br />\n";
													$this->error = $l['str_primary_data_error'];
												}
											}
											if(!$this->error){
												$ttlval = $this->DNSTTL($ttl[$i]);
												$query = "INSERT INTO dns_record (zoneid, type, val1, val2,ttl) 
												VALUES ('" . $this->zoneid . "', 'DELEGATE', '"
												 . $value . "-" . $delegateto[$i] . "','" .
												 stripslashes($delegateuser[$i]) . "','" . $ttlval . "')
												";
												$db->query($query);
												if($db->error()){
													$result .= $html->generic_error .
													$l['str_trouble_with_db'] . 
													$html->generic_error_end . '<br />';
													$this->error = $l['str_trouble_with_db'];
												}else{
													// create zone, affect it to delegateuser
													// Can NOT use standard create way because
													// of EXIST check. BUG: can not insert userlog
													$query = "INSERT INTO dns_zone 
																(zone,zonetype,userid)
													VALUES ('".$value . "-" . $delegateto[$i] . "." . 
													 $this->zonename."','P','".$newuserid."')";
													$res = $db->query($query);
													if($db->error()){
														$this->error = $l['str_trouble_with_db'];
													}else{
														// create dns_confprimary records
														// NO - user has to modify it manually
														// create NS records
														$nskeys = array_keys($this->ns);
														while($nskey = array_shift($nskeys)){
															$query = "INSERT INTO dns_record
																(zoneid,type,val1,val2,ttl)
																VALUES ('" . $this->zoneid . "',
																'SUBNS','" . $value . "-" . $delegateto[$i]
																. "','" . $nskey . "','" .
																	$this->nsttl[$nskey] . "')";
															$res = $db->query($query);
															if($db->error()){
																$this->error = $l['str_trouble_with_db'];
															}
														}

														// create CNAME records
														$newzone = new Zone($value . "-" . $delegateto[$i] . "." . 
													 		$this->zonename, 'P','',$newuserid);
														$newzone->retrieveID($value . "-" . $delegateto[$i] . "." . 
													 		$this->zonename,'P');
														
														for($cnamecounter=$value;
																$cnamecounter <= $delegateto[$i];
																$cnamecounter++){
															$query = "INSERT INTO dns_record 
																		(zoneid, type, val1, val2,ttl) 
																		VALUES 
																		('" . $this->zoneid . "', 
																		'CNAME', '" . $cnamecounter . "',
																		'" . $cnamecounter . "." . $value . "-" . $delegateto[$i] . "." . 
																 		$this->zonename . ".',
																		'" . $ttlval . "')
																		";
															$db->query($query);
															if($db->error()){
																$result .= $html->generic_error . 
																$l['str_trouble_with_db'] . 
																$html->generic_error_end . '<br />';
																$this->error = $l['str_trouble_with_db'];
															}
														} // end for each CNAME
														if(!$this->error){
															$result .= $l['str_primary_ok'] . "<br />\n";
														}
													}
												}
											} // no error
										} // else db error
									} // user exists
								} // no db error
							} // delegateuser not null								
						} // $from < $to
					} // bad upper limit
				} // bad lower limit
			} // not null
			$i++;
		} // while
		return $result;
	}


// *******************************************************
// 	Function UpdateReversePTR($a,$value)
	/**
	 * Update PTR when modifying A or AAAA
	 *
	 * @access private
	 * @return string 1 if success, 0 If DB error, string of error else
	 */

	Function UpdateReversePTR($a,$value,$type) {
		global $l,$db, $config;

		// if reverse IP is managed by current user, update PTR
		// else check if reverse IP delegation exists (ie as CNAME)
		$result .= $l['str_primary_looking_for_reverse'] . "...";
		// construct reverse zone
		if(!strcmp($type,"A")){
			$ipsplit = split('\.',$a);
			$reversezone="";
			$firstip=0;
			while(($reverseipvalue = array_pop($ipsplit)) !== NULL){
				if($firstip){
					$reversezone .= $reverseipvalue . ".";
				}else{
					$firstip = $reverseipvalue;
				}
			}
			$reversezone .= "in-addr.arpa";
		}else{ // not A, then AAAA
			$ip = ConvertIPv6toDotted($a);
			$ipsplit = split('\.',$ip);
			$reversezone="";
			$firstip=0;
			reset($ipsplit);
			// remove first element (has to be modified)
			while(($reverseipvalue = array_pop($ipsplit)) !== NULL ){
				if($firstip){
					$reversezone .= $reverseipvalue . ".";
				}else{
					$firstip = $reverseipvalue;
				}
			}
			$reversezone .= "ip6.arpa";
		}

		// TODO needed to recognize upper than a dot away for IPv6
		if($this->Exists($reversezone,'P')){
			$alluserzones = $this->user->listallzones();
			$ismanaged=0;
			while($userzones = array_pop($alluserzones)){
				if(!strcmp($reversezone,$userzones[0])){
					$ismanaged=1;
				}
			}

			if($ismanaged){
				// modification allowed because same owner
				// looking for zoneid
				$result .= " " . $l['str_primary_zone_managed_by_you'];
				$query = "SELECT id FROM dns_zone 
					WHERE zone='" . $reversezone . "' AND zonetype='P'";
				$res = $db->query($query);
				$line = $db->fetch_row($res);
				$newzoneid=$line[0];
				$result .= " " . $this->AddPTRRecord($newzoneid,array($firstip),array($value .
						"." . $this->zonename . "."),array($l['str_primary_default']),NULL);
				if(!$this->error){
					$result .= " " . $this->flagModified($newzoneid);
					$this->updateSerial($newzoneid);
				}
			}else{
				// zone exists, but not managed by current user.
				// check for subzone managed by current user
				$result .= " " .
				$l['str_primary_main_zone_not_managed_by_you'] . "...";
				$query = "SELECT zone,id FROM dns_zone WHERE
						userid='" . $this->user->userid . "'
						AND zone like '%." . $reversezone . "'";
				$res = $db->query($query);
				$newzoneid = 0;
				while($line = $db->fetch_row($res)){
					$range =array_pop(array_reverse(split('\.',$line[0])));
					list($from,$to) = split('-',$range);
					if(($firstip >= $from) && ($firstip <= $to)){
						$newzoneid=$line[1];
					}
				}
				if($newzoneid){
					$result .= " " . $this->AddPTRRecord($newzoneid,array($firstip),array($value .
							"." . $this->zonename . "."),array($l['str_primary_default']),NULL);
					if(!$this->error){
						$result .= " " . $this->flagModified($newzoneid);
						$this->updateSerial($newzoneid);
					}
				}else{
					// no zone found
					$result .= " " .
							$l['str_primary_reverse_exists_but_ip_not_manageable'] . "<br />";
				}
			}
		}else{
			$result .= sprintf($l['str_primary_not_managed_by_x'],
					$config->sitename) . "<br />";
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
		global $db, $l;

		$result ="";
		$localerror=0;
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
				$localerror = 1;
				$this->error = $l['str_primary_soa_invalid_xfer'];
			}
		}else{
			$xferip='any';
		}
	
		if(!$localerror){
	
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
				$query = "SELECT count(*) FROM dns_confprimary WHERE zoneid='" . $this->zoneid . "'";
	                        $res = $db->query($query);
        	                if($db->error()){
                	                $this->error=$l['str_trouble_with_db'];
                        	        return 0;
	                        }
				$line = $db->fetch_row($res);
				if($line[0] != 0){
                        	        $this->error=$l['str_zone_already_exists'] . "ICI";
                                	return 0;
				} 
 
				$query = "INSERT INTO dns_confprimary (zoneid,serial,xfer,refresh,
						retry,expiry,minimum,defaultttl)
				VALUES ('" . $this->zoneid . "','" . $this->serial . "','" . $xferip . "'
				,'" . $soarefresh . "','" . $soaretry . "','" . $soaexpire . "','" .
				$soaminimum . "','" . $defaultttl . "')";
			}
			$res = $db->query($query);
			if($db->error()){
				$this->error=$l['str_trouble_with_db'];
				return 0;
			}
			return 1;
		}else{
			return 0;
		}
		return 0;
		
	}


// *******************************************************
	
	//	Function getArecords()
	/**
	 * Get all the A records with a given name in current zone
	 * 
	 *
	 *@access public
	 *@params Address of the array to fill (&$arecs) and name of the A records ($name)
	 *@return O (error) or 1 (success)
	 */

	Function getArecords(&$arecs, $name) {
		global $db,$l;
		$this->error='';
		$query = "SELECT val2 
			FROM dns_record 
			WHERE zoneid='" . $this->zoneid . "'
			AND type='A' AND val1='" . $name . "'";
		$res =  $db->query($query);
		$arecs = array();
		$i=0;
		while($line = $db->fetch_row($res)){
			if($db->error()){
				$this->error=$l['str_trouble_with_db'];
				return 0;
			}
			$arecs[$i]=$line[0];
			$i++;
		}
		return 1;
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
		global $db,$l;
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
				$this->error=$l['str_trouble_with_db'];
				return 0;
			}
			$arraytofill[$line[0]]=$line[1];
			$ttltofill[$line[0]] = ($line[2]=="default"?"-1":$line[2]);
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
		global $db,$l;
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
				$this->error=$l['str_trouble_with_db'];
				return 0;
			}
			$array1tofill[$i]=$line[0];
			$array2tofill[$i]=stripslashes($line[1]);
			$ttltofill[$i]=($line[2]=="default"?"-1":$line[2]);
			$i++;
		}
	}

// *******************************************************	
//	Function TempZoneFile()
	/**
	 * Generate the file name (with whole path)
	 *
	 *@access private
	 *@return string file
	 */
	Function tempZoneFile(){
		global $config;
		return ("{$config->tmpdir}{$this->zonename}.{$this->zonetype}");
	}

// *******************************************************	
//	Function generateConfigFile()
	/**
	 * Generate a temporary config file in $this->tempZoneFile()
	 *
	 *@access private
	 *@return int 1
	 */
	Function generateConfigFile(){
		global $config,$l;
		// reinitialize every records after add/delete/modify
		// fill in with records
		$this->RetrieveRecords('NS',$this->ns,$this->nsttl);
		$this->RetrieveRecords('MX',$this->mx,$this->mxttl);
		$this->RetrieveMultiRecords('SUBNS',$this->subns,$this->subnsa,$this->subnsttl);
		$this->RetrieveRecords('CNAME',$this->cname,$this->cnamettl);
		$this->RetrieveMultiRecords('TXT',$this->txt,$this->txtdata,$this->txtttl);
		if($this->reversezone){
			$this->RetrieveMultiRecords('PTR',$this->ptr,$this->ptrname,$this->ptrttl);
			$this->RetrieveMultiRecords('DELEGATE',$this->delegatefromto,$this->delegateuser,$this->delegatettl);
		}else{
			$this->RetrieveRecords('DNAME',$this->dname,$this->dnamettl);
			$this->RetrieveMultiRecords('A',$this->a,$this->aip,$this->attl);
			$this->RetrieveRecords('A6',$this->a6,$this->a6ttl);
			$this->RetrieveMultiRecords('AAAA',$this->aaaa,$this->aaaaip,$this->aaaattl);
		}
		// select SOA items
		$fd = fopen($this->tempZoneFile(),"w");
		if ($fd == 0)
		{
			$this->error = sprintf($l['str_can_not_open_x_for_writing'],
								$this->tempZoneFile());
			return -1;
		}
		$this->generateSOA($this->defaultttl,$config->nsname,$this->zonename,
							$this->user->Retrievemail(), $this->serial,
							$this->refresh,$this->retry,$this->expiry,$this->minimum,$fd);
							
		// retrieve & print NS
		$this->generateConfig("NS",$this->ns,$this->nsttl,$fd);
		// retrieve & print MX
		$this->generateConfig("MX",$this->mx,$this->mxttl,$fd);
				
		if($this->reversezone){
			// retrieve & print PTR
			$this->generateMultiConfig("PTR",$this->ptr,$this->ptrname,$this->ptrttl,$fd);
		}else{ // end reverse zone
			// retrieve & print A
			$this->generateMultiConfig("A",$this->a,$this->aip,$this->attl,$fd);
			// retrieve & print AAAA
			$this->generateMultiConfig("AAAA",$this->aaaa,$this->aaaaip,$this->aaaattl,$fd);
		} // end not reverse zone
		
		$this->generateConfig("CNAME",$this->cname,$this->cnamettl,$fd);
		$this->generateMultiConfig("TXT",$this->txt,$this->txtdata,$this->txtttl,$fd);

		// retrieve & print SUBNS
		$this->generateMultiConfig("NS",$this->subns,$this->subnsa,$this->subnsttl,$fd);

		fputs($fd,"\n\n");
		fclose($fd);
		return 1;
	}


// *******************************************************	
//	Function generateSOA($tttl,$nsname,$zonename,$email,
//						$serial,$refresh,$retry,$expiry,$minimum,$fd="")
	/**
	 * Generate SOA config in a file or as return content
	 *
	 *@access private
	 *@return int 1 if in a file, string content if no file given
	 */
	Function generateSOA($tttl,$nsname,$zonename,$email,
						$serial,$refresh,$retry,$expiry,$minimum,$fd=""){
		global $l;
		
		$content ="\n\$TTL " . $tttl . " ; " . $l['str_primary_default_ttl'] . "\n" . 
					$zonename . ".\t\tIN\tSOA\t" . $nsname . ".\t";
		$mail = ereg_replace("@",".",$email);
		$content .= $mail . ". (";
		$content .= "\n\t\t\t\t" . $serial . "\t; " . $l['str_primary_serial'];
		$content .= "\n\t\t\t\t" . $refresh . "\t; " . $l['str_primary_refresh_period'];
		$content .= "\n\t\t\t\t" . $retry . "\t; " . $l['str_primary_retry_interval'];
		$content .= "\n\t\t\t\t" . $expiry . "\t; " . $l['str_primary_expire_time'];
		$content .= "\n\t\t\t\t" . $minimum . "\t; " . $l['str_primary_negative_caching'];
		$content .= "\n\t\t\t)";
		$content .= "\n\n\$ORIGIN " . $zonename . ".";
		if($fd){
			fputs($fd,$content);
			return 1;
		}else{
			return $content;
		}
	}


// *******************************************************	
//	Function generateMultiConfig($type,$item1,$item2,$ttl,$fd = "")
	/**
	 * Generate config in a file or as return content
	 *
	 *@access private
	 *@return int 1 if in a file, string content if no file given
	 */
	Function generateMultiConfig($type,$item1,$item2,$ttl,$fd = ""){
		// retrieve & print $type
		$counter = 0;
		$content = "";
		while($item1[$counter]){
			if($ttl[$counter] != -1){
				$content .= "\n" . $item1[$counter] . "\t\t" . 
					$ttl[$counter] . "\tIN\t\t" . $type . "\t\t" . $item2[$counter];
			}else{
				$content .= "\n" . $item1[$counter] . "\t\t\tIN\t\t" . $type . "\t\t" . $item2[$counter];
			}
			$counter++;
		}
		$content .= "\n\n";

		if($fd){
			fputs($fd,$content);
			return 1;
		}else{
			return $content;
		}
	}


// *******************************************************	
//	Function generateConfig($type,$item1,$ttl,$fd = "")
	/**
	 * Generate config in a file or as return content
	 *
	 *@access private
	 *@return int 1 if in a file, string content if no file given
	 */
	Function generateConfig($type,$item1,$ttl,$fd = ""){
		// retrieve & print $type
		$counter = 0;
		$content = "";
		
		$keys = array_keys($item1);
		switch($type){
			case "NS":
				while($key = array_shift($keys)){
					if($ttl[$key] != "-1"){
						$content .= "\n\t\t" . $ttl[$key] . "\tIN\t\tNS\t\t" . $key;
					}else{
						$content .= "\n\t\t\tIN\t\tNS\t\t" . $key;
					}
				}
				break;
			case "MX":
				while($key = array_shift($keys)){
					if($ttl[$key] != "-1"){
						$content .= "\n\t\t" . $ttl[$key] . "\tIN\t\tMX\t" . $item1[$key] . "\t" . $key;
					}else{
						$content .= "\n\t\t\tIN\t\tMX\t" . $item1[$key] . "\t" . $key;
					}
				}  		
				break;
			default:	
				while($key = array_shift($keys)){
					if($ttl[$key] != "-1"){
						$content .= "\n" . $key . "\t\t" . $ttl[$key] . 
							"\tIN\t\t" . $type . "\t\t" . $item1[$key];
					}else{
						$content .= "\n" . $key . "\t\t\tIN\t\t" . $type . "\t\t" . $item1[$key];
					}
				}
				break;
		}

		if($fd){
			fputs($fd,$content);
			return 1;
		}else{
			return $content;
		}
	}


// *******************************************************	
//	Function PrintTTL($ttl)
	/**
	 * return TTL 
	 *
	 *@access private
	 *@return string ttl localized value
	 */

	Function PrintTTL($ttl){
		global $l;
    	return ($ttl=="-1"||$ttl=="default"?$l['str_primary_default']:$ttl);
  	}

// *******************************************************	
//	Function DNSTTL($ttl)
	/**
	 * return TTL 
	 *
	 *@access private
	 *@return string ttl value for DB insertion
	 */	
	Function DNSTTL($ttl){
		global $l;
		if(!notnull($ttl)){
			$ttlval = "-1";
		}else{
			if ($ttl==$l['str_primary_default'])
				$ttlval = "-1";
			else
				$ttlval = addslashes($ttl);
		}							
		return $ttlval;
	}
	
// *******************************************************	
//	Function flagModified($zoneid)
	/**
	 * flag given zone as 'M'odified to be generated & reloaded
	 *
	 *@access private
	 *@param $zoneid int zone id
	 *@return string result text
	 */	
	Function flagModified($zoneid){
		global $db, $l;
				
		$query = "UPDATE dns_zone SET 
					status='M' WHERE id='" . $zoneid . "'";
		$res = $db->query($query);
		if($db->error()){
			$result = '<p>' .
			$html->generic_error . $l['str_trouble_with_db'] .
			$html->generic_error_end . '
			' . $l['str_primary_zone_error_not_available_try_again'] . '</p>';
		}
	}
	
// *******************************************************	
//	Function updateSerial($zoneid)
	/**
	 * update zone serial
	 *
	 *@access private
	 *@param $zoneid int zone id
	 *@return int 0 if error, 1 if success
	 */	
	Function updateSerial($zoneid){
		global $db, $l;
		$result ="";
	
		// retrieve zone serial
		$query = "SELECT serial FROM dns_confprimary WHERE zoneid='" . $zoneid . "'";
		$res = $db->query($query);
		if($db->error()){
			$this->error=$l['str_trouble_with_db'];
			return 0;
		}
		$line = $db->fetch_row($res);
		
		$serial = getSerial($line[0]);
		$query = "UPDATE dns_confprimary SET serial='" . $serial . "'
				WHERE zoneid='" . $zoneid . "'";
		$res = $db->query($query);
		if($db->error()){
			$this->error=$l['str_trouble_with_db'];
			return 0;
		}
		return 1;
	}
}
?>
