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

