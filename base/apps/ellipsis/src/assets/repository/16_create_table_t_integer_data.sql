-- ---------------------------------------------------------------------------
-- Table t_integer_data
--
-- Each versioned integer property value is stored in this table and may,
-- optionally, be accompanied by an identifier key specified by the programmed
-- code for any business logic unknown to this repository module. Integer
-- values are stored as whole numbers greater than or equal to 0.
--
-- Note: See boolean data description for key storage information.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `t_integer_data` (
    `integer_uuid` BINARY(16) NOT NULL,
    `key` VARCHAR(45) NULL,
    `value` INT NOT NULL DEFAULT 0,
    `version_uuid` BINARY(16) NOT NULL DEFAULT 0,
    CONSTRAINT `fk_integer_data_version`
        FOREIGN KEY (`version_uuid` )
        REFERENCES `t_version` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_integer_data_integer`
        FOREIGN KEY (`integer_uuid` )
        REFERENCES `t_integer` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_integer_data_integer` ON `t_integer_data` (`integer_uuid` ASC);
CREATE INDEX `fk_integer_data_version` ON `t_integer_data` (`version_uuid` ASC);
CREATE UNIQUE INDEX `integer_data_version_uuid_unique` ON `t_integer_data` (`version_uuid` ASC);

delimiter ;;
CREATE TRIGGER tr_before_integer_data_insert BEFORE INSERT ON t_integer_data
FOR EACH ROW 
    BEGIN
        SET NEW.version_uuid = NEW_VERSION();
    END;;
delimiter ;

