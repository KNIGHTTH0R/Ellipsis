<?php

/**
 * perform mysql communications
 *
 * @author Toby Miller <tobius.miller@gmail.com>
 * @license MIT <http://www.opensource.org/licenses/mit-license.php>
 * @package ellipsis
 * @subpackage modules
 * @depends cURL <http://php.net/curl>
 */

class Mysql {
    /**
     * database connection
     * @var object
     */
    private static $connection = null;

    /**
     * reported errors
     * @var array
     */
    private static $errors = array();

    /**
     * get errors
     *
     * @param void
     * @return array
     */
    public static function errors(){
        if (count(self::$errors) > 0){
            return self::$errors;
        }else return false;
    }

    /**
     * establish a mysql connection
     *
     * @param void
     * @return boolean
     */
    public static function connect(){
        if (self::$connection != null){
            return true;
        } else {
            if (isset($_ENV['MYSQL_HOST']) && isset($_ENV['MYSQL_NAME']) && isset($_ENV['MYSQL_USER']) && isset($_ENV['MYSQL_PASS'])){
                if ($connection = mysql_connect($_ENV['MYSQL_HOST'], $_ENV['MYSQL_USER'], $_ENV['MYSQL_PASS'])){
                    if (mysql_select_db($_ENV['MYSQL_NAME'], $connection)){
                        self::$connection = $connection;
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * sanitize sql query for execution (null if cannot be safely sanitized)
     *
     * @param string $sql
     * @return string|null
     */
    public static function sanitize($sql){
        // perform simple cleanup
        $sql = trim($sql);
        $sql = preg_replace('/\;\s*$/', '', $sql);

        // hex encode any passed values
        $sql = preg_replace('/(`*\w[\w\d_-]+`*)\s*=\s*([\'"])([^\2\\\\]*(?:\\\\.[^\2\\\\]*)*)\2/Ue', "'\\1='.'0x'.asciihex('\\3')", $sql);
        $sql = preg_replace('/=0x(,|\n|\s)/', "=''\\1", $sql);

        // disallow multiple statements
        if (preg_match('/;/', $sql)){
            return null;
        }

        return $sql;
    }

    /**
     * execute a sql query and return expected output
     *
     * note: if $data is provided it works like sprintf, but only %s is supported inside the $sql 
     * param
     *
     * @param string $sql
     * @param array $data
     * @return array|integer|boolean|null
     */
    public static function query($sql, $data = null){
        if (self::connect()){
            if (is_array($data)){
                $matches = substr_count($sql, '%s');
                if ($matches == count($data)){
                    foreach($data as $value){
                        $sql = preg_replace('/[\'"]*\%s[\'"]*/', '0x'.asciihex($value), $sql, 1); 
                    }
                    $sql = preg_replace('/0x([^0-9a-f])/', "''\\1", $sql);
                } else {
                    $sql = null;
                }
            }
            if ($sql != null){
                $sql = self::sanitize($sql);
                if ($sql != null){
                    $result = mysql_query($sql, self::$connection);
                    if ($result !== false){
                        $matched = preg_match('/(\w+\s){1}/m', $sql,$statement);
                        switch(trim(strtolower($statement[0]))){
                            case 'insert':
                            case 'replace':
                            case 'update':
                            case 'delete':
                            case 'drop':
                                $count = mysql_affected_rows(self::$connection);
                                return $count;
                            case 'select':
                            case 'show':
                            case 'describe':
                            case 'explain':
                                $data = array();
                                if (mysql_num_rows($result) > 0){
                                    while($row = mysql_fetch_assoc($result)){
                                        $data[] = (object) array_combine(array_keys($row), array_values($row));
                                    }
                                }
                                return $data;
                            case 'begin':
                            case 'commit':
                            case 'rollback':
                                return true;
                        }
                    }else{
                        self::$errors[] = mysql_error();
                    } 
                }
            }
        }
        return null;
    }
}

