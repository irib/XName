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
use Date::Parse;


####################################
#        DATABASE variables        #
####################################
$DB_HOST='127.0.0.1';
$DB_PORT='3306';
$DB_USER='xnameuser';
$DB_PASSWORD='password';
$DB_NAME='xnamedev';

####################################
#           LOG variables          #
####################################
$LOG_FILE='/tmp/xname.log';
$LOG_PREFIX='XName-insertlogs';


####################################
#         SYSTEM variables         #
####################################
$SYSLOG_FILE = "/var/log/daemon.log";


########################################################################
# STOP STOP STOP STOP STOP STOP STOP STOP STOP STOP STOP STOPS STOP STOP
#
# Do not edit anything below this line           
########################################################################


# parse log file, look for named logs
# retrieve : - date (timestamp(14) format),
# 			 - zone name
#            - status (Error, Information, Warning)

# delete logs older than 3 hours

# TODO : manage case Secondary AND primary
# 	==> must have a table with server & zone & type
# 		and parse logs on each server (no centralized 
# 		management)

# Current solution : print logs whenever primary or secondary
# or both



# Mar  3 05:25:54 unlimited named[30467]: zone lukla.com/IN: refresh: failure trying master 24.244.15.158#53: timed out
$PATTERN_NAMED = "^([^\\s]+)\\s+([^\\s]+)\\s+([^\\s]+)\\s+[^\\s]+\\s+named[^\\s]+\\s(.*)";
# $PATTERN_NAMED = "^([^\\s]+)\\s+([^\\s]+)\\s+([^\\s]+)\\s+([^\\s]+)";
# $1 : month
# $2 : day
# $3 : hour:min:sec
# $4 : content

# connect to DB
# retrieve last parsed line

# open file
# go to last parsed line
# split line
# insert in DB
# next line


# connect to DB
$dsn = "DBI:mysql:" . $DB_NAME . ";host=" . $DB_HOST . ";port=" . $DB_PORT;
$dbh = DBI->connect($dsn, $DB_USER, $DB_PASSWORD);

open(LOG, ">>" . $LOG_FILE);


# retrieve last parsed line
$query = "SELECT line FROM dns_logparser";
my $sth = $dbh->prepare($query);
if(!$sth){
	print LOG $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
}
if (!$sth->execute) {
	print LOG $LOG_PREFIX . " : Error:" . $sth->errstr . "\n";
}
$ref = $sth->fetchrow_hashref();
$lastline = $ref->{'line'};


# open file
open(FILE, "< " . $SYSLOG_FILE) || die "Error";
# go to last parsed line
if($lastline ne ""){
	$currentline=<FILE>;
	# compare currentline date and $lastline date
	# Apr 14 04:25:44 unlimited
	$currentline =~ /^([^\s]+)\s+([^\s]+)\s+([^\s]+)\s+/;
	$currentlinedate=getTimestamp($1,$2,$3);

	$lastline =~ /^([^\s]+)\s+([^\s]+)\s+([^\s]+)\s+/;
	$lastlinedate=getTimestamp($1,$2,$3);

	# if currentline date > lastline date, file has been rotated
	if($currentlinedate < $lastlinedate){
		while(<FILE> ne $lastline){
		}
	}
}
# if no line is read, don't save last read line !
$readline = 0;

while(<FILE>){
	$readline++;
	$line = $_;
	my $status;
	my $zonename;
	my $content;
	my $newcontent;
	
 	if(/$PATTERN_NAMED$/){
		# $1 : month
		# $2 : day
		# $3 : hour:min:sec
		# $4 : content
		my $content = $4;
		# split line
		my $timestamp = getTimestamp($1,$2,$3);
		
		# retrieve zonename...
		if($content =~ /\s('|)([^\/\s]+)\/IN/){
			$zonename = $2;
		}else{
		
			if($content =~ /\/var\/named\/(masters|slaves)\/([^:]+):/){
				$zonename = $2;
			}else{	
				print LOG $LOG_PREFIX . " : Not matching : $content\n";
			}
		}
		
		# status : Error, Warning, Information
		# remove zonename from matching words
		$newcontent=$content;
		$newcontent =~ s/$zonename/ /g;
#		print "Zone $zonename Content: $content\n\tNewcontent: $newcontent\n";
		if($newcontent =~ /(failed|failure|non-authoritative|denied|exceeded|expired)/){
			$status = 'E';
		}else{
			if($newcontent =~ /(started|transfered|end of transfer|loaded|sending notifies)/){
				$status = 'I';
			}else{
				$status = 'W';
			}
		}
		
		# insert in DB
		# escape from mysql... 
		$zonename =~ s/'/\\'/g;
		$content =~ s/'/\\'/g;
		$zonename =~ s/"/\\"/g;		
		$content =~ s/"/\\"/g;
		
		# select zoneid
		$query = "SELECT id FROM dns_zone WHERE zone='" . $zonename .
		"'";
		my $sth = $dbh->prepare($query);
		if(!$sth){
			print LOG $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
		}
		if (!$sth->execute) {
			print LOG $LOG_PREFIX . " : Error:" . $sth->errstr . "\n";
		}
		$ref = $sth->fetchrow_hashref();
		if(!$ref->{'id'}){
			# check if zone exists with a "." at the end
			$query = "SELECT id FROM dns_zone WHERE zone='" . $zonename .
			".'";
			my $sth = $dbh->prepare($query);
			if(!$sth){
				print LOG $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
			}
			if (!$sth->execute) {
				print LOG $LOG_PREFIX . " : Error:" . $sth->errstr . "\n";
			}
			$ref = $sth->fetchrow_hashref();
			if($ref->{'id'}){
		
				$query = "INSERT INTO dns_log (zoneid, date, content, status)
				VALUES ('" . $ref->{'id'} . "','" . $timestamp . "','" . $content . 
				"','" . $status . "')";

				my $sth = $dbh->prepare($query);
				if(!$sth){
					print LOG $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
				}
				if (!$sth->execute) {
					print LOG $LOG_PREFIX . " : Error:" . $sth->errstr . "\n";
				}
			}
		}else{
			$query = "INSERT INTO dns_log (zoneid, date, content, status)
			VALUES ('" . $ref->{'id'} . "','" . $timestamp . "','" . $content . 
			"','" . $status . "')";
			my $sth = $dbh->prepare($query);
			if(!$sth){
				print LOG $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
			}
			if (!$sth->execute) {
				print LOG $LOG_PREFIX . " : Error:" . $sth->errstr . "\n";
			}
		}
		
	}else{
#		print "DONT MATCH : $_\n";
	}
}
close(FILE);


# save last line in DB
if($readline){
	$line =~ s/'/\\'/g;
	$line =~ s/"/\\"/g;
	$query = "DELETE FROM dns_logparser where 1>0";
	my $sth = $dbh->prepare($query);
	if(!$sth){
		print LOG $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
	}
	if (!$sth->execute) {
		print LOG $LOG_PREFIX . " : Error:" . $sth->errstr . "\n";
	}

	$query = "INSERT INTO dns_logparser (line) values ('" . $line . "')";
	my $sth = $dbh->prepare($query);
	if(!$sth){
		print LOG $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
	}
	if (!$sth->execute) {
		print LOG $LOG_PREFIX . " : Error:" . $sth->errstr . "\n";
	}
}


deleteOldLogs(60*6);



close(LOG);








# ###################################################################"


sub deleteOldLogs(){
	# delete logs older than $nbmins minutes
	$nbmins = @_[0];
	$time = time();
	
	$timenew = $time - $nbmins*60;
	
 	@lt = localtime($timenew);
	
	$sec = $lt[0][0];
	$min = $lt[0][1];
	$hour = $lt[0][2];
	$mday = $lt[0][3];
	$mon = $lt[0][4];
	

	if($hour < 10){
		$hour = "0" . $hour;
	}
	if($min < 10){
		$min = "0" . $min;
	}
	if($sec < 10){
		$sec = "0" . $sec;
	}
	$hourminsec = $hour . ":" . $min . ":" . $sec;
	$month = $mon + 1;
	$day = $mday;
	

	$timestamp = getTimestamp($month,$day,$hourminsec);
	
	$query = "DELETE FROM dns_log WHERE
	date < " . $timestamp;
	my $sth = $dbh->prepare($query);
	if(!$sth){
		print LOG $LOG_PREFIX . " : Error:" . $dbh->errstr . "\n";
	}
	if (!$sth->execute) {
		print LOG $LOG_PREFIX . " : Error:" . $sth->errstr . "\n";
	}	

}




# ###################################################################"

sub getTimestamp(){
	my $month = @_[0];
	my $day = @_[1];
	my $time = @_[2];

	my $result;
	my $monthpart;
	my $daypart;
	my $timepart;
	my $year;
	
	if(!($month =~ /[0123456789]/)){
		$monthpart = getMonthNumber($month);
	}else{
		if($month < 10){
			$monthpart = "0" . $month;
		}else{
			$monthpart = $month;
		}
	}
	if($day < 10){
		$daypart = "0" . $day;
	}else{
		$daypart = $day;
	}
	
	$timepart = $time;
	$timepart =~ s/://g;

	# WARNING : what will happen 31/12 and 01/01 ?	
	$year = localtime->year() + 1900;

	if(localtime->mon() == 0){ # Jan
		if($monthpart ne "01"){ # month != jan ==> dec or previous
			$year = $year - 1;
		}
	}

	$result = $year . $monthpart . $daypart . $timepart;
	return $result;
}




# ###################################################################"

sub getMonthNumber(){
	my $month = @_[0];
	my $result;
	my $i;
	my @months = ("Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec");
	$i=1;
	foreach(@months){
		if($month eq $_){
			if($i < 10){
				$result = "0" . $i;
			}else{
				$result = $i;
			}
			return $result;
		}
		$i++;
	}
	return "00";
}


