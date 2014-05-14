SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

CREATE TABLE IF NOT EXISTS `heroes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
AUTO_INCREMENT = 12
DEFAULT CHARACTER SET = latin1
COLLATE = latin1_swedish_ci;

ALTER TABLE `matches` 
ADD COLUMN `week_id` INT(11) NOT NULL AFTER `active`,
ADD COLUMN `strife_match_id` INT(11) NULL DEFAULT NULL AFTER `week_id`;

CREATE TABLE IF NOT EXISTS `pets` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(45) NULL DEFAULT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
AUTO_INCREMENT = 7
DEFAULT CHARACTER SET = latin1
COLLATE = latin1_swedish_ci;

CREATE TABLE IF NOT EXISTS `players` (
  `strife_id` INT(11) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  PRIMARY KEY (`strife_id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = latin1
COLLATE = latin1_swedish_ci;

ALTER TABLE `seasons` 
ADD COLUMN `current` INT(11) NOT NULL DEFAULT '0' AFTER `tag`;

CREATE TABLE IF NOT EXISTS `stats` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `match_id` INT(11) NOT NULL,
  `player_id` INT(11) NOT NULL,
  `team_id` INT(11) NOT NULL,
  `hero_id` INT(11) NOT NULL,
  `pet_id` INT(11) NOT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
AUTO_INCREMENT = 131
DEFAULT CHARACTER SET = latin1
COLLATE = latin1_swedish_ci;

INSERT INTO `heroes` (`id`,`name`) VALUES (1,'Hero_Ray');
INSERT INTO `heroes` (`id`,`name`) VALUES (2,'Hero_Fetterstone');
INSERT INTO `heroes` (`id`,`name`) VALUES (3,'Hero_Claudessa');
INSERT INTO `heroes` (`id`,`name`) VALUES (4,'Hero_Caprice');
INSERT INTO `heroes` (`id`,`name`) VALUES (5,'Hero_Ladytinder');
INSERT INTO `heroes` (`id`,`name`) VALUES (6,'Hero_Shank');
INSERT INTO `heroes` (`id`,`name`) VALUES (7,'Hero_Vermillion');
INSERT INTO `heroes` (`id`,`name`) VALUES (8,'Hero_Malady');
INSERT INTO `heroes` (`id`,`name`) VALUES (9,'Hero_Bo');
INSERT INTO `heroes` (`id`,`name`) VALUES (10,'Hero_Carter');
INSERT INTO `heroes` (`id`,`name`) VALUES (11,'Hero_Ace');


INSERT INTO `pets` (`id`,`name`) VALUES (1,'Familiar_Mystik');
INSERT INTO `pets` (`id`,`name`) VALUES (2,'Familiar_Tortus');
INSERT INTO `pets` (`id`,`name`) VALUES (3,'Familiar_Bounder');
INSERT INTO `pets` (`id`,`name`) VALUES (4,'Familiar_Razer');
INSERT INTO `pets` (`id`,`name`) VALUES (5,'Familiar_Topps');
INSERT INTO `pets` (`id`,`name`) VALUES (6,'Familiar_Luster');

delete from `states`;
INSERT INTO `states` (`id`,`desc`,`code`) VALUES (0,'Open','O');
INSERT INTO `states` (`id`,`desc`,`code`) VALUES (1,'Win','W');
INSERT INTO `states` (`id`,`desc`,`code`) VALUES (2,'Loss','L');
INSERT INTO `states` (`id`,`desc`,`code`) VALUES (3,'Forfeit Loss','FL');
INSERT INTO `states` (`id`,`desc`,`code`) VALUES (4,'Forfeit Win','FW');
INSERT INTO `states` (`id`,`desc`,`code`) VALUES (5,'No show','NS');


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;