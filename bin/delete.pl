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
use POSIX qw(strftime);


# *****************************************************
# Where am i run from
$0 =~ m,(.*/).*,;
$XNAME_HOME = $1;

require $XNAME_HOME . "config.pl";
require $XNAME_HOME . "xname.inc";
# load all languages
if(opendir(DIR,$XNAME_HOME . "strings")){
        foreach(readdir(DIR)){
                if(/^[^\.][^\.]$/){
                        require $XNAME_HOME . "strings/" . $_ . "/strings.inc";
                }
        }
        closedir(DIR);
}else{
        print "ERROR: no language available";
}

$LOG_PREFIX.=$str_log_delete_prefix{$SITE_DEFAULT_LANGUAGE};


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

$query = "SELECT w.userid,u.login
			FROM dns_waitingreply w, dns_user u
			WHERE firstdate <= $timetouse AND w.userid=u.id
			";

my $sth = dbexecute($query,$dbh,LOG);
while (my $ref = $sth->fetchrow_hashref()) {
# for each user, 
	$userid = $ref->{'userid'};
	print LOG logtimestamp() . " " . $LOG_PREFIX . " " . 
		sprintf($str_log_deleting_user_x{$SITE_DEFAULT_LANGUAGE},$userid . " / " . $ref->{'login'}) . "\n";	

	# TODO send email to warn 
	
	# mark zones to be deleted !
	$query = "UPDATE dns_zone set status='D' WHERE userid='" . $userid . "'";
	my $sth2 = dbexecute($query,$dbh,LOG);
	$query = "DELETE FROM dns_user WHERE id='" . $userid . "'";
	my $sth2 = dbexecute($query,$dbh,LOG);
	$query = "DELETE FROM dns_waitingreply WHERE userid='" . $userid . "'";
	my $sth2 = dbexecute($query,$dbh,LOG);
}

$query = "SELECT zone,zonetype
		FROM dns_zone WHERE status='D'";

	$sth = dbexecute($query,$dbh,LOG);

@todelete=();
while (my $ref = $sth->fetchrow_hashref()) {
# for each zone, 
	$zonename = $ref->{'zone'};
	$zonetype = $ref->{'zonetype'};
	print LOG logtimestamp() . " " . $LOG_PREFIX . " " . 
			sprintf($str_log_deleting_zone_x{$SITE_DEFAULT_LANGUAGE},$zonename) . "\n";	

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
	# delete from dns_conf* dns_log dns_record if not already done
	$query = "SELECT id,zonetype FROM dns_zone WHERE zone='" . $_ . "'";
 	my $sth = dbexecute($query,$dbh,LOG);
	my $ref = $sth->fetchrow_hashref();
	$query = "DELETE FROM dns_conf";
	if($ref->{'zonetype'} == 'P'){
		$query .= "primary";
	}else{
		$query .= "secondary";
	}
	$query .= " WHERE zoneid='" . $ref->{'id'} . "'";
 	my $sth = dbexecute($query,$dbh,LOG);

	$query = "DELETE FROM dns_log WHERE  zoneid='" . $ref->{'id'} . "'";
        my $sth = dbexecute($query,$dbh,LOG);

	if($ref->{'zonetype'} == 'P'){
		$query = "DELETE FROM dns_record WHERE  zoneid='" . $ref->{'id'} . "'";
	        my $sth = dbexecute($query,$dbh,LOG);
	}

	$query = "DELETE from dns_zone WHERE zone='" . $_ . "'";
 	my $sth = dbexecute($query,$dbh,LOG);
}

close LOG;
