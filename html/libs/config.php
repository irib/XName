<?

/*
	This file is part of XName.org project
	See	http://www.xname.org/ for details
	
	License: GPLv2
	See LICENSE file, or http://www.gnu.org/copyleft/gpl.html
	
	Author(s): Yann Hirou <hirou@xname.org>

*/


/**
 * Class containing local parameters - must be modified before anything else
 *
 *@access public
 */
Class Config {

	/**
	 * Class constructor, initialize all common vars
	 *
	 *@access public
	 */
	function Config(){
		// site parameters
		$this->sitename = 'XName Demo site';
		$this->domainname = 'dev.xname.org';
		$this->mainurl = 'http://dev.xname.org/';
		$this->contactemail = 'demo@xname.org'; // used on web pages
		$this->tousersource = 'webserver@xname.org'; // used in to-user emails
		$this->emailsignature = 'XName DEMO team';
		$this->cssurl = "style/xname.css";
		// emailfrom & emailto are used when an error
		// occurs, to warn administrator
		$this->emailfrom = 'webserver@xname.org';
		$this->emailto = 'demo@xname.org';
		$this->dbpersistent = 1;
		// host & port needed for chrooted web server
		// without mysql unix socket access
		$this->dbhost = '213.11.111.252';
		$this->dbport = '3306';
		$this->dbuser = 'xnameuser';
		$this->dbpass = 'password';
		$this->dbname = 'xnamedev';
		// your NS parameters
		$this->nsname = 'ns0.xname.org';
		$this->nsaddress = '213.11.111.252';
		// bin paths
		$this->bindig = '/bin/dig';
		$this->binhost = '/bin/host';
		$this->binnamedcheckzone = '/bin/named-checkzone';
		return $this;
	}
}


?>
