<?php

/**
 * Ellipsis Bootstrap
 *
 * "Kick the tires and light the fires, big daddy."
 *
 * @author Toby Miller <tobius.miller@gmail.com>
 * @license MIT <http://www.opensource.org/licenses/mit-license.php>
 * @package ellipsis
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/.ellipsis/ellipsis.php';

// run application(s)
Ellipsis::run('default');
//Ellipsis::run('default', array('SERVER' => array('HTTP_HOST' => 'local.ellipsis.com')));

?>
