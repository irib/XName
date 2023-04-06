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

require "config.pl";

$LOG_PREFIX .='delete';

########################################################################
#         To modify configuration parameters, edit config.pl
########################################################################


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

($sec, $min, $hour, $mday, $mon, $year, $wday, $yday, $isdst) = localtime(time);

$year = 1900 + $year;
$mon++;
if($mon < 10){
	$mon = '0' . $mon;
}

# delete one day after
$mday = $mday - 1;
if($mday < 10){
	$mday = '0' . $mday;
}

if($hour < 10){
	$hour = '0' . $hour;
}

if($min < 10){
	$min = '0' . $min;
}

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
	print LOG $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
}
if (!$sth->execute) {
	print LOG $LOG_PREFIX . " : Error:" . $sth->errstr . "\n";
}

while (my $ref = $sth->fetchrow_hashref()) {
# for each user, 
	$userid = $ref->{'userid'};
	print LOG $LOG_PREFIX . "$timetouse Deleting user $userid\n";	

	# TODO send email to warn 
	

	$query = "DELETE FROM dns_user WHERE id='" . $userid . "'";
	my $sth2 = $dbh->prepare($query);
	if(!$sth2){
		print LOG $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
	}
	if (!$sth2->execute) {
		print LOG $LOG_PREFIX . " : Error:" . $sth->errstr . "\n";
	}
	$query = "DELETE FROM dns_waitingreply WHERE userid='" . $userid . "'";
	my $sth2 = $dbh->prepare($query);
	if(!$sth2){
		print LOG $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
	}
	if (!$sth2->execute) {
		print LOG $LOG_PREFIX . " : Error:" . $sth->errstr . "\n";
	}

}

$query = "SELECT zonename,zonetype
		FROM dns_deleted";

my $sth = $dbh->prepare($query);
if(!$sth){
	print LOG $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
}
if (!$sth->execute) {
	print LOG $LOG_PREFIX . " : Error:" . $sth->errstr . "\n";
}

@todelete=();
while (my $ref = $sth->fetchrow_hashref()) {
# for each zone, 
	$zonename = $ref->{'zonename'};
	$zonetype = $ref->{'zonetype'};
	print LOG $LOG_PREFIX . "$timetouse Deleting zone $zonename\n";	

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
	$query = "DELETE from dns_deleted WHERE zonename='" . $_ . "'";
	my $sth = $dbh->prepare($query);
	if(!$sth){
		print LOG $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
	}
	if (!$sth->execute) {
		print LOG $LOG_PREFIX . " : Error:" . $sth->errstr . "\n";
	}
}

close LOG;
