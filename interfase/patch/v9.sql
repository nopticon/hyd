ALTER TABLE _artists_images ADD image_default TINYINT(1) NOT NULL AFTER ub;

UPDATE _artists_images SET image_default = 1 WHERE image = 1;