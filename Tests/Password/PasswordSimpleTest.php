<?php
/**
 * @copyright  Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Crypt\Tests;

use Joomla\Crypt\PasswordInterface;
use Joomla\Crypt\Password\Simple;

/**
 * Test class for JCryptPasswordSimple.
 *
 * @since  1.0
 */
class PasswordSimpleTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Data provider for testCreate method.
	 *
	 * @return array
	 */
	public function createData()
	{
		// Password, type, salt, expected cost
		return array(
			'Blowfish' => array('password', PasswordInterface::BLOWFISH, 'ABCDEFGHIJKLMNOPQRSTUV',
				'$2y$10$ABCDEFGHIJKLMNOPQRSTUOiAi7OcdE4zRCh6NcGWusEcNPtq6/w8.'),
			'Blowfish2' => array('password', '$2a$', 'ABCDEFGHIJKLMNOPQRSTUV',
				'$2y$10$ABCDEFGHIJKLMNOPQRSTUOiAi7OcdE4zRCh6NcGWusEcNPtq6/w8.'),
			'MD5' => array('password', PasswordInterface::MD5, 'ABCDEFGHIJKL',
				'$1$ABCDEFGH$hGGndps75hhROKqu/zh9q1'),
			'Joomla' => array('password', PasswordInterface::JOOMLA, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ123456',
				'883a96d8da5440781fe7b60f1d4ae2b3:ABCDEFGHIJKLMNOPQRSTUVWXYZ123456'),
			'Blowfish_5' => array('password', PasswordInterface::BLOWFISH, 'ABCDEFGHIJKLMNOPQRSTUV',
				'$2y$05$ABCDEFGHIJKLMNOPQRSTUOvv7EU5o68GAoLxyfugvULZR70IIMZqW', 5),
			'default' => array('password', null, 'ABCDEFGHIJKLMNOPQRSTUV',
				'$2y$05$ABCDEFGHIJKLMNOPQRSTUOvv7EU5o68GAoLxyfugvULZR70IIMZqW', 5)
		);
	}

	/**
	 * Data provider for testCreateException method.
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	public function createExceptionData()
	{
		return array(
			'Bogus' => array('password', 'abc', 'ABCDEFGHIJKLMNOPQRSTUV', '$2y$10$ABCDEFGHIJKLMNOPQRSTUOiAi7OcdE4zRCh6NcGWusEcNPtq6/w8.', 10),
		);
	}

	/**
	 * Tests create method for expected exception
	 *
	 * @param   string   $password  The password to create
	 * @param   string   $type      The type of hash
	 * @param   string   $salt      The salt to be used
	 * @param   string   $expected  The expected result
	 * @param   integer  $cost      The cost value
	 *
	 * @covers  Joomla\Crypt\Password\Simple::create
	 *
	 * @expectedException  InvalidArgumentException
	 *
	 * @return void
	 *
	 * @dataProvider  createExceptionData
	 *
	 * @since  1.0
	 */
	public function testCreateException($password, $type, $salt, $expected, $cost)
	{
		$hasher = $this->getMock('Joomla\\Crypt\\Password\\Simple', array('getSalt'));
		$hasher->setCost($cost);

		$hasher->expects($this->any())
			->method('getSalt')
			->with(strlen($salt))
			->will($this->returnValue($salt));

		$this->assertEquals(
			$expected,
			$hasher->create($password, $type)
		);
	}

	/**
	 * Tests the Joomla\Crypt\Password\Simple::Create method.
	 *
	 * @param   string   $password  The password to create
	 * @param   string   $type      The type of hash
	 * @param   string   $salt      The salt to be used
	 * @param   string   $expected  The expected result
	 * @param   integer  $cost      The cost value
	 *
	 * @return        void
	 *
	 * @dataProvider  createData
	 *
	 * @since   1.0
	 */
	public function testCreate($password, $type, $salt, $expected, $cost = 10)
	{
		$hasher = $this->getMock('Joomla\\Crypt\\Password\\Simple', array('getSalt'));

		$hasher->setCost($cost);

		$hasher->expects($this->any())
			->method('getSalt')
			->with(strlen($salt))
			->will($this->returnValue($salt));

		$this->assertEquals(
			$expected,
			$hasher->create($password, $type)
		);
	}

	/**
	 * Data Provider for testVerify.
	 *
	 * @return array
	 */
	public function verifyData()
	{
		// Password, hash, expected
		return array(
			'Blowfish Valid:' => array('password', '$2y$10$ABCDEFGHIJKLMNOPQRSTUOiAi7OcdE4zRCh6NcGWusEcNPtq6/w8.', true),
			'Blowfish Invalid:' => array('wrong password', '$2y$10$ABCDEFGHIJKLMNOPQRSTUOiAi7OcdE4zRCh6NcGWusEcNPtq6/w8.', false),
			'MD5 Valid' => array('password', '$1$ABCDEFGH$hGGndps75hhROKqu/zh9q1', true),
			'MD5 Invalid' => array('passw0rd', '$1$ABCDEFGH$hGGndps75hhROKqu/zh9q1', false),
			'Joomla Valid' => array('password', '883a96d8da5440781fe7b60f1d4ae2b3:ABCDEFGHIJKLMNOPQRSTUVWXYZ123456', true),
			'Joomla Invalid' => array('passw0rd', '883a96d8da5440781fe7b60f1d4ae2b3:ABCDEFGHIJKLMNOPQRSTUVWXYZ123456', false)
		);
	}

	/**
	 * Tests the verify method.
	 *
	 * @param   string  $password     The password to verify
	 * @param   string  $hash         The hash
	 * @param   string  $expectation  The expected result
	 *
	 * @covers        Joomla\Crypt\Password\Simple::verify
	 * @dataProvider  verifyData
	 *
	 * @return void
	 */
	public function testVerify($password, $hash, $expectation)
	{
		$hasher = new Simple;

		$this->assertEquals($hasher->verify($password, $hash), $expectation);
	}

	/**
	 * Data Provider for testDefaultType
	 *
	 * @return array
	 *
	 * @since   1.0
	 */
	public function defaultTypeData()
	{
		// Type, expectation
		return array(
			'Joomla' => array('Joomla','Joomla'),
			'Null' => array('','$2y$'),
		);
	}

	/**
	 * Tests the setDefaultType method.
	 *
	 * @param   string  $type         The proposed default type
	 * @param   string  $expectation  The expected value of $this->defaultType
	 *
	 * @covers        Joomla\Crypt\Password\Simple::setDefaultType
	 * @dataProvider  defaultTypeData
	 *
	 * @return void
	 *
	 * @since   1.0
	 */
	public function testSetDefaultType($type, $expectation)
	{
		$test = new Simple;
		$test->setDefaultType($type);
		$this->assertThat(
			$test->getDefaultType(),
			$this->equalTo($expectation)
		);
	}

	/**
	 * Tests the getDefaultType method.
	 *
	 * @param   string  $type         The proposed default type
	 * @param   string  $expectation  The expected value of $this->defaultType
	 *
	 * @covers        Joomla\Crypt\Password\Simple::getDefaultType
	 * @dataProvider  defaultTypeData
	 *
	 * @return void
	 *
	 * @since   1.0
	 */
	public function testGetDefaultType($type, $expectation)
	{
		$test = new Simple;
		$test->setDefaultType($type);

		$this->assertThat(
			$test->getDefaultType(),
			$this->equalTo($expectation)
		);
	}
}
