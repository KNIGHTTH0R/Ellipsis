-- ---------------------------------------------------------------------------
-- Table t_mediumbinary_data
--
-- Each versioned mediumbinary property value is stored in this table and may,
-- optionally, be accompanied by an identifier key specified by the programmed
-- code for any business logic unknown to this repository module. Medium Binary
-- values are stored as any binary value less than or equal to 16,777,215 
-- characters in length (approximately 16MB).
--
-- Note: See boolean data description for key storage information.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `t_mediumbinary_data` (
    `mediumbinary_uuid` BINARY(16) NOT NULL,
    `key` VARCHAR(45) NULL,
    `value` MEDIUMBLOB NULL,
    `version_uuid` BINARY(16) NOT NULL DEFAULT 0,
    CONSTRAINT `fk_mediumbinary_data_mediumbinary`
        FOREIGN KEY (`mediumbinary_uuid` )
        REFERENCES `t_mediumbinary` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_mediumbinary_data_version`
        FOREIGN KEY (`version_uuid` )
        REFERENCES `t_version` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_mediumbinary_data_mediumbinary` ON `t_mediumbinary_data` (`mediumbinary_uuid` ASC);
CREATE INDEX `fk_mediumbinary_data_version` ON `t_mediumbinary_data` (`version_uuid` ASC);
CREATE UNIQUE INDEX `mediumbinary_data_version_uuid_unique` ON `t_mediumbinary_data` (`version_uuid` ASC);

delimiter ;;
CREATE TRIGGER tr_before_mediumbinary_data_insert BEFORE INSERT ON t_mediumbinary_data
FOR EACH ROW 
    BEGIN
        SET NEW.version_uuid = NEW_VERSION();
    END;;
delimiter ;

