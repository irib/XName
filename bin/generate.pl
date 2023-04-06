#!/usr/bin/perl

###############################################################
#	This file is part of XName.org project                    #
#	See	http://www.xname.org/ for details                     #
#	                                                          #
#	License: GPLv2                                            #
#	See LICENSE file, or http://www.gnu.org/copyleft/gpl.html #
#	                                                          #
#	Author(s): Yann Hirou <hirou@xname.org>                   #
###############################################################

use DBI;
use IO::Handle;
use GnuPG::Interface;
use Mail::Sendmail;
use Time::localtime;

# *****************************************************
# Where am i run from
$0 =~ m,(.*/).*,;
$XNAME_HOME = $1;

require $XNAME_HOME . "config.pl";
require $XNAME_HOME . "xname.inc";
$LOG_PREFIX .='generate';


########################################################################
# STOP STOP STOP STOP STOP STOP STOP STOP STOP STOP STOP STOPS STOP STOP
#
# Do not edit anything below this line           
########################################################################


# 0/ generate the domains.master files for modified or created zones
# accordingly with database content

# 1/ generate the named.conf file
# 	accordingly with database content

# 2/ reload named with rndc reconfig

# 3/ reload each modified zone one by one (rndc reconfig does not take them)

# 4/ warn for reload by email
#	accordingly with modified content
#   and delete from modified
# and pgp-sign email with "validating zonename email" inside

# 5/ insert zone & mail & date in db







$dsn = "DBI:mysql:" . $DB_NAME . ";host=" . $DB_HOST . ";port=" . $DB_PORT;
$dbh = DBI->connect($dsn, $DB_USER, $DB_PASSWORD);

open(LOG, ">>" . $LOG_FILE);


$query = "SELECT count(*) as count FROM dns_zone 
			WHERE status='D' OR status='M'";

my $sth = $dbh->prepare($query);
if (!$sth) {
	print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
}
if (!$sth->execute) {
	print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $sth->errstr . "\n";
}

$ref = $sth->fetchrow_hashref();
$sth->finish();
if($ref->{'count'}){
	
	# retrieve list of all xname servers
	$query = "SELECT serverip FROM dns_server";
	my $sth = $dbh->prepare($query);
	if (!$sth) {
		print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
	}
	if (!$sth->execute) {
		print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $sth->errstr . "\n";
	}

	$serverlist = "";
	while (my $ref = $sth->fetchrow_hashref()) {
		$serverlist .= $ref->{serverip} . ";";
	}
	$sth->finish();


########################################################################
# GENERATING NAMED_CONF FILE
########################################################################

	# backup named.conf
	`touch $NAMED_CONF`;
	$cpcommand=$CP_COMMAND . " " . $NAMED_CONF . " " .
	$NAMED_TMP_DIR . "named.conf.bak-" . $$;
	@output = `$cpcommand 2>&1`;
	if($#output != -1){
		print  LOG logtimestamp() . " " . $LOG_PREFIX .
			" : Error: Can not backup named.conf\n" . "\t" . $output[0] . "\n";			
	}else{
		
		open(CONF, ">" . $NAMED_CONF) || 	print LOG logtimestamp() . " " .
		$LOG_PREFIX . " :  Error opening $NAMED_CONF\n";
	
		open(CONFHEADERS, "<" . $NAMED_CONF_HEADERS) || 	print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error opening
		$NAMED_CONF_HEADERS\n";

		while(<CONFHEADERS>){
			print CONF $_;
		}
		close CONFHEADERS;


	##############
	# primary NS #
	##############

		$query = "SELECT c.zoneid, c.xfer,z.zone FROM 
			dns_confprimary c, dns_zone z where c.zoneid=z.id";

		my $sth = $dbh->prepare($query);
		if (!$sth) {
			print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
		}
		if (!$sth->execute) {
			print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $sth->errstr . "\n";
		}



		while (my $ref = $sth->fetchrow_hashref()) {
	# write to named.conf

		# zone "zone.name" {
		#		type master;
		#		file "/var/cache/bind/zone.name";
		#       allow-transfer { IPs;};
		# };
		
			# Add ALL xname servers to allow-transfer list if not any
			$xferlist = $ref->{'xfer'};
			if($xferlist ne "any"){
				# concatenate serverlist and xferlist
				$xferlist = $serverlist . $xferlist;
				# explode xferlist to have unique IP addresses
				undef %tmp;
				@tmp{split(/;/,$xferlist)} = ();
				@xferlist = keys %tmp;
				$xferlist = join(";",@xferlist);
			}else{
				$xferlist = "any";
			}
			print CONF '
		
zone "' . $ref->{'zone'} . '" {
	type master;
	file "' . $NAMED_DATA_CHROOTED_DIR . $NAMED_MASTERS_DIR . $ref->{'zone'} . '";
	allow-transfer { ' . $xferlist . '; };
};
';
		}

		$sth->finish();

# *********************************************************


################
# secondary NS #
################

		$query = "SELECT z.zone, c.masters, c.xfer FROM 
			dns_confsecondary c, dns_zone z where c.zoneid=z.id";

		$sth = $dbh->prepare($query);
		if (!$sth) {
			print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
		}
		if (!$sth->execute) {
			print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $sth->errstr . "\n";
		}

		while (my $ref = $sth->fetchrow_hashref()) {
	# write to named.conf

		# zone "zone.name" {
		#		type slave;
		#		file "/var/cache/bind/zone.name";
		#		masters { masterIP; };
		#		allow-transfer {masterIP; IP2; ...};
		# };
		
			if($ref->{'masters'} ne ""){
				$masters = $ref->{'masters'};
				if( $ref->{'masters'}  =~ /;$/){
					chop($masters);
				}
				$xfer = $ref->{'xfer'};
				if($ref->{'xfer'} =~ /;$/){
					chop($xfer);
				}

		# Add ALL xname servers to allow-transfer list if not any
				if($xfer ne "any"){
					# concatenate serverlist and xferlist
					$xfer = $serverlist . $xfer;
					# explode xferlist to have unique IP addresses
					undef %tmp;
					@tmp{split(/;/,$xfer)} = ();
					@xfer = keys %tmp;
					$xfer = join(";",@xfer);
				}else{
					$xfer = "any";
				}

				print CONF '
		
zone "' . $ref->{'zone'} . '" {
	type slave;
	file "' . $NAMED_DATA_CHROOTED_DIR . $NAMED_SLAVES_DIR . $ref->{'zone'} . '";
	masters {' . $masters . '; };
	allow-transfer {' . $xfer. '; };
};';

			} # end if master ne ''
		} # end while

		close CONF;

		$sth->finish();

########################################################################



########################################################################
# GENERATING DATA FILES
# Primary only
########################################################################

		$query = "SELECT c.zoneid, z.zone, u.email, c.serial, c.refresh,
				c.retry,c.expiry,c.minimum,c.defaultttl
				FROM  dns_confprimary c, dns_zone z, dns_user u
				WHERE c.zoneid = z.id AND z.userid=u.id
				AND z.status='M'";

		my $sth = $dbh->prepare($query);
		if(!$sth){
			print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
		}
		if (!$sth->execute) {
			print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $sth->errstr . "\n";
		}

		while (my $ref = $sth->fetchrow_hashref()) {
# for each zone, 

			$zone = $ref->{'zone'};
			$zoneid = $ref->{'zoneid'};
		# Retrieve MAIL
			$email = $ref->{'email'};
			$email =~ s/\@/\./;
		# Retrieve Serial
			$serial = $ref->{'serial'};
		# Retrieve refresh
			$refresh = $ref->{'refresh'};
		# Retrieve retry
			$retry = $ref->{'retry'};
		# Retrieve expiry
			$expiry = $ref->{'expiry'};
		# Retrieve minimum
			$minimum = $ref->{'minimum'};
		# retrieve defaultttl
			$defaultttl = $ref->{'defaultttl'};

			
			# Generate & write header
			if($zone =~ /^(.*)\.([^.]+)$/){
				$origin = $2;
				$prefix = $1;
			}else{
				print LOG logtimestamp() . " " . $LOG_PREFIX . " : ERROR : $zone is not valid";
			}
	
			$header = "\$TTL $defaultttl\n";
			$header .= "\$ORIGIN $origin.
$prefix		IN	SOA		" . $SITE_NS . ". $email. (
			$serial $refresh $retry $expiry $minimum )
";
			$toprint = $header;
				
	# Retrieve & write NSs
			$sth1 = $dbh->prepare("SELECT val1,ttl FROM dns_record 
							WHERE zoneid='" . $zoneid . "'
							AND type='NS' AND val2=''");
			if(!$sth1){
				print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
			}
			if (!$sth1->execute) {
				print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $sth1->errstr . "\n";
			}

			while (my $ref = $sth1->fetchrow_hashref()) {
				$toprint .= "
			";
				if($ref->{'ttl'} ne "default"){
					$toprint .= $ref->{'ttl'};
				}
				$toprint .= "	IN		NS		" . $ref->{'val1'};
			}
	
			$sth1->finish();

	
		# Retrieve & write MXs
			$sth1 = $dbh->prepare("SELECT val1, val2,ttl FROM dns_record 
								WHERE zoneid='" . $zoneid . "'
								AND type='MX'");
			if(!$sth1){
				print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
			}
			if (!$sth1->execute) {
				print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $sth1->errstr . "\n";
			}

			while (my $ref = $sth1->fetchrow_hashref()) {
				$toprint .= "
			";
				if($ref->{'ttl'} ne "default"){
					$toprint .= $ref->{'ttl'};
				}
				$toprint .= "	IN		MX		" . $ref->{'val2'} . "\t" . $ref->{'val1'};
			}

			$sth1->finish();

	# End of zone header, print origin $zone.
			$toprint .= "\n\n\$ORIGIN $zone.\n";


	# Retrieve & write As
			$sth1 = $dbh->prepare("SELECT val1, val2, ttl FROM dns_record 
							WHERE zoneid='" . $zoneid . "'
							AND type='A'");
			if(!$sth1){
				print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
			}
			if (!$sth1->execute) {
				print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $sth1->errstr . "\n";
			}

			while (my $ref = $sth1->fetchrow_hashref()) {
				$toprint .= "
" . $ref->{'val1'} . "			";
				if($ref->{'ttl'} ne "default"){
					$toprint .= $ref->{'ttl'};
				}
				$toprint .= "	IN		A		" . $ref->{'val2'};
			}
			$sth1->finish();
	
	# Retrieve & write CNAMEs
			$sth1 = $dbh->prepare("SELECT val1, val2, ttl FROM dns_record 
							WHERE zoneid='" . $zoneid . "'
							AND type='CNAME'");
			if(!$sth1){
				print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
			}
			if (!$sth1->execute) {
				print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $sth1->errstr . "\n";
			}

			while (my $ref = $sth1->fetchrow_hashref()) {
				$toprint .= "
" . $ref->{'val1'} . "			";
				if($ref->{'ttl'} ne "default"){
					$toprint .= $ref->{'ttl'};
				}
				$toprint .= "	IN		CNAME		" . $ref->{'val2'};
			}
			$sth1->finish();


	# Retrieve & write SubNS
			$sth1 = $dbh->prepare("SELECT val1, val2, ttl FROM dns_record 
							WHERE zoneid='" . $zoneid . "'
							AND type='SUBNS'");
			if(!$sth1){
				print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
			}
			if (!$sth1->execute) {
				print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $sth1->errstr . "\n";
			}

			while (my $ref = $sth1->fetchrow_hashref()) {
				$toprint .= "
" . $ref->{'val1'} . "			";
				if($ref->{'ttl'} ne "default"){
					$toprint .= $ref->{'ttl'};
				}
				$toprint .= "	IN		NS		" . $ref->{'val2'};
			}
			$sth1->finish();

			$toprint .= "\n";


	# open file 
			open(DATA_FILE, ">" . $NAMED_DATA_DIR . $NAMED_MASTERS_DIR . $zone ) || 	print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error
			opening $NAMED_DATA_DIR" . $NAMED_MASTERS_DIR . $zone . "\n";
			print DATA_FILE $toprint;
			close(DATA_FILE);
		}
	
		$sth->finish();
########################################################################





########################################################################
# Reload Named
########################################################################

#  check if error. If error, DO NOT RELOAD

		$error = 0;
		if(system("$CHECKCONF_COMMAND $NAMED_CONF")){
			$error = 1;
		}
		if($error == 1){
		# mail admin
			# copy to named.conf.error, send mail, restore backup
			$command=$CP_COMMAND . " " . $NAMED_CONF . " " . 
				$SERVER_TMP_DIR . "named.conf.error";
			system($command);
			$msgto=$EMAIL_ADMIN;
			$msgsubject="$EMAIL_SUBJECT_PREFIX named.conf error";

			$message = "Error while checking named.conf on
$SERVER_NAME
Previous named.conf has been restored.
Bad named.conf is available at
" . $SERVER_TMP_DIR . "named.conf.error

-----
Check output:
";
			foreach(@result){
				$message .= $_;
			}
			%mail = (
			To		=>	$msgto,
			From 	=> $EMAIL_FROM,
			Subject	=> $msgsubject,
			message	=> $message,
			);
			
			sendmail %mail;

		}else{
			# move backup to named.conf.bak 
			$command=$MV_COMMAND . " " . 
				$SERVER_TMP_DIR . "named.conf.bak-" . $$ . " " .
				$SERVER_TMP_DIR . "named.conf.bak";
			system($command);
			# reload
			system("$RNDC_COMMAND reconfig"); 
		}

########################################################################




########################################################################
# Reload all modified zones - not new zones
########################################################################
		$query = "SELECT zone FROM dns_zone 
				WHERE status='M'";
		my $sth = $dbh->prepare($query);
		if (!$sth) {
			print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
		}
		if (!$sth->execute) {
			print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $sth->errstr . "\n";
		}

		while (my $ref = $sth->fetchrow_hashref()) {
			$zone = $ref->{'zone'};
			system("$RNDC_COMMAND reload $zone");
		}
		$sth->finish();
########################################################################






########################################################################
# Remote server command generation
########################################################################
		# retrieve all servers with their IP (for allow_transfer)
		$query = "SELECT id,serverip,sshhost FROM dns_server  WHERE id!='1'";
		my $sth = $dbh->prepare($query);
		if (!$sth) {
			print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
		}
		if (!$sth->execute) {
			print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $sth->errstr . "\n";
		}
		@serverlist=();
		while (my $ref = $sth->fetchrow_hashref()) {
			$serverid = $ref->{'id'};
			push(@serveridlist,$serverid);
			$serverip{$serverid}=$ref->{'serverip'};
			if($ref->{'sshhost'} != ""){
				$serversshhost{$serverid} = $ref->{'sshhost'};
			}else{
				$serversshhost{$serverid} = $ref->{'serverip'};
			}
		}
		$sth->finish();
	
		# list of server IPs, to be included in allow_transfer if not "any"
		# and list of master servers... 
        $masters=$SITE_NS_IP . ";";
		foreach(values(%serverip)){
			$masters .= $_ . ";";
		}
	
		# generate timestamp for filenames
		$timestamp = localtime();
		$year = 1900 + ($timestamp)->year;
		$mon = ($timestamp)->mon;
		$mon++;
		if($mon < 10){
			$mon = '0' . $mon;
		}
		$mday = ($timestamp)->mday;
		if($mday < 10){
			$mday = '0' . $mday;
		}
		$hour = ($timestamp)->hour;
		if($hour < 10){
			$hour = '0' . $hour;
		}
		$min=($timestamp)->min;
		if($min < 10){
			$min = '0' . $min;
		}
		$timestamp = $year.$mon.$mday.$hour.$min;

		# for each server, retrieve zones registered for this server
		# and print output in server cmd file
		# Retrieve ONLY modified or deleted zones
		foreach(@serveridlist){
			$serverid=$_;
			open(CMD, "> $REMOTE_SERVER_DIR" . $serversshhost{$serverid} . "-" .
			$timestamp) || print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error opening " . 
			$REMOTE_SERVER_DIR . $serversshhost{$serverid} . "-" .
			$timestamp . "\n";
		
			$query="SELECT z.zone,z.zonetype,z.id,z.status, u.email 
					FROM dns_zone z, dns_zonetoserver s, dns_user u
					WHERE z.id=s.zoneid AND s.serverid='" . $serverid . "'
					AND z.status!='' AND z.userid = u.id";
			my $sth = $dbh->prepare($query);
			if (!$sth) {
				print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
			}
			if (!$sth->execute) {
				print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $sth->errstr . "\n";
			}
		
			while (my $ref = $sth->fetchrow_hashref()) {
				# for deleted zones
				if($ref->{'status'} eq 'D'){
					print CMD "Delete " . $ref->{'zone'} . "\n";
				}else{ # end deleted zones
					# for each modified zone, select zonename xfer 
					# and masters (for secondaries)
					if($ref->{'zonetype'} eq 'P'){
						$query="SELECT xfer FROM dns_confprimary WHERE zoneid='"
                                . $ref->{'id'} . "'";
					}else{
                        $query = "SELECT xfer,masters FROM dns_confsecondary
                                  WHERE zoneid='" . $ref->{'id'} . "'";
					}
					my $sth2 = $dbh->prepare($query);
					if (!$sth2) {
						print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
					}
					if (!$sth2->execute) {
						print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $sth2->errstr . "\n";
					}
					$ref2=$sth2->fetchrow_hashref();
					if($ref2->{'masters'}){
						$masters = $ref2->{'masters'} . ";" . $masters;
					}
					if($ref2->{'xfer'} eq "any"){
						$xfer = "any";
					}else{
						$xfer = $masters . $ref2->{'xfer'};
						# unicity of IP addresses in $xfer
						@xfer=split(/;/,$xfer);
						foreach(@xfer){
							$xfernew{$_}=1;
						}
						$xfer = join(';',keys(%xfernew));
					}
					print CMD "Add " . $ref->{'zone'} . " masters " . $masters 
						. " allow-transfer " . $xfer . " email " .
						$ref->{'email'} . "\n";
				} # end status != deleted
			} # end while select zone,zonetype

			close(CMD);
		} # end foreach server

########################################################################







########################################################################
# retrieve emails & warn
########################################################################

		$query = "SELECT u.email, z.zone, z.id FROM dns_zone z, 
				 dns_user u WHERE z.userid=u.id
				 AND z.status='M'";

		my $sth = $dbh->prepare($query);
		if (!$sth) {
			print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
		}
		if (!$sth->execute) {
			print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $sth->errstr . "\n";
		}
		while (my $ref = $sth->fetchrow_hashref()) {
			if(!exists($zonelist{$ref->{'email'}})){
				$zonelist{$ref->{'email'}} = "";
			}
			$zonelist{$ref->{'email'}} .= $ref->{'zone'} . ";";


			my $sth2 = $dbh->prepare("UPDATE dns_zone SET status='' WHERE id='" .
			$ref->{'id'} . "'");
			if (!$sth2) {
				print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
			}
			if (!$sth2->execute) {
				print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $sth2->errstr . "\n";
			}
		}
		$sth->finish();

		# send an email per user for all zones

		while(($email,$listzones) = each(%zonelist)){
			$msgto=$email;
			$listzones =~ s/;/
/g;
			
			$msgsubject="$EMAIL_SUBJECT_PREFIX Reloading your zone(s) on " . $SITE_NS;

			$message = "	
This is an automatic email from name server " . $SITE_NS . " of " . $SITE_NAME . ".
Following zones has been reloaded on it:

$listzones

Your changes on this name server should take effect within few minutes.

";

			$message .=  $EMAIL_SIGNATURE;
			$message .= "\n\n\n\n";
	
			%mail = (
				To		=>	$msgto,
				From 	=> $EMAIL_FROM,
				Subject	=> $msgsubject,
				message	=> $message,
			);
			
			if(!sendmail %mail) {
				print LOG logtimestamp() . " " . $LOG_PREFIX . " : ERROR : an error occured sending mail " . $Mail::Sendmail::error
				. "\n";
			}
		} # end while each %zonelist
########################################################################

	} # end named.conf backup successfull

} # end count of zone modified >= 1 
# Disconnect from the database.
$dbh->disconnect();
close LOG;
