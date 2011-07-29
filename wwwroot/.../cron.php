<?php

/**
 * maintenance steps to be performed by a cron job
 *
 * example$     * * * * * php cron.php
 *
 * @author Toby Miller <tobius.miller@gmail.com>
 * @license MIT <http://www.opensource.org/licenses/mit-license.php>
 */

// load additional functions
include __DIR__ . '/php.php';

// capture current timestamp
$now = time();

// find all files in the wwwroot directory (i.e. the "cache" directory)
$cached = scandir_recursive($_SERVER['DOCUMENT_ROOT'], 'relative', array('...'));

// find all files joined with a timestamp version
foreach($cached as $cache){
    if (preg_match('/^(?<file>.*)\.(?<exp>[0-9]{10})$/', $cache, $match)){
        $file   = $match['file'];
        $exp    = $match['exp'];

        if ($now > $exp){
            // remove the expired pairs
            unlink($_SERVER['DOCUMENT_ROOT'] . $file);
            unlink($_SERVER['DOCUMENT_ROOT'] . $file . '.' . $exp);
        }
    }
}

/**
 * delete cache files
 */
$time = time();
echo $time;


