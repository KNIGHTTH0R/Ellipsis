<?php

/**
 * Ellipsis Config
 *
 * Configure your web application in this file (i.e. database credentials, 
 * custom routes, custom models, default library values, etc.
 *
 * @author Toby Miller <tobius.miller@gmail.com>
 * @license MIT <http://www.opensource.org/licenses/mit-license.php>
 * @package ellipsis
 */

require_once __DIR__ . '/ellipsis.php';

/**
 * error paths
 */
$_ENV['ERROR_404']    = '404.php';
$_ENV['ERROR_500']    = '500.php';

/**
 * mysql connection
 */
$_ENV['MYSQL_HOST']    = 'localhost';
$_ENV['MYSQL_NAME']    = 'ellipsis';
$_ENV['MYSQL_USER']    = 'root';
$_ENV['MYSQL_PASS']    = '';

/**
 * custom route/cache instruction
 */

// re-route a URI
Ellipsis::route('^\/foo\.php$', function(){
    Foo::bar('foo bar yo');
});

// re-route a URI
Ellipsis::route('^\/greetings\.php$', '/hello.php');

// re-route a URI and cache it's results
Ellipsis::route('^\/guest\.php$', '/hello.php', 120);

// re-route a URI using a backreference
Ellipsis::route('^\/greetings\/([^\.]+)\.php$', '/hello/${1}.php');

// re-route a URI using a named backreference
Ellipsis::route('^\/greetings\/(?<name>[^\.]+)\.php$', '/hello/${name}.php');

// re-route a URI using a closure
Ellipsis::route('^\/greetings\/(?<name>[^\.]+)\.php$', function($params){
    // in this example, we used $params['name'] because it was setup as a
    // backreference in the regular expression, but they are also available as
    // indexed results (i.e. $params[0])
    return "/hello/{$params['name']}.php";
});

// respond to a URI within the closure and quit
//Ellipsis::route(array('URI' => '^\/info\.php$'), function($params){
Ellipsis::route('^\/info\.php$', function($params){
    // this is normal PHP, do whatever you want
    phpinfo();

    // returning false within a closure will halt route processing and exit
    // note: you can also call "exit;" if you prefer
    return false;
}, 3600);

// set an environment variable based on a matched URI and continue
Ellipsis::route('^\/admin\/', function($params){
    // NOTE: processing will continue to the next route as long as the closure
    // doesn't return a new uri path or false, so routes can be stacked in this
    // way to manipulate the environment however you see fit
    $_ENV['MODE'] = 'admin';
});

/*
Ellipsis::route(array('URI' => '(.*)\.(php)'), function(){
    $_ENV['FRAMEWORK'] = 'Ellipsis 1';
});
Ellipsis::route(array('URI' => '(?<page>.*)\.(?<extension>php)'), function(){
    Ellipsis::debug(array('second closure', $_ENV['FRAMEWORK']));
    $_ENV['FRAMEWORK'] = 'Ellipsis 2';
});
 */

// set meta model variables
$_ENV['META_PREFIX']  = 'e_';
$_ENV['META_FIELDS']   = array(
    'email' => array(
        'type'      => 'string',
        'validate'  => '/^[^@]@\.[a-z]+$/',
        'example'   => 'tobius.miller@gmail.com'
    ),
    'phone' => array(
        'type'      => 'string',
        'sanitize'  => array('/[^0-9]+/', ''),
        'validate'  => '/^[0-9]{10}$/',
        'render'    => array('/^([0-9]{3})([0-9]{3})([0-9]{4})$/', '$1-$2-$3'),
        'example'   => '614-421-2888'
    ),
    'state' => array(
        'type'      => 'string',
        'validate'  => '/^[A-Z]{2}$/i',
        'render'    => 'strtouppercase',
        'example'   => 'OH'
    ),
    'zip' => array(
        'type'      => 'string',
        'sanitize'  => array('/[^0-9]+/', ''),
        'validate'  => '/^[0-9]{5}([0-9]{4})?$/',
        'render'    => array('/^([0-9]{5})([0-9]{4})?$/', '$1-$2'),
        'example'   => '43215'
    )
);
$_ENV['META_OBJECTS']  = array(
    'owners' => array(
        'name' => array(
            'type'      => 'string',
            'required'  => true
        ),
        'phone' => array(
            'type'      => 'phone',
            'required'  => true
        ),
        'email' => array(
            'type'      => 'email',
            'required'  => true
        )
    ),
    'houses' => array(
        'parent' => array(
            'key'       => 'owner',
            'required'  => true
        ),
        'address' => array(
            'type'      => 'string',
            'required'  => true
        ),
        'city' => array(
            'type'      => 'string',
            'required'  => true
        ),
        'state' => array(
            'type'      => 'state',
            'required'  => true
        ),
        'zip' => array(
            'type'      => 'zip',
            'required'  => true
        ),
        'phone' => array(
            'type'      => 'phone',
            'required'  => false,
            'default'   => null
        )
    ),
    'rooms' => array(
        'parent' => array(
            'key'       => 'house',
            'required'  => true
        ),
        'name' => array(
            'type'      => 'string',
            'required'  => true
        ),
        'square_feet' => array(
            'type'      => 'integer',
            'required'  => true
        ),
        'window_count' => array(
            'type'      => 'integer',
            'required'  => false,
            'default'   => 0
        )
    ),
    'lights' => array(
        'parent' => array(
            'key'       => 'room',
            'required'  => true
        ),
        'watts' => array(
            'type'      => 'integer',
            'required'  => true
        ),
        'lumens' => array(
            'type'      => 'integer',
            'required'  => true
        )
    ),
    'attributes' => array(
        'parents' => array(
            array(
                'key'       => 'house',
                'required'  => false
            ),
            array(
                'key'       => 'room',
                'required'  => false
            ),
            array(
                'key'       => 'light',
                'required'  => false
            )
        ),
        'name' => array(
            'type'      => 'string',
            'required'  => true
        ),
        'value' => array(
            'type'      => 'value',
            'required'  => false,
            'default'   => null
        )
    )
);

// run application
Ellipsis::run();

?>
