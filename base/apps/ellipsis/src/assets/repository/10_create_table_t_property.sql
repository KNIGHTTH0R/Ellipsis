-- ---------------------------------------------------------------------------
-- Table t_property
--
-- Each repository model has, in addition to it's own properties, one or more
-- user-defined property values. These properties are most commonly reflected
-- in programmed code as standard Class or Object properties.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `t_property` (
    `uuid` BINARY(16) NOT NULL DEFAULT 0,
    `model_uuid` BINARY(16) NOT NULL,
    `version_uuid` BINARY(16) NOT NULL DEFAULT 0,
    PRIMARY KEY (`uuid`, `version_uuid`),
    CONSTRAINT `fk_property_model`
        FOREIGN KEY (`model_uuid` )
        REFERENCES `t_model` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_property_version`
        FOREIGN KEY (`version_uuid` )
        REFERENCES `t_version` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_property_model` ON `t_property` (`model_uuid` ASC);
CREATE INDEX `fk_property_version` ON `t_property` (`version_uuid` ASC);
CREATE UNIQUE INDEX `property_version_uuid_unique` ON `t_property` (`version_uuid` ASC);

delimiter ;;
CREATE TRIGGER tr_before_property_insert BEFORE INSERT ON t_property
FOR EACH ROW 
    BEGIN
        DECLARE new_uuid BINARY(16);
        IF (NEW.uuid = 0) THEN
            SET new_uuid = UNHEX(REPLACE(UUID(), '-', ''));
            SET NEW.uuid = new_uuid;
            SET @LAST_INSERT_UUID = new_uuid;
        END IF;
        SET NEW.version_uuid = NEW_VERSION();
    END;;
delimiter ;

