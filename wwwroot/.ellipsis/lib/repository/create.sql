
-- ---------------------------------------------------------------------------
-- Remember environment settings
-- ---------------------------------------------------------------------------
SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';


-- ---------------------------------------------------------------------------
-- Reset schema
-- ---------------------------------------------------------------------------
DROP SCHEMA IF EXISTS `repository`;
CREATE SCHEMA IF NOT EXISTS `repository` DEFAULT CHARACTER SET latin1;
USE `repository`;


-- ---------------------------------------------------------------------------
-- Function NEW_VERSION()
--
-- Create a new repository version record and return it's ID. Every record in
-- every table in this repository module gets its own version_uuid so this
-- function is triggered for every newly inserted table record.
-- ---------------------------------------------------------------------------
delimiter ;;
CREATE FUNCTION NEW_VERSION() RETURNS BINARY(16)
    DETERMINISTIC
BEGIN
    DECLARE new_uuid BINARY(16);
    SET new_uuid = UNHEX(REPLACE(UUID(), '-', ''));
    INSERT INTO t_version (uuid, tag) VALUES (new_uuid, '');
    RETURN new_uuid;
END;;
delimiter ;


-- ---------------------------------------------------------------------------
-- Function GET_CREATED(TABLE, UUID)
--
-- Get the created timestamp for the current ${TABLE}'s ${UUID}.
-- ---------------------------------------------------------------------------
delimiter ;;
CREATE FUNCTION GET_CREATED(p_table VARCHAR(40), p_uuid CHAR(36)) RETURNS INT
    DETERMINISTIC
BEGIN
    DECLARE result INT;
    RETURN (
        SELECT
            v.created
        FROM
            version v,
            p_table pt
        WHERE
            pt.version_uuid = v.uuid AND
            pt.uuid = p_uuid
        ORDER BY
            1 ASC
    );
END;;
delimiter ;


-- ---------------------------------------------------------------------------
-- Table t_version
--
-- Each repository record is associated with a unique version number. No 
-- records are ever deleted except by database administrators performing
-- database archiving. This enables snapshots in time (tags) as well live 
-- rollbacks. A "deleted" record is a new version record created with 
-- ACTIVE=0.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `t_version` (
    `uuid` BINARY(16) NOT NULL,
    `tag` VARCHAR(100) NULL DEFAULT NULL,
    `active` BIT(1) NOT NULL DEFAULT 1,
    `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`uuid`) )
ENGINE = InnoDB;


-- ---------------------------------------------------------------------------
-- Table t_model
--
-- Each repository model represents a manipulatable user-defined data model
-- most commonly reflected in programmed code as a standard Class or Object.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `t_model` (
    `uuid` BINARY(16) NOT NULL DEFAULT 0,
    `version_uuid` BINARY(16) NOT NULL DEFAULT 0,
    PRIMARY KEY (`uuid`, `version_uuid`),
    CONSTRAINT `fk_model_version`
        FOREIGN KEY (`version_uuid` )
        REFERENCES `t_version` (`uuid`)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_model_version` ON `t_model` (`version_uuid` ASC);
CREATE UNIQUE INDEX `model_version_uuid_unique` ON `t_model` (`version_uuid` ASC);

delimiter ;;
CREATE TRIGGER tr_before_model_insert BEFORE INSERT ON t_model
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


-- ---------------------------------------------------------------------------
-- Table t_model_data
--
-- Each repository model has versionable name and description properties.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `t_model_data` (
    `model_uuid` BINARY(16) NOT NULL,
    `name` VARCHAR(45) NOT NULL,
    `description` VARCHAR(255) NULL DEFAULT NULL,
    `version_uuid` BINARY(16) NOT NULL DEFAULT 0,
    PRIMARY KEY (`model_uuid`, `version_uuid`),
    CONSTRAINT `fk_model_data_version`
        FOREIGN KEY (`version_uuid` )
        REFERENCES `t_version` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_model_data_model`
        FOREIGN KEY (`model_uuid` )
        REFERENCES `t_model` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_model_data_model` ON `t_model_data` (`model_uuid` ASC);
CREATE INDEX `fk_model_data_version` ON `t_model_data` (`version_uuid` ASC);
CREATE UNIQUE INDEX `model_data_version_uuid_unique` ON `t_model_data` (`version_uuid` ASC);

delimiter ;;
CREATE TRIGGER tr_before_model_data_insert BEFORE INSERT ON t_model_data
FOR EACH ROW 
    BEGIN
        SET NEW.version_uuid = NEW_VERSION();
    END;;
delimiter ;


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


-- ---------------------------------------------------------------------------
-- Table t_boolean
--
-- Each stored instance property value defined as a type "boolean" results in
-- a boolean record being created.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `t_boolean` (
    `uuid` BINARY(16) NOT NULL DEFAULT 0,
    `instance_uuid` BINARY(16) NOT NULL,
    `property_uuid` BINARY(16) NOT NULL,
    `version_uuid` BINARY(16) NOT NULL DEFAULT 0,
    PRIMARY KEY (`uuid`, `version_uuid`),
    CONSTRAINT `fk_boolean_instance`
        FOREIGN KEY (`instance_uuid` )
        REFERENCES `t_instance` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_boolean_version`
        FOREIGN KEY (`version_uuid` )
        REFERENCES `t_version` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_boolean_property`
        FOREIGN KEY (`property_uuid` )
        REFERENCES `t_property` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_boolean_instance` ON `t_boolean` (`instance_uuid` ASC);
CREATE INDEX `fk_boolean_property` ON `t_boolean` (`property_uuid` ASC);
CREATE INDEX `fk_boolean_version` ON `t_boolean` (`version_uuid` ASC);
CREATE UNIQUE INDEX `boolean_version_uuid_unique` ON `t_boolean` (`version_uuid` ASC);

delimiter ;;
CREATE TRIGGER tr_before_boolean_insert BEFORE INSERT ON t_boolean
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


-- ---------------------------------------------------------------------------
-- Table t_integer_data
--
-- Each versioned integer property value is stored in this table and may,
-- optionally, be accompanied by an identifier key specified by the programmed
-- code for any business logic unknown to this repository module. Integer
-- values are stored as whole numbers greater than or equal to 0.
--
-- Note: See boolean data description for key storage information.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `t_integer_data` (
    `integer_uuid` BINARY(16) NOT NULL,
    `key` VARCHAR(45) NULL,
    `value` INT NOT NULL DEFAULT 0,
    `version_uuid` BINARY(16) NOT NULL DEFAULT 0,
    CONSTRAINT `fk_integer_data_version`
        FOREIGN KEY (`version_uuid` )
        REFERENCES `t_version` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_integer_data_integer`
        FOREIGN KEY (`integer_uuid` )
        REFERENCES `t_integer` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_integer_data_integer` ON `t_integer_data` (`integer_uuid` ASC);
CREATE INDEX `fk_integer_data_version` ON `t_integer_data` (`version_uuid` ASC);
CREATE UNIQUE INDEX `integer_data_version_uuid_unique` ON `t_integer_data` (`version_uuid` ASC);

delimiter ;;
CREATE TRIGGER tr_before_integer_data_insert BEFORE INSERT ON t_integer_data
FOR EACH ROW 
    BEGIN
        SET NEW.version_uuid = NEW_VERSION();
    END;;
delimiter ;


-- ---------------------------------------------------------------------------
-- Table t_double
--
-- Each stored instance property value defined as a type "double" results in
-- a double record being created.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `t_double` (
    `uuid` BINARY(16) NOT NULL DEFAULT 0,
    `instance_uuid` BINARY(16) NOT NULL,
    `property_uuid` BINARY(16) NOT NULL,
    `version_uuid` BINARY(16) NOT NULL DEFAULT 0,
    PRIMARY KEY (`uuid`, `version_uuid`),
    CONSTRAINT `fk_double_instance`
        FOREIGN KEY (`instance_uuid` )
        REFERENCES `t_instance` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_double_version`
        FOREIGN KEY (`version_uuid` )
        REFERENCES `t_version` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_double_property`
        FOREIGN KEY (`property_uuid` )
        REFERENCES `t_property` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_double_instance` ON `t_double` (`instance_uuid` ASC);
CREATE INDEX `fk_double_property` ON `t_double` (`property_uuid` ASC);
CREATE INDEX `fk_double_version` ON `t_double` (`version_uuid` ASC);
CREATE UNIQUE INDEX `double_version_uuid_unique` ON `t_double` (`version_uuid` ASC);

delimiter ;;
CREATE TRIGGER tr_before_double_insert BEFORE INSERT ON t_double
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


-- ---------------------------------------------------------------------------
-- Table t_double_data
--
-- Each versioned double property value is stored in this table and may,
-- optionally, be accompanied by an identifier key specified by the programmed
-- code for any business logic unknown to this repository module. Double
-- values are stored as float values (numbers with decimals) greater than or 
-- equal to 0.
--
-- Note: See boolean data description for key storage information.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `t_double_data` (
  `double_uuid` BINARY(16) NOT NULL,
  `key` VARCHAR(45) NULL,
  `value` DOUBLE NOT NULL DEFAULT 0.0,
  `version_uuid` BINARY(16) NOT NULL DEFAULT 0,
  CONSTRAINT `fk_double_data_version`
    FOREIGN KEY (`version_uuid` )
    REFERENCES `t_version` (`uuid` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_double_data_double`
    FOREIGN KEY (`double_uuid` )
    REFERENCES `t_double` (`uuid` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_double_data_double` ON `t_double_data` (`double_uuid` ASC);
CREATE INDEX `fk_double_data_version` ON `t_double_data` (`version_uuid` ASC);
CREATE UNIQUE INDEX `double_data_version_uuid_unique` ON `t_double_data` (`version_uuid` ASC);

delimiter ;;
CREATE TRIGGER tr_before_double_data_insert BEFORE INSERT ON t_double_data
FOR EACH ROW 
    BEGIN
        SET NEW.version_uuid = NEW_VERSION();
    END;;
delimiter ;


-- ---------------------------------------------------------------------------
-- Table t_datetime
--
-- Each stored instance property value defined as a type "datetime" results in
-- a datetime record being created.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `t_datetime` (
    `uuid` BINARY(16) NOT NULL DEFAULT 0,
    `instance_uuid` BINARY(16) NOT NULL,
    `property_uuid` BINARY(16) NOT NULL,
    `version_uuid` BINARY(16) NOT NULL DEFAULT 0,
    PRIMARY KEY (`uuid`, `version_uuid`),
    CONSTRAINT `fk_datetime_instance`
        FOREIGN KEY (`instance_uuid` )
        REFERENCES `t_instance` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_datetime_version`
        FOREIGN KEY (`version_uuid` )
        REFERENCES `t_version` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_datetime_property`
        FOREIGN KEY (`property_uuid` )
        REFERENCES `t_property` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_datetime_instance` ON `t_datetime` (`instance_uuid` ASC);
CREATE INDEX `fk_datetime_property` ON `t_datetime` (`property_uuid` ASC);
CREATE INDEX `fk_datetime_version` ON `t_datetime` (`version_uuid` ASC);
CREATE UNIQUE INDEX `datetime_version_uuid_unique` ON `t_datetime` (`version_uuid` ASC);

delimiter ;;
CREATE TRIGGER tr_before_datetime_insert BEFORE INSERT ON t_datetime
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


-- ---------------------------------------------------------------------------
-- Table t_datetime_data
--
-- Each versioned datetime property value is stored in this table and may,
-- optionally, be accompanied by an identifier key specified by the programmed
-- code for any business logic unknown to this repository module. Datetime
-- values are stored as Unix time (or POSIX time) which is the number of
-- seconds elapsed since midnight (UTC) January 1, 1970 (not counting leap 
-- seconds). This is an easier time value to convert to whatever time format
-- deemed appropriate for a particular programmed environment.
--
-- Note: See boolean data description for key storage information.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `t_datetime_data` (
    `datetime_uuid` BINARY(16) NOT NULL,
    `key` VARCHAR(45) NULL,
    `value` INT NOT NULL DEFAULT 0,
    `version_uuid` BINARY(16) NOT NULL DEFAULT 0,
    CONSTRAINT `fk_datetime_data_version`
        FOREIGN KEY (`version_uuid` )
        REFERENCES `t_version` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_datetime_data_datetime`
        FOREIGN KEY (`datetime_uuid` )
        REFERENCES `t_datetime` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_datetime_data_datetime` ON `t_datetime_data` (`datetime_uuid` ASC);
CREATE INDEX `fk_datetime_data_version` ON `t_datetime_data` (`version_uuid` ASC);
CREATE UNIQUE INDEX `datetime_data_version_uuid_unique` ON `t_datetime_data` (`version_uuid` ASC);

delimiter ;;
CREATE TRIGGER tr_before_datetime_data_insert BEFORE INSERT ON t_datetime_data
FOR EACH ROW 
    BEGIN
        SET NEW.version_uuid = NEW_VERSION();
    END;;
delimiter ;


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


-- ---------------------------------------------------------------------------
-- Table t_mediumbinary
--
-- Each stored instance property value defined as a type "mediumbinary" 
-- results in a mediumbinary record being created.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `t_mediumbinary` (
    `uuid` BINARY(16) NOT NULL DEFAULT 0,
    `instance_uuid` BINARY(16) NOT NULL,
    `property_uuid` BINARY(16) NOT NULL,
    `version_uuid` BINARY(16) NOT NULL DEFAULT 0,
    PRIMARY KEY (`uuid`, `version_uuid`),
    CONSTRAINT `fk_mediumbinary_version`
        FOREIGN KEY (`version_uuid` )
        REFERENCES `t_version` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_mediumbinary_instance`
        FOREIGN KEY (`instance_uuid` )
        REFERENCES `t_instance` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_mediumbinary_property`
        FOREIGN KEY (`property_uuid` )
        REFERENCES `t_property` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_mediumbinary_instance` ON `t_mediumbinary` (`instance_uuid` ASC);
CREATE INDEX `fk_mediumbinary_property` ON `t_mediumbinary` (`property_uuid` ASC);
CREATE INDEX `fk_mediumbinary_version` ON `t_mediumbinary` (`version_uuid` ASC);
CREATE UNIQUE INDEX `mediumbinary_version_uuid_unique` ON `t_mediumbinary` (`version_uuid` ASC);

delimiter ;;
CREATE TRIGGER tr_before_mediumbinary_insert BEFORE INSERT ON t_mediumbinary
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


-- ---------------------------------------------------------------------------
-- Table t_mediumbinary_data
--
-- Each versioned mediumbinary property value is stored in this table and may,
-- optionally, be accompanied by an identifier key specified by the programmed
-- code for any business logic unknown to this repository module. Medium Binary
-- values are stored as any binary value less than or equal to 16,777,215 
-- characters in length (approximately 16MB).
--
-- Note: See boolean data description for key storage information.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `t_mediumbinary_data` (
    `mediumbinary_uuid` BINARY(16) NOT NULL,
    `key` VARCHAR(45) NULL,
    `value` MEDIUMBLOB NULL,
    `version_uuid` BINARY(16) NOT NULL DEFAULT 0,
    CONSTRAINT `fk_mediumbinary_data_mediumbinary`
        FOREIGN KEY (`mediumbinary_uuid` )
        REFERENCES `t_mediumbinary` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_mediumbinary_data_version`
        FOREIGN KEY (`version_uuid` )
        REFERENCES `t_version` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_mediumbinary_data_mediumbinary` ON `t_mediumbinary_data` (`mediumbinary_uuid` ASC);
CREATE INDEX `fk_mediumbinary_data_version` ON `t_mediumbinary_data` (`version_uuid` ASC);
CREATE UNIQUE INDEX `mediumbinary_data_version_uuid_unique` ON `t_mediumbinary_data` (`version_uuid` ASC);

delimiter ;;
CREATE TRIGGER tr_before_mediumbinary_data_insert BEFORE INSERT ON t_mediumbinary_data
FOR EACH ROW 
    BEGIN
        SET NEW.version_uuid = NEW_VERSION();
    END;;
delimiter ;


-- ---------------------------------------------------------------------------
-- Table t_longbinary
--
-- Each stored instance property value defined as a type "longbinary" results 
-- in a longbinary record being created.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `t_longbinary` (
    `uuid` BINARY(16) NOT NULL DEFAULT 0,
    `instance_uuid` BINARY(16) NOT NULL,
    `property_uuid` BINARY(16) NOT NULL,
    `version_uuid` BINARY(16) NOT NULL DEFAULT 0,
    PRIMARY KEY (`uuid`, `version_uuid`),
    CONSTRAINT `fk_longbinary_instance`
        FOREIGN KEY (`instance_uuid` )
        REFERENCES `t_instance` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_longbinary_property`
        FOREIGN KEY (`property_uuid` )
        REFERENCES `t_property` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_longbinary_version`
        FOREIGN KEY (`version_uuid` )
        REFERENCES `t_version` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_longbinary_instance` ON `t_longbinary` (`instance_uuid` ASC);
CREATE INDEX `fk_longbinary_property` ON `t_longbinary` (`property_uuid` ASC);
CREATE INDEX `fk_longbinary_version` ON `t_longbinary` (`version_uuid` ASC);
CREATE UNIQUE INDEX `longbinary_version_uuid_unique` ON `t_longbinary` (`version_uuid` ASC);

delimiter ;;
CREATE TRIGGER tr_before_longbinary_insert BEFORE INSERT ON t_longbinary
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


-- ---------------------------------------------------------------------------
-- Table t_longbinary_data
--
-- Each versioned longbinary property value is stored in this table and may,
-- optionally, be accompanied by an identifier key specified by the programmed
-- code for any business logic unknown to this repository module. Long Binary
-- values are stored as any binary value less than or equal to 4,294,967,295
-- characters in length (4GB).
--
-- Note: See boolean data description for key storage information.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `t_longbinary_data` (
    `longbinary_uuid` BINARY(16) NOT NULL,
    `key` VARCHAR(45) NULL,
    `value` LONGBLOB NULL,
    `version_uuid` BINARY(16) NOT NULL DEFAULT 0,
    CONSTRAINT `fk_longbinary_data_longbinary`
        FOREIGN KEY (`longbinary_uuid` )
        REFERENCES `t_longbinary` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_longbinary_data_version`
        FOREIGN KEY (`version_uuid` )
        REFERENCES `t_version` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_longbinary_data_longbinary` ON `t_longbinary_data` (`longbinary_uuid` ASC);
CREATE INDEX `fk_longbinary_data_version` ON `t_longbinary_data` (`version_uuid` ASC);
CREATE UNIQUE INDEX `longbinary_data_version_uuid_unique` ON `t_longbinary_data` (`version_uuid` ASC);

delimiter ;;
CREATE TRIGGER tr_before_longbinary_data_insert BEFORE INSERT ON t_longbinary_data
FOR EACH ROW 
    BEGIN
        SET NEW.version_uuid = NEW_VERSION();
    END;;
delimiter ;


-- ---------------------------------------------------------------------------
-- Table t_smalltext
--
-- Each stored instance property value defined as a type "smalltext" results 
-- in a smalltext record being created.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `t_smalltext` (
    `uuid` BINARY(16) NOT NULL DEFAULT 0,
    `instance_uuid` BINARY(16) NOT NULL,
    `property_uuid` BINARY(16) NOT NULL,
    `version_uuid` BINARY(16) NOT NULL DEFAULT 0,
    PRIMARY KEY (`uuid`, `version_uuid`),
    CONSTRAINT `fk_smalltext_instance`
        FOREIGN KEY (`instance_uuid` )
        REFERENCES `t_instance` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_smalltext_version`
        FOREIGN KEY (`version_uuid` )
        REFERENCES `t_version` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_smalltext_property`
        FOREIGN KEY (`property_uuid` )
        REFERENCES `t_property` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_smalltext_instance` ON `t_smalltext` (`instance_uuid` ASC);
CREATE INDEX `fk_smalltext_property` ON `t_smalltext` (`property_uuid` ASC);
CREATE INDEX `fk_smalltext_version` ON `t_smalltext` (`version_uuid` ASC);
CREATE UNIQUE INDEX `smalltext_version_uuid_unique` ON `t_smalltext` (`version_uuid` ASC);

delimiter ;;
CREATE TRIGGER tr_before_smalltext_insert BEFORE INSERT ON t_smalltext
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


-- ---------------------------------------------------------------------------
-- Table t_smalltext_data
--
-- Each versioned smalltext property value is stored in this table and may,
-- optionally, be accompanied by an identifier key specified by the programmed
-- code for any business logic unknown to this repository module. Small Text
-- values are stored as any text value less than or equal to 1000 characters 
-- in length.
--
-- Note: See boolean data description for key storage information.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `t_smalltext_data` (
    `smalltext_uuid` BINARY(16) NOT NULL,
    `key` VARCHAR(45) NULL,
    `value` VARCHAR(1000) NULL,
    `version_uuid` BINARY(16) NOT NULL DEFAULT 0,
    CONSTRAINT `fk_smalltext_data_version`
        FOREIGN KEY (`version_uuid` )
        REFERENCES `t_version` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_smalltext_data_smalltext`
        FOREIGN KEY (`smalltext_uuid` )
        REFERENCES `t_smalltext` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_smalltext_data_smalltext` ON `t_smalltext_data` (`smalltext_uuid` ASC);
CREATE INDEX `fk_smalltext_data_version` ON `t_smalltext_data` (`version_uuid` ASC);
CREATE UNIQUE INDEX `smalltext_data_version_uuid_unique` ON `t_smalltext_data` (`version_uuid` ASC);

delimiter ;;
CREATE TRIGGER tr_before_smalltext_data_insert BEFORE INSERT ON t_smalltext_data
FOR EACH ROW 
    BEGIN
        SET NEW.version_uuid = NEW_VERSION();
    END;;
delimiter ;


-- ---------------------------------------------------------------------------
-- Table t_mediumtext
--
-- Each stored instance property value defined as a type "mediumtext" results 
-- in a mediumtext record being created.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `t_mediumtext` (
    `uuid` BINARY(16) NOT NULL DEFAULT 0,
    `instance_uuid` BINARY(16) NOT NULL,
    `property_uuid` BINARY(16) NOT NULL,
    `version_uuid` BINARY(16) NOT NULL DEFAULT 0,
    PRIMARY KEY (`uuid`, `version_uuid`),
    CONSTRAINT `fk_mediumtext_instance`
        FOREIGN KEY (`instance_uuid` )
        REFERENCES `t_instance` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_mediumtext_version`
        FOREIGN KEY (`version_uuid` )
        REFERENCES `t_version` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_mediumtext_property`
        FOREIGN KEY (`property_uuid` )
        REFERENCES `t_property` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_mediumtext_instance` ON `t_mediumtext` (`instance_uuid` ASC);
CREATE INDEX `fk_mediumtext_property` ON `t_mediumtext` (`property_uuid` ASC);
CREATE INDEX `fk_mediumtext_version` ON `t_mediumtext` (`version_uuid` ASC);
CREATE UNIQUE INDEX `mediumtext_version_uuid_unique` ON `t_mediumtext` (`version_uuid` ASC);

delimiter ;;
CREATE TRIGGER tr_before_mediumtext_insert BEFORE INSERT ON t_mediumtext
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


-- ---------------------------------------------------------------------------
-- Table t_mediumtext_data
--
-- Each versioned mediumtext property value is stored in this table and may,
-- optionally, be accompanied by an identifier key specified by the programmed
-- code for any business logic unknown to this repository module. Medium Text
-- values are stored as any text value less than or equal to 4000 characters 
-- in length.
--
-- Note: See boolean data description for key storage information.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `t_mediumtext_data` (
    `mediumtext_uuid` BINARY(16) NOT NULL,
    `key` VARCHAR(45) NULL,
    `value` VARCHAR(4000) NULL,
    `version_uuid` BINARY(16) NOT NULL DEFAULT 0,
    CONSTRAINT `fk_mediumtext_data_version`
        FOREIGN KEY (`version_uuid` )
        REFERENCES `t_version` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_mediumtext_data_mediumtext`
        FOREIGN KEY (`mediumtext_uuid` )
        REFERENCES `t_mediumtext` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_mediumtext_data_mediumtext` ON `t_mediumtext_data` (`mediumtext_uuid` ASC);
CREATE INDEX `fk_mediumtext_data_version` ON `t_mediumtext_data` (`version_uuid` ASC);
CREATE UNIQUE INDEX `mediumtext_data_version_uuid_unique` ON `t_mediumtext_data` (`version_uuid` ASC);

delimiter ;;
CREATE TRIGGER tr_before_mediumtext_data_insert BEFORE INSERT ON t_mediumtext_data
FOR EACH ROW 
    BEGIN
        SET NEW.version_uuid = NEW_VERSION();
    END;;
delimiter ;


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


-- ---------------------------------------------------------------------------
-- Table t_longtext_data
--
-- Each versioned longtext property value is stored in this table and may,
-- optionally, be accompanied by an identifier key specified by the programmed
-- code for any business logic unknown to this repository module. Long Text
-- values are stored as any text value less than or equal to 65,535 characters 
-- in length.
--
-- Note: See boolean data description for key storage information.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `t_longtext_data` (
    `longtext_uuid` BINARY(16) NOT NULL,
    `key` VARCHAR(45) NULL,
    `value` TEXT NULL,
    `version_uuid` BINARY(16) NOT NULL DEFAULT 0,
    CONSTRAINT `fk_longtext_data_longtext`
        FOREIGN KEY (`longtext_uuid` )
        REFERENCES `t_longtext` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_longtext_data_version`
        FOREIGN KEY (`version_uuid` )
        REFERENCES `t_version` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_longtext_data_longtext` ON `t_longtext_data` (`longtext_uuid` ASC);
CREATE INDEX `fk_longtext_data_version` ON `t_longtext_data` (`version_uuid` ASC);
CREATE UNIQUE INDEX `longtext_data_version_uuid_unique` ON `t_longtext_data` (`version_uuid` ASC);

delimiter ;;
CREATE TRIGGER tr_before_longtext_data_insert BEFORE INSERT ON t_longtext_data
FOR EACH ROW 
    BEGIN
        SET NEW.version_uuid = NEW_VERSION();
    END;;
delimiter ;


-- ---------------------------------------------------------------------------
-- Table t_uberlongtext
--
-- Each stored instance property value defined as a type "uberlongtext" results 
-- in a uberlongtext record being created.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `t_uberlongtext` (
    `uuid` BINARY(16) NOT NULL DEFAULT 0,
    `instance_uuid` BINARY(16) NOT NULL,
    `property_uuid` BINARY(16) NOT NULL,
    `version_uuid` BINARY(16) NOT NULL DEFAULT 0,
    PRIMARY KEY (`uuid`, `version_uuid`),
    CONSTRAINT `fk_uberlongtext_instance`
        FOREIGN KEY (`instance_uuid` )
        REFERENCES `t_instance` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_uberlongtext_version`
        FOREIGN KEY (`version_uuid` )
        REFERENCES `t_version` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_uberlongtext_property`
        FOREIGN KEY (`property_uuid` )
        REFERENCES `t_property` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_uberlongtext_instance` ON `t_uberlongtext` (`instance_uuid` ASC);
CREATE INDEX `fk_uberlongtext_property` ON `t_uberlongtext` (`property_uuid` ASC);
CREATE INDEX `fk_uberlongtext_version` ON `t_uberlongtext` (`version_uuid` ASC);
CREATE UNIQUE INDEX `uberlongtext_version_uuid_unique` ON `t_uberlongtext` (`version_uuid` ASC);

delimiter ;;
CREATE TRIGGER tr_before_uberlongtext_insert BEFORE INSERT ON t_uberlongtext
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


-- ---------------------------------------------------------------------------
-- Table t_model_instance
--
-- Each stored instance property value defined as a type "instance" results in
-- a model_instance record being created.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `t_model_instance` (
    `uuid` BINARY(16) NOT NULL DEFAULT 0,
    `instance_uuid` BINARY(16) NOT NULL,
    `property_uuid` BINARY(16) NOT NULL,
    `version_uuid` BINARY(16) NOT NULL DEFAULT 0,
    PRIMARY KEY (`uuid`, `version_uuid`),
    CONSTRAINT `fk_model_instance_instance`
        FOREIGN KEY (`instance_uuid` )
        REFERENCES `t_instance` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_model_instance_version`
        FOREIGN KEY (`version_uuid` )
        REFERENCES `t_version` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT `fk_model_instance_property`
        FOREIGN KEY (`property_uuid` )
        REFERENCES `t_property` (`uuid` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE INDEX `fk_model_instance_instance` ON `t_model_instance` (`instance_uuid` ASC);
CREATE INDEX `fk_model_instance_property` ON `t_model_instance` (`property_uuid` ASC);
CREATE INDEX `fk_model_instance_version` ON `t_model_instance` (`version_uuid` ASC);
CREATE UNIQUE INDEX `model_instance_version_uuid_unique` ON `t_model_instance` (`version_uuid` ASC);

delimiter ;;
CREATE TRIGGER tr_before_model_instance_insert BEFORE INSERT ON t_model_instance
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


-- ---------------------------------------------------------------------------
-- View v_model
--
-- This model view represents the latest version of every active model record
-- in the repository. All programmed code should refer to this model view 
-- INSTEAD of querying data from the t_model and t_model_data tables directly.
-- A model can be accessed directly by "uuid" or "name".
-- ---------------------------------------------------------------------------

CREATE VIEW v_model AS
SELECT
    hex(m.uuid) AS uuid,
    md.name AS name,
    md.description AS description,
    v2.created AS created
FROM
    t_version v1,
    t_version v2,
    t_model m,
    t_model_data md
LEFT JOIN t_model_data md2 ON
    (
        md.model_uuid = md2.model_uuid AND
        md.version_uuid < md2.version_uuid
    )
WHERE
    md2.version_uuid IS NULL AND
    m.uuid = md.model_uuid AND
    m.version_uuid = v1.uuid AND
    md.version_uuid = v2.uuid AND
    v1.active = TRUE AND
    v2.active = TRUE;


-- ---------------------------------------------------------------------------
-- View v_property
--
-- This property view represents the latest version of every active property
-- record in the repository. All programmed code should refer to this property 
-- view INSTEAD of querying data from the t_property and t_property_data 
-- tables directly. A property can be accessed indirectly by "model_uuid" and
-- then directly by "uuid" or "name".
-- ---------------------------------------------------------------------------

CREATE VIEW v_property AS
SELECT
    hex(p.uuid) AS uuid,
    hex(p.model_uuid) AS model_uuid,
    pd.name AS name,
    pd.description AS description,
    pd.type AS `type`,
    pd.mincount AS mincount,
    pd.maxcount AS maxcount,
    pd.minlength AS minlength,
    pd.maxlength AS maxlength,
    pd.validation AS validation,
    pd.input_search AS input_search,
    pd.input_replace AS input_replace,
    pd.output_search AS output_search,
    pd.output_replace AS output_replace,
    v3.created AS created
FROM
    t_version v1,
    t_version v2,
    t_version v3,
    t_model m,
    t_property p,
    t_property_data pd
LEFT JOIN t_property_data pd2 ON
    (
        pd.property_uuid = pd2.property_uuid AND
        pd.version_uuid < pd2.version_uuid
    )
WHERE
    pd2.version_uuid IS NULL AND
    p.uuid = pd.property_uuid AND
    p.model_uuid = m.uuid AND
    m.version_uuid = v1.uuid AND
    p.version_uuid = v2.uuid AND
    pd.version_uuid = v3.uuid AND
    v1.active = TRUE AND
    v2.active = TRUE AND
    v3.active = TRUE;


-- ---------------------------------------------------------------------------
-- View v_instance
--
-- This instance view represents the latest version of every active instance
-- record in the repository. All programmed code should refer to this instance 
-- view INSTEAD of querying data from the t_instance table directly. An 
-- instance can be accessed indirectly by "model_uuid" and then directly by 
-- "uuid".
-- ---------------------------------------------------------------------------

CREATE VIEW v_instance AS
SELECT
    hex(i.uuid) AS uuid,
    hex(i.model_uuid) AS model_uuid,
    v2.created AS created
FROM
    t_version v1,
    t_version v2,
    t_model m,
    t_instance i
LEFT JOIN t_instance i2 ON
    (
        i.uuid = i2.uuid AND
        i.version_uuid < i2.version_uuid
    )
WHERE
    i2.version_uuid IS NULL AND
    i.model_uuid = m.uuid AND
    m.version_uuid = v1.uuid AND
    i.version_uuid = v2.uuid AND
    v1.active = TRUE AND
    v2.active = TRUE;


-- ---------------------------------------------------------------------------
-- View v_boolean_value
--
-- This value view represents the latest version of every active boolean value 
-- record in the repository. This view should be primarily called by the 
-- generic value view rather than directly.
-- ---------------------------------------------------------------------------

CREATE VIEW v_boolean_value AS
SELECT
    hex(b.uuid) AS uuid,
    hex(b.instance_uuid) AS instance_uuid,
    hex(b.property_uuid) AS property_uuid,
    bd.key AS `key`,
    bd.value AS value,
    v3.created AS created
FROM
    t_version v1,
    t_version v2,
    t_version v3,
    t_version v4,
    t_instance i,
    t_property p,
    t_boolean b,
    t_boolean_data bd
LEFT JOIN t_boolean_data bd2 ON
    (
        bd.boolean_uuid = bd2.boolean_uuid AND
        bd.version_uuid < bd2.version_uuid
    )
WHERE
    bd2.version_uuid IS NULL AND
    b.uuid = bd.boolean_uuid AND
    b.instance_uuid = i.uuid AND
    b.property_uuid = p.uuid AND
    i.version_uuid = v1.uuid AND
    p.version_uuid = v2.uuid AND
    b.version_uuid = v3.uuid AND
    bd.version_uuid = v4.uuid AND
    v1.active = TRUE AND
    v2.active = TRUE AND
    v3.active = TRUE AND
    v4.active = TRUE;


-- ---------------------------------------------------------------------------
-- View v_integer_value
--
-- This value view represents the latest version of every active integer value 
-- record in the repository. This view should be primarily called by the 
-- generic value view rather than directly.
-- ---------------------------------------------------------------------------

CREATE VIEW v_integer_value AS
SELECT
    hex(n.uuid) AS uuid,
    hex(n.instance_uuid) AS instance_uuid,
    hex(n.property_uuid) AS property_uuid,
    nd.key AS `key`,
    nd.value AS value,
    v3.created AS created
FROM
    t_version v1,
    t_version v2,
    t_version v3,
    t_version v4,
    t_instance i,
    t_property p,
    t_integer n,
    t_integer_data nd
LEFT JOIN t_integer_data nd2 ON
    (
        nd.integer_uuid = nd2.integer_uuid AND
        nd.version_uuid < nd2.version_uuid
    )
WHERE
    nd2.version_uuid IS NULL AND
    n.uuid = nd.integer_uuid AND
    n.instance_uuid = i.uuid AND
    n.property_uuid = p.uuid AND
    i.version_uuid = v1.uuid AND
    p.version_uuid = v2.uuid AND
    n.version_uuid = v3.uuid AND
    nd.version_uuid = v4.uuid AND
    v1.active = TRUE AND
    v2.active = TRUE AND
    v3.active = TRUE AND
    v4.active = TRUE;


-- ---------------------------------------------------------------------------
-- View v_double_value
--
-- This value view represents the latest version of every active double value 
-- record in the repository. This view should be primarily called by the 
-- generic value view rather than directly.
-- ---------------------------------------------------------------------------

CREATE VIEW v_double_value AS
SELECT
    hex(d.uuid) AS uuid,
    hex(d.instance_uuid) AS instance_uuid,
    hex(d.property_uuid) AS property_uuid,
    dd.key AS `key`,
    dd.value AS value,
    v3.created AS created
FROM
    t_version v1,
    t_version v2,
    t_version v3,
    t_version v4,
    t_instance i,
    t_property p,
    t_double d,
    t_double_data dd
LEFT JOIN t_double_data dd2 ON
    (
        dd.double_uuid = dd2.double_uuid AND
        dd.version_uuid < dd2.version_uuid
    )
WHERE
    dd2.version_uuid IS NULL AND
    d.uuid = dd.double_uuid AND
    d.instance_uuid = i.uuid AND
    d.property_uuid = p.uuid AND
    i.version_uuid = v1.uuid AND
    p.version_uuid = v2.uuid AND
    d.version_uuid = v3.uuid AND
    dd.version_uuid = v4.uuid AND
    v1.active = TRUE AND
    v2.active = TRUE AND
    v3.active = TRUE AND
    v4.active = TRUE;


-- ---------------------------------------------------------------------------
-- View v_datetime_value
--
-- This value view represents the latest version of every active datetime value 
-- record in the repository. This view should be primarily called by the 
-- generic value view rather than directly.
-- ---------------------------------------------------------------------------

CREATE VIEW v_datetime_value AS
SELECT
    hex(t.uuid) AS uuid,
    hex(t.instance_uuid) AS instance_uuid,
    hex(t.property_uuid) AS property_uuid,
    td.key AS `key`,
    td.value AS value,
    v3.created AS created
FROM
    t_version v1,
    t_version v2,
    t_version v3,
    t_version v4,
    t_instance i,
    t_property p,
    t_datetime t,
    t_datetime_data td
LEFT JOIN t_datetime_data td2 ON
    (
        td.datetime_uuid = td2.datetime_uuid AND
        td.version_uuid < td2.version_uuid
    )
WHERE
    td2.version_uuid IS NULL AND
    t.uuid = td.datetime_uuid AND
    t.instance_uuid = i.uuid AND
    t.property_uuid = p.uuid AND
    i.version_uuid = v1.uuid AND
    p.version_uuid = v2.uuid AND
    t.version_uuid = v3.uuid AND
    td.version_uuid = v4.uuid AND
    v1.active = TRUE AND
    v2.active = TRUE AND
    v3.active = TRUE AND
    v4.active = TRUE;


-- ---------------------------------------------------------------------------
-- View v_smallbinary_value
--
-- This value view represents the latest version of every active smallbinary
-- value record in the repository. This view should be primarily called by the 
-- generic value view rather than directly.
-- ---------------------------------------------------------------------------

CREATE VIEW v_smallbinary_value AS
SELECT
    hex(b.uuid) AS uuid,
    hex(b.instance_uuid) AS instance_uuid,
    hex(b.property_uuid) AS property_uuid,
    bd.key AS `key`,
    bd.value AS value,
    v3.created AS created
FROM
    t_version v1,
    t_version v2,
    t_version v3,
    t_version v4,
    t_instance i,
    t_property p,
    t_smallbinary b,
    t_smallbinary_data bd
LEFT JOIN t_smallbinary_data bd2 ON
    (
        bd.smallbinary_uuid = bd2.smallbinary_uuid AND
        bd.version_uuid < bd2.version_uuid
    )
WHERE
    bd2.version_uuid IS NULL AND
    b.uuid = bd.smallbinary_uuid AND
    b.instance_uuid = i.uuid AND
    b.property_uuid = p.uuid AND
    i.version_uuid = v1.uuid AND
    p.version_uuid = v2.uuid AND
    b.version_uuid = v3.uuid AND
    bd.version_uuid = v4.uuid AND
    v1.active = TRUE AND
    v2.active = TRUE AND
    v3.active = TRUE AND
    v4.active = TRUE;


-- ---------------------------------------------------------------------------
-- View v_mediumbinary_value
--
-- This value view represents the latest version of every active mediumbinary
-- value record in the repository. This view should be primarily called by the 
-- generic value view rather than directly.
-- ---------------------------------------------------------------------------

CREATE VIEW v_mediumbinary_value AS
SELECT
    hex(b.uuid) AS uuid,
    hex(b.instance_uuid) AS instance_uuid,
    hex(b.property_uuid) AS property_uuid,
    bd.key AS `key`,
    bd.value AS value,
    v3.created AS created
FROM
    t_version v1,
    t_version v2,
    t_version v3,
    t_version v4,
    t_instance i,
    t_property p,
    t_mediumbinary b,
    t_mediumbinary_data bd
LEFT JOIN t_mediumbinary_data bd2 ON
    (
        bd.mediumbinary_uuid = bd2.mediumbinary_uuid AND
        bd.version_uuid < bd2.version_uuid
    )
WHERE
    bd2.version_uuid IS NULL AND
    b.uuid = bd.mediumbinary_uuid AND
    b.instance_uuid = i.uuid AND
    b.property_uuid = p.uuid AND
    i.version_uuid = v1.uuid AND
    p.version_uuid = v2.uuid AND
    b.version_uuid = v3.uuid AND
    bd.version_uuid = v4.uuid AND
    v1.active = TRUE AND
    v2.active = TRUE AND
    v3.active = TRUE AND
    v4.active = TRUE;


-- ---------------------------------------------------------------------------
-- View v_longbinary_value
--
-- This value view represents the latest version of every active longbinary
-- value record in the repository. This view should be primarily called by the 
-- generic value view rather than directly.
-- ---------------------------------------------------------------------------

CREATE VIEW v_longbinary_value AS
SELECT
    hex(b.uuid) AS uuid,
    hex(b.instance_uuid) AS instance_uuid,
    hex(b.property_uuid) AS property_uuid,
    bd.key AS `key`,
    bd.value AS value,
    v3.created AS created
FROM
    t_version v1,
    t_version v2,
    t_version v3,
    t_version v4,
    t_instance i,
    t_property p,
    t_longbinary b,
    t_longbinary_data bd
LEFT JOIN t_longbinary_data bd2 ON
    (
        bd.longbinary_uuid = bd2.longbinary_uuid AND
        bd.version_uuid < bd2.version_uuid
    )
WHERE
    bd2.version_uuid IS NULL AND
    b.uuid = bd.longbinary_uuid AND
    b.instance_uuid = i.uuid AND
    b.property_uuid = p.uuid AND
    i.version_uuid = v1.uuid AND
    p.version_uuid = v2.uuid AND
    b.version_uuid = v3.uuid AND
    bd.version_uuid = v4.uuid AND
    v1.active = TRUE AND
    v2.active = TRUE AND
    v3.active = TRUE AND
    v4.active = TRUE;


-- ---------------------------------------------------------------------------
-- View v_smalltext_value
--
-- This value view represents the latest version of every active smalltext
-- value record in the repository. This view should be primarily called by the 
-- generic value view rather than directly.
-- ---------------------------------------------------------------------------

CREATE VIEW v_smalltext_value AS
SELECT
    hex(t.uuid) AS uuid,
    hex(t.instance_uuid) AS instance_uuid,
    hex(t.property_uuid) AS property_uuid,
    td.key AS `key`,
    td.value AS value,
    v3.created AS created
FROM
    t_version v1,
    t_version v2,
    t_version v3,
    t_version v4,
    t_instance i,
    t_property p,
    t_smalltext t,
    t_smalltext_data td
LEFT JOIN t_smalltext_data td2 ON
    (
        td.smalltext_uuid = td2.smalltext_uuid AND
        td.version_uuid < td2.version_uuid
    )
WHERE
    td2.version_uuid IS NULL AND
    t.uuid = td.smalltext_uuid AND
    t.instance_uuid = i.uuid AND
    t.property_uuid = p.uuid AND
    i.version_uuid = v1.uuid AND
    p.version_uuid = v2.uuid AND
    t.version_uuid = v3.uuid AND
    td.version_uuid = v4.uuid AND
    v1.active = TRUE AND
    v2.active = TRUE AND
    v3.active = TRUE AND
    v4.active = TRUE;


-- ---------------------------------------------------------------------------
-- View v_mediumtext_value
--
-- This value view represents the latest version of every active mediumtext
-- value record in the repository. This view should be primarily called by the 
-- generic value view rather than directly.
-- ---------------------------------------------------------------------------

CREATE VIEW v_mediumtext_value AS
SELECT
    hex(t.uuid) AS uuid,
    hex(t.instance_uuid) AS instance_uuid,
    hex(t.property_uuid) AS property_uuid,
    td.key AS `key`,
    td.value AS value,
    v3.created AS created
FROM
    t_version v1,
    t_version v2,
    t_version v3,
    t_version v4,
    t_instance i,
    t_property p,
    t_mediumtext t,
    t_mediumtext_data td
LEFT JOIN t_mediumtext_data td2 ON
    (
        td.mediumtext_uuid = td2.mediumtext_uuid AND
        td.version_uuid < td2.version_uuid
    )
WHERE
    td2.version_uuid IS NULL AND
    t.uuid = td.mediumtext_uuid AND
    t.instance_uuid = i.uuid AND
    t.property_uuid = p.uuid AND
    i.version_uuid = v1.uuid AND
    p.version_uuid = v2.uuid AND
    t.version_uuid = v3.uuid AND
    td.version_uuid = v4.uuid AND
    v1.active = TRUE AND
    v2.active = TRUE AND
    v3.active = TRUE AND
    v4.active = TRUE;


-- ---------------------------------------------------------------------------
-- View v_longtext_value
--
-- This value view represents the latest version of every active longtext
-- value record in the repository. This view should be primarily called by the 
-- generic value view rather than directly.
-- ---------------------------------------------------------------------------

CREATE VIEW v_longtext_value AS
SELECT
    hex(t.uuid) AS uuid,
    hex(t.instance_uuid) AS instance_uuid,
    hex(t.property_uuid) AS property_uuid,
    td.key AS `key`,
    td.value AS value,
    v3.created AS created
FROM
    t_version v1,
    t_version v2,
    t_version v3,
    t_version v4,
    t_instance i,
    t_property p,
    t_longtext t,
    t_longtext_data td
LEFT JOIN t_longtext_data td2 ON
    (
        td.longtext_uuid = td2.longtext_uuid AND
        td.version_uuid < td2.version_uuid
    )
WHERE
    td2.version_uuid IS NULL AND
    t.uuid = td.longtext_uuid AND
    t.instance_uuid = i.uuid AND
    t.property_uuid = p.uuid AND
    i.version_uuid = v1.uuid AND
    p.version_uuid = v2.uuid AND
    t.version_uuid = v3.uuid AND
    td.version_uuid = v4.uuid AND
    v1.active = TRUE AND
    v2.active = TRUE AND
    v3.active = TRUE AND
    v4.active = TRUE;


-- ---------------------------------------------------------------------------
-- View v_uberlongtext_value
--
-- This value view represents the latest version of every active uberlongtext
-- value record in the repository. This view should be primarily called by the 
-- generic value view rather than directly.
-- ---------------------------------------------------------------------------

CREATE VIEW v_uberlongtext_value AS
SELECT
    hex(t.uuid) AS uuid,
    hex(t.instance_uuid) AS instance_uuid,
    hex(t.property_uuid) AS property_uuid,
    td.key AS `key`,
    td.value AS value,
    v3.created AS created
FROM
    t_version v1,
    t_version v2,
    t_version v3,
    t_version v4,
    t_instance i,
    t_property p,
    t_uberlongtext t,
    t_uberlongtext_data td
LEFT JOIN t_uberlongtext_data td2 ON
    (
        td.uberlongtext_uuid = td2.uberlongtext_uuid AND
        td.version_uuid < td2.version_uuid
    )
WHERE
    td2.version_uuid IS NULL AND
    t.uuid = td.uberlongtext_uuid AND
    t.instance_uuid = i.uuid AND
    t.property_uuid = p.uuid AND
    i.version_uuid = v1.uuid AND
    p.version_uuid = v2.uuid AND
    t.version_uuid = v3.uuid AND
    td.version_uuid = v4.uuid AND
    v1.active = TRUE AND
    v2.active = TRUE AND
    v3.active = TRUE AND
    v4.active = TRUE;


-- ---------------------------------------------------------------------------
-- View v_model_instance_value
--
-- This value view represents the latest version of every active model 
-- instance value record in the repository. This view should be primarily 
-- called by the generic value view rather than directly.
-- ---------------------------------------------------------------------------

CREATE VIEW v_model_instance_value AS
SELECT
    hex(mi.uuid) AS uuid,
    hex(mi.instance_uuid) AS instance_uuid,
    hex(mi.property_uuid) AS property_uuid,
    mid.key AS `key`,
    mid.model_uuid AS value_model_uuid,
    mid.instance_uuid AS value_instance_uuid,
    v3.created AS created
FROM
    t_version v1,
    t_version v2,
    t_version v3,
    t_version v4,
    t_version v5,
    t_instance i,
    t_property p,
    t_model vm,
    t_model_instance mi,
    t_model_instance_data mid
LEFT JOIN t_model_instance_data mid2 ON
    (
        mid.model_instance_uuid = mid2.model_instance_uuid AND
        mid.version_uuid < mid2.version_uuid
    )
WHERE
    mid2.version_uuid IS NULL AND
    mi.uuid = mid.model_instance_uuid AND
    mi.instance_uuid = i.uuid AND
    mi.property_uuid = p.uuid AND
    mid.model_uuid = vm.uuid AND
    i.version_uuid = v1.uuid AND
    p.version_uuid = v2.uuid AND
    vm.version_uuid = v3.uuid AND
    mi.version_uuid = v4.uuid AND
    mid.version_uuid = v5.uuid AND
    v1.active = TRUE AND
    v2.active = TRUE AND
    v3.active = TRUE AND
    v4.active = TRUE AND
    v5.active = TRUE;


-- ---------------------------------------------------------------------------
-- View v_value
--
-- This value view represents the latest version of every active value record
-- in the repository. All programmed code should refer to this value view
-- INSTEAD of querying data from the t_{$type} and t_{$type}_data tables
-- directly. A value can be accessed indirectly by "instance_uuid" and 
-- "property_uuid" then directly by "key" (if being used by the program).
-- ---------------------------------------------------------------------------



-- ---------------------------------------------------------------------------
-- Return environment settings to their original state
-- ---------------------------------------------------------------------------
SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

