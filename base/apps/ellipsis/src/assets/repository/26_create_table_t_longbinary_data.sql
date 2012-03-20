-- ---------------------------------------------------------------------------
-- Table t_longbinary_data
--
-- Each versioned longbinary property value is stored in this table and may,
-- optionally, be accompanied by an identifier key specified by the programmed
-- code for any business logic unknown to this repository module. Long Binary
-- values are stored as any binary value less than or equal to 4,294,967,295
-- characters in length (4GB).
--
-- Note: See boolean data description for key storage information.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `t_longbinary_data` (
    `longbinary_uuid` BINARY(16) NOT NULL,
    `key` VARCHAR(45) NULL,
    `value` LONGBLOB NULL,
    `version_uuid` BINARY(16) NOT NULL DEFAULT 0,
    CONSTRAINT `fk_longbinary_data_longbinary`
        FOREIGN KEY (`longbinary_uuid` )
        REFERENCES `t_longbinary` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_longbinary_data_version`
        FOREIGN KEY (`version_uuid` )
        REFERENCES `t_version` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_longbinary_data_longbinary` ON `t_longbinary_data` (`longbinary_uuid` ASC);
CREATE INDEX `fk_longbinary_data_version` ON `t_longbinary_data` (`version_uuid` ASC);
CREATE UNIQUE INDEX `longbinary_data_version_uuid_unique` ON `t_longbinary_data` (`version_uuid` ASC);

delimiter ;;
CREATE TRIGGER tr_before_longbinary_data_insert BEFORE INSERT ON t_longbinary_data
FOR EACH ROW 
    BEGIN
        SET NEW.version_uuid = NEW_VERSION();
    END;;
delimiter ;

