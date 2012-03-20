-- ---------------------------------------------------------------------------
-- Table t_mediumtext_data
--
-- Each versioned mediumtext property value is stored in this table and may,
-- optionally, be accompanied by an identifier key specified by the programmed
-- code for any business logic unknown to this repository module. Medium Text
-- values are stored as any text value less than or equal to 4000 characters 
-- in length.
--
-- Note: See boolean data description for key storage information.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `t_mediumtext_data` (
    `mediumtext_uuid` BINARY(16) NOT NULL,
    `key` VARCHAR(45) NULL,
    `value` VARCHAR(4000) NULL,
    `version_uuid` BINARY(16) NOT NULL DEFAULT 0,
    CONSTRAINT `fk_mediumtext_data_version`
        FOREIGN KEY (`version_uuid` )
        REFERENCES `t_version` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_mediumtext_data_mediumtext`
        FOREIGN KEY (`mediumtext_uuid` )
        REFERENCES `t_mediumtext` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_mediumtext_data_mediumtext` ON `t_mediumtext_data` (`mediumtext_uuid` ASC);
CREATE INDEX `fk_mediumtext_data_version` ON `t_mediumtext_data` (`version_uuid` ASC);
CREATE UNIQUE INDEX `mediumtext_data_version_uuid_unique` ON `t_mediumtext_data` (`version_uuid` ASC);

delimiter ;;
CREATE TRIGGER tr_before_mediumtext_data_insert BEFORE INSERT ON t_mediumtext_data
FOR EACH ROW 
    BEGIN
        SET NEW.version_uuid = NEW_VERSION();
    END;;
delimiter ;

