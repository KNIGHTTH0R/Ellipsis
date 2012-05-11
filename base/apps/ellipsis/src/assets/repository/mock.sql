
-- -----------------------------------------------------
-- Create a building model
-- -----------------------------------------------------
INSERT INTO t_model (name, description) values ('building', 'Exterior building');
DECLARE building_id INT;
SET building_id = LAST_INSERT_ID();

INSERT INTO t_property (model_id, name, description, type, list, instance_model_id) values (building_id, 'address', 'postal address', 'ascii', false, null);
DECLARE building_address_id INT;
SET building_address_id = LAST_INSERT_ID();

INSERT INTO t_property (model_id, name, description, type, list, instance_model_id) values (building_id, 'city', 'city name', 'ascii', false, null);
DECLARE building_city_id INT;
SET building_city_id = LAST_INSERT_ID();

INSERT INTO t_property (model_id, name, description, type, list, instance_model_id) values (building_id, 'state', '2 letter code', 'ascii', false, null);
DECLARE building_state_id INT;
SET building_state_id = LAST_INSERT_ID();

INSERT INTO t_property (model_id, name, description, type, list, instance_model_id) values (building_id, 'zip', '5 or 9 digit postal code', 'ascii', false, null);
DECLARE building_zip_id INT;
SET building_zip_id = LAST_INSERT_ID();

INSERT INTO t_property (model_id, name, description, type, list, instance_model_id) values (building_id, 'color', 'primary exterior paint color', 'ascii', false, null);
DECLARE building_color_id INT;
SET building_color_id = LAST_INSERT_ID();

INSERT INTO t_property (model_id, name, description, type, list, instance_model_id) values (building_id, 'business', 'specify if business', 'boolean', false, null);
DECLARE building_business_id INT;
SET building_business_id = LAST_INSERT_ID();


-- -----------------------------------------------------
-- Create a room model
-- -----------------------------------------------------
INSERT INTO t_model (name, description) values ('room', 'Interior room');
DECLARE room_id INT;
SET room_id = LAST_INSERT_ID();

INSERT INTO t_property (model_id, name, description, type, list, instance_model_id) values (room_id, 'doors', 'number of doors', 'integer', false, null);
DECLARE room_doors_id INT;
SET room_doors_id = LAST_INSERT_ID();

INSERT INTO t_property (model_id, name, description, type, list, instance_model_id) values (room_id, 'windows', 'number of windows', 'integer', false, null);
DECLARE room_windows_id INT;
SET room_windows_id = LAST_INSERT_ID();

INSERT INTO t_property (model_id, name, description, type, list, instance_model_id) values (room_id, 'color', 'primary wall paint color', 'ascii', false, null);
DECLARE room_color_id INT;
SET room_color_id = LAST_INSERT_ID();


-- -----------------------------------------------------
-- Create the resource building
-- -----------------------------------------------------
INSERT INTO t_instance (model_id) values (building_id);
DECLARE resource_id INT;
SET resource_id = LAST_INSERT_ID();
INSERT INTO t_value_integer (instance_id, property_id, list_id, value) values (resource_id, building_address_id, null, '343 N Front St');
INSERT INTO t_value_smalltext (instance_id, property_id, list_id, value) values (resource_id, building_city_id, null, 'Columbus');
INSERT INTO t_value_smalltext (instance_id, property_id, list_id, value) values (resource_id, building_state_id, null, 'OH');
INSERT INTO t_value_smalltext (instance_id, property_id, list_id, value) values (resource_id, building_color_id, null, 'brick');
INSERT INTO t_value_boolean (instance_id, property_id, list_id, value) values (resource_id, building_business_id, null, true);


-- -----------------------------------------------------
-- Create the lobby
-- -----------------------------------------------------
INSERT INTO t_instance (model_id) values (room_id);
DECLARE lobby_id INT;
SET lobby_id = LAST_INSERT_ID();
INSERT INTO t_value_integer (instance_id, property_id, list_id, value) values (resource_id, room_doors_id, null, 4);
INSERT INTO t_value_integer (instance_id, property_id, list_id, value) values (resource_id, room_windows_id, null, 0);
INSERT INTO t_value_smalltext (instance_id, property_id, list_id, value) values (resource_id, room_color_id, null, 'green');

