-- ---------------------------------------------------------------------------
-- Table t_longtext
--
-- Each stored instance property value defined as a type "longtext" results 
-- in a longtext record being created.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `t_longtext` (
    `uuid` BINARY(16) NOT NULL DEFAULT 0,
    `instance_uuid` BINARY(16) NOT NULL,
    `property_uuid` BINARY(16) NOT NULL,
    `version_uuid` BINARY(16) NOT NULL DEFAULT 0,
    PRIMARY KEY (`uuid`, `version_uuid`),
    CONSTRAINT `fk_longtext_instance`
        FOREIGN KEY (`instance_uuid` )
        REFERENCES `t_instance` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_longtext_version`
        FOREIGN KEY (`version_uuid` )
        REFERENCES `t_version` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_longtext_property`
        FOREIGN KEY (`property_uuid` )
        REFERENCES `t_property` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_longtext_instance` ON `t_longtext` (`instance_uuid` ASC);
CREATE INDEX `fk_longtext_property` ON `t_longtext` (`property_uuid` ASC);
CREATE INDEX `fk_longtext_version` ON `t_longtext` (`version_uuid` ASC);
CREATE UNIQUE INDEX `longtext_version_uuid_unique` ON `t_longtext` (`version_uuid` ASC);

delimiter ;;
CREATE TRIGGER tr_before_longtext_insert BEFORE INSERT ON t_longtext
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

