<?php

/**
 * perform mysql communications
 *
 * @author Toby Miller <tobius.miller@gmail.com>
 * @license MIT <http://www.opensource.org/licenses/mit-license.php>
 * @package ellipsis
 * @depends cURL <http://php.net/curl>
 */

class Mysql {
    /**
     * database connection
     * @var object
     */
    private static $connection = null;

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

        // disallow multiple statements
        if (preg_match('/;/', $sql)){
            return null;
        }

        // hex encode any passed values
        $sql = preg_replace('/(`*\w[\w\d_-]+`*)\s*=\s*([\'"])([^\2\\\\]*(?:\\\\.[^\2\\\\]*)*)\2/e', "'\\1='.'0x'.asciihex('\\3')", $sql);
        return $sql;
    }

    /**
     * execute a sql query and return expected output
     *
     * @param string $sql
     * @return array|integer|boolean|null
     */
    public static function query($sql){
        if (self::connect()){
            if ($sql = self::sanitize($sql)){
                $result = mysql_query($sql, self::$connection);
                if ($result !== false){
                    $statement = strtolower(preg_replace('/^\s*(\w+).*$/', '$1', $sql));
                    switch($statement){
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
                }
            }
        }
        return null;
    }
}

