<?php

/**
 * Ellipsis Config
 *
 * Configure the Ellipsis framework in this file (i.e. default routes, default
 * library values, etc.)
 *
 * @author Toby Miller <tobius.miller@gmail.com>
 * @license MIT <http://www.opensource.org/licenses/mit-license.php>
 * @package ellipsis
 */

require_once __DIR__ . '/ellipsis.php';

/**
 * set some preferred system defaults
 */
date_default_timezone_set('America/New_York');

/**
 * default route/cache instructions
 */

// prevent access to known private locations
Ellipsis::route('^\/config\.php$', function(){
    Ellipsis::fail(404);
}, 31536000);
Ellipsis::route('^\/lib\/.*$', function(){ 
    Ellipsis::fail(404); 
}, 31536000);

// enable some common debugging routes
// (note: apps should override these in production settings)
Ellipsis::route('^\/info\.php$', function(){ 
    phpinfo(); 
    return false;
}, 3600);

// run application
// note: app/config.php gets prepended to run() if present
Ellipsis::run();

?>
