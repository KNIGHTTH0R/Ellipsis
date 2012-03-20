-- ---------------------------------------------------------------------------
-- Table t_smallbinary
--
-- Each stored instance property value defined as a type "smallbinary" results 
-- in a smallbinary record being created.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `t_smallbinary` (
    `uuid` BINARY(16) NOT NULL DEFAULT 0,
    `instance_uuid` BINARY(16) NOT NULL,
    `property_uuid` BINARY(16) NOT NULL,
    `version_uuid` BINARY(16) NOT NULL DEFAULT 0,
    PRIMARY KEY (`uuid`, `version_uuid`),
    CONSTRAINT `fk_smallbinary_instance`
        FOREIGN KEY (`instance_uuid` )
        REFERENCES `t_instance` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_smallbinary_version`
        FOREIGN KEY (`version_uuid` )
        REFERENCES `t_version` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_smallbinary_property`
        FOREIGN KEY (`property_uuid` )
        REFERENCES `t_property` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_smallbinary_instance` ON `t_smallbinary` (`instance_uuid` ASC);
CREATE INDEX `fk_smallbinary_property` ON `t_smallbinary` (`property_uuid` ASC);
CREATE INDEX `fk_smallbinary_version` ON `t_smallbinary` (`version_uuid` ASC);
CREATE UNIQUE INDEX `smallbinary_version_uuid_unique` ON `t_smallbinary` (`version_uuid` ASC);

delimiter ;;
CREATE TRIGGER tr_before_smallbinary_insert BEFORE INSERT ON t_smallbinary
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

