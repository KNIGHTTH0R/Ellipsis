<?php

/**
 * additional PHP functions (i.e. not presently provided by or used by PHP)
 *
 * @author Toby Miller <tobius.miller@gmail.com>
 * @license MIT <http://www.opensource.org/licenses/mit-license.php>
 */

/**
 * autoload static library classes
 *
 * @param string $class_name
 * @return void
 */
function __autoload($class_name){
    if (preg_match('/^[a-z]+$/i', $class_name)){
        // first try to load from the application library
        foreach($_ENV['APPS'] as $name => $app){
            if (is_dir($app['SCRIPT_LIB'])){
                $path = $app['SCRIPT_LIB'] . '/' . strtolower($class_name) . '.php';
                if (is_file($path)){
                    require $path;
                    return;
                }
            }
        }

        // second try to load from the framework library
        $path = $_ENV['SCRIPT_LIB'] . '/' . strtolower($class_name) . '.php';
        if (is_file($path)){
            require $path;
        }
    }
}

/**
 * determine if passed array is an associative array or not
 *
 * @param array $array
 * @return boolean
 */
function is_associative_array($array){
    if (!is_array($array) || empty($array)) return false;
    $keys = array_keys($array);
    return array_keys($keys) !== $keys;
}

/**
 * recursively extend one array with another
 *
 * @param array $array1
 * @param array $array2
 * @return array
 */
function array_extend($array, $array2){
    $array = (is_array($array)) ? $array : array();
    $array2 = (is_array($array2)) ? $array2 : array();
    foreach($array2 as $k=>$v){
        if (is_associative_array($v)){
            if(!isset($array[$k])) $array[$k] = $v;
            else $array[$k] = array_extend($array[$k], $v);
        } else {
            if(is_array($v)){
                if (isset($array[$k]) && is_array($array[$k])) $array[$k] = array_merge($array[$k], $v);
                else $array[$k] = $v;
            } else $array[$k] = $v;
        }
    }
    return $array;
}

/**
 * checks if a regexp exists in an array
 *
 * @param string $regexp
 * @param array $haystack
 * @return boolean
 */
function preg_array($regexp, $haystack){
    // extract each recursive value
    $values = array();
    array_walk_recursive($haystack, create_function('$val, $key, $obj', 'array_push($obj, $val);'), $values);
    foreach($values as $value){
        if (preg_match($regexp, $value)){
            return true;
        }
    }
    return false;
}

/**
 * inflection exceptions
 * note: sourced from Akelos
 */
$inflection_exceptions = array(
    'plural' => array(
        '/(quiz)$/i' => '\1zes',
        '/^(ox)$/i' => '\1en',
        '/([m|l])ouse$/i' => '\1ice',
        '/(matr|vert|ind)ix|ex$/i' => '\1ices',
        '/(x|ch|ss|sh)$/i' => '\1es',
        '/([^aeiouy]|qu)ies$/i' => '\1y',
        '/([^aeiouy]|qu)y$/i' => '\1ies',
        '/(hive)$/i' => '\1s',
        '/(?:([^f])fe|([lr])f)$/i' => '\1\2ves',
        '/sis$/i' => 'ses',
        '/([ti])um$/i' => '\1a',
        '/(buffal|tomat)o$/i' => '\1oes',
        '/(bu)s$/i' => '\1ses',
        '/(alias|status)/i'=> '\1es',
        '/(octop|vir)us$/i'=> '\1i',
        '/(ax|test)is$/i'=> '\1es',
        '/s$/i'=> 's',
        '/$/'=> 's'
    ),
    'singular' => array(
        '/(quiz)zes$/i' => '\\1',
        '/(matr)ices$/i' => '\\1ix',
        '/(vert|ind)ices$/i' => '\\1ex',
        '/^(ox)en/i' => '\\1',
        '/(alias|status)es$/i' => '\\1',
        '/([octop|vir])i$/i' => '\\1us',
        '/(cris|ax|test)es$/i' => '\\1is',
        '/(shoe)s$/i' => '\\1',
        '/(o)es$/i' => '\\1',
        '/(bus)es$/i' => '\\1',
        '/([m|l])ice$/i' => '\\1ouse',
        '/(x|ch|ss|sh)es$/i' => '\\1',
        '/(m)ovies$/i' => '\\1ovie',
        '/(s)eries$/i' => '\\1eries',
        '/([^aeiouy]|qu)ies$/i' => '\\1y',
        '/([lr])ves$/i' => '\\1f',
        '/(tive)s$/i' => '\\1',
        '/(hive)s$/i' => '\\1',
        '/([^f])ves$/i' => '\\1fe',
        '/(^analy)ses$/i' => '\\1sis',
        '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => '\\1\\2sis',
        '/([ti])a$/i' => '\\1um',
        '/(n)ews$/i' => '\\1ews',
        '/s$/i' => '',
    ),
    'uncountable' => array(
        'equipment',
        'information',
        'rice',
        'money',
        'species',
        'series',
        'fish',
        'sheep'
    ),
    'irregular' => array(
        'person' => 'people',
        'man' => 'men',
        'child' => 'children',
        'sex' => 'sexes',
        'move' => 'moves'
    )
);

/**
 * singularize an english noun
 * note: sourced from Akelos
 *
 * @param string $noun
 * @return string
 */
function singularize($noun){
    global $inflection_exceptions;
    $lowercased_noun = strtolower($noun);

    foreach ($inflection_exceptions['uncountable'] as $_uncountable){
        if(substr($lowercased_noun,(-1*strlen($_uncountable))) == $_uncountable){
            return $noun;
        }
    }

    foreach ($inflection_exceptions['irregular'] as $_plural=> $_singular){
        if (preg_match('/('.$_singular.')$/i', $noun, $arr)){
            return preg_replace('/('.$_singular.')$/i', substr($arr[0],0,1).substr($_plural,1), $noun);
        }
    }

    foreach ($inflection_exceptions['singular'] as $rule => $replacement){
        if (preg_match($rule, $noun)){
            return preg_replace($rule, $replacement, $noun);
        }
    }

    return $noun;
}

/**
 * pluralize an english noun
 * note: sourced from Akelos
 *
 * @param string $noun
 * @return $string
 */
function pluralize($noun){
    global $inflection_exceptions;
    $lowercased_noun = strtolower($noun);

    foreach ($inflection_exceptions['uncountable'] as $_uncountable){
        if(substr($lowercased_noun,(-1*strlen($_uncountable))) == $_uncountable){
            return $noun;
        }
    }

    foreach ($inflection_exceptions['irregular'] as $_plural=> $_singular){
        if (preg_match('/('.$_plural.')$/i', $noun, $arr)){
            return preg_replace('/('.$_plural.')$/i', substr($arr[0],0,1).substr($_singular,1), $noun);
        }
    }

    foreach ($inflection_exceptions['plural'] as $rule => $replacement){
        if (preg_match($rule, $noun)){
            return preg_replace($rule, $replacement, $noun);
        }
    }

    return $noun;
}

/**
 * recursively list files and directories inside the specified path
 *
 * @param string $directory
 * @param string $format (absolute|relative)
 * @param array $excludes ('*' is wild)
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
                $exclude = preg_quote($exclude, '/');
                $exclude = str_replace('\*', '.*', $exclude);
                if (preg_match('/' . $exclude . '/', $path)){
                    $remove = true;
                }
            }
            if (!$remove) $clean[] = $path;
        }
        $paths = $clean;
    }
    return $paths;
}

/**
 * mime types
 */
$mime_types = array(
    'ascii' => array(
        'js'    => 'application/x-javascript',
        'css'   => 'text/css',
        'rdf'   => 'application/rdf+xml',
        'xul'   => 'application/vnd.mozilla.xul+xml',
        'wbxml' => 'application/vnd.wap.wbxml',
        'wmlc'  => 'application/vnd.wap.wmlc',
        'wmlsc' => 'application/vnd.wap.wmlscriptc',
        'vxml'  => 'application/voicexml+xml',
        'htm'   => 'text/html',
        'html'  => 'text/html',
        'xml'   => 'application/xml',
        'xsl'   => 'application/xml',
        'dtd'   => 'application/xml-dtd',
        'xslt'  => 'application/xslt+xml'
    ),
    'binary' => array(
        'doc'   => 'application/msword',
        'bin'   => 'application/octet-stream',
        'exe'   => 'application/octet-stream',
        'class' => 'application/octet-stream',
        'so'    => 'application/octet-stream',
        'dll'   => 'application/octet-stream',
        'pdf'   => 'application/pdf',
        'eps'   => 'application/postscript',
        'ps'    => 'application/postscript',
        'xls'   => 'application/vnd.ms-excel',
        'ppt'   => 'application/vnd.ms-powerpoint',
        'sit'   => 'application/x-stuffit',
        'tgz'   => 'application/x-tar',
        'tar'   => 'application/x-tar',
        'zip'   => 'application/zip',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'gif'   => 'image/gif',
        'png'   => 'image/png',
        'ico'   => 'image/x-icon',
        'mov'   => 'video/quicktime',
        'qt'    => 'video/quicktime',
        'mp3'   => 'audio/mpeg',
        'mp4'   => 'application/x-shockwave-flash',
        'avi'   => 'application/x-shockwave-flash',
        'swf'   => 'application/x-shockwave-flash'
    )
);

/**
 * get the file type for a particular resource
 *
 * @param string $path
 * @return string|null
 */
function getfiletype($path){
    if (preg_match('/\.[a-z0-9]+$/', $path)){
        $extension = preg_replace('/\.([a-z0-9]+)$/', '$1', $path);
        if (isset($mime_types['binary'][$extension])){
            return 'binary';
        } else if (isset($mime_types['ascii'][$extension])){
            return 'ascii';
        }
    }
    return null;
}

/**
 * get the mime type for a particular resource
 *
 * @param string $path
 * @return string
 */
function getmimetype($path){
    $filetype = getfiletype($path);
    if ($filetype != null){
        return $mime_types[$filetype][$extension];
    } else {
        return 'text/plain';
    }
}

/**
 * touch a file that may or may not exist and, optionally, create missing directories
 *
 * @param string $path
 * @return boolean
 */
function touch_recursive($path){
    if (!preg_match('/^' . preg_quote($_ENV['DOCUMENT_ROOT'], '/') . '/', $path)){
        // for saftey sake
        return false;
    }

    if (!is_file($path)){
        if(!is_dir(dirname($path))){
            mkdir(dirname($path), 0777, true);
        }
    }
    return touch($path);
}


