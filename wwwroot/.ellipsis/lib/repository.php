<?php

/**
 * custom repository CRUD that doesn't sacrifice performance
 *
 * @author Toby Miller <tobius.miller@gmail.com>
 * @license MIT <http://www.opensource.org/licenses/mit-license.php>
 * @package ellipsis
 */

class Repository {

    private $database = null;
    private $fields = array();
    private $models = array();

    /**
     * execute model SQL commands (if first time use)
     *
     * @param void
     * @return void
     */
    public static function construct(){
        $this->fields = array(
            'zip' => array(
                'type'      => 'string',
                'sanitize'  => array('/[^0-9]+/', ''),
                'validate'  => '/^[0-9]{5}([0-9]{4})?$/',
                'render'    => array('/^([0-9]{5})([0-9]{4})?$/', '$1-$2'),
                'example'   => '43215'
            )
        );
    }

    /**
     * set the database instance
     *
     * @param object $database
     * @return void
     */
    public static function setDatabase($database){
    }

    /**
     * set non-standard fields (i.e. extend the built-ins)
     *
     * @param array $data
     * @return boolean
     */
    public static function setFields($data){
    }

    /**
     * set data models
     * 
     * @param array $data
     * @return boolean
     */
    public static function setModels($data){
    }

    /**
     * create a new data record
     *
     * @param string $key
     * @param array $data
     * @return integer|null $id
     */
    public static function create($key, $data){
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

