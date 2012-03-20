-- ---------------------------------------------------------------------------
-- Table t_boolean_data
--
-- Each versioned boolean property value is stored in this table and may,
-- optionally, be accompanied by an identifier key specified by the programmed
-- code for any business logic unknown to this repository module. Boolean
-- values are stored as 0 or 1.
--
-- Key Note: For example, if this instance property is treated as a class 
-- property this key will most likely be null, if this property is treated as 
-- an indexed array item this key will most likely be a number, and if this 
-- property is treated as a member of an associative array this key will most 
-- likely be a string value. It is up to the programmed code to determine what 
-- key value is appropriate to store with each versioned boolean property 
-- value.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `t_boolean_data` (
    `boolean_uuid` BINARY(16) NOT NULL,
    `key` VARCHAR(45) NULL,
    `value` BIT NOT NULL,
    `version_uuid` BINARY(16) NOT NULL DEFAULT 0,
    CONSTRAINT `fk_boolean_data_version`
        FOREIGN KEY (`version_uuid` )
        REFERENCES `t_version` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_boolean_data_boolean`
        FOREIGN KEY (`boolean_uuid` )
        REFERENCES `t_boolean` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_boolean_data_boolean` ON `t_boolean_data` (`boolean_uuid` ASC);
CREATE INDEX `fk_boolean_data_version` ON `t_boolean_data` (`version_uuid` ASC);
CREATE UNIQUE INDEX `boolean_data_version_uuid_unique` ON `t_boolean_data` (`version_uuid` ASC);

delimiter ;;
CREATE TRIGGER tr_before_boolean_data_insert BEFORE INSERT ON t_boolean_data
FOR EACH ROW 
    BEGIN
        SET NEW.version_uuid = NEW_VERSION();
    END;;
delimiter ;

