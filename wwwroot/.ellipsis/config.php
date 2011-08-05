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


/**
 * default route/cache instructions
 */
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

// cache the favorite icon
//Ellipsis::route('^\/favicon\.ico$/', '/favicon.ico', 31536000);

?>
