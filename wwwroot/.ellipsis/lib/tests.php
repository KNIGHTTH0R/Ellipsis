<?php

/**
 * simpletest wrapper for the Ellipsis framework
 *
 * @author Toby Miller <tobius.miller@gmail.com>
 * @license MIT <http://www.opensource.org/licenses/mit-license.php>
 * @package ellipsis
 */
class Test {

    private static $instance = null;

    /**
     * construct a new Test object
     *
     * @param void
     * @return void
     */
    public function __construct(){
        // load simpletest
        if (isset($_ENV['SCRIPT_LIB'] . '/simpletest')){
            require_once $_ENV['SCRIPT_LIB'] . '/simpletest/autorun.php';
        }
    }

    /**
     * find tests to run
     *
     * @param string $app
     * @return array
     */
    public static function findTests($app = null){
        // autorun
        if (self::$instance == null){
            self::$instance = new Test();
        }

        // find all written tests
        if (isset($_ENV['SCRIPT_ROOT'] . '/tests')){
            $_ENV['SCRIPT_ROOT'] . '/tests'
        }
    }

    /**
     * run tests
     *
     * @param array $tests
     * @return void
     */
    public static function runTests($tests = null){
        // autorun
        if (self::$instance == null){
            self::$instance = new Test();
        }
    }
}
