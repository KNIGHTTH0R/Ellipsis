-- ---------------------------------------------------------------------------
-- Function GET_VALUE_DATA_KEY(INSTANCE_UUID, PROPERTY_UUID, TYPE)
--
-- Get the name of the data table key column that the passed uuids and type 
-- correspond to so that the proper value can be queried by a view and cached 
-- in memory. See the views that use this function for examples.
-- ---------------------------------------------------------------------------
delimiter ;;
CREATE FUNCTION GET_VALUE_DATA_KEY(p_instance_uuid BINARY(16), p_property_uuid BINARY(16), p_type VARCHAR(20)) RETURNS VARCHAR(20)
    DETERMINISTIC
BEGIN
    RETURN(CONCAT(GET_VALUE_TABLE(p_instance_uuid, p_property_uuid, p_type), '_data'));
END;;
delimiter ;

