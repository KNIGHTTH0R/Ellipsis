-- ---------------------------------------------------------------------------
-- Table t_integer
--
-- Each stored instance property value defined as a type "integer" results in
-- an integer record being created.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `t_integer` (
    `uuid` BINARY(16) NOT NULL DEFAULT 0,
    `instance_uuid` BINARY(16) NOT NULL,
    `property_uuid` BINARY(16) NOT NULL,
    `version_uuid` BINARY(16) NOT NULL DEFAULT 0,
    PRIMARY KEY (`uuid`, `version_uuid`),
    CONSTRAINT `fk_integer_instance`
        FOREIGN KEY (`instance_uuid` )
        REFERENCES `t_instance` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_integer_version`
        FOREIGN KEY (`version_uuid` )
        REFERENCES `t_version` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_integer_property`
        FOREIGN KEY (`property_uuid` )
        REFERENCES `t_property` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_integer_instance` ON `t_integer` (`instance_uuid` ASC);
CREATE INDEX `fk_integer_property` ON `t_integer` (`property_uuid` ASC);
CREATE INDEX `fk_integer_version` ON `t_integer` (`version_uuid` ASC);
CREATE UNIQUE INDEX `integer_version_uuid_unique` ON `t_integer` (`version_uuid` ASC);

delimiter ;;
CREATE TRIGGER tr_before_integer_insert BEFORE INSERT ON t_integer
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

