<?php
/**
 * @copyright  Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

use Joomla\Utilities\Loader;

/**
 * ArrayHelperTest
 *
 * @since  1.0
 */
class LoaderTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Holds the Loader instance for testing.
	 *
	 * @var  \Joomla\Utilities\Loader
	 */
	protected $fixture;

	/**
	 * Holds default stub paths
	 * 
	 * @var  array
	 */
	protected $subpaths;

	/**
	 * Setup the tests.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function setUp()
	{
		$this->stubpaths = array(
			JPATH_ROOT.DIRECTORY_SEPARATOR.'Tests'.DIRECTORY_SEPARATOR.'Stubs',
			JPATH_ROOT.DIRECTORY_SEPARATOR.'Tests'.DIRECTORY_SEPARATOR.'Stubs'.DIRECTORY_SEPARATOR.'stubdir'
		);
		$this->fixture = new Loader;
	}

	/**
	 * Tear down the tests.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function tearDown()
	{
		$this->fixture = null;
	}

	/**
	 * Tests the constructor.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function testConstructor()
	{
		$this->assertAttributeEquals(
			array(),
			'paths',
			$this->fixture,
			'A default new object should have a null queue.'
		);
	}

	/**
	 * Tests the constructor.
	 *
	 * @return  void
	 *
	 * @since   2.0
	 */
	public function testConstructorWithPaths()
	{
		$loader = new Loader($this->stubpaths);

		$this->assertAttributeEquals(
			$this->stubpaths,
			'paths',
			$loader,
			'The new object should have defined path set.'
		);
	}

	/**
	 * Tests setExtension method
	 * 
	 * @return  void
	 * 
	 * @covers  Joomla\Utilities\Loader::setExtension
	 * @since   2.0
	 */
	public function testSetExtension()
	{
		$this->fixture->setExtension('ini');

		$this->assertAttributeEquals(
			'ini',
			'extension',
			$this->fixture,
			'The loader should nave extension changed.'
		);
	}

	/**
	 * Tests getExtension method
	 * 
	 * @return  void
	 * 
	 * @covers  Joomla\Utilities\Loader::getExtension
	 * @since   2.0
	 */
	public function testGetExtension()
	{
		$this->fixture->setExtension('xml');

		$this->assertEquals(
			'xml',
			$this->fixture->getExtension(),
			'The loader object should return its extension.'
		);
	}

	/**
	 * Tests setPaths method
	 * 
	 * @return  void
	 * 
	 * @covers  Joomla\Utilities\Loader::setPaths
	 * @since   2.0
	 */
	public function testSetPaths()
	{
		$this->fixture->setPaths(JPATH_ROOT.DIRECTORY_SEPARATOR.'Tests'.DIRECTORY_SEPARATOR.'Stubs');

		$this->assertEquals(
			array(JPATH_ROOT.DIRECTORY_SEPARATOR.'Tests'.DIRECTORY_SEPARATOR.'Stubs'),
			$this->fixture->getPaths(),
			'A single path must be inserted into array'
		);

		$this->fixture->setPaths($this->stubpaths);

		$this->assertEquals(
			$this->stubpaths,
			$this->fixture->getPaths(),
			'Reset of paths must rewrite the queue'
		);
	}

	/**
	 * Tests pushPath method
	 * 
	 * @return  void
	 * 
	 * @covers  Joomla\Utilities\Loader::pushPath
	 * @since   2.0
	 */
	public function testPushPath()
	{
		$this->fixture->setPaths(JPATH_ROOT.DIRECTORY_SEPARATOR.'Tests'.DIRECTORY_SEPARATOR.'Stubs');

		$this->fixture->pushPath(JPATH_ROOT.DIRECTORY_SEPARATOR.'Tests'.DIRECTORY_SEPARATOR.'Stubs'.DIRECTORY_SEPARATOR.'stubdir');

		$this->assertEquals(
			array(
				JPATH_ROOT.DIRECTORY_SEPARATOR.'Tests'.DIRECTORY_SEPARATOR.'Stubs',
				JPATH_ROOT.DIRECTORY_SEPARATOR.'Tests'.DIRECTORY_SEPARATOR.'Stubs'.DIRECTORY_SEPARATOR.'stubdir'
			),
			$this->fixture->getPaths(),
			'"stubdir" path must be at the end of array'
		);
	}

	/**
	 * Tests unshiftPath method
	 * 
	 * @return  void
	 * 
	 * @covers  Joomla\Utilities\Loader::unshiftPath
	 * @since   2.0
	 */
	public function testUnshiftPath()
	{

		$this->fixture->setPaths(JPATH_ROOT.DIRECTORY_SEPARATOR.'Tests'.DIRECTORY_SEPARATOR.'Stubs');

		$this->fixture->unshiftPath(JPATH_ROOT.DIRECTORY_SEPARATOR.'Tests'.DIRECTORY_SEPARATOR.'Stubs'.DIRECTORY_SEPARATOR.'stubdir');

		$this->assertEquals(
			array(
				JPATH_ROOT.DIRECTORY_SEPARATOR.'Tests'.DIRECTORY_SEPARATOR.'Stubs'.DIRECTORY_SEPARATOR.'stubdir',
				JPATH_ROOT.DIRECTORY_SEPARATOR.'Tests'.DIRECTORY_SEPARATOR.'Stubs'
			),
			$this->fixture->getPaths(),
			'"stubdir" path must be at the beginning of array'
		);
	}

	/**
	 * Tests exists method
	 * 
	 * @return  void
	 * 
	 * @covers  Joomla\Utilities\Loader::exists
	 * @since   2.0
	 */
	public function testExists()
	{
		$this->fixture->setPaths($this->stubpaths);

		$this->assertTrue(
			$this->fixture->exists('testfile.json')
		);

		$this->assertTrue(
			$this->fixture->exists('anotherfile.ini'),
			'File in subdirectory should be found'
		);
	}

	/**
	 * Tests getSource method
	 * 
	 * @return  void
	 * 
	 * @covers  Joomla\Utilities\Loader::getSource
	 * @since   2.0
	 */
	public function testGetSource()
	{
		$this->fixture->setPaths($this->stubpaths);

		$source = '[section]' . "\n" . 'foo=bar';

		$this->assertEquals(
			$source,
			$this->fixture->getSource('testfile.ini')
		);
	}

	/**
	 * Tests getObject method
	 * 
	 * @return  void
	 * 
	 * @covers  Joomla\Utilities\Loader::getObject
	 * @since   2.0
	 */
	public function testGetObject()
	{
		$this->fixture->setPaths($this->stubpaths);

		$object = new stdClass;
		$object->foo = 'bar';

		$this->assertEquals(
			$object,
			$this->fixture->getObject('testfile.json')
		);
	}
}