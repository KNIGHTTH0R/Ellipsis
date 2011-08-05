<?php

/**
 * Ellipsis App Config
 *
 * Configure your web application in this file (i.e. database credentials, 
 * custom routes, custom models, custom library values, etc.)
 */

/**
 * set some preferred system defaults
 */
date_default_timezone_set('America/New_York');

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

// COOKIE examples

// Check if lang cookie value is empty
Ellipsis::route(array('COOKIE'=>array(
	'lang'=>'^\s*$')), function(){
		// create cookie lang value
		setcookie('lang','UK');
		Ellipsis::debug('setting lang cookie to UK');
});

Ellipsis::route(array('COOKIE'=>array(
	'lang'=>'')), function(){
		// fetch lang value
		Ellipsis::debug('Hello ' . $_COOKIE['lang']);
});


// METHOD examples

// Test if the method is Get
Ellipsis::route(array('METHOD'=>'GET'), function(){
	echo "GETTING";
});

// Test if the method is Post
Ellipsis::route(array('METHOD'=>'POST'), function(){
	echo "POSTING";
	Ellipsis::debug('Posted values: ' . $_POST['lang'] . ' language detected');
});

// URI examples

// re-route a URI
Ellipsis::route('^\/greetings\.php$', '/hello.php');

// re-route a URI and cache it's results for 1 minute
Ellipsis::route('^\/1minute\.php$', '/hello.php', 60);

// re-route a URI using an indexed backreference
// note: if /{$1}.php does not exist, this will throw a 404, try bob
Ellipsis::route('^\/indexed\/([^\.]+)\.php$', '/${1}.php');

// re-route a URI using a named backreference
// note: if /{$name}.php does not exist, this will throw a 404, try sally
Ellipsis::route('^\/named\/(?<name>[^\.]+)\.php$', '/${name}.php');

// re-route a URI using a closure
// note: if /{$name}.php does not exist, this will throw a 404, try sally
Ellipsis::route('^\/closured\/(?<name>[^\.]+)\.php$', function($params){
    // in this example, we used $params['name'] because it was setup as a
    // backreference in the regular expression, but they are also available as
    // indexed results (i.e. $params[0])
    return "/hello/{$params['name']}.php";
});

// respond to a URI within the closure and quit
Ellipsis::route('^\/testinfo\.php$', function($params){
    // this is normal PHP, do whatever you want
    phpinfo();

    // returning false within a closure will halt route processing and exit
    // note: you can also just 'exit;' if you prefer
    return false;
});

// set an environment variable based on a matched URI and continue
Ellipsis::route('^\/admin\/(?<admin_section>[^\.]+)\/', function($params){
    // NOTE: processing will continue to the next route as long as the closure
    // doesn't return a new uri path or false, so routes can be stacked in this
    // way to manipulate the environment however you see fit
    Ellipsis::debug('testing');
    $_ENV['ADMIN_SECTION'] = $params['admin_section'];
});

// test the previously set environment variable
Ellipsis::route('^\/admin\/[^\/]+\/test\.php$', function($params){
    echo "Testing ADMIN_SECTION = {$_ENV['ADMIN_SECTION']}";
    exit;
});

// QUERY/GET and POST examples

// Only grab the value of 'lang' from a query/get string
Ellipsis::route(array('QUERY'=>array(
	'lang'=>'.*')), function(){
	Ellipsis::debug('Get values: ' . $_GET['lang'] . ' language detected');
});

// Check if a coupon code passed in is valid (add ?coupon)
Ellipsis::route(array('GET'=>array(
	'coupon'=>'.*')), function(){
	Ellipsis::debug('Coupon ' . $_GET['coupon'] . ' submitted');
	$coupons = array('1234567','AB346523','JUNESALE');
	echo "Your coupon " . $_GET['coupon'];
	echo in_array($_GET['coupon'],$coupons) ?  " is valid!" : " has expired!";	
});

// Only grab the value of 'cheeseburger' from a post value using a non-regex value holder (accepts all values, including empty)
Ellipsis::route(array('POST'=>array(
	'cheeseburger'=>'')), function(){
	Ellipsis::debug($_POST['cheeseburger'] . ' cheeseburger detected');
});

// Grab only empty values of 'lang' from a get submission
Ellipsis::route(array('GET'=>array(
	'lang'=>'^\s*$')), function(){
	Ellipsis::debug('No language value set from GET, get from cookie or post');
});

//TODO:SERVER examples

//TODO:ENV examples

//TODO:combination example


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

?>

