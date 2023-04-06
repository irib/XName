###############################################################
#	This file is part of XName.org project                    #
#	See	http://www.xname.org/ for details                     #
#	                                                          #
#	License: GPLv2                                            #
#	See LICENSE file, or http://www.gnu.org/copyleft/gpl.html #
#	                                                          #
#	Author(s): Yann Hirou <hirou@xname.org>                   #
###############################################################



####################################
#          SITE variables          #
####################################
$SITE_NAME="XName DEMO";
$SITE_NS="ns0.xname.org";
$SITE_NS_IP="213.11.111.252";

####################################
#        DATABASE variables        #
####################################
$DB_HOST='127.0.0.1';
$DB_PORT='3306';
$DB_USER='xnameuser';
$DB_PASSWORD='password';
$DB_NAME='xnamedev';


####################################
#          NAMED variables         #
####################################
$NAMED_CONF = "/var/chroot/named/etc/named.conf";
$NAMED_CONF_HEADERS = "/var/chroot/named/etc/named_headers";
$NAMED_DATA_DIR = "/var/chroot/named/var/named/";
$NAMED_DATA_CHROOTED_DIR = "/var/named/";
$NAMED_MASTERS_DIR = "masters/";
$NAMED_SLAVES_DIR = "slaves/";
$NAMED_TMP_DIR = "/tmp/";

####################################
#          EMAIL variables         #
####################################
$EMAIL_ADMIN = "demo\@xname.org";
$EMAIL_FROM = "XName DEMO <demo\@xname.org>";
$EMAIL_SUBJECT_PREFIX = "[XName.org]";
$EMAIL_SIGNATURE = "
-- 
Xname.org Team
xname\@xname.org
";


####################################
#         SYSTEM variables         #
####################################
$SYSLOG_FILE = "/var/log/daemon.log";


####################################
#           Commands               #
####################################
$RM_COMMAND = '/bin/rm';
$CP_COMMAND = '/bin/cp';
$RNDC_COMMAND='/usr/local/bin/rndc';
$CHECKCONF_COMMAND = '/usr/local/sbin/named-checkconf';

####################################
#        SHEDULER variables        #
####################################
# $SCHEDULER_RUN_AS_DAEMON
# if you want to run a scheduler, set to 1.
# else, set to 0, and run scripts from crontab
# scripts are: bin/delete.pl, bin/generate.pl, bin/insertlogs.pl,
# bin/sqloptimize.pl. in case of multi-server installation, run also 
# following scripts: bin/pushtoservers.pl, bin/getremotelogs.pl.
$SCHEDULER_RUN_AS_DAEMON=1; 
# following delays can be in min, hour or day. Just precise "H" "M" or "D"
$DELAY_GENERATE="1H"; # delay to re-generate named.conf and reload zones
$DELAY_INSERTLOGS="10M"; # delay to insert logs
$DELAY_RETRIEVE_REMOTE_LOGS="10M";
$DELAY_OPTIMIZE="7D"; # delay to optimize




####################################
#    Multi Server variables        #
####################################
$MULTISERVER='1'; # 0 or 1
$MULTISERVER_EMAILUPDATE=0; # put it to 1 for mail update instead of scp NOT USED
$REMOTE_SERVER_DIR='/tmp/remote/';
$REMOTE_SERVER_LOGS='/tmp/remotelogs/';   # has to be different from $REMOTE_SERVER_DIR
$SCP_COMMAND='/usr/local/bin/scp';


####################################
#           LOG variables          #
####################################
$LOG_FILE='/tmp/xname.log';
$LOG_PREFIX='XName-';
$LOG_HOURS_TO_KEEP='6';
$LOG_NB_MIN_TO_KEEP='10';
# pattern matching for logs
# sample line:
# Mar  3 05:25:54 unlimited named[30467]: zone foo.com/IN: refresh: failure trying master 10.0.0.1#53: timed out
$LOG_PATTERN_NAMED = "^([^\\s]+)\\s+([^\\s]+)\\s+([^\\s]+)\\s+[^\\s]+\\s+named[^\\s]+\\s(.*)";
# $1 : month
# $2 : day
# $3 : hour:min:sec
# $4 : content
# content should match ZONE or FILE:
# foo.com transfer of 'foo.com/IN' from 176.26.0.2#53: failed while receiving responses: REFUSED
$LOG_PATTERN_ZONE = "\\s('|)([^\\/\\s]+)\\/IN";
# $2 : zonename
$pathtofile=$NAMED_DATA_CHROOTED_DIR;
$pathtofile=~ s/\//\\\//g;
$masterslavedirs="(" . $NAMED_MASTERS_DIR . "|" . $NAMED_SLAVES_DIR . ")";
$masterslavedirs =~ s/\///g;
# foo dns_master_load: /var/named/masters/foo:3: ignoring out-of-zone data (bar.com)
$LOG_PATTERN_FILE = $pathtofile . $masterslavedirs . "\\/([^:]+):";
# $2 : zonename


return 1;
