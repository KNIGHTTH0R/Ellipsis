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

