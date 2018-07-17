CREATE TABLE IF NOT EXISTS `^msg_group_messages` (
  `messageid` int(10) unsigned UNIQUE NOT NULL AUTO_INCREMENT,
  `groupid` int(10) unsigned NOT NULL,
  `userid` int(10) unsigned NOT NULL,
	`content` VARCHAR(8000) NULL DEFAULT NULL,
  `format` VARCHAR(20) NOT NULL DEFAULT '',
  `created` datetime NOT NUll
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;