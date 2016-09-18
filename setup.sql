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
  `transmit_batch` varchar(255) DEFAULT NULL,
  `transmitted` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `usr`;
CREATE TABLE `usr` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `phone` varchar(32) NOT NULL DEFAULT '',
  `name` varchar(16) DEFAULT NULL,
  `status` varchar(255) DEFAULT 'user',
  `context` varchar(255) DEFAULT NULL,
  `channel` varchar(16) DEFAULT 'main',
  `joined` datetime DEFAULT NULL,
  `active` datetime DEFAULT NULL,
  `web_active` datetime DEFAULT NULL,
  `invited_by` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `phone` (`phone`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `usr_mute`;
CREATE TABLE `usr_mute` (
  `usr_id` int(11) unsigned NOT NULL,
  `muted_id` int(11) NOT NULL,
  `active` int(11) DEFAULT '1',
  `created` datetime NOT NULL,
  `deleted` datetime DEFAULT NULL,
  UNIQUE KEY `usr_id` (`usr_id`,`muted_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `chat`;
CREATE TABLE `chat` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `channel` varchar(16) DEFAULT 'main',
  `usr_id` int(11) NOT NULL,
  `msg` text NOT NULL,
  `rx_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `avatar_image` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `usr_login`;
CREATE TABLE `usr_login` (
  `login_code` int(11) NOT NULL DEFAULT '0',
  `usr_id` int(11) unsigned DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`login_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `rx_hold`;
CREATE TABLE `rx_hold` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `rx_id` int(11) unsigned NOT NULL,
  `usr_id` int(11) DEFAULT NULL,
  `active` int(11) DEFAULT '1',
  `held` datetime DEFAULT NULL,
  `accepted` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
