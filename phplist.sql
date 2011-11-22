begin;

set search_path to phplist;

CREATE TABLE eventlog (
  id serial NOT NULL,
  entered timestamp NULL default NULL,
  page varchar(100) NULL default NULL,
  entry text,
  PRIMARY KEY  (id)
);


CREATE TABLE keymanager_keydata (
  name varchar(255) NOT NULL default '',
  id integer NOT NULL,
  data text,
  PRIMARY KEY  (name,id)
);


CREATE TABLE keymanager_keys (
  id serial NOT NULL,
  keyid varchar(255) NOT NULL,
  email varchar(255) NULL default NULL,
  name varchar(255) NULL default NULL,
  fingerprint varchar(255) NULL default NULL,
  can_encrypt integer NULL default NULL,
  can_sign integer NULL default NULL,
  deleted integer default '0',
  PRIMARY KEY  (id)
);


CREATE TABLE phplist_admin (
  id serial NOT NULL,
  loginname varchar(25) NOT NULL default '',
  namelc varchar(255) NULL default NULL,
  email varchar(255) NOT NULL default '',
  created timestamp NULL default NULL,
  modified timestamp NOT NULL default CURRENT_TIMESTAMP,
  modifiedby varchar(25) NULL default NULL,
  password varchar(255) NULL default NULL,
  passwordchanged date NULL default NULL,
  superuser integer default '0',
  disabled integer default '0',
  PRIMARY KEY  (id)
);
CREATE UNIQUE INDEX loginname ON phplist_admin (loginname );


CREATE TABLE phplist_admin_attribute (
  adminattributeid integer NOT NULL default '0',
  adminid integer NOT NULL default '0',
  value varchar(255) NULL default NULL,
  PRIMARY KEY  (adminattributeid,adminid)
);


CREATE TABLE phplist_admin_task (
  adminid integer NOT NULL default '0',
  taskid integer NOT NULL default '0',
  level integer NULL default NULL,
  PRIMARY KEY  (adminid,taskid)
);


CREATE TABLE phplist_adminattribute (
  id serial NOT NULL,
  name varchar(255) NOT NULL default '',
  type varchar(30) NULL default NULL,
  listorder integer NULL default NULL,
  default_value varchar(255) NULL default NULL,
  required integer NULL default NULL,
  tablename varchar(255) NULL default NULL,
  PRIMARY KEY  (id)
);


CREATE TABLE phplist_attachment (
  id serial NOT NULL,
  filename varchar(255) NULL default NULL,
  remotefile varchar(255) NULL default NULL,
  mimetype varchar(255) NULL default NULL,
  description text,
  size integer NULL default NULL,
  PRIMARY KEY  (id)
);


CREATE TABLE phplist_bounce (
  id serial NOT NULL,
  date timestamp NULL default NULL,
  header text,
  data bytea,
  status varchar(255) NULL default NULL,
  comment text,
  PRIMARY KEY  (id)
);
CREATE INDEX dateindex ON phplist_bounce (date);


CREATE TABLE phplist_bounceregex (
  id serial NOT NULL,
  regex varchar(255) NULL default NULL,
  action varchar(255) NULL default NULL,
  listorder integer default '0',
  admin integer NULL default NULL,
  comment text,
  status varchar(255) NULL default NULL,
  count integer default '0',
  PRIMARY KEY  (id)
);
CREATE UNIQUE INDEX regex ON phplist_bounceregex (regex);


CREATE TABLE phplist_bounceregex_bounce (
  regex integer NOT NULL default '0',
  bounce integer NOT NULL default '0',
  PRIMARY KEY  (regex,bounce)
);


CREATE TABLE phplist_config (
  item varchar(35) NOT NULL default '',
  value text,
  editable integer default '1',
  type varchar(25) NULL default NULL,
  PRIMARY KEY  (item)
);


CREATE TABLE phplist_eventlog (
  id serial NOT NULL,
  entered timestamp NULL default NULL,
  page varchar(100) NULL default NULL,
  entry text,
  PRIMARY KEY  (id)
);


CREATE TABLE phplist_linktrack (
  linkid serial NOT NULL,
  messageid integer NOT NULL default '0',
  userid integer NOT NULL default '0',
  url varchar(255) NULL default NULL,
  forward text,
  firstclick timestamp NULL default NULL,
  latestclick timestamp NOT NULL default CURRENT_TIMESTAMP,
  clicked integer default '0',
  PRIMARY KEY  (linkid)
);
CREATE UNIQUE INDEX messageid_lt ON phplist_linktrack (messageid, userid, url);
CREATE INDEX miduidurlindex ON phplist_linktrack (messageid, userid, url);


CREATE TABLE phplist_linktrack_forward (
  id serial NOT NULL,
  url varchar(255) NULL default NULL,
  personalise integer default '0',
  PRIMARY KEY  (id)
);
CREATE UNIQUE INDEX ulrunique ON phplist_linktrack_forward (url);
CREATE INDEX urlindex_forward ON phplist_linktrack_forward (url);


CREATE TABLE phplist_linktrack_ml (
  messageid integer NOT NULL,
  forwardid integer NOT NULL,
  firstclick timestamp NULL default NULL,
  latestclick timestamp NULL default NULL,
  total integer default '0',
  clicked integer default '0',
  htmlclicked integer default '0',
  textclicked integer default '0',
  PRIMARY KEY  (messageid,forwardid)
);
CREATE INDEX midindex ON phplist_linktrack_ml (messageid);
CREATE INDEX fwdindex ON phplist_linktrack_ml (forwardid);


CREATE TABLE phplist_linktrack_uml_click (
  id serial NOT NULL,
  messageid integer NOT NULL,
  userid integer NOT NULL,
  forwardid integer NULL default NULL,
  firstclick timestamp NULL default NULL,
  latestclick timestamp NULL default NULL,
  clicked integer default '0',
  htmlclicked integer default '0',
  textclicked integer default '0',
  PRIMARY KEY  (id)
);
CREATE UNIQUE INDEX miduidfwdid ON phplist_linktrack_uml_click (messageid, userid, forwardid);
CREATE INDEX midindex_uml ON phplist_linktrack_uml_click (messageid);
CREATE INDEX uidindex ON phplist_linktrack_uml_click (userid);
CREATE INDEX miduidindex ON phplist_linktrack_uml_click (messageid, userid);


CREATE TABLE phplist_linktrack_userclick (
  linkid integer NOT NULL default '0',
  userid integer NOT NULL default '0',
  messageid integer NOT NULL default '0',
  name varchar(255) NULL default NULL,
  data text,
  date timestamp NULL default NULL
);
CREATE INDEX linkusermessageindex ON phplist_linktrack_userclick (linkid, userid, messageid);


CREATE TABLE phplist_list (
  id serial NOT NULL,
  name varchar(255) NOT NULL default '',
  description text,
  entered timestamp NULL default NULL,
  listorder integer NULL default NULL,
  prefix varchar(10) NULL default NULL,
  modified timestamp NOT NULL default CURRENT_TIMESTAMP,
  active integer NULL default NULL,
  owner integer NULL default NULL,
  rssfeed varchar(255) NULL default NULL,
  PRIMARY KEY  (id)
);


CREATE TABLE phplist_listattr_bpleaseche (
  id serial NOT NULL,
  name varchar(255) NULL default NULL,
  listorder integer default '0',
  PRIMARY KEY  (id)
);
CREATE UNIQUE INDEX name_bpl ON phplist_listattr_bpleaseche (name);


CREATE TABLE phplist_listattr_bwheredoyo (
  id serial NOT NULL,
  name varchar(255) NULL default NULL,
  listorder integer default '0',
  PRIMARY KEY  (id)
);
CREATE UNIQUE INDEX name_bwh ON phplist_listattr_bwheredoyo (name);


CREATE TABLE phplist_listattr_cbgroup (
  id serial NOT NULL,
  name varchar(255) NULL default NULL,
  listorder integer default '0',
  PRIMARY KEY  (id)
);
CREATE UNIQUE INDEX name_cbg ON phplist_listattr_cbgroup (name);


CREATE TABLE phplist_listattr_comments (
  id serial NOT NULL,
  name varchar(255) NULL default NULL,
  listorder integer default '0',
  PRIMARY KEY  (id)
);
CREATE UNIQUE INDEX name_ltr ON phplist_listattr_comments (name);


CREATE TABLE phplist_listattr_countries (
  id serial NOT NULL,
  name varchar(255) NULL default NULL,
  listorder integer default '0',
  PRIMARY KEY  (id)
);
CREATE UNIQUE INDEX name_lac ON phplist_listattr_countries (name);


CREATE TABLE phplist_listattr_hiddenfiel (
  id serial NOT NULL,
  name varchar(255) NULL default NULL,
  listorder integer default '0',
  PRIMARY KEY  (id)
);
CREATE UNIQUE INDEX name_lah ON phplist_listattr_hiddenfiel (name);


CREATE TABLE phplist_listattr_iagreewith (
  id serial NOT NULL,
  name varchar(255) NULL default NULL,
  listorder integer default '0',
  PRIMARY KEY  (id)
);
CREATE UNIQUE INDEX name_lai ON phplist_listattr_iagreewith (name);


CREATE TABLE phplist_listattr_most (
  id serial NOT NULL,
  name varchar(255) NULL default NULL,
  listorder integer default '0',
  PRIMARY KEY  (id)
);
CREATE UNIQUE INDEX name_lam ON phplist_listattr_most (name);


CREATE TABLE phplist_listattr_othercomme (
  id serial NOT NULL,
  name varchar(255) NULL default NULL,
  listorder integer default '0',
  PRIMARY KEY  (id)
);
CREATE UNIQUE INDEX name_lao ON phplist_listattr_othercomme (name);


CREATE TABLE phplist_listattr_publickey (
  id serial NOT NULL,
  name varchar(255) NULL default NULL,
  listorder integer default '0',
  PRIMARY KEY  (id)
);
CREATE UNIQUE INDEX name_lap ON phplist_listattr_publickey (name);


CREATE TABLE phplist_listattr_somemoreco (
  id serial NOT NULL,
  name varchar(255) NULL default NULL,
  listorder integer default '0',
  PRIMARY KEY  (id)
);
CREATE UNIQUE INDEX name_las ON phplist_listattr_somemoreco (name);


CREATE TABLE phplist_listmessage (
  id serial NOT NULL,
  messageid integer NOT NULL default '0',
  listid integer NOT NULL default '0',
  entered timestamp NULL default NULL,
  modified timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (id)
);
CREATE UNIQUE INDEX messageid_lm ON phplist_listmessage (messageid, listid);


CREATE TABLE phplist_listrss (
  listid integer NOT NULL default '0',
  type varchar(255) NULL default NULL,
  entered timestamp NULL default NULL,
  info text
);


CREATE TABLE phplist_listuser (
  userid integer NOT NULL default '0',
  listid integer NOT NULL default '0',
  entered timestamp NULL default NULL,
  modified timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (userid,listid)
);


CREATE TABLE phplist_message (
  id serial NOT NULL,
  subject varchar(255) NOT NULL default '',
  fromfield varchar(255) NOT NULL default '',
  tofield varchar(255) NOT NULL default '',
  replyto varchar(255) NOT NULL default '',
  message text,
  footer text,
  entered timestamp NULL default NULL,
  modified timestamp NOT NULL default CURRENT_TIMESTAMP,
  status varchar(255) NULL default NULL,
  processed integer default '0',
  userselection text,
  sent timestamp NULL default NULL,
  htmlformatted integer default '0',
  sendformat varchar(20) NULL default NULL,
  template integer NULL default NULL,
  astext integer default '0',
  ashtml integer default '0',
  astextandhtml integer default '0',
  viewed integer default '0',
  bouncecount integer default '0',
  sendstart timestamp NULL default NULL,
  aspdf integer default '0',
  astextandpdf integer default '0',
  rsstemplate varchar(100) NULL default NULL,
  owner integer NULL default NULL,
  embargo timestamp NULL default NULL,
  repeatinterval integer default '0',
  repeatuntil timestamp NULL default NULL,
  textmessage text,
  PRIMARY KEY  (id)
);


CREATE TABLE phplist_message_attachment (
  id serial NOT NULL,
  messageid integer NOT NULL default '0',
  attachmentid integer NOT NULL default '0',
  PRIMARY KEY  (id)
);


CREATE TABLE phplist_messagedata (
  name varchar(100) NOT NULL default '',
  id integer NOT NULL default '0',
  data text,
  PRIMARY KEY  (name,id)
);


CREATE TABLE phplist_rssitem (
  id serial NOT NULL,
  title varchar(100) NOT NULL default '',
  link varchar(100) NOT NULL default '',
  source varchar(255) NULL default NULL,
  list integer NULL default NULL,
  added timestamp NULL default NULL,
  processed integer default '0',
  astext integer default '0',
  ashtml integer default '0',
  PRIMARY KEY  (id)
);
CREATE INDEX title_rss ON phplist_rssitem (title, link);


CREATE TABLE phplist_rssitem_data (
  itemid integer NOT NULL default '0',
  tag varchar(100) NOT NULL default '',
  data text,
  PRIMARY KEY  (itemid,tag)
);


CREATE TABLE phplist_rssitem_user (
  itemid integer NOT NULL default '0',
  userid integer NOT NULL default '0',
  entered timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (itemid,userid)
);


CREATE TABLE phplist_sendprocess (
  id serial NOT NULL,
  started timestamp NULL default NULL,
  modified timestamp NOT NULL default CURRENT_TIMESTAMP,
  alive integer default '1',
  ipaddress varchar(50) NULL default NULL,
  page varchar(100) NULL default NULL,
  PRIMARY KEY  (id)
);


CREATE TABLE phplist_subscribepage (
  id serial NOT NULL,
  title varchar(255) NOT NULL default '',
  active integer default '0',
  owner integer NULL default NULL,
  PRIMARY KEY  (id)
);


CREATE TABLE phplist_subscribepage_data (
  id integer NOT NULL default '0',
  name varchar(100) NOT NULL default '',
  data text,
  PRIMARY KEY  (id,name)
);


CREATE TABLE phplist_task (
  id serial NOT NULL,
  page varchar(25) NULL default NULL,
  type varchar(25) NULL default NULL,
  PRIMARY KEY  (id)
);
CREATE UNIQUE INDEX page ON phplist_task (page);


CREATE TABLE phplist_template (
  id serial NOT NULL,
  title varchar(255) NOT NULL default '',
  template text,
  listorder integer NULL default NULL,
  PRIMARY KEY  (id)
);
CREATE UNIQUE INDEX title_template ON phplist_template (title);


CREATE TABLE phplist_templateimage (
  id serial NOT NULL,
  template integer NULL default NULL,
  mimetype varchar(100) NULL default NULL,
  filename varchar(100) NULL default NULL,
  data bytea,
  width integer NULL default NULL,
  height integer NULL default NULL,
  PRIMARY KEY  (id)
);


CREATE TABLE phplist_urlcache (
  id serial NOT NULL,
  url varchar(255) NOT NULL default '',
  lastmodified integer NULL default NULL,
  added timestamp NULL default NULL,
  content text,
  PRIMARY KEY  (id)
);
CREATE INDEX urlindex_urlcache ON phplist_urlcache (url);


CREATE TABLE phplist_user_attribute (
  id serial NOT NULL,
  name varchar(255) NOT NULL default '',
  type varchar(30) NULL default NULL,
  listorder integer NULL default NULL,
  default_value varchar(255) NULL default NULL,
  required integer NULL default NULL,
  tablename varchar(255) NULL default NULL,
  PRIMARY KEY  (id)
);


CREATE TABLE phplist_user_blacklist (
  email varchar(255) NOT NULL default '',
  added timestamp NULL default NULL
);
CREATE UNIQUE INDEX email_bl ON phplist_user_blacklist (email);


CREATE TABLE phplist_user_blacklist_data (
  email varchar(255) NOT NULL default '',
  name varchar(100) NULL default NULL,
  data text
);
CREATE UNIQUE INDEX email_bld ON phplist_user_blacklist_data (email);


CREATE TABLE phplist_user_message_bounce (
  id serial NOT NULL,
  "user" integer NOT NULL default '0',
  message integer NOT NULL default '0',
  bounce integer NOT NULL default '0',
  time timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (id)
);
CREATE INDEX user_b ON phplist_user_message_bounce ("user", message, bounce);


CREATE TABLE phplist_user_message_forward (
  id serial NOT NULL,
  "user" integer NOT NULL default '0',
  message integer NOT NULL default '0',
  forward varchar(255) NULL default NULL,
  status varchar(255) NULL default NULL,
  time timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (id)
);
CREATE INDEX user_mf ON phplist_user_message_forward ("user", message);


CREATE TABLE phplist_user_rss (
  userid integer NOT NULL default '0',
  last timestamp NULL default NULL,
  PRIMARY KEY  (userid)
);


CREATE TABLE phplist_user_user (
  id serial NOT NULL,
  email varchar(255) NOT NULL default '',
  confirmed integer default '0',
  optedin integer default '0',
  entered timestamp NULL default NULL,
  modified timestamp NOT NULL default CURRENT_TIMESTAMP,
  uniqid varchar(255) NULL default NULL,
  htmlemail integer default '0',
  bouncecount integer default '0',
  subscribepage integer default '0',
  rssfrequency varchar(100) NULL default NULL,
  password varchar(255) NULL default NULL,
  passwordchanged timestamp NULL default NULL,
  disabled integer default '0',
  extradata text,
  foreignkey varchar(100) NULL default NULL,
  blacklisted integer default '0',
  PRIMARY KEY  (id)
);
CREATE UNIQUE INDEX email_u ON phplist_user_user (email);
CREATE INDEX fkey ON phplist_user_user (foreignkey);
CREATE INDEX index_uniqid ON phplist_user_user (uniqid);


CREATE TABLE phplist_user_user_attribute (
  attributeid integer NOT NULL default '0',
  userid integer NOT NULL default '0',
  value text,
  PRIMARY KEY  (attributeid,userid)
);
CREATE INDEX userindex_ua ON phplist_user_user_attribute (userid);
CREATE INDEX attindex ON phplist_user_user_attribute (attributeid);
CREATE INDEX userattid ON phplist_user_user_attribute (attributeid, userid);
CREATE INDEX attuserid ON phplist_user_user_attribute (userid, attributeid);


CREATE TABLE phplist_user_user_history (
  id serial NOT NULL,
  userid integer NOT NULL default '0',
  ip varchar(255) NULL default NULL,
  date timestamp NULL default NULL,
  summary varchar(255) NULL default NULL,
  detail text,
  systeminfo text,
  PRIMARY KEY  (id)
);


CREATE TABLE phplist_usermessage (
  messageid integer NOT NULL default '0',
  userid integer NOT NULL default '0',
  entered timestamp NULL default NULL,
  viewed timestamp NULL default NULL,
  status varchar(255) NULL default NULL,
  PRIMARY KEY  (userid,messageid)
);
CREATE INDEX userindex_um ON phplist_usermessage (userid);
CREATE INDEX messageindex ON phplist_usermessage (messageid);
CREATE INDEX enteredindex ON phplist_usermessage (entered);


CREATE TABLE phplist_userstats (
  id serial NOT NULL,
  unixdate integer NULL default NULL,
  item varchar(255) NULL default NULL,
  listid integer default '0',
  value integer default '0',
  PRIMARY KEY  (id)
);
CREATE UNIQUE INDEX entry ON phplist_userstats (unixdate, item, listid);
CREATE INDEX listdateindex ON phplist_userstats (listid, unixdate);

commit;
