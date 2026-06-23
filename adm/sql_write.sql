CREATE TABLE IF NOT EXISTS `landing_page` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject` varchar(255) NOT NULL DEFAULT '',
  `company` varchar(255) NOT NULL DEFAULT '',
  `tel` varchar(50) NOT NULL DEFAULT '',
  `category` varchar(100) NOT NULL DEFAULT '',
  `area` varchar(255) NOT NULL DEFAULT '',
  `hero_text` text NOT NULL,
  `intro_text` text NOT NULL,
  `hero_image` varchar(255) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `landing_inquiry` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `landing_id` int(11) NOT NULL DEFAULT 0,
  `wr_name` varchar(100) NOT NULL DEFAULT '',
  `wr_1` varchar(100) NOT NULL DEFAULT '',
  `wr_content` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `landing_id` (`landing_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
