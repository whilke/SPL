SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

CREATE TABLE IF NOT EXISTS `options` (
  `option_id` INT(11) NOT NULL AUTO_INCREMENT,
  `poll_id` INT(11) NOT NULL,
  `title` VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`option_id`),
  INDEX `option_poll_id` (`poll_id` ASC),
  CONSTRAINT `options_ibfk_1`
    FOREIGN KEY (`poll_id`)
    REFERENCES `spl-prod`.`polls` (`poll_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = latin1
COLLATE = latin1_swedish_ci;

CREATE TABLE IF NOT EXISTS `polls` (
  `poll_id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL DEFAULT '',
  `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `closed` TINYINT(1) NOT NULL DEFAULT '0' COMMENT 'if the poll is closed or not',
  PRIMARY KEY (`poll_id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = latin1
COLLATE = latin1_swedish_ci;

CREATE TABLE IF NOT EXISTS `votes` (
  `vote_id` INT(11) NOT NULL AUTO_INCREMENT,
  `option_id` INT(11) NOT NULL,
  `user_id` INT(11) UNSIGNED NOT NULL,
  `timestamp` INT(10) NOT NULL,
  PRIMARY KEY (`vote_id`),
  INDEX `vote_option_id` (`option_id` ASC),
  INDEX `fk_votes_1_idx` (`user_id` ASC),
  CONSTRAINT `fk_votes_1`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `votes_ibfk_1`
    FOREIGN KEY (`option_id`)
    REFERENCES `options` (`option_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = latin1
COLLATE = latin1_swedish_ci;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
