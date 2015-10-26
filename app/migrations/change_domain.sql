
DELETE FROM _application WHERE config_name = 'address';
DELETE FROM _application WHERE config_name = 'a_picnik_key';
DELETE FROM _application WHERE config_name = 'default_pagetitle';

UPDATE _application SET config_value = 'Republica Rock' WHERE config_name = 'sitename' OR config_name = 'site_desc';

UPDATE _application SET config_value = 'dev.republicarock.com' WHERE config_name = 'server_name';
UPDATE _application SET config_value = '.dev.republicarock.com' WHERE config_name = 'cookie_domain';
UPDATE _application SET config_value = 'republicarock' WHERE config_name = 'cookie_name';

UPDATE _application SET config_value = 'assets/' WHERE config_name = 'assets_url';
UPDATE _application SET config_value = 'assets/artists/' WHERE config_name = 'artists_url';
UPDATE _application SET config_value = 'assets/events/' WHERE config_name = 'events_url';
UPDATE _application SET config_value = 'assets/news/' WHERE config_name = 'news_url';

INSERT INTO _application (config_name, config_value) VALUES ('project_path', '/var/www/republica-rock/');

UPDATE _application SET config_value = 'public/assets/' WHERE config_name = 'assets_path';
UPDATE _application SET config_value = 'public/assets/artists/' WHERE config_name = 'artists_path';
UPDATE _application SET config_value = 'public/assets/events/' WHERE config_name = 'events_path';
UPDATE _application SET config_value = 'public/assets/news/' WHERE config_name = 'news_path';

UPDATE _application SET config_value = 'cache/' WHERE config_name = 'cache_path';

-- post_active

ALTER TABLE `_forum_posts` ADD COLUMN `post_active` TINYINT(1) NOT NULL DEFAULT '1' AFTER `post_id`;

ALTER TABLE `_forums` ADD COLUMN `forum_active` TINYINT(1) NOT NULL DEFAULT '1' AFTER `cat_id`;

ALTER TABLE `_forum_topics` ADD COLUMN `topic_active` TINYINT(1) NULL AFTER `topic_last_post_id`;
UPDATE _forum_topics SET topic_active = 1 WHERE topic_active = 0;

CREATE TABLE _menu IF NOT EXISTS (
	`menu_id` mediumint(5) NOT NULL AUTO_INCREMENT,
	`menu_alias` varchar(100) NOT NULL,
	`menu_name` varchar(100) NOT NULL,
	`menu_icon` varchar(25) NOT NULL,
	`menu_order` mediumint(5) NOT NULL DEFAULT '0',
	`menu_validate` varchar(100) NOT NULL,
	PRIMARY KEY (`menu_id`)
) ENGINE=InnoDB;

INSERT INTO `_menu` (`menu_id`, `menu_alias`, `menu_name`, `menu_icon`, `menu_order`, `menu_validate`)
VALUES
	(1,'today','TODAY','star',10,'member'),
	(2,'artists','ARTISTS','fire',20,''),
	(3,'events','EVENTS','camera',30,''),
	(4,'board','BOARD','th-list',40,''),
	(5,'broadcast','BROADCAST','volume-up',50,''),
	(6,'community','COMMUNITY','th',60,''),
	(7,'acp','ACP','share',70,'acp');