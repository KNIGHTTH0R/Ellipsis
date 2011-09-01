
-- -----------------------------------------------------
-- Remember environment settings
-- -----------------------------------------------------
SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';


-- -----------------------------------------------------
-- Reset schema
-- -----------------------------------------------------
DROP SCHEMA IF EXISTS repository;
CREATE SCHEMA IF NOT EXISTS repository DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE repository;


-- -----------------------------------------------------
-- Table t_version
--
-- Every repository record is associated with a unique
-- version number. Therefore records are never deleted
-- or updated, rather they are always supersceded by a
-- newer record value. A "deleted" record is simply a
-- record with two versions attached where the newer
-- version has an active value of 0.
-- -----------------------------------------------------
DROP TABLE IF EXISTS t_version;

CREATE TABLE IF NOT EXISTS t_version (
  id INT NOT NULL AUTO_INCREMENT,
  tag VARCHAR(100) NULL,
  active BIT(1) NOT NULL DEFAULT 1,
  created DATETIME NOT NULL,
  PRIMARY KEY (id))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Function INIT_UUID(UUID)
-- -----------------------------------------------------
delimiter $$
CREATE FUNCTION INIT_UUID(uuid CHAR(32))
RETURNS CHAR(32)
DETERMINISTIC
BEGIN
    DECLARE result CHAR(32);
    SET result = uuid;
    IF uuid IS NULL OR uuid = '' THEN
        SET result = UUID();
    END IF;
    RETURN result;
END$$


-- -----------------------------------------------------
-- Function NEW_VERSION()
-- -----------------------------------------------------
delimiter $$
CREATE FUNCTION NEW_VERSION()
RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE result CHAR(32);
    INSERT INTO t_version (created) VALUES (NOW());
    SET result = LAST_INSERT_ID();
    RETURN result;
END$$


-- -----------------------------------------------------
-- Table t_model
--
-- These records represent manipulatable data models.
-- These records are most likely to be represented by a
-- user defined application module (i.e. class object).
-- -----------------------------------------------------
DROP TABLE IF EXISTS t_model;

CREATE TABLE IF NOT EXISTS t_model (
  id INT NOT NULL AUTO_INCREMENT,
  uuid CHAR(32) NOT NULL,
  name VARCHAR(45) NOT NULL,
  description VARCHAR(255) NULL,
  version_id INT NOT NULL,
  PRIMARY KEY (id),
  INDEX fk_model_version (version_id ASC),
  CONSTRAINT fk_model_version
    FOREIGN KEY (version_id )
    REFERENCES t_version (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

DROP TRIGGER IF EXISTS tr_before_model_insert;

delimiter $$
CREATE TRIGGER tr_before_model_insert BEFORE INSERT ON t_model
FOR EACH ROW 
    BEGIN
        SET NEW.version_id = NEW_VERSION();
        SET NEW.uuid = INIT_UUID(NEW.uuid);
    END$$
delimiter ;

-- -----------------------------------------------------
-- Table t_instance
--
-- These records represent populated data model 
-- instances (i.e. data records).
-- -----------------------------------------------------
DROP TABLE IF EXISTS t_instance;

CREATE  TABLE IF NOT EXISTS t_instance (
  id INT NOT NULL AUTO_INCREMENT,
  uuid CHAR(32) NOT NULL,
  model_id INT NOT NULL,
  version_id INT NOT NULL,
  PRIMARY KEY (id),
  INDEX fk_instance_version (version_id ASC),
  INDEX fk_instance_model (model_id ASC),
  CONSTRAINT fk_instance_version
    FOREIGN KEY (version_id )
    REFERENCES t_version (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_instance_model
    FOREIGN KEY (model_id )
    REFERENCES t_model (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

DROP TRIGGER IF EXISTS tr_before_instance_insert;

delimiter $$
CREATE TRIGGER tr_before_instance_insert BEFORE INSERT ON t_instance
FOR EACH ROW 
    BEGIN
        SET NEW.version_id = NEW_VERSION();
        SET NEW.uuid = INIT_UUID(NEW.uuid);
    END$$
delimiter ;


-- -----------------------------------------------------
-- Table t_property
--
-- These records represent the individual properties
-- that identify the types of data being stored for a
-- particular data model. These typically correlate to
-- application module properties (i.e. properties in a
-- class object).
-- -----------------------------------------------------
DROP TABLE IF EXISTS t_property ;

CREATE  TABLE IF NOT EXISTS t_property (
  id INT NOT NULL AUTO_INCREMENT,
  uuid CHAR(32) NOT NULL,
  name VARCHAR(45) NOT NULL,
  description VARCHAR(255) NULL,
  type ENUM('boolean','integer','double','datetime','binary','ascii','instance') NOT NULL,
  list BIT(1) NOT NULL DEFAULT 0,
  instance_model_id INT NULL,
  model_id INT NOT NULL,
  version_id INT NOT NULL,
  PRIMARY KEY (id),
  INDEX fk_property_instance_model (instance_model_id ASC),
  INDEX fk_property_version (version_id ASC),
  INDEX fk_property_model (model_id ASC),
  CONSTRAINT fk_property_instance_model
    FOREIGN KEY (instance_model_id )
    REFERENCES t_model (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_property_model
    FOREIGN KEY (model_id )
    REFERENCES t_model (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_property_version
    FOREIGN KEY (version_id )
    REFERENCES t_version (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

DROP TRIGGER IF EXISTS tr_before_property_insert;

delimiter $$
CREATE TRIGGER tr_before_property_insert BEFORE INSERT ON t_property
FOR EACH ROW 
    BEGIN
        SET NEW.version_id = NEW_VERSION();
        SET NEW.uuid = INIT_UUID(NEW.uuid);
    END$$
delimiter ;


-- -----------------------------------------------------
-- Table t_list
--
-- These records represent property values that exist as
-- a list of values rather than as an individual value.
-- -----------------------------------------------------
DROP TABLE IF EXISTS t_list ;

CREATE  TABLE IF NOT EXISTS t_list (
  id INT NOT NULL AUTO_INCREMENT,
  uuid CHAR(32) NOT NULL,
  property_id INT NOT NULL,
  instance_id INT NOT NULL,
  version_id INT NOT NULL,
  PRIMARY KEY (id),
  INDEX fk_list_property (property_id ASC),
  INDEX fk_list_instance (instance_id ASC),
  INDEX fk_list_version (version_id ASC),
  CONSTRAINT fk_list_property
    FOREIGN KEY (property_id )
    REFERENCES t_property (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_list_instance
    FOREIGN KEY (instance_id )
    REFERENCES t_instance (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_list_version
    FOREIGN KEY (version_id )
    REFERENCES t_version (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

DROP TRIGGER IF EXISTS tr_before_list_insert;

delimiter $$
CREATE TRIGGER tr_before_list_insert BEFORE INSERT ON t_list
FOR EACH ROW 
    BEGIN
        SET NEW.version_id = NEW_VERSION();
        SET NEW.uuid = INIT_UUID(NEW.uuid);
    END$$
delimiter ;


-- -----------------------------------------------------
-- Table t_boolean_value
--
-- These records represent all property values of the
-- "boolean" field type.
-- -----------------------------------------------------
DROP TABLE IF EXISTS t_boolean_value ;

CREATE  TABLE IF NOT EXISTS t_boolean_value (
  id INT NOT NULL AUTO_INCREMENT,
  uuid CHAR(32) NOT NULL,
  value BIT(1) NOT NULL,
  property_id INT NULL,
  list_id INT NULL,
  instance_id INT NOT NULL,
  version_id INT NOT NULL,
  PRIMARY KEY (id),
  INDEX fk_boolean_value_property (property_id ASC),
  INDEX fk_boolean_value_list (list_id ASC),
  INDEX fk_boolean_value_instance (instance_id ASC),
  INDEX fk_boolean_value_version (version_id ASC),
  CONSTRAINT fk_boolean_value_property
    FOREIGN KEY (property_id )
    REFERENCES t_property (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_boolean_value_list
    FOREIGN KEY (list_id )
    REFERENCES t_list (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_boolean_value_instance
    FOREIGN KEY (instance_id )
    REFERENCES t_instance (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_boolean_value_version
    FOREIGN KEY (version_id )
    REFERENCES t_version (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

DROP TRIGGER IF EXISTS tr_before_boolean_value_insert;

delimiter $$
CREATE TRIGGER tr_before_boolean_value_insert BEFORE INSERT ON t_boolean_value
FOR EACH ROW 
    BEGIN
        SET NEW.version_id = NEW_VERSION();
        SET NEW.uuid = INIT_UUID(NEW.uuid);
    END$$
delimiter ;


-- -----------------------------------------------------
-- Table t_integer_value
--
-- These records represent all property values of the
-- "integer" field type.
-- -----------------------------------------------------
DROP TABLE IF EXISTS t_integer_value ;

CREATE  TABLE IF NOT EXISTS t_integer_value (
  id INT NOT NULL,
  uuid CHAR(32) NOT NULL,
  value INT NOT NULL DEFAULT 0,
  property_id INT NULL,
  list_id INT NULL,
  instance_id INT NOT NULL,
  version_id INT NOT NULL,
  PRIMARY KEY (id),
  INDEX fk_integer_value_property (property_id ASC),
  INDEX fk_integer_value_list (list_id ASC),
  INDEX fk_integer_value_instance (instance_id ASC),
  INDEX fk_integer_value_version (version_id ASC),
  CONSTRAINT fk_integer_value_property
    FOREIGN KEY (property_id )
    REFERENCES t_property (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_integer_value_list
    FOREIGN KEY (list_id )
    REFERENCES t_list (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_integer_value_instance
    FOREIGN KEY (instance_id )
    REFERENCES t_instance (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_integer_value_version
    FOREIGN KEY (version_id )
    REFERENCES t_version (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

DROP TRIGGER IF EXISTS tr_before_integer_value_insert;

delimiter $$
CREATE TRIGGER tr_before_integer_value_insert BEFORE INSERT ON t_integer_value
FOR EACH ROW 
    BEGIN
        SET NEW.version_id = NEW_VERSION();
        SET NEW.uuid = INIT_UUID(NEW.uuid);
    END$$
delimiter ;

-- -----------------------------------------------------
-- Table t_double_value
--
-- These records represent all property values of the
-- "double" field type.
-- -----------------------------------------------------
DROP TABLE IF EXISTS t_double_value ;

CREATE TABLE IF NOT EXISTS t_double_value (
  id INT NOT NULL AUTO_INCREMENT,
  uuid CHAR(32) NOT NULL,
  value DOUBLE NOT NULL DEFAULT 0.0,
  property_id INT NULL,
  list_id INT NULL,
  instance_id INT NOT NULL,
  version_id INT NOT NULL,
  PRIMARY KEY (id),
  INDEX fk_double_value_property (property_id ASC),
  INDEX fk_double_value_list (list_id ASC),
  INDEX fk_double_value_instance (instance_id ASC),
  INDEX fk_double_value_version (version_id ASC),
  CONSTRAINT fk_double_value_property
    FOREIGN KEY (property_id )
    REFERENCES t_property (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_double_value_list
    FOREIGN KEY (list_id )
    REFERENCES t_list (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_double_value_instance
    FOREIGN KEY (instance_id )
    REFERENCES t_instance (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_double_value_version
    FOREIGN KEY (version_id )
    REFERENCES t_version (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

DROP TRIGGER IF EXISTS tr_before_double_value_insert;

delimiter $$
CREATE TRIGGER tr_before_double_value_insert BEFORE INSERT ON t_double_value
FOR EACH ROW 
    BEGIN
        SET NEW.version_id = NEW_VERSION();
        SET NEW.uuid = INIT_UUID(NEW.uuid);
    END$$
delimiter ;


-- -----------------------------------------------------
-- Table t_datetime_value
--
-- These records represent all property values of the
-- "datetime" field type.
-- -----------------------------------------------------
DROP TABLE IF EXISTS t_datetime_value ;

CREATE  TABLE IF NOT EXISTS t_datetime_value (
  id INT NOT NULL AUTO_INCREMENT,
  uuid CHAR(32) NOT NULL,
  value DATETIME NULL,
  property_id INT NULL,
  list_id INT NULL,
  instance_id INT NOT NULL,
  version_id INT NOT NULL,
  PRIMARY KEY (id),
  INDEX fk_datetime_value_property (property_id ASC),
  INDEX fk_datetime_value_list (list_id ASC),
  INDEX fk_datetime_value_instance (instance_id ASC),
  INDEX fk_datetime_value_version (version_id ASC),
  CONSTRAINT fk_datetime_value_property
    FOREIGN KEY (property_id )
    REFERENCES t_property (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_datetime_value_list
    FOREIGN KEY (list_id )
    REFERENCES t_list (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_datetime_value_instance
    FOREIGN KEY (instance_id )
    REFERENCES t_instance (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_datetime_value_version
    FOREIGN KEY (version_id )
    REFERENCES t_version (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

DROP TRIGGER IF EXISTS tr_before_datetime_value_insert;

delimiter $$
CREATE TRIGGER tr_before_datetime_value_insert BEFORE INSERT ON t_datetime_value
FOR EACH ROW 
    BEGIN
        SET NEW.version_id = NEW_VERSION();
        SET NEW.uuid = INIT_UUID(NEW.uuid);
    END$$
delimiter ;


-- -----------------------------------------------------
-- Table t_binary_value
--
-- These records represent all property values of the
-- "binary" field type.
-- -----------------------------------------------------
DROP TABLE IF EXISTS t_binary_value ;

CREATE  TABLE IF NOT EXISTS t_binary_value (
  id INT NOT NULL AUTO_INCREMENT,
  uuid CHAR(32) NOT NULL,
  value LONGBLOB NULL,
  property_id INT NULL,
  list_id INT NULL,
  instance_id INT NOT NULL,
  version_id INT NOT NULL,
  PRIMARY KEY (id),
  INDEX fk_binary_value_property (property_id ASC),
  INDEX fk_binary_value_list (list_id ASC),
  INDEX fk_binary_value_instance (instance_id ASC),
  INDEX fk_binary_value_version (version_id ASC),
  CONSTRAINT fk_binary_value_property
    FOREIGN KEY (property_id )
    REFERENCES t_property (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_binary_value_list
    FOREIGN KEY (list_id )
    REFERENCES t_list (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_binary_value_instance
    FOREIGN KEY (instance_id )
    REFERENCES t_instance (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_binary_value_version
    FOREIGN KEY (version_id )
    REFERENCES t_version (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

DROP TRIGGER IF EXISTS tr_before_binary_value_insert;

delimiter $$
CREATE TRIGGER tr_before_binary_value_insert BEFORE INSERT ON .t_binary_value
FOR EACH ROW 
    BEGIN
        SET NEW.version_id = NEW_VERSION();
        SET NEW.uuid = INIT_UUID(NEW.uuid);
    END$$
delimiter ;


-- -----------------------------------------------------
-- Table t_smalltext_value
--
-- These records represent all property values of the
-- "ascii" field type measuring less than or equal to
-- 1000 characters in length.
-- -----------------------------------------------------
DROP TABLE IF EXISTS t_smalltext_value ;

CREATE  TABLE IF NOT EXISTS t_smalltext_value (
  id INT NOT NULL AUTO_INCREMENT,
  uuid CHAR(32) NOT NULL,
  value VARCHAR(1000) NULL,
  property_id INT NULL,
  list_id INT NULL,
  instance_id INT NOT NULL,
  version_id INT NOT NULL,
  PRIMARY KEY (id),
  INDEX fk_smalltext_value_property (property_id ASC),
  INDEX fk_smalltext_value_list (list_id ASC),
  INDEX fk_smalltext_value_instance (instance_id ASC),
  INDEX fk_smalltext_value_version (version_id ASC),
  CONSTRAINT fk_smalltext_value_property
    FOREIGN KEY (property_id )
    REFERENCES t_property (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_smalltext_value_list
    FOREIGN KEY (list_id )
    REFERENCES t_list (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_smalltext_value_instance
    FOREIGN KEY (instance_id )
    REFERENCES t_instance (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_smalltext_value_version
    FOREIGN KEY (version_id )
    REFERENCES t_version (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

DROP TRIGGER IF EXISTS tr_before_smalltext_value_insert;

delimiter $$
CREATE TRIGGER tr_before_smalltext_value_insert BEFORE INSERT ON t_smalltext_value
FOR EACH ROW 
    BEGIN
        SET NEW.version_id = NEW_VERSION();
        SET NEW.uuid = INIT_UUID(NEW.uuid);
    END$$
delimiter ;


-- -----------------------------------------------------
-- Table t_mediumtext_value
--
-- These records represent all property values of the
-- "ascii" field type measuring between 1001 and 4000
-- characters in length.
-- -----------------------------------------------------
DROP TABLE IF EXISTS t_mediumtext_value ;

CREATE  TABLE IF NOT EXISTS t_mediumtext_value (
  id INT NOT NULL AUTO_INCREMENT,
  uuid CHAR(32) NOT NULL,
  value VARCHAR(4000) NULL,
  property_id INT NULL,
  list_id INT NULL,
  instance_id INT NOT NULL,
  version_id INT NOT NULL,
  PRIMARY KEY (id),
  INDEX fk_mediumtext_value_property (property_id ASC),
  INDEX fk_mediumtext_value_list (list_id ASC),
  INDEX fk_mediumtext_value_instance (instance_id ASC),
  INDEX fk_mediumtext_value_version (version_id ASC),
  CONSTRAINT fk_mediumtext_value_property
    FOREIGN KEY (property_id )
    REFERENCES t_property (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_mediumtext_value_list
    FOREIGN KEY (list_id )
    REFERENCES t_list (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_mediumtext_value_instance
    FOREIGN KEY (instance_id )
    REFERENCES t_instance (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_mediumtext_value_version
    FOREIGN KEY (version_id )
    REFERENCES t_version (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

DROP TRIGGER IF EXISTS tr_before_mediumtext_value_insert;

delimiter $$
CREATE TRIGGER tr_before_mediumtext_value_insert BEFORE INSERT ON t_mediumtext_value
FOR EACH ROW 
    BEGIN
        SET NEW.version_id = NEW_VERSION();
        SET NEW.uuid = INIT_UUID(NEW.uuid);
    END$$
delimiter ;


-- -----------------------------------------------------
-- Table t_longtext_value
--
-- These records represent all property values of the
-- "ascii" field type measuring greater than 4000 
-- characters in length.
-- -----------------------------------------------------
DROP TABLE IF EXISTS t_longtext_value ;

CREATE  TABLE IF NOT EXISTS t_longtext_value (
  id INT NOT NULL AUTO_INCREMENT,
  uuid CHAR(32) NOT NULL,
  value LONGTEXT NULL,
  property_id INT NULL,
  list_id INT NULL,
  instance_id INT NOT NULL,
  version_id INT NOT NULL,
  PRIMARY KEY (id),
  INDEX fk_longtext_value_property (property_id ASC),
  INDEX fk_longtext_value_list (list_id ASC),
  INDEX fk_longtext_value_instance (instance_id ASC),
  INDEX fk_longtext_value_version (version_id ASC),
  CONSTRAINT fk_longtext_value_property
    FOREIGN KEY (property_id )
    REFERENCES t_property (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_longtext_value_list
    FOREIGN KEY (list_id )
    REFERENCES t_list (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_longtext_value_instance
    FOREIGN KEY (instance_id )
    REFERENCES t_instance (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_longtext_value_version
    FOREIGN KEY (version_id )
    REFERENCES t_version (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

DROP TRIGGER IF EXISTS tr_before_longtext_value_insert;

delimiter $$
CREATE TRIGGER tr_before_longtext_value_insert BEFORE INSERT ON t_longtext_value
FOR EACH ROW 
    BEGIN
        SET NEW.version_id = NEW_VERSION();
        SET NEW.uuid = INIT_UUID(NEW.uuid);
    END$$
delimiter ;


-- -----------------------------------------------------
-- Table t_instance_value
--
-- These records represent all property values of the
-- "instance" field type. This value type is speical
-- because it replaces the requirement for one-to-many 
-- data model associations.
-- -----------------------------------------------------
DROP TABLE IF EXISTS t_instance_value ;

CREATE  TABLE IF NOT EXISTS t_instance_value (
  id INT NOT NULL AUTO_INCREMENT,
  uuid CHAR(32) NOT NULL,
  value_instance_id INT NOT NULL,
  property_id INT NULL,
  list_id INT NULL,
  instance_id INT NOT NULL,
  version_id INT NOT NULL,
  PRIMARY KEY (id),
  INDEX fk_instance_value_value_instance (value_instance_id ASC),
  INDEX fk_instance_value_property (property_id ASC),
  INDEX fk_instance_value_list (list_id ASC),
  INDEX fk_instance_value_instance (instance_id ASC),
  INDEX fk_instance_value_version (version_id ASC),
  CONSTRAINT fk_instance_value_value_instance
    FOREIGN KEY (value_instance_id )
    REFERENCES t_instance (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_instance_value_property
    FOREIGN KEY (property_id )
    REFERENCES t_property (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_instance_value_list
    FOREIGN KEY (list_id )
    REFERENCES t_list (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_instance_value_instance
    FOREIGN KEY (instance_id )
    REFERENCES t_instance (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT fk_instance_value_version
    FOREIGN KEY (version_id )
    REFERENCES t_version (id )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

DROP TRIGGER IF EXISTS tr_before_instance_value_insert;

delimiter $$
CREATE TRIGGER tr_before_instance_value_insert BEFORE INSERT ON t_instance_value
FOR EACH ROW 
    BEGIN
        SET NEW.version_id = NEW_VERSION();
        SET NEW.uuid = INIT_UUID(NEW.uuid);
    END$$
delimiter ;


-- -----------------------------------------------------
-- View v_model
-- -----------------------------------------------------
DROP VIEW IF EXISTS v_model;

CREATE VIEW v_model AS
SELECT
  m.uuid AS uuid,
  m.id AS id,
  m.name AS name,
  m.description AS description,
  m.version_id AS version_id
FROM
  t_model AS m,
  t_version AS v
WHERE
  m.version_id = v.id AND
  v.active = true
GROUP BY
  m.uuid;


-- -----------------------------------------------------
-- Replace remembered environment settings
-- -----------------------------------------------------
SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

