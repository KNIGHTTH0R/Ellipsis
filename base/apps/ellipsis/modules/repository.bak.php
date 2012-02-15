<?php

/**
 * Repository is an ORM (Object Relational Mapping) module built for Ellipsis 
 * that intentionally takes advantage of native functionality in MySQL rather
 * than obscuring it in favor of the mythical cross-database support that most
 * other ORM modules claim is important (and most developers never use). The 
 * goals of this module are to support data integrity, version control, high 
 * performance, and easy manipulation of data and structure in PHP.
 *
 * Note: In the future this module will also support MongoDB as a way to 
 * balance between data integrity (MySQL) and speed (MongoDB). This will be 
 * accomplished primarily through the addition of an integrity flag. For 
 * example, all models flagged with {'integrity' => true} (aka lossless) will 
 * pass through transactional MySQL calls while all other models (lossful) 
 * will pass through MongoDB. This will allow each models data to be 
 * prioritized appropriately.
 *
 * Warning: The SQL structure for this repository module will not function
 * properly with a small thread_stack size. It is recommended to increase
 * your thread_stack size to 192K in my.cnf and restart mysqld before 
 * attempting to use this module.
 *
 * How to define a custom property type
 * Repository::$types = array(
 *     'phone' => array(
 *         'type'       => 'ascii',
 *         'sanitize'   => array('/[^0-9]+/', '//'),
 *         'validate'   => '/^[2-9][0-9]{9}$/',
 *         'format'     => array('/^([0-9]{3})([0-9]{3})([0-9]{4})$/', '($1) $2-$3'),
 *         'example'    => '(614) 621-2888'
 *     ),
 *     'zip' => array(
 *         'type'       => 'ascii',
 *         'sanitize'   => array('/[^0-9]+/', '//'),
 *         'validate'   => '/^[0-9]{5}$/',
 *         'example'    => '(614) 621-2888'
 *     )
 * );
 *
 * How to create a model
 * Repository::$models = array(
 *     'house' => array(
 *         'address' => array(
 *             'type'       => 'ascii',
 *             'required'   => true
 *         ),
 *         'city' => array(
 *             'type'       => 'ascii',
 *             'required'   => true
 *         'state' => array(
 *             'type'       => 'ascii',
 *             'required'   => true
 *         ),
 *         'zip' => array(
 *             'type'       => 'ascii',
 *             'required'   => true
 *         ),
 *         'phone' => array(
 *             'type'       => 'phone',
 *             'required'   => false
 *         ),
 *         'residential' => array(
 *             'type'       => 'boolean',
 *             'required'   => true
 *         )
 *     ),
 *     'owner' => array(
 *         'name' => array(
 *             'type'       => 'ascii',
 *             'required'   => true
 *         ),
 *         'email' => array(
 *             'type'       => 'ascii',
 *             'required'   => true
 *         ),
 *         'phone' => array(
 *             'type'       => 'phone',
 *             'required'   => false
 *         ),
 *         'houses' => array(
 *             'type'       => 'instance',
 *             'model'      => 'house',
 *             'list'       => true
 *         )
 *     )
 * );
 *
 * How to create an instance
 *
 * How to retrieve an instance
 *
 * How to update an instance
 *
 * How to delete an instance
 *
 * How to migrate a model
 * Repository::migrate(4);
 * 
 *
 * @author Toby Miller <tobius.miller@gmail.com>
 * @license MIT <http://www.opensource.org/licenses/mit-license.php>
 * @package ellipsis
 */

class Repository {

    /**
     * reference to self (useful for going between static and object)
     * @var object
     */
    private static $_self = null;

    /**
     * models available in the database
     * @var array
     */
    private static $_models = array();

    /**
     * models defined at runtime
     *
     * These will be evaluated against those in the database and updated accordingly.
     *
     * @var array
     */
    public static $models = array();

    /**
     * native property types
     * @var array
     */
    private static $_nativeTypes = array('boolean', 'integer', 'double', 'datetime', 'binary', 'ascii', 'instance');

    /**
     * extended property types
     *
     * An example extended property type array would look like this:
     *     array(
     *         'extendedType' => array(
     *             'type'       => 'nativeType',
     *             'sanitize'   => array('/regexpSearch/', '/regexpReplace/'),
     *             'validate'   => '/regexpValidMatch/',
     *             'format'     => array('/regexpRenderSearch/', '/regexpRenderReplace/'),
     *             'example'    => 'validExampleValue'
     *         ),
     *     )
     *
     * The purpose of each key is as follows:
     *     type - maps to the native property type in the database
     *     sanitize - uses a regexp to replace a passed value with a cleaner version before database entry
     *     validate - uses a regexp to determine if the sanitized value is valid or not
     *     format - uses a regexp to rended a value from the database for an optimized display
     *     example - makes a valid example available for error messaging
     *
     * @var array
     */
    private static $_extendedTypes = array();

    /**
     * extended property types defined at runtime
     *
     * These will be evaluated and added to _extendedTypes accordingly.
     *
     * @var array
     */
    public static $types = array();

    /**
     * get the native type of the passed property type
     *
     * @param string $type
     * @return string|null
     */
    private static function _getNativeType($type){
        if (in_array($type, self::$_nativeTypes)){
            return $type;
        } else {
            if (array_key_exists($type, self::$_extendedTypes)){
                if (in_array(self::$_extendedTypes[$type]['type'], self::$_nativeTypes)){
                    return self::$_extendedTypes[$type]['type'];
                }
            }
        }
        return null;
    }

    /**
     * determine if the passed value is of the expected property type
     *
     * @param string $type
     * @param mixed $value
     * @return boolean
     */
    private static function _isPropertyType($type, $value){
        $nativeType = self::_getNativeType($type);
        if ($nativeType != null){
            switch($nativeType){
                case 'boolean':
                    if (is_bool($value)){
                        return true;
                    } else if (is_numeric($value) && ($value == 0 || $value == 1)){
                        return true;
                    } else if (is_string($value)){
                        if ($value == 'true' || $value == 'false'){
                            return true;
                        } else if ($value == '1' || $value == '0'){
                            return true;
                        }
                    }
                    break;
                case 'integer':
                    if (is_int($value)){
                        return true;
                    } else if (preg_match('/^[0-9]+$/', $value)){
                        return true;
                    }
                    break;
                case 'double':
                    if (is_float($value) || is_numeric($value)){
                        return true;
                    }
                    break;
                case 'datetime':
                    if (is_int($value) || strtotime($value)){
                        return true;
                    }
                    break;
                case 'binary':
                    if (is_binary($value)){
                        return true;
                    }
                    break;
                case 'ascii':
                    if (is_string($value)){
                        return true;
                    }
                    break;
                case 'instance':
                    $uuid = null;
                    if (is_object($value) && isset($value->uuid) && is_mysql_uuid($value->uuid)){
                        $uuid = $value->uuid;
                    } else if (is_mysql_uuid($value)){
                        $uuid = $value;
                    }
                    if ($uuid != null){
                        $id = Mysql::query("SELECT MAX(id) FROM t_instance where uuid = '{$uuid}'");
                        if ($id && is_int($id)){
                            return true;
                        }
                    }
                    break;
            }
        }

        // all else failed
        return false;
    }

    /**
     * sanitize the passed property value
     *
     * @param string $type
     * @param mixed $value
     * @return mixed|null
     */
    private static function _sanitizeValue($type, $value){
        if (self::_isPropertyType($type, $value)){
            $nativeType = self::_getNativeType($type);
            if ($nativeType != null){
                switch($nativeType){
                    case 'boolean':
                        if (is_bool($value)){
                            return $value;
                        } else if (is_numeric($value) && ($value == 1 || $value == 0)){
                            return ($value == 1) ? true : false;
                        } else if (is_string($value)){
                            if ($value == 'true' || $value == 'false'){
                                return ($value == 'true') ? true : false;
                            } else if ($value == '1' || $value == '0'){
                                return ($value == 1) ? true : false;
                            }
                        }
                        break;
                    case 'integer':
                        if (is_int($value)){
                            return $value;
                        } else if (preg_match('/^[0-9]+$/', $value)){
                            return (int) $value;
                        }
                        break;
                    case 'double':
                        if (is_float($value)){
                            return $value;
                        } else if (is_numeric($value)){
                            return (float) $value;
                        }
                        break;
                    case 'datetime':
                        if (is_int($value)){
                            return $value;
                        } else {
                            $time = strtotime($value);
                            if ($time){
                                return $time;
                            }
                        }
                        break;
                    case 'binary':
                        if (is_binary($value)){
                            return $value;
                        } else {
                            return pack((string) $value);
                        }
                        break;
                    case 'ascii':
                        if ($type != $nativeType){
                            if (self::$_extendedTypes[$type]['sanitize']){
                                $value = preg_replace(self::$_extendedTypes[$type]['sanitize'][0], self::$_extendedTypes[$type]['sanitize'][1], $value);
                            }
                        }
                        return (string) $value;
                    case 'instance':
                        if (self::_isPropertyType('uuid', $value)){
                            return $value;
                        }
                        break;
                }
            }
        }

        // all else failed
        return null;
    }

    /**
     * determine if the passed value is valid
     *
     * @param string $type
     * @param mixed $value
     * @return boolean
     */
    private static function _isValidValue($type, $value){
        $value = self::_sanitizeValue($type, $value);
        if ($value != null){
            $nativeType = self::_getNativeType($type);
            if ($nativeType == 'ascii'){
                if (isset(self::$_extendedTypes[$type]['validate'])){
                    if (!preg_match(self::$_extendedTypes[$type]['validate'], $value)){
                        return false;
                    }
                }
            }
            return true;
        }
        return false;
    }

    /**
     * format property value for display
     *
     * @param string $type
     * @param mixed $value
     * @return mixed|null
     */
    private static function _formatValue($type, $value){
        $nativeType = self::_getNativeType($type);
        if ($nativeType != $type && $type == 'ascii'){
            if (self::$_extendedTypes[$type]['format']){
                $value = preg_replace(self::$_extendedTypes[$type]['format'][0], self::$_extendedTypes[$type]['sanitize'][1], $value);
            }
        }
        return $value;
    }

    /**
     * load available data models
     *
     * @param void
     * @return void
     */
    private static function _loadModels(){
        if (self::$_models == null){
            $sql = 'SELECT id, uuid, name, description, version_id, created FROM v_model WHERE 1';
            $models = Mysql::query($sql);
            if ($models){
                $_models = array();
                foreach($models as $model){
                    $sql = "SELECT id, model_id, uuid, name, description, type, list, instance_model_id, version_id, created FROM v_property WHERE model_id = '{$model->id}'";
                    $properties = Mysql::query($sql);
                    if ($properties){
                        $model->properties = $properties;
                        $_models[] = $model;
                    }
                }
                self::$_models = $_models;
            }
        }
    }

    /**
     * create a new data model
     *
     * @param string $name
     * @param string $description
     * @param array $properties
     * @return boolean
     */
    private static function _createModel($name, $description, $properties){
        // prevent duplicate models within a living revision
        foreach(self::$_models as $model){
            if ($model->name == $name) return false;
        }

        // perform basic data validation
        $valid = true;
        if (!preg_match('/^[a-z0-9_-]{0,45}$/i', $name)){
            $valid = false;
        } else if (!preg_match('/^[a-z0-9 \'"\._-]{0,255}$/i', $description)){
            $valid = false;
        } else {
            foreach($properties as $property){
                if (!preg_match('/^[a-z0-9_-]{0,45}$/i', $property->name)){
                    $valid = false;
                } else if (!preg_match('/^[a-z0-9_-]{0,255}$/i', $property->description)){
                    $valid = false;
                } else if (!in_array($property->type, array('boolean','integer','double','datetime','binary','ascii','instance'))){
                    $valid = false;
                } else if ($property->type == 'instance'){
                    if (!is_numeric($property->instance_model_id)){
                        $valid = false;
                    } else {
                        $found = false;
                        foreach(self::$_models as $model){
                            if ($model->id == $property->instance_model_id) $found = true;
                        }
                        if (!$found) $valid = false;
                    }
                }
                if ($valid){
                    if (self::_isPropertyType($property->type, $property->value)){
                        $property->value = self::_toType($property->type);
                        if (!$property->list){
                            $property->list = false;
                        } else {
                            $property->list = true;
                        }
                    } else {
                        $valid = false;
                    }
                }
            }
        }

        if ($valid){
            // perform a transaction
            Mysql::query('BEGIN');
            $success = true;

            // create a new model record
            $model_id = Mysql::query("INSERT INTO t_model (name, description) VALUES ('{$name}', '{$description}')");

            if ($model_id){
                // create new property records
                foreach($properties as $property){
                    $property_id = Mysql::query("INSERT INTO t_property (model_id, name, description, type, instance_model_id, list) VALUES ({$model_id}, '{$property->name}', '{$property->description}', '{$property->type}', {$property->instance_model_id}, {$property->list})");
                    if (!$property_id){
                        $success = false;
                        break;
                    }
                }
            }

            if ($success){
                // commit the transaction
                Mysql::query('COMMIT');

                // everything succeeded, reload the available models
                self::$_models = null;
                self::_loadModels();

                // notify success
                return true;
            } else {
                // roll back the transaction
                Mysql::query('ROLLBACK');
            }
        }

        // notify failure
        return false;
    }

    /**
     * create a new data instance
     *
     * @param string $model_uuid
     * @param array $property_values
     * @return integer|null
     */
    private static function _createInstance($model_uuid, $property_values){
        $valid_model = false;
        $valid_properties = true;

        // validate structure
        foreach(self::$_models as $model){
            if ($model->uuid == $model_uuid){
                $valid_model = true;
                foreach($model as $key => $value){
                    if (!in_array($key, array('id', 'uuid', 'created'))){
                        if (!array_key_exists($key, $property_values)){
                            $valid_properties = false;
                            break;
                        }
                    }
                }
                break;
            }
        }

        if ($valid_model && $valid_properties){
            // validate data
            foreach(self::$_rules as $type => $ruleset){

            }
        }

        return null;
    }

    /**
     * query an existing data instance
     *
     * @param string $uuid
     * @return object|null
     */
    public static query($uuid){
        return null;
    }

    /**
     * read an existing data record
     *
     * @param integer|string $id|$uuid
     * @return array|null $data
     */
    public static function read($id){
        return null;
    }

    /**
     * update an existing data record
     *
     * @param integer|string $id|$uuid
     * @param array $data
     * @return boolean
     */
    public static function update($id, $data){
        return false;
    }

    /**
     * delete an existing data record
     * note: this meta model supports versioning, so this is really deactivate
     *
     * @param integer|string $id|$uuid
     * @return boolean
     */
    public static function delete($id){
        return false;
    }
}

/**
 * trigger static constructor
 */
Model::construct();

