ALTER TABLE _artists_images ADD image_default TINYINT(1) NOT NULL AFTER ub;

UPDATE _artists_images SET image_default = 1 WHERE image = 1;

ALTER TABLE _forum_posts
	ADD post_likes INT( 11 ) NOT NULL,
	ADD post_dislikes INT( 11 ) NOT NULL;

CREATE TABLE IF NOT EXISTS `_genres` (
  `genre_id` int(11) NOT NULL AUTO_INCREMENT,
  `genre_alias` varchar(100) NOT NULL,
  `genre_name` varchar(100) NOT NULL,
  PRIMARY KEY (`genre_id`)
) ENGINE=InnoDB

ALTER TABLE `_events` CHANGE COLUMN `text` `text` TEXT NULL;

ALTER TABLE `_members` CHANGE COLUMN `user_posts` `user_posts` INT(11) UNSIGNED NOT NULL DEFAULT '0';