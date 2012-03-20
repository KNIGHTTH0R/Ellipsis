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

