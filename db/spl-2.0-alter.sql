SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

ALTER TABLE `teams` 
ADD COLUMN `contact_twitter` VARCHAR(200) NULL DEFAULT NULL AFTER `slot6_strife_id`,
ADD COLUMN `contact_facebook` VARCHAR(200) NULL DEFAULT NULL AFTER `contact_twitter`,
ADD COLUMN `contact_twitch` VARCHAR(200) NULL DEFAULT NULL AFTER `contact_facebook`;

ALTER TABLE `users` 
ADD COLUMN `team_id` INT(11) NULL DEFAULT NULL AFTER `email`,
ADD COLUMN `username` VARCHAR(45) NULL DEFAULT NULL AFTER `team_id`,
ADD COLUMN `strife_id` INT(11) NULL DEFAULT NULL AFTER `active`;

INSERT INTO `groups` (`id`,`name`,`description`) VALUES (5,'teamowner','Team Owner');
INSERT INTO `groups` (`id`,`name`,`description`) VALUES (6,'teampow','Team Manager');
INSERT INTO `groups` (`id`,`name`,`description`) VALUES (7,'teamsub','Team Sub');


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
