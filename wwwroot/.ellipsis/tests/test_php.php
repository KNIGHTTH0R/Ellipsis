<?php
// simpletest 
require_once('../lib/simpletest/autorun.php');
// mock file system object
require_once('../lib/vfsstream/vfsStream.php');

// library to test
require_once('../php.php');

// test class
class TestOfPhp extends UnitTestCase {
	function setUp()
	{
		// global configurations
	}
	
	function tearDown()
	{
		// global tear down
	}
	
	function testAutoload()
	{
		// set up ENV, since framework is not running
		$_ENV['APPS'] = array();
		$_ENV['SCRIPT_LIB'] = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'lib';
		
		// call a class that is not loaded
		$http = new HTTP();
		
		// is http a class?
		$this->assertTrue(class_exists("HTTP"), "Class on access path should load");
	}
	
	function testIsAssociativeArray()
	{
		// Not array 
		$testArray = 'array(1,2)';
		$this->assertFalse(is_associative_array($testArray), "Non-array should return false");

		// Array 
		$testArray = array(1,2);
		$this->assertFalse(is_associative_array($testArray), "Normal array should return false");
		
		// Associative Array
		$testArray = array('a'=>1,'b'=>2);
		$this->assertTrue(is_associative_array($testArray), "Associative array should return true");
		
	}
	
	function testArrayExtend()
	{
		// Not array
		$testArrA = 'a';
		$testArrB = 'b';
		$this->assertIdentical(array_extend($testArrA, $testArrB), Array(),'Non-array should return empty array');

		// Arrays
		$testArrA = array('a');
		$testArrB = array('b');
		$this->assertIdentical(array_extend($testArrA, $testArrB), Array('b'),'Non-Associative Arrays should return flattened array (key-based merge)');

		// Associative Arrays
		$testArrA = array('1'=>'a');
		$testArrB = array('2'=>'b');
		$this->assertIdentical(array_extend($testArrA, $testArrB), Array('1'=>'a','2'=>'b'),'Associative Arrays should return merged array');
	}
	
	function testPregArray()
	{
		// Not in array
		$testArray = array('a'=>'bo','b'=>'bob','c'=>'ob','d'=>array('dan','steve'));
		$this->assertFalse(preg_array('/^[0-9]{3}/', $testArray),'Array with no matches returns false');

		// In array
		$this->assertTrue(preg_array('/bob/', $testArray),'Array with matches returns true');
	}
	
	function testSingularize()
	{
		$this->assertIdentical(singularize('buffaloes'),'buffalo','Singularize reduces the number of buffaloes to one buffalo');
		$this->assertIdentical(singularize('species'),'species','Singularize does nothing with species');
		$this->assertIdentical(singularize('people'),'person','Singularize reduces the number of people to one person');
	}

	function testPluralize()
	{
		$this->assertIdentical(pluralize('buffalo'),'buffaloes','Pluralize increases the number of buffaloes');
		$this->assertIdentical(pluralize('species'),'species','Pluralize does nothing with species');
		$this->assertIdentical(pluralize('person'),'people','Pluralize increases the number of people');
	}
	
	function testScandirRecursive()
	{
		// mock recursive dir object
		$mockDir = array('Core' => array('AbstractFactory' => array('test.php'    => 'some text content',
                                                'other.php'   => 'Some more text content',
                                                'Invalid.csv' => 'Something else',
                                          ),
                     'AnEmptyFolder'   => array(),
                     'badlocation.php' => 'some bad content',
               )
		);
		$root = vfsStream::create($mockDir);
		$expect = Array
		(
		    'vfs://Core/AbstractFactory',
		    'vfs://Core/AnEmptyFolder',
		    'vfs://Core/badlocation.php',
		    'vfs://Core/AbstractFactory/Invalid.csv',
		    'vfs://Core/AbstractFactory/other.php',
		    'vfs://Core/AbstractFactory/test.php'
		);

		// test absolute
		$this->assertIdentical($expect, scandir_recursive(vfsStream::url('Core')), 'Should return directory structure with absolute paths');
		
		$expect = Array
		(
		    '/AbstractFactory',
		    '/AnEmptyFolder',
		    '/badlocation.php',
		    '/AbstractFactory/Invalid.csv',
		    '/AbstractFactory/other.php',
		    '/AbstractFactory/test.php'
		);

		// test relative
		$this->assertIdentical($expect, scandir_recursive(vfsStream::url('Core'),'relative'), 'Should return directory structure with relative paths');

		$expect = Array
		(
		    'vfs://Core/AbstractFactory',
		    'vfs://Core/AnEmptyFolder',
		    'vfs://Core/badlocation.php',
		    'vfs://Core/AbstractFactory/other.php',
		    'vfs://Core/AbstractFactory/test.php'
		);

		// test excludes
		$this->assertIdentical($expect, scandir_recursive(vfsStream::url('Core'),'absolute', array('Invalid.csv')), 'Should return directory structure with relative paths');
	}

	function testGetExtension()
	{
		// mock recursive dir object
		$mockDir = array('Core' => array(
                     'test.js' => 'alert("hi");',
                     'test.doc'=> 'mswordbinary',
                     'test.pcx'=> 'PAINTBRUSH IN DA HOUSE'
               )
		);
		
		$this->assertIdentical(getextension(vfsStream::url('Core/test.js')), 'js','Should return js');
		$this->assertIdentical(getextension(vfsStream::url('Core/test.doc')), 'doc','Should return doc');
		$this->assertIdentical(getextension(vfsStream::url('Core/test.pcx')), 'pcx','Should return pcx');		
	}

	function testGetFileType()
	{
		// mock recursive dir object
		$mockDir = array('Core' => array(
                     'test.js' => 'alert("hi");',
                     'test.doc'=> 'mswordbinary',
                     'test.pcx'=> 'PAINTBRUSH IN DA HOUSE'
               )
		);
		
		$this->assertIdentical(getfiletype(vfsStream::url('Core/test.js')), 'ascii','Should return ascii as filetype');
		$this->assertIdentical(getfiletype(vfsStream::url('Core/test.doc')), 'binary','Should return binary as filetype');
		$this->assertIdentical(getfiletype(vfsStream::url('Core/test.pcx')), null,'Should return null');
	}
	
	function testGetMimeType()
	{
		// mock recursive dir object
		$mockDir = array('Core' => array(
                     'test.js' => 'alert("hi");',
                     'test.doc'=> 'mswordbinary',
                     'test.pcx'=> 'PAINTBRUSH IN DA HOUSE'
               )
		);
		
		$this->assertIdentical(getmimetype(vfsStream::url('Core/test.js')), 'application/x-javascript','Should return javascript as filetype');
		$this->assertIdentical(getmimetype(vfsStream::url('Core/test.doc')), 'application/msword','Should return msword as filetype');
		$this->assertIdentical(getmimetype(vfsStream::url('Core/test.pcx')), 'text/plain','Should return text');
	}
	
	function testTouch_Recursive()
	{
		// mock recursive dir object
		$mockDir = array('Core' => array(
							'AbstractFactory' => array()
				        	)
						);
		$root = vfsStream::create($mockDir);
		echo 'url: ' . vfsStream::url('Core/AbstractFactory') . "\n";
		$this->assertFalse(vfsStreamWrapper::getRoot()->getChild('AbstractFactory')->hasChild('tmp'), "tmp should not exist");
		$this->assertTrue(touch_recursive(vfsStream::url('Core/AbstractFactory') . '/tmp/index.php1234'),"Touch should return true");
		$this->assertTrue(vfsStreamWrapper::getRoot()->getChild('AbstractFactory')->hasChild('tmp'), "tmp directory should exist");
		$this->assertTrue(vfsStreamWrapper::getRoot()->getChild('AbstractFactory')->getChild('tmp')->hasChild('index.php1234'), "index.php should exist");
	}
	
}
?>