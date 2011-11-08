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
     * graceful exit strategy
     * @var boolean
     */
    private static $graceful = false;

    /**
     * errors
     * @var array
     */
    private static $errors = array();

    /**
     * static constructor (manually executed in class file)
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
                'CACHE_DIR'     => $_SERVER['DOCUMENT_ROOT'] . '/.cached',
                'SCRIPT_ROOT'   => __DIR__,
                'SCRIPT_LIB'    => __DIR__ . '/lib',
                'DEBUG'         => true,
                'CURRENT'       => null,
                'APPS'          => array(),
                'ROUTES'        => array(),
                'PARAMS'        => array(),
                'CACHE_TIME'    => null,
                'HTTP_CODE'     => array(
                    '400'   => array(
                        'title'         => 'Bad Request',
                        'description'   => 'This is not a valid HTTP request',
                        'translation'   => 'Whatever in the hell you\'re doing, stop it!'
                    ),
                    '401'   => array(
                        'title'         => 'Unauthorized',
                        'description'   => 'User authentication is required before continuing',
                        'translation'   => 'You need to take mo out for lobster first'
                    ),
                    '402'   => array(
                        'title'         => 'Payment Required',
                        'description'   => 'User payment is required before continuing',
                        'translation'   => 'Give me whatever\'s in your wallet and we\'ll see'
                    ),
                    '403'   => array(
                        'title'         => 'Forbidden',
                        'description'   => 'User access is denied',
                        'translation'   => 'Losers go home!'
                    ),
                    '404'   => array(
                        'title'         => 'File Not Found',
                        'description'   => 'Requested URI was not found on this server',
                        'translation'   => 'Didn\'t find what you were looking for? Hmm, maybe you should try another URL'
                    ),
                    '405'   => array(
                        'title'         => 'Method Not Allowed',
                        'description'   => 'Requested method is not supported on this server',
                        'translation'   => 'GET, POST, HEAD, those are all valid options ... So get with the program and stop whatever in the hell you\'re doing'
                    ),
                    '406'   => array(
                        'title'         => 'Not Acceptable',
                        'description'   => 'Requested URI is of a Content-Type that your browser cannot accept',
                        'translation'   => 'Your browser pretty much sucks because it can\'t handle this Content-Type ... maybe it\'s finally time to upgrade'
                    ),
                    '407'   => array(
                        'title'         => 'Proxy Authentication Required',
                        'description'   => 'Proxy authentication is required before continuing',
                        'translation'   => 'Haha! Your IT department doesn\'t trust you to use this proxy server ... I hope you remembered your username and password'
                    ),
                    '408'   => array(
                        'title'         => 'Request Timeout',
                        'description'   => 'Server timed out waiting for a URI request from the user',
                        'translation'   => 'Sorry, I can\'t hold my breath for that long. Hit refresh and I\'ll take a deep breath and try again.'
                    ),
                    '409'   => array(
                        'title'         => 'Conflict',
                        'description'   => 'Server refused to service the request because of a possible conflict',
                        'translation'   => 'Sorry, this is a conflict of interest for me, don\'t make me choose'
                    ),
                    '410'   => array(
                        'title'         => 'Gone',
                        'description'   => 'Requested URI was removed and no forwarding address is known',
                        'translation'   => 'Well, this is a little embarrassing, we seem to have misplaced what you\'re looking for ... oops?'
                    ),
                    '411'   => array(
                        'title'         => 'Length Required',
                        'description'   => 'Request message was without a required Content-Length',
                        'translation'   => 'Hmm, something isn\'t right here ... is that an anvil or a painted styrofoam block shaped like an anvil?'
                    ),
                    '412'   => array(
                        'title'         => 'Precondition Failed',
                        'description'   => 'Request did not pass servers definition of a valid HTTP request',
                        'translation'   => 'Sorry, me no speako hacko ... seriously, you know we log this stuff right?'
                    ),
                    '413'   => array(
                        'title'         => 'Request Entity Too Large',
                        'description'   => 'Request entity is larger than the server is able or willing to process',
                        'translation'   => 'Sorry ma\'am but I\'m going to have to ask you to pay for two seats'
                    ),
                    '414'   => array(
                        'title'         => 'Request-URI Too Long',
                        'description'   => 'Request-URI is longer than the server is willing to interpret',
                        'translation'   => 'Ooh! Ooh! You should checkout this really cool service called TinyURL ... it could change your world'
                    ),
                    '415'   => array(
                        'title'         => 'Unsupported Media Type',
                        'description'   => 'Server refused to service request for an unsupported format',
                        'translation'   => 'Are you seriously trying to feed me onions? Because I distinctly remember telling you how allergic I am!'
                    ),
                    '416'   => array(
                        'title'         => 'Requested Range Not Satisfiable',
                        'description'   => 'Server refused to service a Range request-header because the range being requested is not valid',
                        'translation'   => 'Sorry, that\'s not how many fingers I was holding up, try again'
                    ),
                    '417'   => array(
                        'title'         => 'Expectation Failed',
                        'description'   => 'Server refused to service an Expect request-header because the server would have been unable to meet the expectation',
                        'translation'   => 'Woah! Woah! Woah! I actually thought I knew HTTP, but you are blowing my mind! I need to go lay down for a minute.'
                    ),
                    '500'   => array(
                        'title'         => 'Internal Server Error',
                        'description'   => 'Server encountered an unexpected condition',
                        'translation'   => 'Oops, that\'s my bad ... I hope the IT guys are seeing this'
                    ),
                    '501'   => array(
                        'title'         => 'Not Implemented',
                        'description'   => 'Server does not support the functionality required to fulfill the request',
                        'translation'   => 'OPTIONS, HEAD, GET, POST, PUT, DELETE, TRACE, CONNECT, those I understand ... why don\'t you try again'
                    ),
                    '502'   => array(
                        'title'         => 'Bad Gateway',
                        'description'   => 'Server received an invalid response from the upstream server while trying to fulfill your request as an intermediate gateway or proxy',
                        'translation'   => 'I tried to get this for you, but this other guy\'s being a pain right now, hit me up again later'
                    ),
                    '503'   => array(
                        'title'         => 'Service Unavailable',
                        'description'   => 'Server is unable to fulfill requests due to either a temporary server overload or a maintenance task',
                        'translation'   => 'Sorry, but I\'ve just got way too many things going on right now. Give me a minute to compose myself'
                    ),
                    '504'   => array(
                        'title'         => 'Gateway Timeout',
                        'description'   => 'Server received a timeout response from the upstream server while trying to fulfill your request as an intermediate gateway or proxy',
                        'translation'   => 'You know that other guy that I was getting that thing from for you? Well, he said I was taking too long, hit me up again later'
                    ),
                    '505'   => array(
                        'title'         => '505',
                        'description'   => 'Server does not support the HTTP protocol version used in this request',
                        'translation'   => 'That sounds like a really cool new version to "experiment" with, just not in public yet. Let\'s go slow, I\'m worth it!'
                    )
                )
            )
        );

        // reset PATH_INFO (this was deprecated in PHP)
        $_SERVER['PATH_INFO'] = preg_replace('/\?.*$/', '', $_SERVER['REQUEST_URI']);

        // start outer buffer
        ob_start();

        // start inner buffer
        ob_start(array('Ellipsis', 'buffer'));

        // parse errors for pretty output
        set_error_handler(array('Ellipsis', 'parse_error'), E_ALL);

        // ensure that our destructor function gets run
        register_shutdown_function(array('Ellipsis', 'destruct'));
    }

    /**
     * parse errors
     *
     * @param integer $error_number
     * @param string $error_message
     * @param string $error_file
     * @param integer $error_line
     * @param array $error_context
     * @return void
     */
    public static function parse_error($error_number, $error_message, $error_file = null, $error_line = null, $error_context = null){
        $error_types = array(
            E_ERROR             => 'Fatal Error',
            E_WARNING           => 'Warning',
            E_PARSE             => 'Parse Error',
            E_NOTICE            => 'Notice',
            E_CORE_ERROR        => 'Fatal Core Error',
            E_CORE_WARNING      => 'Core Warning',
            E_COMPILE_ERROR     => 'Compilation Error',
            E_COMPILE_WARNING   => 'Compilation Warning',
            E_USER_ERROR        => 'Triggered Error',
            E_USER_WARNING      => 'Triggered Warning',
            E_USER_NOTICE       => 'Triggered Notice',
            E_STRICT            => 'Deprecation Notice',
            E_RECOVERABLE_ERROR => 'Catchable Fatal Error'
        );
        $error = array(
            'number'    => $error_number,
            'message'   => $error_message,
            'file'      => $error_file,
            'line'      => $error_line,
            'context'   => $error_context,
            'type'      => $error_types[$error_number]
        );
        self::$errors[] = $error;
        return false;
    }

    /**
     * buffer output
     *
     * @todo reintroduce gzip handling
     *
     * @param string $buffer
     * @param integer $mode
     * @return void
     */
    public static function buffer($buffer, $mode){
        // catch the errors that weren't catchable
        if (preg_match('/<\/b>:\s*(.+) in <b>(.+)<\/b> on line <b>(.+)<\/b>/U', $buffer, $matches)){
            Ellipsis::parse_error(E_ERROR, $matches[1], $matches[2], $matches[3]);
        }

        // if there were errors, let that be the only output
        if (count(self::$errors) > 0){
            $buffer = '';
        }

        // return whatever buffer is applicable
        return $buffer;
    }

    /**
     * last command executed for Ellipsis
     *
     * @param void
     * @return void
     */
    public static function destruct(){
        // flush inner buffer
        ob_end_flush();

        // test for error encounters
        if (count(self::$errors) > 0){
            echo "<h1>Internal Error" . (count(self::$errors) > 1 ? "s" : "") . "</h1>";
            foreach(self::$errors as $error){
                echo "<h3>{$error['type']}</h3>";
                echo "<div>{$error['message']} in {$error['file']} on line {$error['line']}</div>";
            }
            exit;
        }
        
        // compute total execution time for performance tuners
        if ($_ENV['DEBUG']){
            $_ENV['STOP_TIME'] = microtime(true);
            $_ENV['EXECUTION_TIME'] = $_ENV['STOP_TIME'] - $_ENV['START_TIME'];
            self::debug("Execution Time: {$_ENV['EXECUTION_TIME']}s");
        }

        // perform a graceful failure
        if (self::$graceful) self::fail(404, 'Failed to match any routes ' . time());

        // cache output if being saved
        if ($_ENV['CACHE_TIME'] > 0){
            // define the file pair
            $output_file    = $_SERVER['DOCUMENT_ROOT'] . $_SERVER['PATH_INFO'];
            $cache_file     = $_ENV['CACHE_DIR'] . '/files' . $_SERVER['PATH_INFO'];
            $cache_time     = time() + $_ENV['CACHE_TIME'];
            //$cache_file     = $output_file . '.' . $cache_time;

            // ensure writability
            $write = false;
            if (is_writable($output_file) && is_writable($cache_file)){
                $write = true;
            } else {
                $hash = md5($output_file);
                if (touch_recursive($output_file . '.' . $hash . '.tmp') && touch_recursive($cache_file . '.' . $hash . '.tmp')){
                    @unlink($output_file . '.' . $hash . '.tmp');
                    @unlink($cache_file . '.' . $hash . '.tmp');
                    $write = true;
                }
            }
            if ($write){
                // capture the current buffer
                $buffer = ob_get_contents();

                // write the buffer to the output file
                $fp = fopen($output_file, 'wb');
                fwrite($fp, $buffer);
                fclose($fp);

                // write the expiration time to the cache file
                file_put_contents($cache_file, $cache_time);

                // output debug info
                self::debug("Cache Time: " . date('l jS \of F Y h:i:s A T', $cache_time));
            }
        }

        // flush outer buffer and let nature take its course
        ob_end_flush();
        exit;
    }

    /**
     * match a route against this session
     *
     * @param array $route
     * @return boolean
     */
    public static function match(&$route){
        // match the conditions of the route
        $match = true;
        foreach($route['conditions'] as $variable => $condition){
            // prepare the discovered condition to be used as a regexp
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
                    $expression = is_regexp('/' . $condition . '/U') ? '/' . $condition . '/U' : (is_regexp($condition) ? $condition : null);
                    /*
                    if (!preg_match($expression, $_SERVER['PATH_INFO'])){
                        $match = false;
                        break 2;
                    }
                     */
                    if (preg_match($expression, $_SERVER['PATH_INFO'], $matches)){
                        if (count($matches) > 1){
                            // capture backreferences by index and name
                            array_shift($matches);
                            foreach($matches as $k => $v){
                                if (is_numeric($k)){
                                    $route['params'][($k+1)] = $v;
                                } else {
                                    $route['params'][$k] = $v;
                                }
                            }
                        }
                    } else {
                        $match = false;
                        break 2;
                    }
                    break;
                case 'QUERY':
                case 'GET':
                    foreach($condition as $key => $val){
                        $expression = is_regexp('/' . $val . '/U') ? '/' . $val . '/U' : (is_regexp($val) ? $val : null);
                        if (!isset($_GET[$key]) || !preg_match($expression, $_GET[$key])){
                            $match = false;
                            break 3;
                        }
                    }
                    break;
                case 'POST':
                    foreach($condition as $key => $val){
                        $expression = is_regexp('/' . $val . '/U') ? '/' . $val . '/U' : (is_regexp($val) ? $val : null);
                        if (!isset($_POST[$key]) || !preg_match($expression, $_POST[$key])){
                            $match = false;
                            break 3;
                        }
                    }
                    break;
                case 'REQUEST':
                    foreach($condition as $key => $val){
                        $expression = is_regexp('/' . $val . '/U') ? '/' . $val . '/U' : (is_regexp($val) ? $val : null);
                        if (!isset($_REQUEST[$key]) || !preg_match($expression, $_REQUEST[$key])){
                            $match = false;
                            break 3;
                        }
                    }
                    break;
                case 'COOKIE':
                    foreach($condition as $key => $val){
                        $expression = is_regexp('/' . $val . '/U') ? '/' . $val . '/U' : (is_regexp($val) ? $val : null);
                        if (!isset($_COOKIE[$key]) || !preg_match($expression, $_COOKIE[$key])){
                            $match = false;
                            break 3;
                        }
                    }
                    break;
                case 'SERVER':
                    foreach($condition as $key => $val){
                        $expression = is_regexp('/' . $val . '/U') ? '/' . $val . '/U' : (is_regexp($val) ? $val : null);
                        if (!isset($_SERVER[$key]) || !preg_match($expression, $_SERVER[$key])){
                            $match = false;
                            break 3;
                        }
                    }
                    break;
                case 'ENV':
                    foreach($condition as $key => $val){
                        $expression = is_regexp('/' . $val . '/U') ? '/' . $val . '/U' : (is_regexp($val) ? $val : null);
                        if (!isset($_ENV[$key]) || !preg_match($expression, $_ENV[$key])){
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

        return $match;
    }

    /**
     * run application
     *
     * @param string $app
     * @param mixed $conditions
     * @return void
     */
    public static function run($app, $conditions = array()){
        $route = array(
            'application'   => $app,
            'conditions'    => (is_string($conditions) ? array('URI' => $conditions) : $conditions),
            'params'        => array(),
            'rewrite_path'  => null,
            'closure'       => null,
            'cache'         => null
        );

        // reset graceful failure
        self::$graceful = false;

        if (self::match($route)){
            // load the ellipsis config
            include_once "{$_ENV['SCRIPT_ROOT']}/config.php";

            // find and load the app config(s)
            if (is_file("{$_SERVER['DOCUMENT_ROOT']}/.{$app}/config.php")){
                $_ENV['CURRENT'] = $app;
                $_ENV['APPS'][$app] = array(
                    'SCRIPT_ROOT'  => "{$_SERVER['DOCUMENT_ROOT']}/.{$app}",
                    'SCRIPT_LIB'   => "{$_SERVER['DOCUMENT_ROOT']}/.{$app}/lib"
                );
                include_once "{$_SERVER['DOCUMENT_ROOT']}/.{$app}/config.php";
            }

            // process routes
            foreach($_ENV['ROUTES'] as $route){
                // match the conditions of the route
                if (self::match($route)){
                    // this route matched, decide if it should be processed
                    $process = false;
                    if ($route['closure']){
                        // capture the current cache setting (might need to undo)
                        $cache_time = $_ENV['CACHE_TIME'];
                        $_ENV['CACHE_TIME'] = $route['cache'];

                        // process these instructions as a closure
                        $result = $route['closure']($route['params']);
                        if ($result === false){
                            // this closure returned false, forcibly exit
                            exit;
                        } else {
                            // undo cache setting
                            $_ENV['CACHE_TIME'] = $cache_time;

                            if (is_string($result)){
                                // this closure returned a new path, set it and load it
                                $route['rewrite_path'] = $result;
                                $process = true;
                            } else {
                                // this closure has finished, on to the next route
                                continue;
                            }
                        }
                    } else {
                        $process = true;
                    }

                    if ($process){
                        // perform backreference replacements first (if applicable)
                        preg_match_all('/\$\{([^\}]+)\}/', $route['rewrite_path'], $matches);
                        if (count($matches) > 1){
                            foreach($matches[1] as $match){
                                if (is_numeric($match) && isset($route['params'][$match])){
                                    $route['rewrite_path'] = preg_replace('/\$\{' . $match . '\}/', $route['params'][$match], $route['rewrite_path']);
                                } else if (isset($route['params']["{$match}"])){
                                    $route['rewrite_path'] = preg_replace('/\$\{' . $match . '\}/', $route['params'][$match], $route['rewrite_path']);
                                } else {
                                    // leaving a backreference behind will result in an invalid file path
                                    self::fail(500, 'Invalid Path: Closure backreference could not be replaced');
                                }
                            }
                        }

                        // process rewrite_path
                        if (!preg_match('/\$\{/', $route['rewrite_path'])){
                            $_ENV['CACHE_TIME'] = $route['cache'];
                            self::load($route['rewrite_path']);
                        }
                    }
                }
            }

            // process source files
            $source_paths = scandir_recursive($_ENV['APPS'][$_ENV['CURRENT']]['SCRIPT_ROOT'], 'relative');
            foreach($source_paths as $path){
                if ($_SERVER['PATH_INFO'] == $path){
                    $_ENV['CACHE_TIME'] = $route['cache'];
                    self::load($path);
                }
            }

            // all other attempts at routing failed, pave the way for a graceful exit
            self::$graceful = true;
        }
    }

    /**
     * configure the cache seconds for this output buffer
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
     * expire all currently cached output buffers by expunging them
     *
     * @param void
     * @return void
     */
    public static function expire(){
        $now = time();
        $expire_files = scandir_recursive($_ENV['CACHE_DIR'] . '/files');
        foreach($expire_files as $expire_file){
            $expire_time = file_get_contents($expire_file);
            if ($now >= $expire_time){
                $cached_file = $_SERVER['DOCUMENT_ROOT'] . preg_replace('/^.*\/files/', '', $expire_file);
                if (@unlink($cached_file)){
                    @unlink($expire_file);
                }
            }
        }
    }

    /**
     * add a route configuration to be evaluated against the current output buffer
     *
     * @param mixed $conditions (uri_param | conditions)
     * @param mixed $instruction (rewrite_path | closure)
     * @param integer $cache (seconds)
     * @return void
     */
    public static function route($conditions, $instruction, $cache = null){
        $conditions     = is_string($conditions) ? array('URI' => $conditions) : $conditions;
        $rewrite_path   = is_string($instruction) ? $instruction : null;
        $closure        = is_object($instruction) ? $instruction : null;
        $_ENV['ROUTES'][] = array(
            'application'   => $_ENV['CURRENT'],
            'conditions'    => $conditions,
            'params'        => array(),
            'rewrite_path'  => $rewrite_path,
            'closure'       => $closure,
            'cache'         => $cache
        );
    }

    /**
     * load the destination resource and exit
     *
     * @param string $path
     * @return void
     */
    public static function load($path){
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
        if (is_file($_ENV['APPS'][$_ENV['CURRENT']]['SCRIPT_ROOT'] . $path)){
            if (preg_match('/\.php$/', $path)){
                include $_ENV['APPS'][$_ENV['CURRENT']]['SCRIPT_ROOT'] . $path;
            } else {
                $fp = fopen($_ENV['APPS'][$_ENV['CURRENT']]['SCRIPT_ROOT'] . $path, 'rb');
                header("Content-Length: " . filesize($_ENV['APPS'][$_ENV['CURRENT']]['SCRIPT_ROOT'] . $path));
                fpassthru($fp);
            }
        } else {
            self::fail(404);
        }

        // no more computing beyond this point
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
        if (isset($_ENV['HTTP_CODE'][$code])){
            self::debug("{$code} - {$message}", $_ENV['HTTP_CODE'][$code]);
            if (isset($_ENV['HTTP_CODE'][$code]['path'])){
                if (is_file($_ENV['APPS'][$_ENV['CURRENT']]['SCRIPT_ROOT'] . '/' . $_ENV['HTTP_CODE'][$code]['path'])){
                    $PARAMS['ERROR_CODE'] = $code;
                    $PARAMS['ERROR_TITLE'] = $_ENV['HTTP_CODE'][$code]['title'];
                    $PARAMS['ERROR_DESCRIPTION'] = $_ENV['HTTP_CODE'][$code]['description'];
                    $PARAMS['ERROR_TRANSLATION'] = $_ENV['HTTP_CODE'][$code]['translation'];
                    include $_ENV['APPS'][$_ENV['CURRENT']]['SCRIPT_ROOT'] . '/' . $_ENV['HTTP_CODE'][$code]['path'];
                    exit;
                }
            } else {
                echo "<h1>{$code} - {$_ENV['HTTP_CODE'][$code]['title']}</h1>";
                echo "<div onmouseover=\"this.innerHTML='" . preg_quote($_ENV['HTTP_CODE'][$code]['translation'], "'") . "';\" onmouseout=\"this.innerHTML='" . preg_quote($_ENV['HTTP_CODE'][$code]['description'], "'") . "';\">{$_ENV['HTTP_CODE'][$code]['description']}</div>";
            }
        } else {
            self::debug("{$code} - {$message}");
            echo "<h1>{$code} - Unknown Error</h1>";
            echo "<div>{$message}</div>";
        }
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
                ChromePhp::log("{$_SERVER['PATH_INFO']}: {$message}", $data);
            } else {
                ChromePhp::log("{$_SERVER['PATH_INFO']}: {$message}");
            }
        }
    }
}

/**
 * trigger static constructor
 */
Ellipsis::construct();


