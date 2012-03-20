-- ---------------------------------------------------------------------------
-- Table t_instance
--
-- Each repository model is realized as a populated instance. This is a set of
-- mapped values that correspond to one or more versions of a specific model.
-- Each repository model instance has a unique UUID property to be used as for
-- direct lookups.
--
-- Note: The only reason that an instance has a version is so that it can be
-- deleted with ACTIVE=0. No other data on the actual instance iteself is
-- versionable.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `t_instance` (
    `uuid` BINARY(16) NOT NULL DEFAULT 0,
    `model_uuid` BINARY(16) NOT NULL,
    `version_uuid` BINARY(16) NOT NULL DEFAULT 0,
    PRIMARY KEY (`uuid`, `version_uuid`),
    CONSTRAINT `fk_instance_version`
        FOREIGN KEY (`version_uuid` )
        REFERENCES `t_version` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_instance_model`
        FOREIGN KEY (`model_uuid` )
        REFERENCES `t_model` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_instance_model` ON `t_instance` (`model_uuid` ASC);
CREATE INDEX `fk_instance_version` ON `t_instance` (`version_uuid` ASC);
CREATE UNIQUE INDEX `instance_version_uuid_unique` ON `t_instance` (`version_uuid` ASC);

delimiter ;;
CREATE TRIGGER tr_before_instance_insert BEFORE INSERT ON t_instance
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

