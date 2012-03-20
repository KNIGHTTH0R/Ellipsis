
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

