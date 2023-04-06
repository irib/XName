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
use Time::localtime;

# *****************************************************
# Where am i run from
$0 =~ m,(.*/).*,;
$XNAME_HOME = $1;

require $XNAME_HOME . "config.pl";
require $XNAME_HOME . "xname.inc";
$LOG_PREFIX='XName-delete';

########################################################################
# STOP STOP STOP STOP STOP STOP STOP STOP STOP STOP STOP STOPS STOP STOP
#
# Do not edit anything below this line           
########################################################################


# Delete old users from DB
# Table impacted : 
# dns_waitingreply 
# dns_user

$dsn = "DBI:mysql:" . $DB_NAME . ";host=" . $DB_HOST . ";port=" . $DB_PORT;
$dbh = DBI->connect($dsn, $DB_USER, $DB_PASSWORD);

open(LOG, ">>" . $LOG_FILE);

$timestamp = localtime();
$year = 1900 + ($timestamp)->year;
$mon = ($timestamp)->mon;
$mon++;
if($mon < 10){
	$mon = '0' . $mon;
}
$mday = ($timestamp)->mday;
# delete one day after
$mday = $mday - 1;
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
$sec=($timestamp)->sec;
if($sec < 10){
	$sec = '0' . $sec;
}

$timetouse = $year . $mon . $mday . $hour . $min . $sec;

$query = "SELECT userid
			FROM dns_waitingreply
			WHERE firstdate <= $timetouse
			";

my $sth = $dbh->prepare($query);
if(!$sth){
	print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
}
if (!$sth->execute) {
	print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $sth->errstr . "\n";
}

while (my $ref = $sth->fetchrow_hashref()) {
# for each user, 
	$userid = $ref->{'userid'};
	print LOG logtimestamp() . " " . $LOG_PREFIX . " Deleting user $userid\n";	

	# TODO send email to warn 
	

	$query = "DELETE FROM dns_user WHERE id='" . $userid . "'";
	my $sth2 = $dbh->prepare($query);
	if(!$sth2){
		print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
	}
	if (!$sth2->execute) {
		print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $sth->errstr . "\n";
	}
	$query = "DELETE FROM dns_waitingreply WHERE userid='" . $userid . "'";
	my $sth2 = $dbh->prepare($query);
	if(!$sth2){
		print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
	}
	if (!$sth2->execute) {
		print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $sth->errstr . "\n";
	}

}

$query = "SELECT zone,zonetype
		FROM dns_zone WHERE status='D'";

my $sth = $dbh->prepare($query);
if(!$sth){
	print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
}
if (!$sth->execute) {
	print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $sth->errstr . "\n";
}

@todelete=();
while (my $ref = $sth->fetchrow_hashref()) {
# for each zone, 
	$zonename = $ref->{'zone'};
	$zonetype = $ref->{'zonetype'};
	print LOG logtimestamp() . " " . $LOG_PREFIX . " Deleting zone $zonename\n";	

	# Delete $NAMED_DATA_DIR/masters|slaves
	if($zonetype eq "P"){
		$command= "$RM_COMMAND $NAMED_DATA_DIR" . $NAMED_MASTERS_DIR . $zonename;
	}else{
		$command= "$RM_COMMAND $NAMED_DATA_DIR" . $NAMED_SLAVES_DIR . $zonename;
	}
	`$command`;
	push(@todelete,$zonename);
}


# delete from DB
while(<@todelete>){
	$query = "DELETE from dns_zone WHERE zone='" . $_ . "'";
	my $sth = $dbh->prepare($query);
	if(!$sth){
		print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
	}
	if (!$sth->execute) {
		print LOG logtimestamp() . " " . $LOG_PREFIX . " : Error:" . $sth->errstr . "\n";
	}
}

close LOG;
