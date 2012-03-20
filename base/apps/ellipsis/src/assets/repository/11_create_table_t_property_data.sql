-- ---------------------------------------------------------------------------
-- Table t_property_data
--
-- Each repository model property has a versionable name, description, and 
-- type. As well as a few other special properties such as:
--
--   mincount:       minimum allowable number of property values
--   maxcount:       maximum allowable number of property values
--   minlength:      minimum allowable numeric value or string length
--   maxlength:      maximum allowable numeric value or string length
--   validation:     regexp used to validate the passed value
--   input_search:   regexp used to prepare values before insertion (search)
--   input_replace:  regexp used to prepare values before insertion (replace)
--   output_search:  regexp used to prepare values before extraction (search)
--   output_replace: regexp used to prepare values before extraction (replace)
--
-- Note: It is highly recommended to pre-interpret these filters in the 
-- programmed code to increase performance by eliminating unnecessary 
-- database communications.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `t_property_data` (
    `property_uuid` BINARY(16) NOT NULL,
    `name` VARCHAR(45) NOT NULL,
    `description` VARCHAR(255) NULL DEFAULT NULL,
    `type` ENUM('boolean','integer','double','datetime','binary','ascii','instance') NOT NULL,
    `mincount` INT NOT NULL DEFAULT 1,
    `maxcount` INT NOT NULL DEFAULT 1,
    `minlength` INT NULL DEFAULT NULL,
    `maxlength` INT NULL DEFAULT NULL,
    `validation` VARCHAR(255) NULL DEFAULT NULL,
    `input_search` VARCHAR(255) NULL DEFAULT NULL,
    `input_replace` VARCHAR(255) NULL DEFAULT NULL,
    `output_search` VARCHAR(255) NULL DEFAULT NULL,
    `output_replace` VARCHAR(255) NULL DEFAULT NULL,
    `version_uuid` BINARY(16) NOT NULL DEFAULT 0,
    PRIMARY KEY (`property_uuid`, `version_uuid`),
    CONSTRAINT `fk_property_data_version`
        FOREIGN KEY (`version_uuid` )
        REFERENCES `t_version` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_property_data_property`
        FOREIGN KEY (`property_uuid` )
        REFERENCES `t_property` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_property_data_property` ON `t_property_data` (`property_uuid` ASC);
CREATE INDEX `fk_property_data_version` ON `t_property_data` (`version_uuid` ASC);
CREATE UNIQUE INDEX `property_data_version_uuid_unique` ON `t_property_data` (`version_uuid` ASC);

delimiter ;;
CREATE TRIGGER tr_before_property_data_insert BEFORE INSERT ON t_property_data
FOR EACH ROW 
    BEGIN
        SET NEW.version_uuid = NEW_VERSION();
    END;;
delimiter ;

