CREATE TABLE IF NOT EXISTS `^msg_group_users` (
  `groupid` int(10) unsigned NOT NULL,
  `userid` int(10) unsigned NOT NULL,
  `join` smallint unsigned NOT NULL,
  `notify` smallint unsigned NOT NULL,
  UNIQUE (groupid, userid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;