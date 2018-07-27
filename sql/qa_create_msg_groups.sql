CREATE TABLE IF NOT EXISTS `^msg_groups` (
  `groupid` int(10) unsigned UNIQUE NOT NULL AUTO_INCREMENT,
  `title` varchar(255),
  `created` datetime NOT NUll
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;