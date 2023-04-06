create database xnamedev;
use xnamedev;

CREATE TABLE dns_user (
	id	int auto_increment unique,
	login varchar(255) NOT NULL,
	email varchar(255) NOT NULL,
	password varchar(255) NOT NULL,
	valid enum('0','1') default '0',
	creationdate timestamp,
	KEY login (login),
	KEY userid(id)
);


CREATE TABLE dns_zone (
	id int auto_increment unique,
	zone varchar(255) NOT NULL,
	userid int NOT NULL,
	zonetype enum('P','S','B') NOT NULL,
	KEY zone (zone,zonetype),
	KEY userid (userid),
	KEY zoneid (id)
);


CREATE TABLE dns_confsecondary (
	zoneid int NOT NULL,
	masters varchar(255) NULL,
	xfer varchar(255) NULL default 'any',
	tsig text NULL,
	serial varchar(255) NOT NULL,
	KEY conf_id (zoneid)
);


CREATE TABLE dns_confprimary (
	zoneid int NOT NULL,
	serial varchar(255) NOT NULL,
	refresh varchar(255) NOT NULL default '10800',
	retry varchar(255) NOT NULL default '1800',
	expiry varchar(255) NOT NULL default '3600000',
	minimum varchar(255) NOT NULL default '43200',
	xfer varchar(255) NULL default 'any',
	KEY conf_id (zoneid)
);

CREATE TABLE dns_log (
	zoneid int NOT NULL,
	date timestamp(14) NOT NULL,
	content varchar(255) NOT NULL,
	status enum('E','I','W') default 'I',
	KEY log_id(zoneid),
	KEY status_id(status),
	KEY date_id(date)
);

CREATE TABLE dns_logparser (
	line varchar(255)
);

CREATE TABLE dns_session (
	sessionID varchar(255) NOT NULL,
	userid int NOT NULL,
	date timestamp(14) NOT NULL,
	KEY session_id(sessionID)
);

CREATE TABLE dns_modified (
	zoneid int NOT NULL,
	KEY zone_id(zoneid)	
);

CREATE TABLE dns_deleted (
	zonename varchar(255) NOT NULL,
	zonetype enum('P','S','B') NOT NULL,
	userid int NOT NULL
);

CREATE TABLE dns_generate (
	busy enum('0','1')
);



CREATE TABLE dns_record (
	zoneid int NOT NULL,
	type enum('MX','NS','A','AZONE','CNAME','DNAME','A6','AAAA','SUBNS') NOT NULL,
	val1 varchar(255) NULL,
	val2 varchar(255) NOT NULL,
	KEY record_id(zoneid),
	KEY type_id(type)
);

CREATE TABLE dns_recovery (
	userid int NOT NULL,
	id varchar(255),
	insertdate timestamp(14),
	KEY user_id(userid),
	KEY session_id(id)
);


CREATE TABLE dns_waitingreply (
	userid int NOT NULL,
	firstdate timestamp(14),
	email varchar(255) NOT NULL,
	id varchar(255) NOT NULL,
	KEY firstdate_id (firstdate)
);

