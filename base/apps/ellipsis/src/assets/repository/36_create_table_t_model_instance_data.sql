-- ---------------------------------------------------------------------------
-- Table t_model_instance_data
--
-- Each versioned model_instance property value is stored in this table and may,
-- optionally, be accompanied by an identifier key specified by the programmed
-- code for any business logic unknown to this repository module. Model 
-- Instance values are stored as any other valid repository model instance.
-- One custom callout for the model_instance_data record (as it differs from
-- all other data records) is that it has a model_uuid and an instance_uuid 
-- INSTEAD of a value column.
--
-- Note: See boolean data description for key storage information.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `t_model_instance_data` (
    `model_instance_uuid` BINARY(16) NOT NULL,
    `key` VARCHAR(45) NULL,
    `model_uuid` BINARY(16) NOT NULL,
    `instance_uuid` BINARY(16) NULL,
    `version_uuid` BINARY(16) NOT NULL DEFAULT 0,
    CONSTRAINT `fk_model_instance_data_version`
        FOREIGN KEY (`version_uuid` )
        REFERENCES `t_version` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_model_instance_data_model_instance`
        FOREIGN KEY (`model_instance_uuid` )
        REFERENCES `t_model_instance` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_value_model_instance_data_model`
        FOREIGN KEY (`model_uuid` )
        REFERENCES `t_model` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_value_model_instance_data_instance`
        FOREIGN KEY (`instance_uuid` )
        REFERENCES `t_instance` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_model_instance_data_model_instance` ON `t_model_instance_data` (`model_instance_uuid` ASC);
CREATE INDEX `fk_value_model_instance_data_model` ON `t_model_instance_data` (`model_uuid` ASC);
CREATE INDEX `fk_value_model_instance_data_instance` ON `t_model_instance_data` (`instance_uuid` ASC);
CREATE INDEX `fk_model_instance_data_version` ON `t_model_instance_data` (`version_uuid` ASC);
CREATE UNIQUE INDEX `model_instance_data_version_uuid_unique` ON `t_model_instance_data` (`version_uuid` ASC);

delimiter ;;
CREATE TRIGGER tr_before_model_instance_data_insert BEFORE INSERT ON t_model_instance_data
FOR EACH ROW 
    BEGIN
        SET NEW.version_uuid = NEW_VERSION();
    END;;
delimiter ;

