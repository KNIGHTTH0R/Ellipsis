-- ---------------------------------------------------------------------------
-- Table t_longtext_data
--
-- Each versioned longtext property value is stored in this table and may,
-- optionally, be accompanied by an identifier key specified by the programmed
-- code for any business logic unknown to this repository module. Long Text
-- values are stored as any text value less than or equal to 65,535 characters 
-- in length.
--
-- Note: See boolean data description for key storage information.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `t_longtext_data` (
    `longtext_uuid` BINARY(16) NOT NULL,
    `key` VARCHAR(45) NULL,
    `value` TEXT NULL,
    `version_uuid` BINARY(16) NOT NULL DEFAULT 0,
    CONSTRAINT `fk_longtext_data_longtext`
        FOREIGN KEY (`longtext_uuid` )
        REFERENCES `t_longtext` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_longtext_data_version`
        FOREIGN KEY (`version_uuid` )
        REFERENCES `t_version` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_longtext_data_longtext` ON `t_longtext_data` (`longtext_uuid` ASC);
CREATE INDEX `fk_longtext_data_version` ON `t_longtext_data` (`version_uuid` ASC);
CREATE UNIQUE INDEX `longtext_data_version_uuid_unique` ON `t_longtext_data` (`version_uuid` ASC);

delimiter ;;
CREATE TRIGGER tr_before_longtext_data_insert BEFORE INSERT ON t_longtext_data
FOR EACH ROW 
    BEGIN
        SET NEW.version_uuid = NEW_VERSION();
    END;;
delimiter ;

