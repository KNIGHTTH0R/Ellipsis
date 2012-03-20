-- ---------------------------------------------------------------------------
-- Table t_smalltext_data
--
-- Each versioned smalltext property value is stored in this table and may,
-- optionally, be accompanied by an identifier key specified by the programmed
-- code for any business logic unknown to this repository module. Small Text
-- values are stored as any text value less than or equal to 1000 characters 
-- in length.
--
-- Note: See boolean data description for key storage information.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `t_smalltext_data` (
    `smalltext_uuid` BINARY(16) NOT NULL,
    `key` VARCHAR(45) NULL,
    `value` VARCHAR(1000) NULL,
    `version_uuid` BINARY(16) NOT NULL DEFAULT 0,
    CONSTRAINT `fk_smalltext_data_version`
        FOREIGN KEY (`version_uuid` )
        REFERENCES `t_version` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_smalltext_data_smalltext`
        FOREIGN KEY (`smalltext_uuid` )
        REFERENCES `t_smalltext` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_smalltext_data_smalltext` ON `t_smalltext_data` (`smalltext_uuid` ASC);
CREATE INDEX `fk_smalltext_data_version` ON `t_smalltext_data` (`version_uuid` ASC);
CREATE UNIQUE INDEX `smalltext_data_version_uuid_unique` ON `t_smalltext_data` (`version_uuid` ASC);

delimiter ;;
CREATE TRIGGER tr_before_smalltext_data_insert BEFORE INSERT ON t_smalltext_data
FOR EACH ROW 
    BEGIN
        SET NEW.version_uuid = NEW_VERSION();
    END;;
delimiter ;

