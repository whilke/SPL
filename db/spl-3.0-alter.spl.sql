SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

CREATE TABLE IF NOT EXISTS `spl`.`draft_chat` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `draft_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `data` VARCHAR(255) NOT NULL,
  `timestamp` DOUBLE NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_draft_chat_1_idx` (`draft_id` ASC),
  CONSTRAINT `fk_draft_chat_1`
    FOREIGN KEY (`draft_id`)
    REFERENCES `spl`.`draft_lobby` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = latin1
COLLATE = latin1_swedish_ci;

CREATE TABLE IF NOT EXISTS `spl`.`draft_lobby` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `match_id` INT(11) NOT NULL,
  `title` VARCHAR(50) NULL DEFAULT NULL,
  `state` SMALLINT(6) NULL DEFAULT NULL,
  `glory_seat` INT(11) NULL DEFAULT NULL,
  `valor_seat` INT(11) NULL DEFAULT NULL,
  `round_time` INT(11) NULL DEFAULT NULL,
  `glory_extra_time` SMALLINT(6) NULL DEFAULT '60',
  `valor_extra_time` SMALLINT(6) NULL DEFAULT '60',
  `password` VARCHAR(45) NULL DEFAULT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = latin1
COLLATE = latin1_swedish_ci;

CREATE TABLE IF NOT EXISTS `spl`.`draft_picks` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `draft_id` INT(11) NOT NULL,
  `user_id` INT(10) UNSIGNED NOT NULL,
  `hero_id` INT(11) NOT NULL,
  `timestamp` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `pick_type` SMALLINT(6) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_draft_picks_1_idx` (`draft_id` ASC),
  CONSTRAINT `fk_draft_picks_1`
    FOREIGN KEY (`draft_id`)
    REFERENCES `spl`.`draft_lobby` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = latin1
COLLATE = latin1_swedish_ci;

CREATE TABLE IF NOT EXISTS `spl`.`draft_teams` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `draft_id` INT(11) NOT NULL,
  `team_id` INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_draft_teams_1_idx` (`draft_id` ASC),
  CONSTRAINT `fk_draft_teams_1`
    FOREIGN KEY (`draft_id`)
    REFERENCES `spl`.`draft_lobby` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = latin1
COLLATE = latin1_swedish_ci;

CREATE TABLE IF NOT EXISTS `spl`.`draft_users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `draft_id` INT(11) NOT NULL,
  `user_id` INT(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_draft_users_1_idx` (`draft_id` ASC),
  CONSTRAINT `fk_draft_users_1`
    FOREIGN KEY (`draft_id`)
    REFERENCES `spl`.`draft_lobby` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = latin1
COLLATE = latin1_swedish_ci;

ALTER TABLE `spl`.`heroes` 
ADD COLUMN `desc` VARCHAR(45) NULL DEFAULT NULL AFTER `name`;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
