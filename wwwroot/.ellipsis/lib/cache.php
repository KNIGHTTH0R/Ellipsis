<?php

/**
 * cache dynamically generated data locally to ease resource intensive operations
 *
 * @author Toby Miller <tobius.miller@gmail.com>
 * @license MIT <http://www.opensource.org/licenses/mit-license.php>
 * @package ellipsis
 * @subpackage modules
 */

class Cache {
    /**
     * put data into storage
     *
     * @param mixed $meta
     * @param mixed $data
     * @param integer $seconds (default = one hour)
     * @return boolean
     */
    public static function put($meta, $data, $seconds = null){
        // provide default cache length
        $seconds = ($seconds != null) ? $seconds : 86400;

        if ($data == null){
            // really? don't waste my time!
            return true;
        }

        // generate a filepath unique to this meta info
        $filename   = md5(serialize($meta)) . '.json';
        $filepath   = $_ENV['CACHE_DIR'] . '/objects/' . $filename;

        // store results as json for a shorter trip next time around
        if (is_writable($_ENV['CACHE_DIR'] . '/objects/')){
            $encoded = json_encode(array('data' => $data, 'expires' => (time() + $seconds)));
            if (@file_put_contents($filepath, $encoded) !== false) return true;
        }

        // something went wrong
        return false;
    }

    /**
     * get data out of storage
     *
     * @param mixed $meta
     * @return mixed|null
     */
    public static function get($meta){
        // generate the filepath that was used for this meta info
        $filename   = md5(serialize($meta)) . '.json';
        $filepath   = $_ENV['CACHE_DIR'] . '/objects/' . $filename;

        if (is_file($filepath) && is_readable($filepath)){
            $content = file_get_contents($filepath);
            $decoded = json_decode($content, true);

            if (time() >= $data['expires']){
                // delete this file, it has expired
                if (@unlink($filepath) !== false) return null;
            } else {
                return $data['data'];
            }
        }

        // couldn't find data
        return null;
    }
}

