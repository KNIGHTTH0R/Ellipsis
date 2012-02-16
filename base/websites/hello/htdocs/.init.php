<?php

/**
 * Ellipsis Init
 *
 * Initialize Ellipsis by specifying which apps to load and in what
 * order. This file also acts as the link between the web directory
 * structure and the Ellipsis directory structure.
 *
 * @author Toby Miller <tobius.miller@gmail.com>
 * @license MIT <http://www.opensource.org/licenses/mit-license.php>
 * @package ellipsis
 */

// define the run environment
$_ENV['RUN'] = array('ellipsis');

// locate the bootstrap file
include '../../../bootstrap.php';

?>
