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

// load ellipsis
require_once __DIR__ . '/ellipsis.php';

// set mysql connecton credentials
$_ENV['MYSQL_HOST']    = 'localhost';
$_ENV['MYSQL_NAME']    = 'ellipsis';
$_ENV['MYSQL_USER']    = 'root';
$_ENV['MYSQL_PASS']    = '';

// set error handling routes
$_ENV['ROUTE_404']    = '404.php';
$_ENV['ROUTE_500']    = '500.php';

// set custom routes
//Ellipsis::route(array('URI' => '^\/(.*)\.(?<ext>.*)$'), '/mypage.php?original=${pagename}');
Ellipsis::route(array('URI' => '^\/hello\/(?<name>.*)\.(?<ext>.*)$'), function($params){
    Ellipsis::debug('route', $params);
    return '/${name}.php';
}, 60);
/*Ellipsis::route(array('URI' => '^\/(?<pagename>.*)\.(?<ext>.*)$'), function($r){
    return '/mypage.php';
});
 */
//Ellipsis::route(array('URI' => '\/(custom)\..*'), '/mydir/$1.php');

/*
Ellipsis::route(array('URI' => '(.*)\.(php)'), function(){
    $_ENV['FRAMEWORK'] = 'Ellipsis 1';
});
Ellipsis::route(array('URI' => '(?<page>.*)\.(?<extension>php)'), function(){
    Ellipsis::debug(array('second closure', $_ENV['FRAMEWORK']));
    $_ENV['FRAMEWORK'] = 'Ellipsis 2';
});
 */

/*
Ellipsis::route(array('URI' => '\/hello\/(<?id>[^\/]+)'), 'hello.php');
Ellipsis::route(array('URI' => '\/brand\/(<?brandid>[^\/]+)\/product\/<?productid>'), 'product.php');
Ellipsis::route(array(
    'URI' => '\/firefox\.php',
    'SERVER' => array(
        'HTTP_HOST'         => 'local\.ellipsis\.com',
        'HTTP_USER_AGENT'   => '.*Firefox.*'
    )
), '/info.php');
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
