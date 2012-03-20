-- ---------------------------------------------------------------------------
-- Table t_uberlongtext_data
--
-- Each versioned uberlongtext property value is stored in this table and may,
-- optionally, be accompanied by an identifier key specified by the programmed
-- code for any business logic unknown to this repository module. Uberlong 
-- Text values are stored as any text value less than or equal to 
-- 4,294,967,295 characters in length.
--
-- Note: See boolean data description for key storage information.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `t_uberlongtext_data` (
    `uberlongtext_uuid` BINARY(16) NOT NULL,
    `key` VARCHAR(45) NULL,
    `value` LONGTEXT NULL,
    `version_uuid` BINARY(16) NOT NULL DEFAULT 0,
    CONSTRAINT `fk_uberlongtext_data_version`
        FOREIGN KEY (`version_uuid` )
        REFERENCES `t_version` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_uberlongtext_data_uberlongtext`
        FOREIGN KEY (`uberlongtext_uuid` )
        REFERENCES `t_uberlongtext` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_uberlongtext_data_uberlongtext` ON `t_uberlongtext_data` (`uberlongtext_uuid` ASC);
CREATE INDEX `fk_uberlongtext_data_version` ON `t_uberlongtext_data` (`version_uuid` ASC);
CREATE UNIQUE INDEX `uberlongtext_data_version_uuid_unique` ON `t_uberlongtext_data` (`version_uuid` ASC);

delimiter ;;
CREATE TRIGGER tr_before_uberlongtext_data_insert BEFORE INSERT ON t_uberlongtext_data
FOR EACH ROW 
    BEGIN
        SET NEW.version_uuid = NEW_VERSION();
    END;;
delimiter ;

