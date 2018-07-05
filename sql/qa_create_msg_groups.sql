CREATE TABLE IF NOT EXISTS `^msg_groups` (
  `groupid` int(10) unsigned UNIQUE NOT NULL,
  `title` varchar(255) NOT NULL,
  `created` datetime NOT NUll
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;