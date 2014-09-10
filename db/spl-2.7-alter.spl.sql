SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

CREATE TABLE IF NOT EXISTS `broadcasts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `isMatch` TINYINT(4) NOT NULL DEFAULT '0',
  `match_id` INT(11) NULL DEFAULT NULL,
  `timestamp` DATETIME NOT NULL,
  `title` VARCHAR(100) NULL DEFAULT NULL,
  `desc` VARCHAR(255) NULL DEFAULT NULL,
  `deleted` BIT(1) NOT NULL DEFAULT b'0',
  `url` VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `id_UNIQUE` (`id` ASC))
ENGINE = InnoDB
DEFAULT CHARACTER SET = latin1
COLLATE = latin1_swedish_ci;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
