#
# Table structure for table 'tx_sevenpack_references'
#
CREATE TABLE tx_sevenpack_references (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,

	bibtype int(11) DEFAULT '0' NOT NULL,
	citeid tinytext NOT NULL,
	title text NOT NULL,
	journal tinytext NOT NULL,
	year int(11) DEFAULT '0' NOT NULL,
	month int(11) DEFAULT '0' NOT NULL,
	day int(11) DEFAULT '0' NOT NULL,
	volume tinytext NOT NULL,
	number tinytext NOT NULL,
	number2 tinytext NOT NULL,
	pages tinytext NOT NULL,
	abstract text NOT NULL,
	full_text longtext NOT NULL,
	full_text_tstamp int(11) DEFAULT '0' NOT NULL,
	full_text_file_url tinytext NOT NULL,
	affiliation text NOT NULL,
	note text NOT NULL,
	annotation text NOT NULL,
	keywords text NOT NULL,
	tags text NOT NULL,
	file_url tinytext NOT NULL,
	web_url tinytext NOT NULL,
	web_url2 tinytext NOT NULL,
	misc tinytext NOT NULL,
	misc2 tinytext NOT NULL,
	editor tinytext NOT NULL,
	publisher tinytext NOT NULL,
	howpublished tinytext NOT NULL,
	address tinytext NOT NULL,
	series tinytext NOT NULL,
	edition tinytext NOT NULL,
	chapter tinytext NOT NULL,
	booktitle text NOT NULL,
	school tinytext NOT NULL,
	institute tinytext NOT NULL,
	organization tinytext NOT NULL,
	institution tinytext NOT NULL,
	event_name tinytext NOT NULL,
	event_place tinytext NOT NULL,
	event_date tinytext NOT NULL,
	state int(11) DEFAULT '0' NOT NULL,
	type tinytext NOT NULL,
	language tinytext NOT NULL,
	ISBN tinytext NOT NULL,
	ISSN tinytext NOT NULL,
	DOI tinytext NOT NULL,
	extern tinyint(3) DEFAULT '0' NOT NULL,
	reviewed tinyint(3) DEFAULT '0' NOT NULL,
	in_library tinyint(3) DEFAULT '0' NOT NULL,
	borrowed_by tinytext NOT NULL,
	
	PRIMARY KEY (uid),
	KEY parent (pid)
);


#
# Table structure for table 'tx_sevenpack_authors'
#
CREATE TABLE tx_sevenpack_authors (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,

	forename tinytext NOT NULL,
	surname tinytext NOT NULL,
	url tinytext NOT NULL,
	fe_user_id int(11) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);


#
# Table structure for table 'tx_sevenpack_authorships'
#
CREATE TABLE tx_sevenpack_authorships (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,

	pub_id int(11) DEFAULT '0' NOT NULL,
	author_id int(11) DEFAULT '0' NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY publication (pub_id),
	KEY author (author_id)
);
