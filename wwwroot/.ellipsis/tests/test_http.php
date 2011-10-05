<?php
// simpletest 
require_once('../lib/simpletest/autorun.php');
// http 
require_once('../lib/http.php');
// php utilities are required
require_once('../php.php');

class TestOfHttp extends UnitTestCase {
	
	
	function setUp()
	{
		// global configurations
		
	}
	
	function tearDown()
	{
		// global tear down
	}
	
	function testConstruct()
	{
		$this->http = new HTTP(array(CURLOPT_PROXY=>'testproxy'));

		$compare = <<<EOS
HTTP::__set_state(array(
   'uri' => NULL,
   'method' => 'GET',
   'cookies' => 
  array (
  ),
   'headers' => 
  array (
    0 => 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
    1 => 'Accept-Language: en-US,en;q=0.8',
    2 => 'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.3',
  ),
   'options' => 
  array (
    64 => 1,
    10065 => false,
    10102 => 'gzip,deflate,sdch',
    52 => 0,
    68 => 5,
    42 => 1,
    19913 => 1,
    10018 => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_7) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/13.0.782.112 Safari/535.1',
    41 => 0,
    10004 => 'testproxy',
  ),
   'redirects' => 
  array (
  ),
))
EOS;
		
		$this->assertTrue(strcmp(var_export($this->http),$compare),"Exported object matches");
		
	}
	
	function testGet()
	{
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
	
}
?>