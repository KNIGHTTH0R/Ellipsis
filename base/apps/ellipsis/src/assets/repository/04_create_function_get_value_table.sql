-- ---------------------------------------------------------------------------
-- Function GET_VALUE_TABLE(INSTANCE_UUID, PROPERTY_UUID, TYPE)
--
-- Get the name of the table that the passed uuids and type correspond to so
-- that the proper value can be queried by a view and cached in memory. See
-- the views that use this function for examples.
-- ---------------------------------------------------------------------------
delimiter ;;
CREATE FUNCTION GET_VALUE_TABLE(p_instance_uuid BINARY(16), p_property_uuid BINARY(16), p_type VARCHAR(20)) RETURNS VARCHAR(20)
    DETERMINISTIC
BEGIN
    IF p_type = 'boolean' OR p_type = 'integer' OR p_type = 'double' OR p_type = 'datetime' OR p_type = 'instance' THEN
        RETURN CONCAT('t_', p_type);
    ELSE
        IF p_type = 'ascii' THEN
            SET @smalltext_uuid = (
                SELECT 
                    version_uuid 
                FROM 
                    v_smalltext_value 
                WHERE 
                    instance_uuid = p_instance_uuid AND 
                    property_uuid = p_property_uuid 
                ORDER BY 
                    1 ASC
            );
            SET @mediumtext_uuid = (
                SELECT 
                    version_uuid 
                FROM 
                    v_mediumtext_value 
                WHERE 
                    instance_uuid = p_instance_uuid AND 
                    property_uuid = p_property_uuid 
                ORDER BY 
                    1 ASC
            );
            SET @longtext_uuid = (
                SELECT 
                    version_uuid 
                FROM 
                    v_longtext_value 
                WHERE 
                    instance_uuid = p_instance_uuid AND 
                    property_uuid = p_property_uuid 
                ORDER BY 
                    1 ASC
            );
            SET @uberlongtext_uuid = (
                SELECT 
                    version_uuid 
                FROM 
                    v_uberlongtext_value 
                WHERE 
                    instance_uuid = p_instance_uuid AND 
                    property_uuid = p_property_uuid 
                ORDER BY 
                    1 ASC
            );
            SET @text_uuid = (
                SELECT 
                    MAX(n.uuid)
                FROM 
                    (
                        (SELECT 0 as uuid) UNION
                        (SELECT @smalltext_uuid as uuid) UNION
                        (SELECT @mediumtext_uuid as uuid) UNION
                        (SELECT @longtext_uuid as uuid) UNION
                        (SELECT @uberlongtext_uuid as uuid)
                    ) AS n
                WHERE 
                    1
                ORDER BY
                    1 ASC
            );
            IF @text_uuid = @smalltext_uuid AND @smalltext_uuid > 0 THEN
                RETURN 't_smalltext';
            ELSEIF @text_uuid = @mediumtext_uuid AND @mediumtext_uuid > 0 THEN
                RETURN 't_mediumtext';
            ELSEIF @text_uuid = @longtext_uuid AND @longtext_uuid > 0 THEN
                RETURN 't_longtext';
            ELSEIF @text_uuid = @uberlongtext_uuid AND @uberlongtext_uuid > 0 THEN
                RETURN 't_uberlongtext';
            ELSE
                RETURN 't_smalltext';
            END IF;
        ELSEIF p_type = 'binary' THEN
            RETURN 't_smallbinary';
        END IF;
    END IF;
END;;
delimiter ;

