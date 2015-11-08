DROP TABLE IF EXISTS groups;
CREATE TABLE groups (
	id TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
	name VARCHAR(255) NOT NULL DEFAULT '',
	PRIMARY KEY  (id)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1 ;

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
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	title VARCHAR(255) NOT NULL DEFAULT '',
	groupname VARCHAR(255) NOT NULL DEFAULT '',
	reqid INT UNSIGNED NOT NULL DEFAULT '0',
	PRIMARY KEY (id)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1;

CREATE INDEX ix_predb_title ON predb (title);
CREATE INDEX ix_predb_reqid on predb (reqid);
