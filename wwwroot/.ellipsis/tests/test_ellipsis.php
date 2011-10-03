<?php
// simpletest 
require_once('../lib/simpletest/autorun.php');

class TestOfEllipsis extends UnitTestCase {
	
	// since Ellipsis self-constructs on include, it needs to be a conditional situation
	function includeEllipsis(){
		// Ellipsis Core
		require_once('../ellipsis.php');
	}
	
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
		// Set up environment for test
		$_SERVER['REQUEST_URI'] = '/bob';

		$this->includeEllipsis();		

		// ENV vars are set
		$this->assertEqual(substr(__DIR__, 0,strrpos(__DIR__, DIRECTORY_SEPARATOR)), $_ENV['SCRIPT_ROOT'], '$_ENV variables should be set.  SCRIPT_ROOT = ' . $_ENV['SCRIPT_ROOT'] . " DIR = " . __DIR__);
		// PATH_INFO is parsed
		$this->assertEqual('/bob', $_SERVER['PATH_INFO'], '$_SERVER[\'PATH_INFO\'] should be populated.  PATH_INFO = ' . $_SERVER['PATH_INFO']);
		
	}
	
	function testDestruct()
	{
		// is this necessary to test?
	}
	
	function testRun()
	{
		//
	}
	
	function testCache()
	{
		
	}
	
	function testRoute()
	{
		
	}
	
	function testLoad()
	{
		
	}
	
	function testFail()
	{
		
	}
	
	function testDebug()
	{
		
	}
}
?>