<?php

// add debug type to PHP native error types
define('E_DEBUG', 3);

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
     * dump PHP and/or Ellipsis error messages
     *
     * @param void
     * @return void
     */
    private static function dump_error_messages(){
        echo "<h1>Internal Error" . (count($_ENV['ERRORS']) > 1 ? "s" : "") . "</h1>";
        foreach($_ENV['ERRORS'] as $error){
            echo "<h3>{$error['type']}</h3>";
            echo "<div>{$error['message']} in {$error['file']} on line {$error['line']}</div>";
        }
    }

    /**
     * dump Ellipsis debug data
     *
     * @param string $message
     * @param mixed $data
     * @return void
     */
    private static function dump_debug_messages($message, $data = null){
    }

    /**
     * compare a route filter to the current session
     *
     * @param array $filter
     * @return boolean
     */
    private static function route_compare(array &$filter){
        // determine if route filter and session match
        $match = true;
        foreach($filter['conditions'] as $variable => $condition){
            // prepare the discovered condition to be used as a regexp
            switch($variable){
                case 'METHOD':
                    // compare a specific request method (default is GET)
                    if ($condition != $_SERVER['REQUEST_METHOD']){
                        $match = false;
                        break 2;
                    }
                    break;
                case 'URI':
                    // compare a specific URI pattern
                    // @todo: confirm that this new URI (non-regexp) code works as expected
                    $expression = is_regexp('/' . $condition . '/U') ? '/' . $condition . '/U' : (is_regexp($condition) ? $condition : null);
                    if (!($expression == null && $condition == $_SERVER['PATH_INFO'])){
                        if (preg_match($expression, $_SERVER['PATH_INFO'], $matches)){
                            if (count($matches) > 1){
                                // capture backreferences by index and name
                                array_shift($matches);
                                foreach($matches as $k => $v){
                                    if (is_numeric($k)){
                                        $filter['params'][($k+1)] = $v;
                                    } else {
                                        $filter['params'][$k] = $v;
                                    }
                                }
                            }
                        } else {
                            $match = false;
                            break 2;
                        }
                    } 
                    break;
                case 'QUERY':
                case 'GET':
                    echo "GET";
                    // compare get variables (aka query string values)
                    // @todo: confirm that this new GET (non-regexp) code works as expected
                    foreach($condition as $key => $val){
                        $expression = is_regexp('/' . $val . '/U') ? '/' . $val . '/U' : (is_regexp($val) ? $val : null);
                        if (!($expression == null && isset($_GET[$key]) && $condition == $_GET[$key])){
                            if (!isset($_GET[$key]) || !preg_match($expression, $_GET[$key])){
                                $match = false;
                                break 3;
                            }
                        }
                    }
                    break;
                case 'POST':
                    // compare post variables
                    foreach($condition as $key => $val){
                        $expression = is_regexp('/' . $val . '/U') ? '/' . $val . '/U' : (is_regexp($val) ? $val : null);
                        if (!isset($_POST[$key]) || !preg_match($expression, $_POST[$key])){
                            $match = false;
                            break 3;
                        }
                    }
                    break;
                case 'REQUEST':
                    // compare request variables
                    foreach($condition as $key => $val){
                        $expression = is_regexp('/' . $val . '/U') ? '/' . $val . '/U' : (is_regexp($val) ? $val : null);
                        if (!isset($_REQUEST[$key]) || !preg_match($expression, $_REQUEST[$key])){
                            $match = false;
                            break 3;
                        }
                    }
                    break;
                case 'COOKIE':
                    // compare cookie variables
                    foreach($condition as $key => $val){
                        $expression = is_regexp('/' . $val . '/U') ? '/' . $val . '/U' : (is_regexp($val) ? $val : null);
                        if (!isset($_COOKIE[$key]) || !preg_match($expression, $_COOKIE[$key])){
                            $match = false;
                            break 3;
                        }
                    }
                    break;
                case 'SERVER':
                    // compare server variables
                    foreach($condition as $key => $val){
                        $expression = is_regexp('/' . $val . '/U') ? '/' . $val . '/U' : (is_regexp($val) ? $val : null);
                        if (!isset($_SERVER[$key]) || !preg_match($expression, $_SERVER[$key])){
                            $match = false;
                            break 3;
                        }
                    }
                    break;
                case 'ENV':
                    // compare environment variables
                    foreach($condition as $key => $val){
                        $expression = is_regexp('/' . $val . '/U') ? '/' . $val . '/U' : (is_regexp($val) ? $val : null);
                        if (!isset($_ENV[$key]) || !preg_match($expression, $_ENV[$key])){
                            $match = false;
                            break 3;
                        }
                    }
                    break;
                default:
                    // unrecognized variable type
                    $match = false;
                    break 2;
            }
        }

        // return result
        return $match;
    }

    /**
     * cache the current buffer
     *
     * @param int $seconds
     * @return void
     */
    private static function cache_buffer($seconds = null){
        // define the buffer website file
        $website_file = $_SERVER['DOCUMENT_ROOT'] . $_SERVER['PATH_INFO'];

        // define the buffer expiration file
        $expiration_file = "{$_ENV['WEBSITE_CACHE_ROOT']}/expirations{$_SERVER['PATH_INFO']}";

        // define the buffer expiration timestamp
        $expiration_time = time() + $_ENV['CACHE_TIME'];

        // test writability
        $write = false;
        if (is_writable($website_file) && is_writable($expiration_file)){
            $write = true;
        } else {
            $hash = md5($website_file);
            if (touch_recursive("{$website_file}.{$hash}.tmp") && touch_recursive("{$expiration_file}.{$hash}.tmp")){
                @unlink("{$website_file}.{$hash}.tmp");
                @unlink("{$expiration_file}.{$hash}.tmp");
                $write = true;
            }
        }

        // write this buffer to cache
        if ($write){
            // capture the current buffer
            $buffer = ob_get_contents();

            // write the buffer to the website file
            $fp = fopen($website_file, 'wb');
            fwrite($fp, $buffer);
            fclose($fp);

            // write the expiration time to the expiration file
            file_put_contents($expiration_file, $expiration_time);

            // output debug information
            self::debug("Cache Time: " . date('l jS \of F Y h:i:s A T', $cache_time));
        }
    }

    /**
     * initialize Ellipsis
     *
     * @param void
     * @return void
     */
    public static function initialize(){
        // set default environment variables
        $_ENV = array_merge($_ENV, 
            array(
                'APPS'              => (isset($_ENV['APPS']) && is_array($_ENV['APPS'])) ? $_ENV['APPS'] : array(),
                'CACHE_TIME'        => null,
                'CURRENT'           => (isset($_ENV['CURRENT'])) ? $_ENV['CURRENT'] : null,
                'DEBUG'             => true,
                'DEBUG_PROXY'       => null,
                'ERRORS'            => (isset($_ENV['ERRORS']) && is_array($_ENV['ERRORS'])) ? $_ENV['ERRORS'] : array(),
                'EXECUTION_TIME'    => null,
                'GRACEFUL'          => false,
                'HTTP_CODE'         => array(
                    '400' => array(
                        'title'         => 'Bad Request',
                        'description'   => 'This is not a valid HTTP request',
                        'translation'   => 'Whatever in the hell you\'re doing, stop it!'
                    ),
                    '401' => array(
                        'title'         => 'Unauthorized',
                        'description'   => 'User authentication is required before continuing',
                        'translation'   => 'You need to take mo out for lobster first'
                    ),
                    '402' => array(
                        'title'         => 'Payment Required',
                        'description'   => 'User payment is required before continuing',
                        'translation'   => 'Give me whatever\'s in your wallet and we\'ll see'
                    ),
                    '403' => array(
                        'title'         => 'Forbidden',
                        'description'   => 'User access is denied',
                        'translation'   => 'Losers go home!'
                    ),
                    '404' => array(
                        'title'         => 'File Not Found',
                        'description'   => 'Requested URI was not found on this server',
                        'translation'   => 'Didn\'t find what you were looking for? Hmm, maybe you should try another URL'
                    ),
                    '405' => array(
                        'title'         => 'Method Not Allowed',
                        'description'   => 'Requested method is not supported on this server',
                        'translation'   => 'GET, POST, HEAD, those are all valid options ... So get with the program and stop whatever in the hell you\'re doing'
                    ),
                    '406' => array(
                        'title'         => 'Not Acceptable',
                        'description'   => 'Requested URI is of a Content-Type that your browser cannot accept',
                        'translation'   => 'Your browser pretty much sucks because it can\'t handle this Content-Type ... maybe it\'s finally time to upgrade'
                    ),
                    '407' => array(
                        'title'         => 'Proxy Authentication Required',
                        'description'   => 'Proxy authentication is required before continuing',
                        'translation'   => 'Haha! Your IT department doesn\'t trust you to use this proxy server ... I hope you remembered your username and password'
                    ),
                    '408' => array(
                        'title'         => 'Request Timeout',
                        'description'   => 'Server timed out waiting for a URI request from the user',
                        'translation'   => 'Sorry, I can\'t hold my breath for that long. Hit refresh and I\'ll take a deep breath and try again.'
                    ),
                    '409' => array(
                        'title'         => 'Conflict',
                        'description'   => 'Server refused to service the request because of a possible conflict',
                        'translation'   => 'Sorry, this is a conflict of interest for me, don\'t make me choose'
                    ),
                    '410' => array(
                        'title'         => 'Gone',
                        'description'   => 'Requested URI was removed and no forwarding address is known',
                        'translation'   => 'Well, this is a little embarrassing, we seem to have misplaced what you\'re looking for ... oops?'
                    ),
                    '411' => array(
                        'title'         => 'Length Required',
                        'description'   => 'Request message was without a required Content-Length',
                        'translation'   => 'Hmm, something isn\'t right here ... is that an anvil or a painted styrofoam block shaped like an anvil?'
                    ),
                    '412' => array(
                        'title'         => 'Precondition Failed',
                        'description'   => 'Request did not pass servers definition of a valid HTTP request',
                        'translation'   => 'Sorry, me no speako hacko ... seriously, you know we log this stuff right?'
                    ),
                    '413' => array(
                        'title'         => 'Request Entity Too Large',
                        'description'   => 'Request entity is larger than the server is able or willing to process',
                        'translation'   => 'Sorry ma\'am but I\'m going to have to ask you to pay for two seats'
                    ),
                    '414' => array(
                        'title'         => 'Request-URI Too Long',
                        'description'   => 'Request-URI is longer than the server is willing to interpret',
                        'translation'   => 'Ooh! Ooh! You should checkout this really cool service called TinyURL ... it could change your world'
                    ),
                    '415' => array(
                        'title'         => 'Unsupported Media Type',
                        'description'   => 'Server refused to service request for an unsupported format',
                        'translation'   => 'Are you seriously trying to feed me onions? Because I distinctly remember telling you how allergic I am!'
                    ),
                    '416' => array(
                        'title'         => 'Requested Range Not Satisfiable',
                        'description'   => 'Server refused to service a Range request-header because the range being requested is not valid',
                        'translation'   => 'Sorry, that\'s not how many fingers I was holding up, try again'
                    ),
                    '417' => array(
                        'title'         => 'Expectation Failed',
                        'description'   => 'Server refused to service an Expect request-header because the server would have been unable to meet the expectation',
                        'translation'   => 'Woah! Woah! Woah! I actually thought I knew HTTP, but you are blowing my mind! I need to go lay down for a minute.'
                    ),
                    '500' => array(
                        'title'         => 'Internal Server Error',
                        'description'   => 'Server encountered an unexpected condition',
                        'translation'   => 'Oops, that\'s my bad ... I hope the IT guys are seeing this'
                    ),
                    '501' => array(
                        'title'         => 'Not Implemented',
                        'description'   => 'Server does not support the functionality required to fulfill the request',
                        'translation'   => 'OPTIONS, HEAD, GET, POST, PUT, DELETE, TRACE, CONNECT, those I understand ... why don\'t you try again'
                    ),
                    '502' => array(
                        'title'         => 'Bad Gateway',
                        'description'   => 'Server received an invalid response from the upstream server while trying to fulfill your request as an intermediate gateway or proxy',
                        'translation'   => 'I tried to get this for you, but this other guy\'s being a pain right now, hit me up again later'
                    ),
                    '503' => array(
                        'title'         => 'Service Unavailable',
                        'description'   => 'Server is unable to fulfill requests due to either a temporary server overload or a maintenance task',
                        'translation'   => 'Sorry, but I\'ve just got way too many things going on right now. Give me a minute to compose myself'
                    ),
                    '504' => array(
                        'title'         => 'Gateway Timeout',
                        'description'   => 'Server received a timeout response from the upstream server while trying to fulfill your request as an intermediate gateway or proxy',
                        'translation'   => 'You know that other guy that I was getting that thing from for you? Well, he said I was taking too long, hit me up again later'
                    ),
                    '505' => array(
                        'title'         => '505',
                        'description'   => 'Server does not support the HTTP protocol version used in this request',
                        'translation'   => 'That sounds like a really cool new version to "experiment" with, just not in public yet. Let\'s go slow, I\'m worth it!'
                    )
                ),
                'PARAMS'        => array(),
                'PATH_INFO'     => preg_replace('/\?.*$/', '', $_SERVER['REQUEST_URI']),
                'RUN'           => ((isset($_ENV['RUN']) && is_array($_ENV['RUN'])) ? $_ENV['RUN'] : array()),
                'START_TIME'    => (isset($_ENV['START_TIME']) && $_ENV['START_TIME'] != null) ? $_ENV['START_TIME'] : microtime(true),
                'STOP_TIME'     => null
            )
        );

        // start outer buffer
        ob_start();

        // start inner buffer
        ob_start(array('Ellipsis', 'buffer_handler'));

        // set error handler
        set_error_handler(array('Ellipsis', 'error_handler'), E_ALL);

        // register destruct method
        register_shutdown_function(array('Ellipsis', 'terminate'));
    }

    /**
     * manage inner buffer with Ellipsis
     * 
     * @param string $buffer
     * @param integer $mode
     * @return void
     */
    public static function buffer_handler($buffer, $mode){
        // find uncatchable errors
        if (preg_match('/<\/b>:\s*(.+) in <b>(.+)<\/b> on line <b>(.+)<\/b>/U', $buffer, $matches)){
            Ellipsis::error_handler(E_ERROR, $matches[1], $matches[2], $matches[3]);
        }

        // empty buffer (if errors)
        if (count($_ENV['ERRORS']) > 0){
            $buffer = '';
        }

        // return applicable buffer
        return $buffer;
    }

    /**
     * manage both PHP errors and Ellipsis application errors
     *
     * @param integer $error_number
     * @param string $error_message
     * @param string $error_file
     * @param integer $error_line
     * @param array $error_context
     * @return void
     */
    public static function error_handler($error_number, $error_message, $error_file = null, $error_line = null, $error_context = null){
        $reporting = ini_get('error_reporting');
        if(!($reporting & $error_number)) return false;

        $error_types = array(
            E_ERROR             => 'Fatal Error',
            E_WARNING           => 'Warning',
            E_DEBUG             => 'Debug',
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
        $_ENV['ERRORS'][] = $error;
        return false;
    }

    /**
     * terminate Ellipsis
     *
     * @param void
     * @return void
     */
    public static function terminate(){
        // flush inner buffer
        ob_end_flush();

        // test for errors
        if (count($_ENV['ERRORS']) > 0){
            self::dump_error_messages();
            exit;
        }

        // collect debug data (if debug is set)
        if ($_ENV['DEBUG']){
            // calculate execution time
            $_ENV['STOP_TIME'] = microtime(true);
            $_ENV['EXECUTION_TIME'] = $_ENV['STOP_TIME'] - $_ENV['START_TIME'];
            self::debug("Execution Time: {$_ENV['EXECUTION_TIME']}s");

            // calculate memory usage
            $units = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
            $memory = memory_get_peak_usage(true);
            $_ENV['PEAK_MEMORY_USAGE'] = round($memory / pow(1024, ($i = floor(log($memory, 1024)))), 2) . ' ' . $units[$i];
            self::debug("Peak Memory Usage: {$_ENV['PEAK_MEMORY_USAGE']}");
        }

        // perform a graceful failure (if graceful is set)
        // @todo: This may be completely unnecessary now that we're allowing app stacking
        if ($_ENV['GRACEFUL']){
            if (ob_get_contents() == ''){
                self::fail(404, 'Failed to match any routes ' . time());
            }
        }

        // cache the output (if cache is set)
        if ($_ENV['CACHE_TIME'] > 0){
            self::cache_buffer($_ENV['CACHE_TIME']);
        }

        // flush outer buffer
        ob_end_flush();

        // real exit
        exit;
    }

    /**
     * execute Ellipsis
     *
     * @param void
     * @return void
     */
    public static function execute(){
        // process routes
        foreach($_ENV['ROUTES'] as $route){
            // reset graceful failure
            $_ENV['GRACEFUL'] = false;

            // set current context identity
            $_ENV['CURRENT'] = $route['application'];

            // process routes
            self::process_route($route);

            // set graceful to true (if nothing else took)
            $_ENV['GRACEFUL'] = true;
        }

        // process source files
        $reversed = array_reverse(array_keys($_ENV['APPS']));
        foreach($reversed as $app_name){
            $htdocs_root = "{$_ENV['APPS'][$app_name]['APP_SRC_ROOT']}/htdocs";
            if (is_dir($htdocs_root)){
                $htdocs_paths = scandir_recursive($htdocs_root, 'relative');
                foreach($htdocs_paths as $path){
                    if ($_SERVER['PATH_INFO'] == $path){
                        // load path
                        self::load_path($path);
                    }
                }
            }
        }
    }

    /**
     * process route
     *
     * @param array $route
     * @return void
     */
    private static function process_route($route){
        // compare the route filter to the current session
        if (self::route_compare($route)){
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
                    self::load_path($route['rewrite_path']);
                }
            }
        }
    }

    /**
     * cleanup expired cache buffers
     *
     * @param void
     * @return void
     */
    public static function clean_buffers(){
        // locate cached buffers that have expired and delete them
    }

    /**
     * add a routing rule to the current application
     *
     * @param mixed $conditions (uri_param | conditions)
     * @param mixed $instruction (rewrite_path | closure)
     * @param integer $cache (seconds)
     * @return void
     */
    public static function add_route($conditions, $instruction, $cache = null, $override = false){
        // parse route
        $route = null;
        $conditions     = is_string($conditions) ? array('URI' => $conditions) : $conditions;
        $rewrite_path   = is_string($instruction) ? $instruction : null;
        $closure        = is_object($instruction) ? $instruction : null;
        $override       = ($override !== false) ? true : false;
        $route = array(
            'application'   => $_ENV['CURRENT'],
            'hash'          => md5(serialize($conditions)),
            'conditions'    => $conditions,
            'params'        => array(),
            'rewrite_path'  => $rewrite_path,
            'closure'       => $closure,
            'cache'         => $cache,
            'override'      => $override
        );

        // record route
        if ($_ENV['CURRENT'] != null){
            $_ENV['APPS'][$_ENV['CURRENT']]['APP_ROUTES'][] = $route;
        } else {
            $_ENV['WEBSITE_ROUTES'][] = $route;
        }
    }

    /**
     * load the destination path resource and exit
     *
     * @param string $path
     * @return void
     */
    public static function load_path($path){
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

        // output content type header
        header("Content-Type: $mime_type");

        // load path resource
        $htdocs_root = "{$_ENV['APPS'][$_ENV['CURRENT']]['APP_SRC_ROOT']}/htdocs";
        if (is_file("{$htdocs_root}{$path}")){
            if (preg_match('/\.php$/', $path)){
                include "{$htdocs_root}{$path}";
            } else {
                $fp = fopen("{$htdocs_root}{$path}", 'rb');
                header('Content-Length: ' . filesize("{$htdocs_root}{$path}"));
                fpassthru($fp);
            }
        } else {
            self::fail(404);
        }

        // exit
        exit;
    }

    /**
     * record debug statement
     *
     * @param string $message
     * @param mixed $data
     * @return boolean
     */
    public static function debug($message, $data = null){
        if ($_ENV['DEBUG']){
            if ($data != null){
                ChromePhp::log("{$_SERVER['PATH_INFO']}: {$message}", $data);
            } else {
                ChromePhp::log("{$_SERVER['PATH_INFO']}: {$message}");
            }
        }
    }

    /**
     * record an HTTP failure
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

} Ellipsis::initialize();

