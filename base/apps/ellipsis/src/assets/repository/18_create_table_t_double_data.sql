-- ---------------------------------------------------------------------------
-- Table t_double_data
--
-- Each versioned double property value is stored in this table and may,
-- optionally, be accompanied by an identifier key specified by the programmed
-- code for any business logic unknown to this repository module. Double
-- values are stored as float values (numbers with decimals) greater than or 
-- equal to 0.
--
-- Note: See boolean data description for key storage information.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `t_double_data` (
  `double_uuid` BINARY(16) NOT NULL,
  `key` VARCHAR(45) NULL,
  `value` DOUBLE NOT NULL DEFAULT 0.0,
  `version_uuid` BINARY(16) NOT NULL DEFAULT 0,
  CONSTRAINT `fk_double_data_version`
    FOREIGN KEY (`version_uuid` )
    REFERENCES `t_version` (`uuid` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_double_data_double`
    FOREIGN KEY (`double_uuid` )
    REFERENCES `t_double` (`uuid` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_double_data_double` ON `t_double_data` (`double_uuid` ASC);
CREATE INDEX `fk_double_data_version` ON `t_double_data` (`version_uuid` ASC);
CREATE UNIQUE INDEX `double_data_version_uuid_unique` ON `t_double_data` (`version_uuid` ASC);

delimiter ;;
CREATE TRIGGER tr_before_double_data_insert BEFORE INSERT ON t_double_data
FOR EACH ROW 
    BEGIN
        SET NEW.version_uuid = NEW_VERSION();
    END;;
delimiter ;

