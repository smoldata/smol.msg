DROP TABLE IF EXISTS `rx`;
CREATE TABLE `rx` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `usr_id` int(11) unsigned NOT NULL,
  `msg` text CHARACTER SET utf8mb4,
  `received` datetime DEFAULT NULL,
  `post_json` text CHARACTER SET utf8mb4,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `tx`;
CREATE TABLE `tx` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `rx_id` int(11) NOT NULL,
  `usr_id` int(11) NOT NULL,
  `msg` TEXT CHARACTER SET utf8mb4,
  `queued` datetime NOT NULL,
  `ok` tinyint(4) DEFAULT NULL,
  `post_json` text CHARACTER SET utf8mb4,
  `response_json` text CHARACTER SET utf8mb4,
  `transmitted` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `usr`;
CREATE TABLE `usr` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `phone` varchar(32) NOT NULL DEFAULT '',
  `name` varchar(16) DEFAULT NULL,
  `context` varchar(255) DEFAULT NULL,
  `joined` datetime DEFAULT NULL,
  `active` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `phone` (`phone`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `usr_mute`;
CREATE TABLE `usr_mute` (
  `usr_id` int(11) unsigned NOT NULL,
  `muted_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  UNIQUE KEY `usr_id` (`usr_id`,`muted_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
