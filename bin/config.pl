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
#           LOG variables          #
####################################
$LOG_FILE='/tmp/xname.log';
$LOG_PREFIX='XName-';
$LOG_HOURS_TO_KEEP='6';


####################################
#         SYSTEM variables         #
####################################
$SYSLOG_FILE = "/var/log/daemon.log";


####################################
#           Commands               #
####################################
$RM_COMMAND = '/bin/rm';
$RNDC_COMMAND='/usr/local/bin/rndc';
$CHECKCONF_COMMAND = '/usr/local/sbin/named-checkconf';
$RELOADALL_COMMAND = '/etc/init.d/named stop && /usr/bin/killall named && /etc/init.d/named start';
