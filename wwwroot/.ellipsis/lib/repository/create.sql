
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
    created TIMESTAMP NOT NULL,
    PRIMARY KEY (id))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Function INIT_UUID(UUID)
-- -----------------------------------------------------
delimiter ~
CREATE FUNCTION INIT_UUID(uuid CHAR(36))
RETURNS CHAR(36)
DETERMINISTIC
BEGIN
    DECLARE result CHAR(36);
    SET result = uuid;
    IF uuid IS NULL OR uuid = '' THEN
        SET result = UUID();
    END IF;
    RETURN result;
END~
delimiter ;


-- -----------------------------------------------------
-- Function NEW_VERSION()
-- -----------------------------------------------------
delimiter ~
CREATE FUNCTION NEW_VERSION()
RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE result CHAR(36);
    INSERT INTO t_version (tag) VALUES ('');
    SET result = LAST_INSERT_ID();
    RETURN result;
END~
delimiter ;


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
    uuid CHAR(36) NOT NULL,
    name VARCHAR(45) NOT NULL,
    description VARCHAR(255) NULL,
    version_id INT NOT NULL,
    PRIMARY KEY (id),
    INDEX fk_model_version (version_id ASC),
    CONSTRAINT fk_model_version
        FOREIGN KEY (version_id)
        REFERENCES t_version (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION
)
ENGINE = InnoDB;

DROP TRIGGER IF EXISTS tr_before_model_insert;

delimiter ~
CREATE TRIGGER tr_before_model_insert BEFORE INSERT ON t_model
FOR EACH ROW 
    BEGIN
        SET NEW.version_id = NEW_VERSION();
        SET NEW.uuid = INIT_UUID(NEW.uuid);
    END~
delimiter ;

-- -----------------------------------------------------
-- Table t_instance
--
-- These records represent populated data model 
-- instances (i.e. data records).
-- -----------------------------------------------------
DROP TABLE IF EXISTS t_instance;

CREATE TABLE IF NOT EXISTS t_instance (
    id INT NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    model_id INT NOT NULL,
    version_id INT NOT NULL,
    PRIMARY KEY (id),
    INDEX fk_instance_version (version_id ASC),
    INDEX fk_instance_model (model_id ASC),
    CONSTRAINT fk_instance_version
        FOREIGN KEY (version_id)
        REFERENCES t_version (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT fk_instance_model
        FOREIGN KEY (model_id)
        REFERENCES t_model (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION
)
ENGINE = InnoDB;

DROP TRIGGER IF EXISTS tr_before_instance_insert;

delimiter ~
CREATE TRIGGER tr_before_instance_insert BEFORE INSERT ON t_instance
FOR EACH ROW 
    BEGIN
        SET NEW.version_id = NEW_VERSION();
        SET NEW.uuid = INIT_UUID(NEW.uuid);
    END~
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
DROP TABLE IF EXISTS t_property;

CREATE TABLE IF NOT EXISTS t_property (
    id INT NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
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
        FOREIGN KEY (instance_model_id)
        REFERENCES t_model (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT fk_property_model
        FOREIGN KEY (model_id)
        REFERENCES t_model (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT fk_property_version
        FOREIGN KEY (version_id)
        REFERENCES t_version (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION
)
ENGINE = InnoDB;

DROP TRIGGER IF EXISTS tr_before_property_insert;

delimiter ~
CREATE TRIGGER tr_before_property_insert BEFORE INSERT ON t_property
FOR EACH ROW 
    BEGIN
        SET NEW.version_id = NEW_VERSION();
        SET NEW.uuid = INIT_UUID(NEW.uuid);
    END~
delimiter ;


-- -----------------------------------------------------
-- Table t_list
--
-- These records represent property values that exist as
-- a list of values rather than as an individual value.
-- -----------------------------------------------------
DROP TABLE IF EXISTS t_list;

CREATE TABLE IF NOT EXISTS t_list (
    id INT NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    property_id INT NOT NULL,
    instance_id INT NOT NULL,
    version_id INT NOT NULL,
    PRIMARY KEY (id),
    INDEX fk_list_property (property_id ASC),
    INDEX fk_list_instance (instance_id ASC),
    INDEX fk_list_version (version_id ASC),
    CONSTRAINT fk_list_property
        FOREIGN KEY (property_id)
        REFERENCES t_property (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT fk_list_instance
        FOREIGN KEY (instance_id)
        REFERENCES t_instance (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT fk_list_version
        FOREIGN KEY (version_id)
        REFERENCES t_version (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION
    )
ENGINE = InnoDB;

DROP TRIGGER IF EXISTS tr_before_list_insert;

delimiter ~
CREATE TRIGGER tr_before_list_insert BEFORE INSERT ON t_list
FOR EACH ROW 
    BEGIN
        SET NEW.version_id = NEW_VERSION();
        SET NEW.uuid = INIT_UUID(NEW.uuid);
    END~
delimiter ;


-- -----------------------------------------------------
-- Table t_value_boolean
--
-- These records represent all property values of the
-- "boolean" field type.
-- -----------------------------------------------------
DROP TABLE IF EXISTS t_value_boolean;

CREATE TABLE IF NOT EXISTS t_value_boolean (
    id INT NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    value BIT(1) NOT NULL,
    property_id INT NULL,
    list_id INT NULL,
    instance_id INT NOT NULL,
    version_id INT NOT NULL,
    PRIMARY KEY (id),
    INDEX fk_value_boolean_property (property_id ASC),
    INDEX fk_value_boolean_list (list_id ASC),
    INDEX fk_value_boolean_instance (instance_id ASC),
    INDEX fk_value_boolean_version (version_id ASC),
    CONSTRAINT fk_value_boolean_property
        FOREIGN KEY (property_id)
        REFERENCES t_property (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT fk_value_boolean_list
        FOREIGN KEY (list_id)
        REFERENCES t_list (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT fk_value_boolean_instance
        FOREIGN KEY (instance_id)
        REFERENCES t_instance (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT fk_value_boolean_version
        FOREIGN KEY (version_id)
        REFERENCES t_version (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION
)
ENGINE = InnoDB;

DROP TRIGGER IF EXISTS tr_before_value_boolean_insert;

delimiter ~
CREATE TRIGGER tr_before_value_boolean_insert BEFORE INSERT ON t_value_boolean
FOR EACH ROW 
    BEGIN
        SET NEW.version_id = NEW_VERSION();
        SET NEW.uuid = INIT_UUID(NEW.uuid);
    END~
delimiter ;


-- -----------------------------------------------------
-- Table t_value_integer
--
-- These records represent all property values of the
-- "integer" field type.
-- -----------------------------------------------------
DROP TABLE IF EXISTS t_value_integer;

CREATE TABLE IF NOT EXISTS t_value_integer (
    id INT NOT NULL,
    uuid CHAR(36) NOT NULL,
    value INT NOT NULL DEFAULT 0,
    property_id INT NULL,
    list_id INT NULL,
    instance_id INT NOT NULL,
    version_id INT NOT NULL,
    PRIMARY KEY (id),
    INDEX fk_value_integer_property (property_id ASC),
    INDEX fk_value_integer_list (list_id ASC),
    INDEX fk_value_integer_instance (instance_id ASC),
    INDEX fk_value_integer_version (version_id ASC),
    CONSTRAINT fk_value_integer_property
        FOREIGN KEY (property_id)
        REFERENCES t_property (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT fk_value_integer_list
        FOREIGN KEY (list_id)
        REFERENCES t_list (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT fk_value_integer_instance
        FOREIGN KEY (instance_id)
        REFERENCES t_instance (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT fk_value_integer_version
        FOREIGN KEY (version_id)
        REFERENCES t_version (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION
)
ENGINE = InnoDB;

DROP TRIGGER IF EXISTS tr_before_value_integer_insert;

delimiter ~
CREATE TRIGGER tr_before_value_integer_insert BEFORE INSERT ON t_value_integer
FOR EACH ROW 
    BEGIN
        SET NEW.version_id = NEW_VERSION();
        SET NEW.uuid = INIT_UUID(NEW.uuid);
    END~
delimiter ;

-- -----------------------------------------------------
-- Table t_value_double
--
-- These records represent all property values of the
-- "double" field type.
-- -----------------------------------------------------
DROP TABLE IF EXISTS t_value_double;

CREATE TABLE IF NOT EXISTS t_value_double (
    id INT NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    value DOUBLE NOT NULL DEFAULT 0.0,
    property_id INT NULL,
    list_id INT NULL,
    instance_id INT NOT NULL,
    version_id INT NOT NULL,
    PRIMARY KEY (id),
    INDEX fk_value_double_property (property_id ASC),
    INDEX fk_value_double_list (list_id ASC),
    INDEX fk_value_double_instance (instance_id ASC),
    INDEX fk_value_double_version (version_id ASC),
    CONSTRAINT fk_value_double_property
        FOREIGN KEY (property_id)
        REFERENCES t_property (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT fk_value_double_list
        FOREIGN KEY (list_id)
        REFERENCES t_list (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT fk_value_double_instance
        FOREIGN KEY (instance_id)
        REFERENCES t_instance (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT fk_value_double_version
        FOREIGN KEY (version_id)
        REFERENCES t_version (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION
)
ENGINE = InnoDB;

DROP TRIGGER IF EXISTS tr_before_value_double_insert;

delimiter ~
CREATE TRIGGER tr_before_value_double_insert BEFORE INSERT ON t_value_double
FOR EACH ROW 
    BEGIN
        SET NEW.version_id = NEW_VERSION();
        SET NEW.uuid = INIT_UUID(NEW.uuid);
    END~
delimiter ;


-- -----------------------------------------------------
-- Table t_value_datetime
--
-- These records represent all property values of the
-- "datetime" field type.
-- -----------------------------------------------------
DROP TABLE IF EXISTS t_value_datetime;

CREATE TABLE IF NOT EXISTS t_value_datetime (
    id INT NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    value INT NULL,
    property_id INT NULL,
    list_id INT NULL,
    instance_id INT NOT NULL,
    version_id INT NOT NULL,
    PRIMARY KEY (id),
    INDEX fk_value_datetime_property (property_id ASC),
    INDEX fk_value_datetime_list (list_id ASC),
    INDEX fk_value_datetime_instance (instance_id ASC),
    INDEX fk_value_datetime_version (version_id ASC),
    CONSTRAINT fk_value_datetime_property
        FOREIGN KEY (property_id)
        REFERENCES t_property (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT fk_value_datetime_list
        FOREIGN KEY (list_id)
        REFERENCES t_list (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT fk_value_datetime_instance
        FOREIGN KEY (instance_id)
        REFERENCES t_instance (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT fk_value_datetime_version
        FOREIGN KEY (version_id)
        REFERENCES t_version (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION
)
ENGINE = InnoDB;

DROP TRIGGER IF EXISTS tr_before_value_datetime_insert;

delimiter ~
CREATE TRIGGER tr_before_value_datetime_insert BEFORE INSERT ON t_value_datetime
FOR EACH ROW 
    BEGIN
        SET NEW.version_id = NEW_VERSION();
        SET NEW.uuid = INIT_UUID(NEW.uuid);
    END~
delimiter ;


-- -----------------------------------------------------
-- Table t_value_binary
--
-- These records represent all property values of the
-- "binary" field type.
-- -----------------------------------------------------
DROP TABLE IF EXISTS t_value_binary;

CREATE TABLE IF NOT EXISTS t_value_binary (
    id INT NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    value LONGBLOB NULL,
    property_id INT NULL,
    list_id INT NULL,
    instance_id INT NOT NULL,
    version_id INT NOT NULL,
    PRIMARY KEY (id),
    INDEX fk_value_binary_property (property_id ASC),
    INDEX fk_value_binary_list (list_id ASC),
    INDEX fk_value_binary_instance (instance_id ASC),
    INDEX fk_value_binary_version (version_id ASC),
    CONSTRAINT fk_value_binary_property
        FOREIGN KEY (property_id)
        REFERENCES t_property (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT fk_value_binary_list
        FOREIGN KEY (list_id)
        REFERENCES t_list (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT fk_value_binary_instance
        FOREIGN KEY (instance_id)
        REFERENCES t_instance (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT fk_value_binary_version
        FOREIGN KEY (version_id)
        REFERENCES t_version (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION
)
ENGINE = InnoDB;

DROP TRIGGER IF EXISTS tr_before_value_binary_insert;

delimiter ~
CREATE TRIGGER tr_before_value_binary_insert BEFORE INSERT ON .t_value_binary
FOR EACH ROW 
    BEGIN
        SET NEW.version_id = NEW_VERSION();
        SET NEW.uuid = INIT_UUID(NEW.uuid);
    END~
delimiter ;


-- -----------------------------------------------------
-- Table t_value_smalltext
--
-- These records represent all property values of the
-- "ascii" field type measuring less than or equal to
-- 1000 characters in length.
-- -----------------------------------------------------
DROP TABLE IF EXISTS t_value_smalltext;

CREATE TABLE IF NOT EXISTS t_value_smalltext (
    id INT NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    value VARCHAR(1000) NULL,
    property_id INT NULL,
    list_id INT NULL,
    instance_id INT NOT NULL,
    version_id INT NOT NULL,
    PRIMARY KEY (id),
    INDEX fk_value_smalltext_property (property_id ASC),
    INDEX fk_value_smalltext_list (list_id ASC),
    INDEX fk_value_smalltext_instance (instance_id ASC),
    INDEX fk_value_smalltext_version (version_id ASC),
    CONSTRAINT fk_value_smalltext_property
        FOREIGN KEY (property_id)
        REFERENCES t_property (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT fk_value_smalltext_list
        FOREIGN KEY (list_id)
        REFERENCES t_list (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT fk_value_smalltext_instance
        FOREIGN KEY (instance_id)
        REFERENCES t_instance (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT fk_value_smalltext_version
        FOREIGN KEY (version_id)
        REFERENCES t_version (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION
)
ENGINE = InnoDB;

DROP TRIGGER IF EXISTS tr_before_value_smalltext_insert;

delimiter ~
CREATE TRIGGER tr_before_value_smalltext_insert BEFORE INSERT ON t_value_smalltext
FOR EACH ROW 
    BEGIN
        SET NEW.version_id = NEW_VERSION();
        SET NEW.uuid = INIT_UUID(NEW.uuid);
    END~
delimiter ;


-- -----------------------------------------------------
-- Table t_value_mediumtext
--
-- These records represent all property values of the
-- "ascii" field type measuring between 1001 and 4000
-- characters in length.
-- -----------------------------------------------------
DROP TABLE IF EXISTS t_value_mediumtext;

CREATE TABLE IF NOT EXISTS t_value_mediumtext (
    id INT NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    value VARCHAR(4000) NULL,
    property_id INT NULL,
    list_id INT NULL,
    instance_id INT NOT NULL,
    version_id INT NOT NULL,
    PRIMARY KEY (id),
    INDEX fk_value_mediumtext_property (property_id ASC),
    INDEX fk_value_mediumtext_list (list_id ASC),
    INDEX fk_value_mediumtext_instance (instance_id ASC),
    INDEX fk_value_mediumtext_version (version_id ASC),
    CONSTRAINT fk_value_mediumtext_property
        FOREIGN KEY (property_id)
        REFERENCES t_property (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT fk_value_mediumtext_list
        FOREIGN KEY (list_id)
        REFERENCES t_list (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT fk_value_mediumtext_instance
        FOREIGN KEY (instance_id)
        REFERENCES t_instance (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT fk_value_mediumtext_version
        FOREIGN KEY (version_id)
        REFERENCES t_version (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION
)
ENGINE = InnoDB;

DROP TRIGGER IF EXISTS tr_before_value_mediumtext_insert;

delimiter ~
CREATE TRIGGER tr_before_value_mediumtext_insert BEFORE INSERT ON t_value_mediumtext
FOR EACH ROW 
    BEGIN
        SET NEW.version_id = NEW_VERSION();
        SET NEW.uuid = INIT_UUID(NEW.uuid);
    END~
delimiter ;


-- -----------------------------------------------------
-- Table t_value_longtext
--
-- These records represent all property values of the
-- "ascii" field type measuring greater than 4000 
-- characters in length.
-- -----------------------------------------------------
DROP TABLE IF EXISTS t_value_longtext;

CREATE TABLE IF NOT EXISTS t_value_longtext (
    id INT NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    value LONGTEXT NULL,
    property_id INT NULL,
    list_id INT NULL,
    instance_id INT NOT NULL,
    version_id INT NOT NULL,
    PRIMARY KEY (id),
    INDEX fk_value_longtext_property (property_id ASC),
    INDEX fk_value_longtext_list (list_id ASC),
    INDEX fk_value_longtext_instance (instance_id ASC),
    INDEX fk_value_longtext_version (version_id ASC),
    CONSTRAINT fk_value_longtext_property
        FOREIGN KEY (property_id)
        REFERENCES t_property (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT fk_value_longtext_list
        FOREIGN KEY (list_id)
        REFERENCES t_list (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT fk_value_longtext_instance
        FOREIGN KEY (instance_id)
        REFERENCES t_instance (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT fk_value_longtext_version
        FOREIGN KEY (version_id)
        REFERENCES t_version (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION
)
ENGINE = InnoDB;

DROP TRIGGER IF EXISTS tr_before_value_longtext_insert;

delimiter ~
CREATE TRIGGER tr_before_value_longtext_insert BEFORE INSERT ON t_value_longtext
FOR EACH ROW 
    BEGIN
        SET NEW.version_id = NEW_VERSION();
        SET NEW.uuid = INIT_UUID(NEW.uuid);
    END~
delimiter ;


-- -----------------------------------------------------
-- Table t_value_instance
--
-- These records represent all property values of the
-- "instance" field type. This value type is speical
-- because it replaces the requirement for one-to-many 
-- data model associations.
-- -----------------------------------------------------
DROP TABLE IF EXISTS t_value_instance;

CREATE TABLE IF NOT EXISTS t_value_instance (
    id INT NOT NULL AUTO_INCREMENT,
    uuid CHAR(36) NOT NULL,
    value INT NOT NULL,
    property_id INT NULL,
    list_id INT NULL,
    instance_id INT NOT NULL,
    version_id INT NOT NULL,
    PRIMARY KEY (id),
    INDEX fk_value_instance_value (value ASC),
    INDEX fk_value_instance_property (property_id ASC),
    INDEX fk_value_instance_list (list_id ASC),
    INDEX fk_value_instance_instance (instance_id ASC),
    INDEX fk_value_instance_version (version_id ASC),
    CONSTRAINT fk_value_instance_value
        FOREIGN KEY (value)
        REFERENCES t_instance (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT fk_value_instance_property
        FOREIGN KEY (property_id)
        REFERENCES t_property (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT fk_value_instance_list
        FOREIGN KEY (list_id)
        REFERENCES t_list (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT fk_value_instance_instance
        FOREIGN KEY (instance_id)
        REFERENCES t_instance (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION,
    CONSTRAINT fk_value_instance_version
        FOREIGN KEY (version_id)
        REFERENCES t_version (id)
        ON DELETE NO ACTION
        ON UPDATE NO ACTION
)
ENGINE = InnoDB;

DROP TRIGGER IF EXISTS tr_before_value_instance_insert;

delimiter ~
CREATE TRIGGER tr_before_value_instance_insert BEFORE INSERT ON t_value_instance
FOR EACH ROW 
    BEGIN
        SET NEW.version_id = NEW_VERSION();
        SET NEW.uuid = INIT_UUID(NEW.uuid);
    END~
delimiter ;


-- -----------------------------------------------------
-- View v_model
-- -----------------------------------------------------
DROP VIEW IF EXISTS v_model;

CREATE VIEW v_model AS
SELECT
    m.id AS id,
    m.uuid AS uuid,
    m.name AS name,
    m.description AS description,
    m.version_id AS version_id,
    v.created AS created
FROM
    t_version v,
    t_model m
LEFT JOIN t_model m2 ON
    (
        m.uuid = m2.uuid AND 
        m.id < m2.id
    )
WHERE
    m2.id IS NULL AND
    m.version_id = v.id AND
    v.active = true;


-- -----------------------------------------------------
-- View v_instance
-- -----------------------------------------------------
DROP VIEW IF EXISTS v_instance;

CREATE VIEW v_instance AS
SELECT
    i.id AS id,
    i.uuid AS uuid,
    i.model_id AS model_id,
    i.version_id AS version_id,
    v.created AS created
FROM
    t_version v,
    v_model m,
    t_instance i
LEFT JOIN t_instance i2 ON
    (
        i.uuid = i2.uuid AND 
        i.id < i2.id
    )
WHERE
    i2.id IS NULL AND
    i.model_id = m.id AND
    i.version_id = v.id AND
    v.active = true;


-- -----------------------------------------------------
-- View v_property
-- -----------------------------------------------------
DROP VIEW IF EXISTS v_property;

CREATE VIEW v_property AS
SELECT
    p.id AS id,
    p.uuid AS uuid,
    p.name AS name,
    p.description AS description,
    p.type AS type,
    p.list AS list,
    p.instance_model_id AS instance_model_id,
    p.model_id AS model_id,
    p.version_id AS version_id,
    v.created AS created
FROM
    t_version v,
    v_model m,
    t_property p
LEFT JOIN t_property p2 ON
    (
        p.uuid = p2.uuid AND 
        p.id < p2.id
    )
WHERE
    p2.id IS NULL AND
    p.model_id = m.id AND
    p.version_id = v.id AND
    v.active = true;


-- -----------------------------------------------------
-- Replace remembered environment settings
-- -----------------------------------------------------
SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;

