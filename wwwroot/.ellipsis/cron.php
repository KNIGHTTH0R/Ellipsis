<?php

/**
 * maintenance steps to be performed by a cron job
 *
 * example$     * * * * * php cron.php
 *
 * @author Toby Miller <tobius.miller@gmail.com>
 * @license MIT <http://www.opensource.org/licenses/mit-license.php>
 */

/**
 * recursively list files and directories inside the specified path
 *
 * @param string $directory
 * @param string $format (absolute|relative)
 * @param array $excludes
 * @return array
 */
function scandir_recursive($directory, $format = 'absolute', $excludes = null){
    $paths = array();
    $stack[] = $directory;
    while($stack){
        $this_resource = array_pop($stack);
        if ($resource = scandir($this_resource)){
            $i = 0;
            while (isset($resource[$i])){
                if ($resource[$i] != '.' && $resource[$i] != '..'){
                    $current = array(
                        'absolute' => "{$this_resource}/{$resource[$i]}",
                        'relative' => preg_replace('/' . preg_quote($directory, '/') . '/', '', "{$this_resource}/{$resource[$i]}")
                    );
                    if (is_file($current['absolute'])){
                        $paths[] = $current[$format];
                    } elseif (is_dir($current['absolute'])){
                        $paths[] = $current[$format];
                        $stack[] = $current['absolute'];
                    }
                }
                $i++;
            }
        }
    }
    if ($excludes != null && is_array($excludes)){
        $clean = array();
        foreach($paths as $path){
            $remove = false;
            foreach($excludes as $exclude){
                if (preg_match('/' . preg_quote($exclude, '/') . '/', $path)){
                    $remove = true;
                }
            }
            if (!$remove) $clean[] = $path;
        }
        $paths = $clean;
    }
    return $paths;
}

// start a reporter
$report = '';

// capture command line argument
if (isset($argv) && count($argv) >= 2){
    $cache_dir  = $argv[1];
    $report .= "cache_dir = {$cache_dir}\n";

    if (is_dir($cache_dir)){
        // capture current timestamp
        $now = time();

        // find all files in the wwwroot directory (i.e. the "cache" directory)
        $cached = scandir_recursive($cache_dir, 'absolute', array('/.*'));

        // find all files joined with a timestamp version
        foreach($cached as $cache){
            if (preg_match('/^(?<file>.*)\.(?<exp>[0-9]{10})$/', $cache, $match)){
                $file   = $match['file'];
                $exp    = intval($match['exp']);

                if ($now > $exp && is_file($file) && is_file($file.'.'.$exp)){
                    // remove the expired pairs
                    unlink($file);
                    unlink($file . '.' . $exp);

                    // write to the report
                    $report .= "unlinked {$file}\n";
                    $report .= "unlinked {$file}.{$exp}\n";
                }
            }
        }
    }
}

// output what was done (in case someone wants to log it)
echo $report;

