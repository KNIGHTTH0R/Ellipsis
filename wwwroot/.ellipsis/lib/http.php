<?php

/**
 * perform stateful http/https web requests
 *
 * @author Toby Miller <tobius.miller@gmail.com>
 * @license MIT <http://www.opensource.org/licenses/mit-license.php>
 * @package ellipsis
 * @depends cURL <http://php.net/curl>
 */

class HTTP {

    private $uri = null;
    private $method = 'GET';
    private $cookies = array();
    private $headers = array();
    private $options = array();
    private $redirects = array();

    /**
     * construct a new HTTP object
     *
     * @param array $curloptions
     * @return void
     */
    public function __construct($curloptions=null){

        // set default curl options
        $this->options = array(
            CURLOPT_SSL_VERIFYPEER  => 1,
            CURLOPT_CAINFO          => realpath(dirname(__FILE__) . '/curl.crt.pem'),
            CURLOPT_ENCODING        => 'gzip,deflate,sdch',
            CURLOPT_FOLLOWLOCATION  => 0,
            CURLOPT_MAXREDIRS       => 5,
            CURLOPT_HEADER          => 1,
            CURLOPT_RETURNTRANSFER  => 1,
            CURLOPT_USERAGENT       => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_7) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/13.0.782.112 Safari/535.1',
            CURLOPT_VERBOSE         => 0,
            CURLINFO_HEADER_OUT		=> 1
        );
        if (is_array($curloptions)){
            $this->options = array_extend($this->options, $curloptions);
        }

        // set default curl http headers
        $this->headers = array(
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.8',
            'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.3'
        );
    }

    /**
     * process a url get request
     *
     * @param string $uri
     * @param array $headers
     * @param array $cookies
     * @param string $proxy (i.e. 127.0.0.1:8888)
     * @return object
     */
    public static function get($uri, $headers=null, $cookies=null, $proxy=null){
        $options = null;

        if ($_ENV['DEBUG_PROXY'] && $_ENV['DEBUG_PROXY'] != '' && $proxy == null){
            $proxy = $_ENV['DEBUG_PROXY'];
        }

        // build proxy (if applicable)
        if ($proxy != null && preg_match('/^([^:]+):(.*)$/', $proxy, $matches)){
            $options = array(
                CURLOPT_PROXY           => $matches[1],
                CURLOPT_PROXYPORT       => $matches[2],
                CURLOPT_SSL_VERIFYPEER  => 0
            );
        }

        // create a request object
        $request = new self($options);

        // get a response object
        return $request->exec($uri, 'GET', null, $headers, $cookies, null);
    }

    /**
     * process a url post request
     *
     * @param string $uri
     * @param array $data
     * @param array $headers
     * @param array $cookies
     * @param string $proxy (i.e. 127.0.0.1:8888)
     * @return object
     */
    public static function post($uri, $data=null, $headers=null, $cookies=null, $proxy=null){
        $options = null;

        if ($_ENV['DEBUG_PROXY'] && $_ENV['DEBUG_PROXY'] != '' && $proxy == null){
            $proxy = $_ENV['DEBUG_PROXY'];
        }

        // build proxy (if applicable)
        if ($proxy != null && preg_match('/^([^:]+):(.*)$/', $proxy, $matches)){
            $options = array(
                CURLOPT_PROXY           => $matches[1],
                CURLOPT_PROXYPORT       => $matches[2],
                CURLOPT_SSL_VERIFYPEER  => false
            );
        }

        // create a request object
        $request = new self($options);

        // get a response object
        return $request->exec($uri, 'POST', $data, $headers, $cookies, null);
    }

    /**
     * process a url put request
     *
     * @param string $uri
     * @param array $data
     * @param array $headers
     * @param array $cookies
     * @param string $proxy (i.e. 127.0.0.1:8888)
     * @return object
     */
    public static function put($uri, $data=null, $headers=null, $cookies=null, $proxy=null){
        $options = null;

        if ($_ENV['DEBUG_PROXY'] && $_ENV['DEBUG_PROXY'] != '' && $proxy == null){
            $proxy = $_ENV['DEBUG_PROXY'];
        }

        // build proxy (if applicable)
        if ($proxy != null && preg_match('/^([^:]+):(.*)$/', $proxy, $matches)){
            $options = array(
                CURLOPT_PROXY           => $matches[1],
                CURLOPT_PROXYPORT       => $matches[2],
                CURLOPT_SSL_VERIFYPEER  => false
            );
        }

        // create a request object
        $request = new self($options);

        // get a response object
        return $request->exec($uri, 'PUT', $data, $headers, $cookies, null);
    }

    /**
     * process a variable url request
     *
     * @param string $uri
	 * @param string $verb (GET, PUT, POST, DELETE)
     * @param array $data
     * @param array $headers
     * @param array $cookies
     * @param string $proxy (i.e. 127.0.0.1:8888)
     * @return object
     */
    public static function exec_variable($uri, $verb, $data=null, $headers=null, $cookies=null, $proxy=null){
        $options = null;

        if ($_ENV['DEBUG_PROXY'] && $_ENV['DEBUG_PROXY'] != '' && $proxy == null){
            $proxy = $_ENV['DEBUG_PROXY'];
        }
		
        // build proxy (if applicable)
        if ($proxy != null && preg_match('/^([^:]+):(.*)$/', $proxy, $matches)){
            $options = array(
                CURLOPT_PROXY           => $matches[1],
                CURLOPT_PROXYPORT       => $matches[2],
                CURLOPT_SSL_VERIFYPEER  => false
            );
        }

        // create a request object
        $request = new self($options);

        // get a response object
        return $request->exec($uri, $verb, $data, $headers, $cookies, null);
    }


    /**
     * execute a url http request
     *
     * @param string $uri
     * @param string $method
     * @param array $data
     * @param array $headers
     * @param array $cookies
     * @param array $options
     * @return object
     */
    private function exec($uri, $method, $data=null, $headers=null, $cookies=null, $options=null){
        // setup self
        $this->uri = trim($uri);
        $this->method = $method;

        // fashion a response object
        $response = (object) array(
            'uri'       => null,
            'method'    => null,
            'data'      => null,
            'status'    => null,
            'cookies'   => null,
            'options'   => null,
            'headers'   => null,
            'body'      => null,
            'info'		=> null
        );

        // get handle on curl :)
        $this->handle = curl_init($this->uri);

        try {
            // curl options
            if (!empty($options)) $this->options = array_extend($this->options, $options);
            if (!empty($this->options)) curl_setopt_array($this->handle, $this->options);

            // curl put/post form data
            if ($this->method == 'POST' || $this->method == 'PUT' && !empty($data)){
                $fields = (is_associative_array($data) ? http_build_query($data, '', '&') : (is_array($data) ? http_build_query($data) : $data));

                if ($this->method == 'POST' && !empty($data)){
                    curl_setopt($this->handle, CURLOPT_POST, true);
                } else if ($this->method == 'PUT' && !empty($data)){
                    curl_setopt($this->handle, CURLOPT_CUSTOMREQUEST, 'PUT');
                    $this->headers = array_merge($this->headers, array('Content-Length: ' . strlen($fields)));
                }

                curl_setopt($this->handle, CURLOPT_POSTFIELDS, $fields);
            }

            // curl headers
            if (!empty($headers)) $this->headers = array_merge($this->headers, $headers);
            if (!empty($this->headers)) curl_setopt($this->handle, CURLOPT_HTTPHEADER, $this->headers);

            // curl cookies
            if (!empty($cookies)) $this->cookies = array_extend($this->cookies, $cookies);
            if (!empty($this->cookies)) curl_setopt($this->handle, CURLOPT_COOKIE, $this->serializeCookies($this->cookies));

            // execute the request
            $result = curl_exec($this->handle);

            // check for errors
            if (curl_getinfo($this->handle, CURLINFO_HTTP_CODE) >= 400){
                trigger_error('Server error: ' . curl_getinfo($this->handle, CURLINFO_HTTP_CODE));
            }
            if (curl_errno($this->handle)){
                switch(curl_errno($this->handle)){
                    case CURLE_COULDNT_CONNECT:
                        trigger_error('Could not connect to host');
                        break;
                    case CURLE_OPERATION_TIMEOUTED:
                        trigger_error('Timeout: ' . curl_error($this->handle));
                        break;
                    case CURLE_HTTP_NOT_FOUND:
                    case CURLE_HTTP_PORT_FAILED:
                    case CURLE_HTTP_POST_ERROR:
                    case CURLE_HTTP_RANGE_ERROR:
                        trigger_error('Server error: ' . curl_error($this->handle));
                        break;
                    default:
                        trigger_error('Curl error: ' . curl_error($this->handle));
                }
            }

            // capture response data
			// (strip additional HTTP headers, which aren't accounted for in CURLINFO_HEADER_SIZE)
            $split = preg_split('/(\r\n){2,}/', $result);
            $header = $split[count($split)-2];
            $body = $split[count($split)-1];
            $status = curl_getinfo($this->handle, CURLINFO_HTTP_CODE);

            // parse the Location header
            // per http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.30
            // the Location header "consists of a single absolute URI"
            if (preg_match('/^Location: (.+)$/m', $header, $location_header)){
                $location = $location_header[1];
            } else {
                $location = $this->uri;
            }

			// parse the Set-Cookie lines from response header
			// VERY IMPORTANT:  malformed cookies from some websites REQUIRE the pattern: [^;\n\s]
			//                  please do NOT reduce the pattern back to: [^;]
            //                  as not everyone follows the rules
            if (preg_match_all("/^Set-Cookie: ([^;\n\s]+)/m", $header, $cookie_lines)) {
                $cookies = $cookie_lines[1];
			}

            // set response data to the response object
            $response->uri      = $location;
            $response->method   = $method;
            $response->data     = $data;
            $response->status   = $status;
            $response->cookies  = $this->deserializeCookies($cookies);
            $response->headers  = $this->deserializeHeaders($header);
            $response->body     = $body;
			$response->info		= curl_getinfo($this->handle);
			

            // if redirect (3xx) status, update cookies and redirect
            if (strpos($status, '3') === 0) {
                $response = $this->followRedirect($response);
            }

        } catch(Exception $e){
            trigger_error('Server error: ' . curl_error($this->handle));
        }
        
        // return the response object
        $response->redirectCount = count($this->redirects);
        return $response;
    }

    /**
     * follow a redirect (3xx)
     *
     * @param object $response
     * @return object
     */
    private function followRedirect($response){
        // record this redirect for troubleshooting purposes
        array_push($this->redirects, $response);

        // perform the redirect
        $redirect = $this->exec($response->uri, 'GET', null, null, $response->cookies, null);

        // merge the response and redirect cookies
        $redirect->cookies = array_extend($response->cookies, $redirect->cookies);

        // return the redirect object
        return $redirect;
    }

    /**
     * serialize a header hash into an HTTP-friendly header string
     *
     * @param array $headers
     * @return string
     */
    private function serializeHeaders($headers){
        if (is_associative_array($headers)){
            $hash = array();
            foreach($headers as $key=>$val) $hash[] = $key.':'.$val;
            $headers = implode("\n", $hash);
        } else if (is_array($headers)){
            $headers = '';
        }
        return $headers;
    }

    /**
     * deserialize a header string into a hash
     *
     * @param string $headers
     * @return array
     */
    private function deserializeHeaders($headers){
        if (is_string($headers)){
            $hash = array();
            $headers = trim($headers);
            $lines = preg_split('/\n/', $headers);
            foreach($lines as $line){
                if (preg_match('/^([^:]+) *: *(.*)$/', $line, $matches)){
                    $hash[$matches[1]] = $matches[2];
                }
            }
            $headers = $hash;
        }
        return $headers;
    }

    /**
     * serialize a cookie hash into an HTTP-friendly cookie string
     *
     * @param array $cookies
     * @return string
     */
    private function serializeCookies($cookies){
        if (is_associative_array($cookies)){
            $hash = array();
            foreach($cookies as $key=>$val) $hash[] = $key.'='.$val;
            $cookies = implode('; ', $hash);
        } else if (is_array($cookies)){
            $cookies = '';
        }
        return $cookies;
    }

    /**
     * deserialize a cookie string into a hash
     *
     * @param string $cookies
     * @return array
     */
    private function deserializeCookies($cookies){
        if (is_string($cookies)){
            $hash = array();
            $cookies = trim($cookies);
            $lines = preg_split('/ *; */', $cookies);
            foreach($lines as $line){
                if (preg_match('/^([^:]+) *= *(.*)$/', $line, $matches)){
                    $hash[$matches[1]] = $matches[2];
                }
            }
            $cookies = $hash;
        }
        return $cookies;
    }
}

?>
