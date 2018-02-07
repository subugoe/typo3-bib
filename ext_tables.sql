#
# Table structure for table 'tx_bib_domain_model_reference'
#
CREATE TABLE tx_bib_domain_model_reference (
  uid                INT(11)                NOT NULL AUTO_INCREMENT,
  pid                INT(11) DEFAULT '0'    NOT NULL,
  tstamp             INT(11) DEFAULT '0'    NOT NULL,
  crdate             INT(11) DEFAULT '0'    NOT NULL,
  cruser_id          INT(11) DEFAULT '0'    NOT NULL,
  sorting            INT(10) DEFAULT '0'    NOT NULL,
  deleted            TINYINT(4) DEFAULT '0' NOT NULL,
  hidden             TINYINT(4) DEFAULT '0' NOT NULL,

  bibtype            INT(11) DEFAULT '0'    NOT NULL,
  citeid             TINYTEXT               NOT NULL,
  title              TEXT                   NOT NULL,
  journal            TINYTEXT               NOT NULL,
  year               INT(11) DEFAULT '0'    NOT NULL,
  month              INT(11) DEFAULT '0'    NOT NULL,
  day                INT(11) DEFAULT '0'    NOT NULL,
  volume             TINYTEXT               NOT NULL,
  number             TINYTEXT               NOT NULL,
  number2            TINYTEXT               NOT NULL,
  pages              TINYTEXT               NOT NULL,
  abstract           TEXT                   NOT NULL,
  full_text          LONGTEXT               NOT NULL,
  full_text_tstamp   INT(11) DEFAULT '0'    NOT NULL,
  full_text_file_url TINYTEXT               NOT NULL,
  affiliation        TEXT                   NOT NULL,
  note               TEXT                   NOT NULL,
  annotation         TEXT                   NOT NULL,
  keywords           TEXT                   NOT NULL,
  tags               TEXT                   NOT NULL,
  file_url           TINYTEXT               NOT NULL,
  web_url            TINYTEXT               NOT NULL,
  web_url_date       TINYTEXT               NOT NULL,
  web_url2           TINYTEXT               NOT NULL,
  web_url2_date      TINYTEXT               NOT NULL,
  misc               TINYTEXT               NOT NULL,
  misc2              TINYTEXT               NOT NULL,
  editor             TINYTEXT               NOT NULL,
  publisher          TINYTEXT               NOT NULL,
  howpublished       TINYTEXT               NOT NULL,
  address            TINYTEXT               NOT NULL,
  series             TINYTEXT               NOT NULL,
  edition            TINYTEXT               NOT NULL,
  chapter            TINYTEXT               NOT NULL,
  booktitle          TEXT                   NOT NULL,
  school             TINYTEXT               NOT NULL,
  institute          TINYTEXT               NOT NULL,
  organization       TINYTEXT               NOT NULL,
  institution        TINYTEXT               NOT NULL,
  event_name         TINYTEXT               NOT NULL,
  event_place        TINYTEXT               NOT NULL,
  event_date         TINYTEXT               NOT NULL,
  state              INT(11) DEFAULT '0'    NOT NULL,
  type               TINYTEXT               NOT NULL,
  language           TINYTEXT               NOT NULL,
  ISBN               TINYTEXT               NOT NULL,
  ISSN               TINYTEXT               NOT NULL,
  DOI                TINYTEXT               NOT NULL,
  extern             TINYINT(3) DEFAULT '0' NOT NULL,
  reviewed           TINYINT(3) DEFAULT '0' NOT NULL,
  in_library         TINYINT(3) DEFAULT '0' NOT NULL,
  borrowed_by        TINYTEXT               NOT NULL,

  PRIMARY KEY (uid),
  KEY parent (pid)
);

#
# Table structure for table 'tx_bib_domain_model_author'
#
CREATE TABLE tx_bib_domain_model_author (
  uid        INT(11)                NOT NULL AUTO_INCREMENT,
  pid        INT(11) DEFAULT '0'    NOT NULL,
  tstamp     INT(11) DEFAULT '0'    NOT NULL,
  crdate     INT(11) DEFAULT '0'    NOT NULL,
  cruser_id  INT(11) DEFAULT '0'    NOT NULL,
  deleted    TINYINT(4) DEFAULT '0' NOT NULL,

  forename   TINYTEXT               NOT NULL,
  surname    TINYTEXT               NOT NULL,
  url        TINYTEXT               NOT NULL,
  fe_user_id INT(11) DEFAULT '0'    NOT NULL,

  PRIMARY KEY (uid),
  KEY parent (pid)
);

#
# Table structure for table 'tx_bib_domain_model_authorships'
#
CREATE TABLE tx_bib_domain_model_authorships (
  uid       INT(11)                NOT NULL AUTO_INCREMENT,
  pid       INT(11) DEFAULT '0'    NOT NULL,
  deleted   TINYINT(4) DEFAULT '0' NOT NULL,

  pub_id    INT(11) DEFAULT '0'    NOT NULL,
  author_id INT(11) DEFAULT '0'    NOT NULL,
  sorting   INT(10) DEFAULT '0'    NOT NULL,

  PRIMARY KEY (uid),
  KEY publication (pub_id),
  KEY author (author_id)
);
