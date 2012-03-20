-- ---------------------------------------------------------------------------
-- Table t_datetime_data
--
-- Each versioned datetime property value is stored in this table and may,
-- optionally, be accompanied by an identifier key specified by the programmed
-- code for any business logic unknown to this repository module. Datetime
-- values are stored as Unix time (or POSIX time) which is the number of
-- seconds elapsed since midnight (UTC) January 1, 1970 (not counting leap 
-- seconds). This is an easier time value to convert to whatever time format
-- deemed appropriate for a particular programmed environment.
--
-- Note: See boolean data description for key storage information.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `t_datetime_data` (
    `datetime_uuid` BINARY(16) NOT NULL,
    `key` VARCHAR(45) NULL,
    `value` INT NOT NULL DEFAULT 0,
    `version_uuid` BINARY(16) NOT NULL DEFAULT 0,
    CONSTRAINT `fk_datetime_data_version`
        FOREIGN KEY (`version_uuid` )
        REFERENCES `t_version` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_datetime_data_datetime`
        FOREIGN KEY (`datetime_uuid` )
        REFERENCES `t_datetime` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_datetime_data_datetime` ON `t_datetime_data` (`datetime_uuid` ASC);
CREATE INDEX `fk_datetime_data_version` ON `t_datetime_data` (`version_uuid` ASC);
CREATE UNIQUE INDEX `datetime_data_version_uuid_unique` ON `t_datetime_data` (`version_uuid` ASC);

delimiter ;;
CREATE TRIGGER tr_before_datetime_data_insert BEFORE INSERT ON t_datetime_data
FOR EACH ROW 
    BEGIN
        SET NEW.version_uuid = NEW_VERSION();
    END;;
delimiter ;

