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
     * load data models
     *
     * @param void
     * @return void
     */
    private static function loadModels(){
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
    private static function createModel($name, $description, $properties){
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
                self::loadModels();

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
     * @return boolean
     */
    public static function createInstance($model_id, $property_values){
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

