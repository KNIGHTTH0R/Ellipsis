<?php

/**
 * Ellipsis
 *
 * Ellipsis is a boilerplate web application that celebrates PHP for what it is;
 * a simple, lightweight, extremely flexible scripting language that has been 
 * optimized for web development. Ellipsis is not an attempt to morph PHP into 
 * something that it is not; namely a compiled programming environment.
 *
 * @author Toby Miller <tobius.miller@gmail.com>
 * @license MIT <http://www.opensource.org/licenses/mit-license.php>
 * @package ellipsis
 */
class Ellipsis {

    /**
     * initial (manual) constructor for Ellipsis
     * 
     * @param void
     * @return void
     */
    public static function construct(){
        // record execution time
        $_ENV['START_TIME'] = microtime(true);

        // include additional PHP functions
        include __DIR__ . '/php.php';

        // configure system defaults using super globals
        $_ENV = array_merge($_ENV, 
            array(
                'SCRIPT_ROOT'   => __DIR__,
                'SCRIPT_LIB'    => __DIR__ . '/lib',
                'SCRIPT_SRC'    => __DIR__ . '/src',
                'DEBUG'         => true,
                'ROUTES'        => array(),
                'PARAMS'        => array(),
                'CACHE_TIME'    => null
            )
        );

        // set PATH_INFO which has been deprecated, but is really useful still
        $_SERVER['PATH_INFO'] = preg_replace('/\?.*$/', '', $_SERVER['REQUEST_URI']);

        // start buffering so we can output header data during output
        if (!ob_start('ob_gzhandler')) ob_start();

        // ensure that our destructor function is run
        register_shutdown_function(function(){
            Ellipsis::destruct();
        });
    }

    /**
     * last command executed for Ellipsis
     *
     * @param void
     * @return void
     */
    public static function destruct(){
        // compute total execution time for performance tuners
        if ($_ENV['DEBUG']){
            $_ENV['STOP_TIME'] = microtime(true);
            $_ENV['EXECUTION_TIME'] = $_ENV['STOP_TIME'] - $_ENV['START_TIME'];
            self::debug("Execution Time: {$_ENV['EXECUTION_TIME']}s");
        }

        // cache output if being saved
        if ($_ENV['CACHE_TIME'] > 0){
            // define the file pair
            $output_file    = $_SERVER['DOCUMENT_ROOT'] . $_SERVER['REQUEST_URI'];
            $cache_file     = $output_file . '.' . (time() + $_ENV['CACHE_TIME']);

            // touch the files
            if (touch_recursive($output_file) && touch_recursive($cache_file)){
                // capture the current buffer
                $buffer = ob_get_contents();

                // write the buffer to the output file
                $fp = fopen($output_file, 'wb');
                fwrite($fp, $buffer);
                fclose($fp);
            }
        }

        // finally, let nature take it's course
        ob_end_flush();
        exit;
    }

    /**
     * run the current request through Ellipsis
     *
     * @param void
     * @return void
     */
    public static function run(){
        // process routes
        foreach($_ENV['ROUTES'] as $route){
            // match the conditions of the route
            $match = true;
            foreach($route['conditions'] as $variable => $condition){
                switch($variable){
                    case 'METHOD':
                        // match a specific request method (default is GET)
                        if ($condition != $_SERVER['REQUEST_METHOD']){
                            $match = false;
                            break 2;
                        }
                        break;
                    case 'URI':
                        // match a specific URI pattern
                        preg_match('/' . $condition . '/U', $_SERVER['REQUEST_URI'], $matches);
                        if ($matches && count($matches) > 1){
                            if (is_associative_array($matches)){
                                // capture backreferences by index and name
                                array_shift($matches);
                                foreach($matches as $k => $v){
                                    if (is_numeric($k)){
                                        $route['params']['indexed'][] = $v;
                                    } else {
                                        $route['params']['named'][$k] = $v;
                                    }
                                }
                            } else if (is_array($matches) && count($matches) > 1){
                                // capture backreferences by index
                                array_shift($matches);
                                array_push($route['params']['indexed'], $matches);
                            }
                        } else {
                            $match = false;
                            break 2;
                        }
                        break;
                    case 'QUERY':
                    case 'GET':
                        foreach($condition as $key => $val){
                            if (!isset($_GET[$key]) || !preg_match('/' . $val . '/U', $_GET[$key])){
                                $match = false;
                                break 3;
                            }
                        }
                        break;
                    case 'POST':
                        foreach($condition as $key => $val){
                            if (!isset($_POST[$key]) || !preg_match('/' . $val . '/U', $_POST[$key])){
                                $match = false;
                                break 3;
                            }
                        }
                        break;
                    case 'COOKIE':
                        foreach($condition as $key => $val){
                            if (!isset($_COOKIE[$key]) || !preg_match('/' . $val . '/U', $_COOKIE[$key])){
                                $match = false;
                                break 3;
                            }
                        }
                        break;
                    case 'SERVER':
                        foreach($condition as $key => $val){
                            if (!isset($_SERVER[$key]) || !preg_match('/' . $val . '/U', $_SERVER[$key])){
                                $match = false;
                                break 3;
                            }
                        }
                        break;
                    case 'ENV':
                        foreach($condition as $key => $val){
                            if (!isset($_ENV[$key]) || !preg_match('/' . $val . '/U', $_ENV[$key])){
                                $match = false;
                                break 3;
                            }
                        }
                        break;
                    default:
                        $match = false;
                        break 2;
                }
            }
            if ($match){
                // this route matched, process it
                if ($route['closure']){
                    $result = $route['closure']($route['params']);
                    if ($result === false){
                        // this closure returned false, exit
                        self::cache($route['cache']);
                        exit;
                    } else if (is_string($result)){
                        // this closure returned a new path, set it and load it
                        $route['path_info'] = $result;

                        // perform backreference replacements first (if applicable)
                        preg_match_all('/\$\{([^\}]+)\}/', $route['path_info'], $matches);
                        if (count($matches) > 1){
                            foreach($matches[1] as $match){
                                if (is_numeric($match) && isset($route['params']['indexed'][$match])){
                                    $route['path_info'] = preg_replace('/\$\{' . $match . '\}/', $route['params']['indexed'][$match], $route['path_info']);
                                } else if (isset($route['params']['named']["{$match}"])){
                                    $route['path_info'] = preg_replace('/\$\{' . $match . '\}/', $route['params']['named'][$match], $route['path_info']);
                                } else {
                                    // leaving a backreference behind will result in an invalid file path
                                    self::fail(500, 'Invalid Path: Closure backreference could not be replaced');
                                }
                            }
                        }
                        if (!preg_match('/\$\{/', $route['path_info']) && preg_match('/' . preg_quote($route['path_info'], '/U') . '/', $_SERVER['PATH_INFO'])){
                            self::load($route['path_info'], $route['cache']);
                        }
                    } else {
                        // this closure has finished, on to the next route
                        continue;
                    }
                } else {
                    if (!preg_match('/\$\{/', $route['path_info']) && preg_match('/' . preg_quote($route['path_info'], '/U') . '/', $_SERVER['PATH_INFO'])){
                        self::load($route['path_info'], $route['cache']);
                    }
                }
            }
        }

        // process source files
        $source_paths = scandir_recursive($_ENV['SCRIPT_SRC'], 'relative');
        foreach($source_paths as $path){
            if ($_SERVER['PATH_INFO'] == $path){
                self::load($path, $route['cache']);
            }
        }

        // all other attempts at routing failed
        self::fail(404, 'Failed to match any routes ' . time());
    }

    /**
     * configure this output to be cached
     *
     * @param integer $seconds
     * @return boolean
     */
    public static function cache($seconds){
        if (is_numeric($seconds) && $seconds > 0){
            $_ENV['CACHE_TIME'] = $seconds;
        }
    }

    /**
     * configure a new route to be used
     *
     * @param array $conditions
     * @param mixed $instruction (path_info | closure)
     * @param integer $cache (seconds)
     * @return void
     */
    public static function route($conditions, $instruction, $cache = null){
        $path_info  = is_string($instruction) ? $instruction : null;
        $closure    = is_object($instruction) ? $instruction : null;
        $_ENV['ROUTES'][] = array(
            'conditions'    => $conditions,
            'params'        => array('indexed' => array(), 'named' => array()),
            'path_info'     => $path_info,
            'closure'       => $closure,
            'cache'         => $cache
        );
    }

    /**
     * load the destination resource and exit
     * note: this is the end process so it must exit when finished
     *
     * @param string $path
     * @param integer $cache
     * @return void
     */
    public static function load($path, $cache = null){
        // find appropriate mime type
        if (preg_match('/\.php$/', $_SERVER['PATH_INFO'])){
            $mime_type = 'text/html';
        } else if (preg_match('/\.[a-z0-9]+$/', $_SERVER['PATH_INFO'])){
            $mime_type = getmimetype($_SERVER['PATH_INFO']);
        } else if (preg_match('/\.php$/', $path)){
            $mime_type = 'text/html';
        } else {
            $mime_type = getmimetype($path);
        }

        // output appropriate content type
        header("Content-Type: $mime_type");

        // load appropriate resource
        if (is_file($_ENV['SCRIPT_SRC'] . $path)){
            if (preg_match('/\.php$/', $path)){
                include $_ENV['SCRIPT_SRC'] . $path;
            } else {
                $fp = fopen($_ENV['SCRIPT_SRC'] . $path, 'rb');
                header("Content-Length: " . filesize($_ENV['SCRIPT_SRC'] . $path));
                fpassthru($fp);
            }
        } else {
            self::fail(404);
        }

        // no more computing beyond this point
        if ($cache != null) self::cache($cache);
        exit;
    }

    /**
     * show a failed load attempt and exit
     *
     * @param integer $code
     * @param string $message
     * @return void
     */
    public static function fail($code, $message = null){
        self::debug("Error ($code): $message");
        if (isset($_ENV['ROUTE_' . $code])){
            if (is_file($_ENV['SCRIPT_SRC'] . '/' . $_ENV['ROUTE_' . $code])){
                include $_ENV['SCRIPT_SRC'] . '/' . $_ENV['ROUTE_' . $code];
                exit;
            }
        }
        echo "{$code}: {$message}";
        exit;
    }

    /**
     * send a debug message to the browser
     *
     * @param string $message
     * @param mixed $data
     * @return void
     */
    function debug($message, $data = undefined){
        if ($_ENV['DEBUG']){
            if ($data != undefined){
                ChromePhp::log("{$_SERVER['REQUEST_URI']}: {$message}", $data);
            } else {
                ChromePhp::log("{$_SERVER['REQUEST_URI']}: {$message}");
            }
        }
    }
}

/**
 * trigger static constructor
 */
Ellipsis::construct();


