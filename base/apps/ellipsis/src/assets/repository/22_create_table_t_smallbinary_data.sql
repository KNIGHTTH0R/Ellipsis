-- ---------------------------------------------------------------------------
-- Table t_smallbinary_data
--
-- Each versioned smallbinary property value is stored in this table and may,
-- optionally, be accompanied by an identifier key specified by the programmed
-- code for any business logic unknown to this repository module. Small Binary
-- values are stored as any binary value less than or equal to 65,535 
-- characters in length (approximately 64KB).
--
-- Note: See boolean data description for key storage information.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `t_smallbinary_data` (
    `smallbinary_uuid` BINARY(16) NOT NULL,
    `key` VARCHAR(45) NULL,
    `value` BLOB NULL,
    `version_uuid` BINARY(16) NOT NULL DEFAULT 0,
    CONSTRAINT `fk_smallbinary_data_version`
        FOREIGN KEY (`version_uuid` )
        REFERENCES `t_version` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_smallbinary_data_binary`
        FOREIGN KEY (`smallbinary_uuid` )
        REFERENCES `t_smallbinary` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_smallbinary_data_binary` ON `t_smallbinary_data` (`smallbinary_uuid` ASC);
CREATE INDEX `fk_smallbinary_data_version` ON `t_smallbinary_data` (`version_uuid` ASC);
CREATE UNIQUE INDEX `smallbinary_data_version_uuid_unique` ON `t_smallbinary_data` (`version_uuid` ASC);

delimiter ;;
CREATE TRIGGER tr_before_smallbinary_data_insert BEFORE INSERT ON t_smallbinary_data
FOR EACH ROW 
    BEGIN
        SET NEW.version_uuid = NEW_VERSION();
    END;;
delimiter ;

