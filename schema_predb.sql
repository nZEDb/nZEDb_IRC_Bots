DROP TABLE IF EXISTS groups;
CREATE TABLE groups (
	id INT(11) NOT NULL AUTO_INCREMENT,
	name VARCHAR(255) NOT NULL DEFAULT '',
	PRIMARY KEY  (id)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE INDEX ix_groups_id ON groups(id);

INSERT INTO groups (name) VALUES ('alt.binaries.boneless');
INSERT INTO groups (name) VALUES ('alt.binaries.cd.image');
INSERT INTO groups (name) VALUES ('alt.binaries.console.ps3');
INSERT INTO groups (name) VALUES ('alt.binaries.erotica');
INSERT INTO groups (name) VALUES ('alt.binaries.games.nintendods');
INSERT INTO groups (name) VALUES ('alt.binaries.games.wii');
INSERT INTO groups (name) VALUES ('alt.binaries.games.xbox360');
INSERT INTO groups (name) VALUES ('alt.binaries.inner-sanctum');
INSERT INTO groups (name) VALUES ('alt.binaries.mom');
INSERT INTO groups (name) VALUES ('alt.binaries.moovee');
INSERT INTO groups (name) VALUES ('alt.binaries.movies.divx');
INSERT INTO groups (name) VALUES ('alt.binaries.sony.psp');
INSERT INTO groups (name) VALUES ('alt.binaries.sounds.mp3.complete_cd');
INSERT INTO groups (name) VALUES ('alt.binaries.sounds.flac');
INSERT INTO groups (name) VALUES ('alt.binaries.teevee');
INSERT INTO groups (name) VALUES ('alt.binaries.warez');


DROP TABLE IF EXISTS predb;
CREATE TABLE predb (
	id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	title VARCHAR(255) NOT NULL DEFAULT '',
	size VARCHAR(50) NULL,
	category VARCHAR(255) NULL,
	predate DATETIME DEFAULT NULL,
	source VARCHAR(50) NOT NULL DEFAULT '',
	md5 VARCHAR(32) NOT NULL DEFAULT '0',
	sha1 VARCHAR(40) NOT NULL DEFAULT '0',
	requestid INT(10) UNSIGNED NOT NULL DEFAULT '0',
	groupid INT(10) UNSIGNED NOT NULL DEFAULT '0',
	nuked TINYINT(1) NOT NULL DEFAULT '0',
	nukereason VARCHAR(255) NULL,
	files VARCHAR(50) NULL,
	filename VARCHAR(255) NOT NULL DEFAULT '',
	nfo VARCHAR(255) DEFAULT NULL,
	dumped TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
	shared TINYINT(1) NOT NULL DEFAULT '0',
	PRIMARY KEY (id)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1;

CREATE INDEX ix_predb_title ON predb (title);
CREATE INDEX ix_predb_predate ON predb (predate);
CREATE INDEX ix_predb_source ON predb (source);
CREATE INDEX ix_predb_requestid on predb (requestid, groupid);
