<?php

class TestOfHttp extends UnitTestCase { 
    private $_proxy = null;

	function setUp(){
        // setup proxy server (if running)
        if (@fsockopen('127.0.0.1', 8888, $proxy_errno, $proxy_errstr, 30) !== false){
            $this->_proxy = '127.0.0.1:8888';
        }
	}
	
	function tearDown(){
		// global tear down
	}

    /*
    public static function get($uri, $headers=null, $cookies=null, $proxy=null){
    public static function post($uri, $data=null, $headers=null, $cookies=null, $proxy=null){
    public static function put($uri, $data=null, $headers=null, $cookies=null, $proxy=null){
    */

    function testGet(){
        // valid http uri
        $test = Http::get('http://www.google.com/');
        $this->assertTrue(($test->status == 200), 'Failed to return successful HTTP response');
        $this->assertTrue(($test->method == 'GET'), 'Failed to use GET method');
        $this->assertTrue(($test->body != ''), 'Failed to return body value');
        $this->assertTrue(($test->error == ''), 'Requesting a valid URI resulted in an error');

        // valid http uri, valid http headers
        $test = Http::get('http://www.google.com/', array(
            ''
        ));

        // invalid http uri format
        $test = Http::get('hello world');
        $this->assertTrue(($test->errno == CURLE_COULDNT_RESOLVE_HOST), 'Failed to throw an error while resolving a bad URI');
        
        // invlaid http hostname
        $test = Http::get('http://www.iveryseriouslydoubtthatthisdomainexists.com/');
        $this->assertTrue(($test->errno == CURLE_COULDNT_RESOLVE_HOST), 'Failed to throw an error while resolving a bad hostname');

        // valid https uri
        $test = Http::get('https://encrypted.google.com/');
        $this->assertTrue(($test->status == 200), 'Failed to return successful HTTP response');
        $this->assertTrue(($test->method == 'GET'), 'Failed to use GET method');
        $this->assertTrue(($test->body != ''), 'Failed to return body value');
        $this->assertTrue(($test->error == ''), 'Requesting a valid URI resulted in an error');
        //print '<pre>' . print_r($test, true) . '</pre>';
        //exit;

        // valid http uri, valid http headers
        //$test = Http::get('http://www.google.com/', array('


        // valid http uri, valid http headers, valid http cookies
        // valid http uri, valid http headers, valid http cookies, valid http proxy
    }

    /*
	function testGet(){
		$testGet = HTTP::get('http://localhost');
		$this->assertTrue($testGet->body == '','get returns an empty body');
	}
	
	function testPost()
	{
		
	}
	
	function testExec()
	{
		
	}
	
	function testFollowRedirect()
	{
		
	}
	
	function testSerializeHeaders()
	{
		
	}
	
	function testDeserializeHeaders()
	{
		
	}
	
	function testSerializeCookies()
	{
		
	}

	function testDeserializeCookies()
	{
		
	}
     */
	
}
?>
