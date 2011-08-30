<?php
require_once('../lib/simpletest/autorun.php');
require_once('../ellipsis.php');
class TestOfEllipsis extends UnitTestCase {
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
		
		// run construct
		Ellipsis::construct();
		
		// ENV vars are set
		$this->assert(__DIR__, $_ENV['SCRIPT_ROOT'], '$_ENV variables should be set');
		// PATH_INFO is parsed
		$this->assert('bob', $_SERVER['PATH_INFO'], '$_SERVER[\'PATH_INFO\'] should be populated');
		
	}
}
?>