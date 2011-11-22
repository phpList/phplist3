begin;

set search_path to phplist;

grant usage on schema phplist to lcli_web;

grant select, insert, update on phplist_config to lcli_web;
grant select, insert, update, delete on phplist_user_user to lcli_web;
grant select, update on phplist_user_user_id_seq to lcli_web;
grant select, insert, delete on phplist_user_user_history to lcli_web;
grant select, update on phplist_user_user_history_id_seq to lcli_web;
grant select, insert, update, delete on phplist_user_user_attribute to lcli_web;
grant select, insert, update, delete on phplist_list to lcli_web;
grant select, update on phplist_list_id_seq to lcli_web;
grant select, insert, update, delete on phplist_listuser to lcli_web;
grant select, insert on phplist_user_blacklist to lcli_web;
grant select, insert on phplist_user_blacklist_data to lcli_web;
grant select, insert, update on phplist_message to lcli_web;
grant select, update on phplist_message_id_seq to lcli_web;
grant select, insert, update on phplist_messagedata to lcli_web;
grant select, insert, delete on phplist_listmessage to lcli_web;
grant select, update on phplist_listmessage_id_seq to lcli_web;
grant select, insert, update, delete on phplist_usermessage to lcli_web;
grant select, update on phplist_user_attribute to lcli_web;
grant select, insert, update, delete on phplist_sendprocess to lcli_web;
grant select, update on phplist_sendprocess_id_seq to lcli_web;
grant select on phplist_template to lcli_web;
grant select on phplist_templateimage to lcli_web;
grant select on phplist_bounce to lcli_web;
grant select, insert, delete on phplist_user_message_bounce to lcli_web;
grant select, update on phplist_user_message_bounce_id_seq to lcli_web;
grant select on phplist_user_message_forward to lcli_web;
grant select on phplist_admin to lcli_web;
grant select on phplist_adminattribute to lcli_web;
grant select on phplist_admin_attribute to lcli_web;
grant select on phplist_admin_task to lcli_web;
grant select on phplist_task to lcli_web;
grant select on phplist_subscribepage to lcli_web;
grant select on phplist_subscribepage_data to lcli_web;
grant select, insert on phplist_eventlog to lcli_web;
grant select, update on phplist_eventlog_id_seq to lcli_web;
grant select on phplist_attachment to lcli_web;
grant select on phplist_message_attachment to lcli_web;
grant select on phplist_urlcache to lcli_web;
grant select on phplist_linktrack to lcli_web;
grant select, update on phplist_linktrack_linkid_seq to lcli_web;
grant select, insert on phplist_linktrack_forward to lcli_web;
grant select, update on phplist_linktrack_forward_id_seq to lcli_web;
grant select on phplist_linktrack_userclick to lcli_web;
grant select, insert, update on phplist_linktrack_ml to lcli_web;
grant select, insert, update on phplist_linktrack_uml_click to lcli_web;
grant select, update on phplist_linktrack_uml_click_id_seq to lcli_web;
grant select, insert, update on phplist_userstats to lcli_web;
grant select, update on phplist_userstats_id_seq to lcli_web;
grant select on phplist_bounceregex to lcli_web;
grant select on phplist_bounceregex_bounce to lcli_web;
-- added by hand
grant select on phplist_listattr_countries to lcli_web;
grant select on phplist_listattr_most to lcli_web;
grant select on phplist_listattr_bwheredoyo to lcli_web;
grant select on phplist_listattr_bpleaseche to lcli_web;
grant delete on phplist_urlcache to lcli_web;


commit;
