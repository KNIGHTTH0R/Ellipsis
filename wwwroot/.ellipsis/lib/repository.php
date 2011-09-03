<?php

/**
 * Repository is an ORM (Object Relational Mapping) module built for Ellipsis 
 * that intentionally relies on native functionality found within MySQL. The 
 * goals behind this module are data integrity, version control, high 
 * performance, and simple structure manipulation.
 *
 * @author Toby Miller <tobius.miller@gmail.com>
 * @license MIT <http://www.opensource.org/licenses/mit-license.php>
 * @package ellipsis
 */

class Repository {

    /**
     * self representation
     * @var object
     */
    private static $_self = null;

    /**
     * loaded data models
     * @var array
     */
    private static $_models = null;

    /**
     * loaded property rules
     * @var array
     */
    private static $_rules = null;

    /**
     * determine if the passed value is of a certain type
     *
     * @param string $type
     * @param mixed $value
     * @return boolean
     */
    private static function _isType($type, $value){
        switch($type){
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

        // all else failed
        return false;
    }

    /**
     * convert the passed value to a certain type
     *
     * @param string $type
     * @param mixed $value
     * @return mixed|null
     */
    private static function _toType($type, $value){
        switch($type){
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
                return (string) $value;
            case 'instance':
                if (self::_isType('uuid', $value)){
                    return $value;
                }
                break;
        }

        // all else failed
        return null;
    }

    /**
     * load data models
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
                    if (!$property->list){
                        $property->list = false;
                    } else {
                        $property->list = true;
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

            // commit or rollback the transaction
            if ($success){
                Mysql::query('COMMIT');

                // everything succeeded, reload the available models
                self::$_models = null;
                self::_loadModels();

                // notify success
                return true;
            } else {
                Mysql::query('ROLLBACK');
            }
        }

        // notify failure
        return false;
    }

    /**
     * create a new data instance
     *
     * @param integer $model_id
     * @param object $property_values
     * @return integer|null
     */
    private static function _createInstance($model_id, $property_values){
        $valid_model = false;
        $valid_properties = true;

        // validate structure
        foreach(self::$_models as $model){
            if ($model->id == $model_id){
                $valid_model = true;
                foreach($model as $key => $value){
                    if ($key != 'id'){
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

    /**
     * say hello
     *
     * @param string $name
     * @return string
     */
    public static function hello($name = 'stranger'){
        return 'Hello ' . $name . '!';
    }
}

/**
 * trigger static constructor
 */
Model::construct();

