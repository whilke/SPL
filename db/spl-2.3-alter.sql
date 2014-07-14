SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

CREATE TABLE IF NOT EXISTS `admin_issues` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(45) NOT NULL,
  `desc` TEXT NOT NULL,
  `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `votes_needed` SMALLINT(6) NOT NULL,
  `yes_votes` SMALLINT(6) NULL DEFAULT NULL,
  `no_votes` SMALLINT(6) NULL DEFAULT NULL,
  `ap_votes` SMALLINT(6) NULL DEFAULT NULL,
  `active` SMALLINT(6) NOT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = latin1
COLLATE = latin1_swedish_ci;

CREATE TABLE IF NOT EXISTS `admin_issues_votes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `timestamp` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `issue_id` INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_admin_issues_votes_1_idx` (`issue_id` ASC),
  CONSTRAINT `fk_admin_issues_votes_1`
    FOREIGN KEY (`issue_id`)
    REFERENCES `admin_issues` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = latin1
COLLATE = latin1_swedish_ci;

ALTER TABLE `matches` 
CHANGE COLUMN `home_team_points` `home_team_points` INT(11) NOT NULL DEFAULT '0' ,
CHANGE COLUMN `away_team_points` `away_team_points` INT(11) NOT NULL DEFAULT '0' ;

ALTER TABLE `teams` 
ADD COLUMN `manager_id` INT(11) NULL DEFAULT NULL AFTER `contact_twitch`;

ALTER TABLE `season_group` 
ADD COLUMN `isopen` SMALLINT(6) NOT NULL DEFAULT 0 AFTER `season_id`;

ALTER TABLE `season_group_teams` 
ADD COLUMN `seeding` SMALLINT(6) NOT NULL DEFAULT 50 AFTER `team_id`;

CREATE TABLE IF NOT EXISTS `users_team_history` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `old_team` INT(11) NULL DEFAULT NULL,
  `new_team` INT(11) NULL DEFAULT NULL,
  `season_id` INT(11) NULL DEFAULT NULL,
  `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

CREATE TABLE IF NOT EXISTS `admin_issues` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(45) NOT NULL,
  `desc` TEXT NOT NULL,
  `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `votes_needed` SMALLINT(6) NOT NULL,
  `yes_votes` SMALLINT(6) NULL DEFAULT NULL,
  `no_votes` SMALLINT(6) NULL DEFAULT NULL,
  `ap_votes` SMALLINT(6) NULL DEFAULT NULL,
  `active` SMALLINT(6) NOT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = latin1
COLLATE = latin1_swedish_ci;

CREATE TABLE IF NOT EXISTS `admin_issues_votes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `timestamp` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `issue_id` INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_admin_issues_votes_1_idx` (`issue_id` ASC),
  CONSTRAINT `fk_admin_issues_votes_1`
    FOREIGN KEY (`issue_id`)
    REFERENCES `admin_issues` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = latin1
COLLATE = latin1_swedish_ci;

ALTER TABLE `matches` 
CHANGE COLUMN `home_team_points` `home_team_points` INT(11) NOT NULL DEFAULT '0' ,
CHANGE COLUMN `away_team_points` `away_team_points` INT(11) NOT NULL DEFAULT '0' ;

ALTER TABLE `teams` 
ADD COLUMN `manager_id` INT(11) NULL DEFAULT NULL AFTER `contact_twitch`;

ALTER TABLE `season_group` 
ADD COLUMN `isopen` SMALLINT(6) NOT NULL DEFAULT 0 AFTER `season_id`;

ALTER TABLE `season_group_teams` 
ADD COLUMN `seeding` SMALLINT(6) NOT NULL DEFAULT 50 AFTER `team_id`;

CREATE TABLE IF NOT EXISTS `users_team_history` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `old_team` INT(11) NULL DEFAULT NULL,
  `new_team` INT(11) NULL DEFAULT NULL,
  `season_id` INT(11) NULL DEFAULT NULL,
  `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `fk_users_team_history_1_idx` (`user_id` ASC),
  CONSTRAINT `fk_users_team_history_1`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = latin1
COLLATE = latin1_swedish_ci;

INSERT INTO `seasons` (`id`,`name`,`start`,`end`,`active`,`tag`,`current`) VALUES (7,'Strife Beta Series 2','2014-07-12 00:00:00','2014-07-20 00:00:00',0,'SB2',0);
INSERT INTO `weeks` (`id`,`tag`,`start`,`end`,`season_id`) VALUES (8,'W1','2014-07-21 00:00:00','2014-07-27 00:00:00',7);
INSERT INTO `season_group` (`id`,`name`,`season_id`, `isopen`) VALUES (4,'Pro',7, 0);
INSERT INTO `season_group` (`id`,`name`,`season_id`, `isopen`) VALUES (5,'Challenger',7, 1);

INSERT INTO `seasons_teams` (`season_id`, `team_id`) VALUES ( 7, 26);
INSERT INTO `seasons_teams` (`season_id`, `team_id`) VALUES ( 7, 36);
INSERT INTO `seasons_teams` (`season_id`, `team_id`) VALUES ( 7, 4);
INSERT INTO `seasons_teams` (`season_id`, `team_id`) VALUES ( 7, 16);
INSERT INTO `seasons_teams` (`season_id`, `team_id`) VALUES ( 7, 27);
INSERT INTO `seasons_teams` (`season_id`, `team_id`) VALUES ( 7, 2);

INSERT INTO `season_group_teams` (`season_group_id`, `team_id`, `seeding`) VALUES ( 4, 26, 1);
INSERT INTO `season_group_teams` (`season_group_id`, `team_id`, `seeding`) VALUES ( 4, 36, 2);
INSERT INTO `season_group_teams` (`season_group_id`, `team_id`, `seeding`) VALUES ( 4, 4, 3);
INSERT INTO `season_group_teams` (`season_group_id`, `team_id`, `seeding`) VALUES ( 4, 16, 4);
INSERT INTO `season_group_teams` (`season_group_id`, `team_id`, `seeding`) VALUES ( 4, 27, 5);
INSERT INTO `season_group_teams` (`season_group_id`, `team_id`, `seeding`) VALUES ( 4, 2, 6);


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

  INDEX `fk_users_team_history_1_idx` (`user_id` ASC),
  CONSTRAINT `fk_users_team_history_1`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = latin1
COLLATE = latin1_swedish_ci;

INSERT INTO `seasons` (`id`,`name`,`start`,`end`,`active`,`tag`,`current`) VALUES (7,'Strife Beta Series 2','2014-07-12 00:00:00','2014-07-20 00:00:00',0,'SB2',0);
INSERT INTO `weeks` (`id`,`tag`,`start`,`end`,`season_id`) VALUES (8,'W1','2014-07-21 00:00:00','2014-07-27 00:00:00',7);
INSERT INTO `season_group` (`id`,`name`,`season_id`, `isopen`) VALUES (4,'Pro',7, 0);
INSERT INTO `season_group` (`id`,`name`,`season_id`, `isopen`) VALUES (5,'Challenger',7, 1);

INSERT INTO `seasons_teams` (`season_id`, `team_id`) VALUES ( 7, 26);
INSERT INTO `seasons_teams` (`season_id`, `team_id`) VALUES ( 7, 36);
INSERT INTO `seasons_teams` (`season_id`, `team_id`) VALUES ( 7, 4);
INSERT INTO `seasons_teams` (`season_id`, `team_id`) VALUES ( 7, 16);
INSERT INTO `seasons_teams` (`season_id`, `team_id`) VALUES ( 7, 27);
INSERT INTO `seasons_teams` (`season_id`, `team_id`) VALUES ( 7, 2);

INSERT INTO `season_group_teams` (`season_group_id`, `team_id`, `seeding`) VALUES ( 4, 26, 1);
INSERT INTO `season_group_teams` (`season_group_id`, `team_id`, `seeding`) VALUES ( 4, 36, 2);
INSERT INTO `season_group_teams` (`season_group_id`, `team_id`, `seeding`) VALUES ( 4, 4, 3);
INSERT INTO `season_group_teams` (`season_group_id`, `team_id`, `seeding`) VALUES ( 4, 16, 4);
INSERT INTO `season_group_teams` (`season_group_id`, `team_id`, `seeding`) VALUES ( 4, 27, 5);
INSERT INTO `season_group_teams` (`season_group_id`, `team_id`, `seeding`) VALUES ( 4, 2, 6);


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
