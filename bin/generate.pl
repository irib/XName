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

require "config.pl";

$LOG_PREFIX .='generate';


########################################################################
#         To modify configuration parameters, edit config.pl
########################################################################


########################################################################
# STOP STOP STOP STOP STOP STOP STOP STOP STOP STOP STOP STOPS STOP STOP
#
# Do not edit anything below this line           
########################################################################


# -1/ check for expired zones in temp db (cf 4)
# ==> have to be done in another place & run before
# --- done in delete.pl run via crontab


# 0/ generate the domains.master files
# accordingly with database content

# 1/ generate the named.conf file
# 	accordingly with database content

# 2/ reload named

# 3/ warn for reload by email
#	accordingly with modified content
#   and delete from modified
# and pgp-sign email with "validating zonename email" inside

# 4/ insert zone & mail & date in db







$dsn = "DBI:mysql:" . $DB_NAME . ";host=" . $DB_HOST . ";port=" . $DB_PORT;
$dbh = DBI->connect($dsn, $DB_USER, $DB_PASSWORD);

open(LOG, ">>" . $LOG_FILE);

# ***************************************************** 
# GENERATING DATA FILES
# Primary only
$query = "SELECT c.zoneid, z.zone, u.email, c.serial, c.refresh,
c.retry,c.expiry,c.minimum
		FROM dns_modified m, dns_confprimary c, dns_zone z, dns_user u
		WHERE m.zoneid = c.zoneid AND c.zoneid = z.id AND z.userid=u.id";
#$query = "SELECT c.zoneid, z.zone, u.email, c.serial, c.refresh,
#c.retry,c.expiry,c.minimum
#		FROM dns_confprimary c, dns_zone z, dns_user u
#		WHERE  c.zoneid = z.id AND z.userid=u.id";

my $sth = $dbh->prepare($query);
if(!$sth){
	print LOG $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
}
if (!$sth->execute) {
	print LOG $LOG_PREFIX . " : Error:" . $sth->errstr . "\n";
}

while (my $ref = $sth->fetchrow_hashref()) {
# for each zone, 

	$zone = $ref->{'zone'};
	$zoneid = $ref->{'zoneid'};
#	print $zone . "\n";	
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

			
	# Generate & write header
	if($zone =~ /^(.*)\.([^.]+)$/){
		$origin = $2;
		$prefix = $1;
	}else{
	print LOG $LOG_PREFIX . " : ERROR : $zone is not valid";
	}
	
	$header = "\$TTL $minimum\n";
	$header .= "\$ORIGIN $origin.
$prefix		IN	SOA		" . $SITE_NS . ". $email. (
			$serial $refresh $retry $expiry $minimum )
";
	$toprint = $header;
				
	# Retrieve & write NSs
	$sth1 = $dbh->prepare("SELECT val1 FROM dns_record 
							WHERE zoneid='" . $zoneid . "'
							AND type='NS' AND val2=''");
	if(!$sth1){
		print LOG $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
	}
	if (!$sth1->execute) {
		print LOG $LOG_PREFIX . " : Error:" . $sth1->errstr . "\n";
	}

	while (my $ref = $sth1->fetchrow_hashref()) {
		$toprint .= "
			IN		NS		" . $ref->{'val1'};
	}
	
	$sth1->finish();

	
	# Retrieve & write MXs
	$sth1 = $dbh->prepare("SELECT val1, val2 FROM dns_record 
							WHERE zoneid='" . $zoneid . "'
							AND type='MX'");
	if(!$sth1){
		print LOG $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
	}
	if (!$sth1->execute) {
		print LOG $LOG_PREFIX . " : Error:" . $sth1->errstr . "\n";
	}

	while (my $ref = $sth1->fetchrow_hashref()) {
		$toprint .= "
			IN		MX		" . $ref->{'val2'} . "\t" . $ref->{'val1'};
	}

	$sth1->finish();

	# End of zone header, print origin $zone.
	$toprint .= "\n\n\$ORIGIN $zone.\n";


	# 11 02 2002 Retrieve & write A for Zone
	$sth1 = $dbh->prepare("SELECT val1 FROM dns_record
							WHERE zoneid='" . $zoneid . "'
							AND type='AZONE'");
	if(!$sth1){
		print LOG $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
	}
	if (!$sth1->execute) {
		print LOG $LOG_PREFIX . " : Error:" . $sth1->errstr . "\n";
	}

	while (my $ref = $sth1->fetchrow_hashref()) {
		$toprint .= "
			IN		A		" . $ref->{'val1'};
	}

	$sth1->finish();
							


	# Retrieve & write As
	$sth1 = $dbh->prepare("SELECT val1, val2 FROM dns_record 
							WHERE zoneid='" . $zoneid . "'
							AND type='A'");
	if(!$sth1){
		print LOG $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
	}
	if (!$sth1->execute) {
		print LOG $LOG_PREFIX . " : Error:" . $sth1->errstr . "\n";
	}

	while (my $ref = $sth1->fetchrow_hashref()) {
		$toprint .= "
" . $ref->{'val1'} . "			IN		A		" . $ref->{'val2'};
	}
	$sth1->finish();
	
	# Retrieve & write CNAMEs
	$sth1 = $dbh->prepare("SELECT val1, val2 FROM dns_record 
							WHERE zoneid='" . $zoneid . "'
							AND type='CNAME'");
	if(!$sth1){
		print LOG $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
	}
	if (!$sth1->execute) {
		print LOG $LOG_PREFIX . " : Error:" . $sth1->errstr . "\n";
	}

	while (my $ref = $sth1->fetchrow_hashref()) {
		$toprint .= "
" . $ref->{'val1'} . "			IN		CNAME		" . $ref->{'val2'};
	}
	$sth1->finish();


	# Retrieve & write SubNS
	$sth1 = $dbh->prepare("SELECT val1, val2 FROM dns_record 
							WHERE zoneid='" . $zoneid . "'
							AND type='SUBNS'");
	if(!$sth1){
		print LOG $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
	}
	if (!$sth1->execute) {
		print LOG $LOG_PREFIX . " : Error:" . $sth1->errstr . "\n";
	}

	while (my $ref = $sth1->fetchrow_hashref()) {
		$toprint .= "
" . $ref->{'val1'} . "			IN		NS		" . $ref->{'val2'};
	}
	$sth1->finish();

	$toprint .= "\n";


	# open file 
	open(DATA_FILE, ">" . $NAMED_DATA_DIR . $NAMED_MASTERS_DIR . $zone ) || 	print LOG $LOG_PREFIX . " : Error
	opening $NAMED_DATA_DIR $NAMED_MASTERS_DIR $zone";
	print DATA_FILE $toprint;
	close(DATA_FILE);
}

$sth->finish();





# ***************************************************** 
# GENERATING NAMED_CONF FILE

# primary NS

my $sth = $dbh->prepare("SELECT c.zoneid, c.xfer,z.zone FROM 
		dns_confprimary c, dns_zone z where c.zoneid=z.id");
if (!$sth) {
	print LOG $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
}
if (!$sth->execute) {
	print LOG $LOG_PREFIX . " : Error:" . $sth->errstr . "\n";
}

open(CONF, ">" . $NAMED_CONF) || 	print LOG $LOG_PREFIX . " :  Error opening $NAMED_CONF";

open(CONFHEADERS, "<" . $NAMED_CONF_HEADERS) || 	print LOG $LOG_PREFIX . " : Error opening
$NAMED_CONF_HEADERS";

while(<CONFHEADERS>){
	print CONF $_;
}
close CONFHEADERS;


while (my $ref = $sth->fetchrow_hashref()) {
	# write to named.conf

		# zone "zone.name" {
		#		type master;
		#		file "/var/cache/bind/zone.name";
		#       allow-transfer { IPs;};
		# };

		print CONF '
		
zone "' . $ref->{'zone'} . '" {
	type master;
	file "' . $NAMED_DATA_CHROOTED_DIR . 'masters/' . $ref->{'zone'} . '";
	allow-transfer { ' . $ref->{'xfer'} . '; };
};
';

# *********************************************************

$zone = $ref->{'zone'};
# reload named for each concerned zone ONLY

`$RNDC_COMMAND reload $zone`

}		


$sth->finish();

# **********

# secondary NS

 $sth = $dbh->prepare("SELECT z.zone, c.masters, c.xfer FROM 
		dns_confsecondary c, dns_zone z where c.zoneid=z.id");
if (!$sth) {
	print LOG $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
}
if (!$sth->execute) {
	print LOG $LOG_PREFIX . " : Error:" . $sth->errstr . "\n";
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
		print CONF '
		
zone "' . $ref->{'zone'} . '" {
	type slave;
	file "' . $NAMED_DATA_CHROOTED_DIR . $NAMED_MASTERS_DIR . $ref->{'zone'} . '";
	masters {' . $masters . '; };
	allow-transfer {' . $xfer. '; };
};';

	}

# *********************************************************

$zone = $ref->{'zone'};
# reload named for each concerned zone ONLY


 `$RNDC_COMMAND reload $zone`

	
}


close CONF;

$sth->finish();




# *********************************************************


# reload named

#  check if error. If error, DO NOT RELOAD

@result = `$CHECKCONF_COMMAND $NAMED_CONF`;
$error = 0;
foreach(@result){
	if(/error/){
		$error = 1;
	}
}
if($error == 1){
	# mail admin
	$msgto=$EMAIL_ADMIN;
	$msgsubject="$EMAIL_SUBJECT_PREFIX reload error";

	$message = @result;

		%mail = (
		To		=>	$msgto,
		From 	=> $EMAIL_FROM,
		Subject	=> $msgsubject,
		message	=> $message,
		);
		
		sendmail %mail;

}else{
	# reload
	`$RELOADALL_COMMAND`;
}

# *********************************************************




# *********************************************************


# retrieve emails & warn


# for secondary NS

my $sth = $dbh->prepare("SELECT u.email, z.zone, z.id FROM dns_zone z, 
				dns_modified m, dns_user u WHERE m.zoneid=z.id
				AND z.userid=u.id");
if (!$sth) {
	print LOG $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
}
if (!$sth->execute) {
	print LOG $LOG_PREFIX . " : Error:" . $sth->errstr . "\n";
}

while (my $ref = $sth->fetchrow_hashref()) {
	# send an email

	$msgto=$ref->{'email'};
	$msgsubject="$EMAIL_SUBJECT_PREFIX Reloading zone " . $ref->{'zone'};

	$message = "	
This is an automatic email.

The Name server of " . $SITE_NAME . " has been reloaded.

Your changes regarding zone " . $ref->{'zone'} . " 
on our name server should take effect within few minutes.

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
		print LOG $LOG_PREFIX . " : ERROR : an error occured sending mail " . $Mail::Sendmail::error
		. "\n";
	}else{

		my $sth2 = $dbh->prepare("DELETE from dns_modified WHERE zoneid='" .
		$ref->{'id'} . "'");
		if (!$sth2) {
			print LOG $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
		}
		if (!$sth2->execute) {
			print LOG $LOG_PREFIX . " : Error:" . $sth->errstr . "\n";
		}
	}
}

# Disconnect from the database.
$dbh->disconnect();
close LOG;
