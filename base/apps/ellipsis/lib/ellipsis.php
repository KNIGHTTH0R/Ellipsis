<?php

/**
 * ellipsis functions (most of which I wish were built into PHP)
 *
 * @package ellipsis
 * @subpackage global
 * @author Toby Miller <tobius.miller@gmail.com>
 * @license MIT <http://www.opensource.org/licenses/mit-license.php>
 */

/**
 * inflection exceptions
 * note: sourced from Akelos
 */
$_ENV['INFLECTION_EXCEPTIONS'] = array(
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
 * mime types
 */
$_ENV['MIME_TYPES'] = array(
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
 * recursively extend one array with another
 *
 * @param array $array1
 * @param array $array2
 * @return array
 */
function array_extend($array1, $array2){
    $array1 = (is_array($array1)) ? $array1 : array();
    $array2 = (is_array($array2)) ? $array2 : array();
    foreach($array2 as $k=>$v){
        if (is_array($v) && is_associative_array($v)){
            if(!isset($array1[$k])) $array1[$k] = $v;
            else $array1[$k] = array_extend($array1[$k], $v);
        } else {
            if(is_array($v)){
                if (isset($array1[$k]) && is_array($array1[$k])) $array1[$k] = array_merge($array1[$k], $v);
                else $array1[$k] = $v;
            } else $array1[$k] = $v;
        }
    }
    return $array1;
}

/**
 * convert ascii to hexadecimal
 *
 * @param string $ascii
 * @return string
 */
function asciihex($ascii){
    $length = strlen($ascii);
    $hex = '';
    for ($i = 0; $i < $length; $i++){
        $hex .= sprintf("%02x", ord(substr($ascii, $i, 1)));
    }
    return $hex;
}

/**
 * decrypt data
 *
 * @param string $salt
 * @param string $encrypted
 * @return mixed $unencrypted
 */
function decrypt($salt, $encrypted){
    $decrypted = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($salt), base64_decode($encrypted), MCRYPT_MODE_CBC, md5(md5($salt))), "\0");
    return $decrypted;
}

/**
 * encrypt data
 *
 * @param string $salt
 * @param mixed $unencrypted
 * @return string $encrypted
 */
function encrypt($salt, $unencrypted){
    $encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($salt), $unencrypted, MCRYPT_MODE_CBC, md5(md5($salt))));
    return $encrypted;
}

/**
 * get the file extension for a particular resource
 * 
 * @param string $path
 * @return string|null
 * 
 */
function getextension($path)
{
	$extension = null;
    if (preg_match('/\.([a-z0-9]+)$/', $path, $found)){
		$extension = $found[1];
	}
	return $extension;
}

/**
 * get the file type for a particular resource
 *
 * @param string $path
 * @return string|null
 */
function getfiletype($path){
	$extension = getextension($path);
	if($extension!=null)
	{
        if (isset($_ENV['MIME_TYPES']['binary'][$extension])){
            return 'binary';
        } else if (isset($_ENV['MIME_TYPES']['ascii'][$extension])){
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
	$extension = getextension($path);
    $filetype = getfiletype($path);
    if ($filetype != null){
        return $_ENV['MIME_TYPES'][$filetype][$extension];
    } else {
        return 'text/plain';
    }
}

/**
 * convert hexadecimal to ascii
 *
 * @param string $hex
 * @return string
 */
function hexascii($hex){
    $length = strlen($hex);
    $ascii = '';
    for ($i = 0; $i < $length; $i+=2){
        $ascii .= chr(hexdec(substr($hex, $i, 2)));
    }
    return $ascii;
}

/**
 * convert hex to rgb
 *
 * @param string $hex
 * @retrun array
 */
function hexrgb($hex){
    $hex = ($hex[0] == '#') ? substr($hex, 1) : $hex;

    if (strlen($hex) == 3){
        list($r, $g, $b) = array($hex[0].$hex[0], $hex[1].$hex[1], $hex[2].$hex[2]);
    } else if (strlen($hex) == 6){
        list($r, $g, $b) = array($hex[0].$hex[1], $hex[2].$hex[3], $hex[4].$hex[5]);
    }

    if (isset($r) && isset($g) && isset($b)){
        return array(hexdec($r), hexdec($g), hexdec($b));
    } else {
        return array(0, 0, 0);
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
 * determine if the passed string is a valid email address
 *
 * @param string $email
 * @return boolean
 */
function is_email($email){
    return (filter_var($email, FILTER_VALIDATE_EMAIL) !== false);
}

/**
 * determine if the passed string is a valid IP address according to IPV4 and IPV6 rules
 *
 * @param string $ip
 * @return boolean
 */
function is_ip($ip){
    return (filter_var($ip, FILTER_VALIDATE_IP) !== false);
}

/**
 * determine if the passed string is a valid regular expression according to Perl Regexp
 *
 * @param string $regexp
 * @return boolean
 */
function is_regexp($regexp){
    return (@preg_match($regexp, null) !== false);
}

/**
 * determine if the passed string is a valid URI according to RFC2396
 *
 * @param string $uri
 * @return boolean
 */
function is_uri($uri){
    return (filter_var($uri, FILTER_VALIDATE_URL) !== false);
}

/**
 * alias for is_uri
 */
function is_url($uri){
    return is_uri($uri);
}

/**
 * returns the jsonp encoded representation of a value
 *
 * @param string $jsonp
 * @param mixed $value
 * @param integer $options
 * @return string
 */
function jsonp_encode($jsonp, $value, $options = null){
    $option = ($options != null) ? 0 : $options;
    return $jsonp . '(' . json_encode($value, $options) . ');';
}

/**
 * pluralize an english noun
 * note: sourced from Akelos
 *
 * @param string $noun
 * @return $string
 */
function pluralize($noun){
    $lowercased_noun = strtolower($noun);

    foreach($_ENV['INFLECTION_EXCEPTIONS']['uncountable'] as $uncountable){
        if(substr($lowercased_noun,(-1*strlen($uncountable))) == $uncountable){
            return $noun;
        }
    }

    foreach($_ENV['INFLECTION_EXCEPTIONS']['irregular'] as $plural => $singular){
        if (preg_match('/('.$plural.')$/i', $noun, $arr)){
            return preg_replace('/('.$plural.')$/i', substr($arr[0],0,1).substr($singular,1), $noun);
        }
    }

    foreach($_ENV['INFLECTION_EXCEPTIONS']['plural'] as $rule => $replacement){
        if (preg_match($rule, $noun)){
            return preg_replace($rule, $replacement, $noun);
        }
    }

    return $noun;
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
    array_walk_recursive($haystack, create_function('$val, $key, &$obj', 'array_push($obj, $val);'), $values);
    foreach($values as $value){
        if (preg_match($regexp, $value)){
            return true;
        }
    }
    return false;
}

/**
 * pretend to be a web environment
 *
 * note: this is useful for running cli unit tests
 *
 * @param string $webroot
 * @param string $appname
 * @param string $domain
 * @param string $uri
 * @return void
 */
function pretend($webroot, $appname, $domain = 'local.ellipsis.com', $uri = '/index.php'){
    $_SERVER = array_merge(
        $_SERVER,
        array(
            'HTTP_HOST'             => $domain,
            'HTTP_CONNECTION'       => 'keep-alive',
            'HTTP_USER_AGENT'       => 'Mozilla/5.0 (Macintosh; Intel Mac OSX 10_6_8) AppleWebKit/535.2 (KHTML, like Gecko) Chrome/15.0.874.121 Safari/535.2',
            'HTTP_ACCEPT'           => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_ENCODING'  => 'gzip,deflate,sdch',
            'HTTP_ACCEPT_LANGUAGE'  => 'en-US,en;q=0.8',
            'HTTP_ACCEPT_CHARSET'   => 'ISO-8859-1,utf-8;q=0.7,*;q=0.3',
            'SERVER_SIGNATURE'      => '',
            'SERVER_SOFTWARE'       => 'Apache/2.2.14 (Unix) DAV/2 mod_ssl/2.2.14 OpenSSL/0.9.8l PHP/5.3.1 mod_perl/2.0.4 Perl/v5.10.1',
            'SERVER_NAME'           => $domain,
            'SERVER_ADDR'           => '127.0.0.1',
            'SERVER_PORT'           => '80',
            'REMOTE_ADDR'           => '127.0.0.1',
            'DOCUMENT_ROOT'         => $webroot,
            'SERVER_ADMIN'          => 'root@localhost.com',
            'SCRIPT_FILENAME'       => "{$webroot}/.init.php",
            'REMOTE_PORT'           => '51212',
            'REDIRECT_URL'          => $uri,
            'GATEWAY_INTERFACE'     => 'CGI/1.1',
            'SERVER_PROTOCOL'       => 'HTTP/1.1',
            'REQUEST_METHOD'        => 'GET',
            'QUERY_STRING'          => '',
            'REQUEST_URI'           => $uri,
            'SCRIPT_NAME'           => '/.init.php',
            'PHP_SELF'              => '/.init.php'
        )
    );
    $_ENV = array_merge($_ENV, array('USERNAME' => $_ENV['USER']));
    //print '<pre>' . print_r(array($_SERVER, $_ENV), true) . '</pre>';
}

/**
 * determine if request variable exists and, if so, return it's value, null otherwise
 *
 * @param string $key
 * @return string
 */
function request($key){
    if (array_key_exists($key, $_REQUEST)){
        return $_REQUEST[$key];
    }
    return null;
}

/**
 * convert rgb to hex
 *
 * @param integer $r
 * @param integer $g
 * @param integer $b
 * @return string
 */
function rgbhex($r, $g, $b){
    $r  = intval($r);
    $g  = intval($g);
    $b  = intval($b);
    $r  = dechex($r < 0 ? 0 : ($r > 255 ? 255 : $r));
    $g  = dechex($g < 0 ? 0 : ($g > 255 ? 255 : $g));
    $b  = dechex($b < 0 ? 0 : ($b > 255 ? 255 : $b));
    
    $hex = (strlen($r) < 2 ? '0' : '') . $r;
    $hex .= (strlen($g) < 2 ? '0' : '') . $g;
    $hex .= (strlen($b) < 2 ? '0' : '') . $b;

    return $hex;
}

/**
 * recursively list files and directories inside the specified path
 *
 * @param string $directory
 * @param string $format (absolute|relative)
 * @param array $excludes ('*' is wild)
 * @return array
 */
function scandir_recursive($directory, $format = null, $excludes = array()){
    $format = ($format == null) ? 'absolute' : $format;
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
    if (count($excludes) > 0){
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
 * singularize an english noun
 * note: sourced from Akelos
 *
 * @param string $noun
 * @return string
 */
function singularize($noun){
    $lowercased_noun = strtolower($noun);

    foreach($_ENV['INFLECTION_EXCEPTIONS']['uncountable'] as $uncountable){
        if(substr($lowercased_noun,(-1*strlen($uncountable))) == $uncountable){
            return $noun;
        }
    }

    foreach($_ENV['INFLECTION_EXCEPTIONS']['irregular'] as $plural => $singular){
        if (preg_match('/('.$singular.')$/i', $noun, $arr)){
            return preg_replace('/('.$singular.')$/i', substr($arr[0],0,1).substr($plural,1), $noun);
        }
    }

    foreach($_ENV['INFLECTION_EXCEPTIONS']['singular'] as $rule => $replacement){
        if (preg_match($rule, $noun)){
            return preg_replace($rule, $replacement, $noun);
        }
    }

    return $noun;
}

/**
 * generate a system tmpdir and return it's path
 *
 * @param void
 * @return string or null
 */
function tmpdir(){
    $tmpdir = $_ENV['TMPDIR'] . $_SERVER['UNIQUE_ID'];
    if (!is_file($tmpdir) && !is_dir($tmpdir) && (mkdir($tmpdir))){
        return "{$tmpdir}/";
    }
    return null;
}

/**
 * touch a file that may or may not exist and, optionally, create missing directories
 *
 * @param string $path
 * @return boolean
 */
function touch_recursive($path,$override=false){
    if (!preg_match('/^' . preg_quote($_SERVER['DOCUMENT_ROOT'], '/') . '/', $path) && !$override){
        // for safety's sake
        return false;
    }

    if (!is_file($path)){
        if(!is_dir(dirname($path))){
            mkdir(dirname($path), 0777, true);
        }
    }
    return touch($path);
}

/**
 * determine if the passed resource has a "value" and return it, false otherwise
 *
 * @TODO Make this make sense, this isn't doing what I want it to do
 *
 * @param mixed $resource
 * @return mixed
 */
function value($resource){
    if ($resource){
        if (is_object($resource) || is_array($resource) || is_resource($resource)){
            return $resource;
        } else {
            $cast = strval($resource);
            if ($cast != '' && $cast != 'null'){
                return $cast;
            }
        }
    }
    return false;
}