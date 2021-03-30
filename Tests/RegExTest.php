<?php
/**
 * @copyright  Copyright (C) 2021 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Utilities\Tests;

use Joomla\Utilities\RegEx;
use PHPUnit\Framework\TestCase;

/**
 * ArrayHelperTest
 *
 * @since  __DEPLOY_VERSION__
 */
class RegExTest extends TestCase
{
	public function testExample3()
	{
		$scheme = RegEx::optional('http://');
		$host   = RegEx::capture('[^/]+', 'host');

		$matches = RegEx::match('~^' . $scheme . $host . '~', 'http://www.php.net/index.html');

		$host = $matches['host'];

		$segment = '[^.]+';

		$domain  = RegEx::capture($segment . '\.' . $segment . '$', 'domain');
		$matches = RegEx::match('~' . $domain . '~', $host);

		self::assertEquals('php.net', $matches['domain']);
	}

	public function testExample4()
	{
		$str = 'foobar: 2008';

		$name  = RegEx::capture('\w+', 'name');
		$digit = RegEx::capture('\d+', 'digit');

		$matches = RegEx::match('~' . $name . ': ' . $digit . '~', $str);

		self::assertEquals(
			array(
				'name'  => 'foobar',
				'digit' => '2008'
			),
			$matches
		);
	}

	public function testOptional()
	{
		$regex = 'a' . RegEx::optional('b') . 'c';

		self::assertEquals(
			array('result' => 'ac'),
			RegEx::match(
				'~' . RegEx::capture($regex, 'result') . '~',
				'aaaacccc'
			)
		);

		self::assertEquals(
			array('result' => 'abc'),
			RegEx::match(
				'~' . RegEx::capture($regex, 'result') . '~',
				'aaaabcccc'
			)
		);

		self::assertEquals(
			array(),
			RegEx::match(
				'~' . RegEx::capture($regex, 'result') . '~',
				'aaaabbcccc'
			)
		);
	}

	public function testOneOrMore()
	{
		$regex = 'a' . RegEx::oneOrMore('b') . 'c';

		self::assertEquals(
			array(),
			RegEx::match(
				'~' . RegEx::capture($regex, 'result') . '~',
				'aaaacccc'
			)
		);

		self::assertEquals(
			array('result' => 'abc'),
			RegEx::match(
				'~' . RegEx::capture($regex, 'result') . '~',
				'aaaabcccc'
			)
		);

		self::assertEquals(
			array('result' => 'abbc'),
			RegEx::match(
				'~' . RegEx::capture($regex, 'result') . '~',
				'aaaabbcccc'
			)
		);
	}

	public function testNoneOrMore()
	{
		$regex = 'a' . RegEx::noneOrMore('b') . 'c';

		self::assertEquals(
			array('result' => 'ac'),
			RegEx::match(
				'~' . RegEx::capture($regex, 'result') . '~',
				'aaaacccc'
			)
		);

		self::assertEquals(
			array('result' => 'abc'),
			RegEx::match(
				'~' . RegEx::capture($regex, 'result') . '~',
				'aaaabcccc'
			)
		);

		self::assertEquals(
			array('result' => 'abbc'),
			RegEx::match(
				'~' . RegEx::capture($regex, 'result') . '~',
				'aaaabbcccc'
			)
		);
	}

	public function testAnyOfList()
	{
		$regex = 'a' . RegEx::anyOf('1', '2', '3') . 'c';

		self::assertEquals(
			array(),
			RegEx::match(
				'~' . RegEx::capture($regex, 'result') . '~',
				'aaaacccc'
			)
		);

		self::assertEquals(
			array('result' => 'a1c'),
			RegEx::match(
				'~' . RegEx::capture($regex, 'result') . '~',
				'aaaa1cccc'
			)
		);

		self::assertEquals(
			array('result' => 'a2c'),
			RegEx::match(
				'~' . RegEx::capture($regex, 'result') . '~',
				'aaaa2cccc'
			)
		);
	}

	public function testAnyOfArray()
	{
		$regex = 'a' . RegEx::anyOf(array('1', '2', '3')) . 'c';

		self::assertEquals(
			array(),
			RegEx::match(
				'~' . RegEx::capture($regex, 'result') . '~',
				'aaaacccc'
			)
		);

		self::assertEquals(
			array('result' => 'a1c'),
			RegEx::match(
				'~' . RegEx::capture($regex, 'result') . '~',
				'aaaa1cccc'
			)
		);

		self::assertEquals(
			array('result' => 'a2c'),
			RegEx::match(
				'~' . RegEx::capture($regex, 'result') . '~',
				'aaaa2cccc'
			)
		);
	}
}
