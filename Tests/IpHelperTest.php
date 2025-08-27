<?php
/**
 * @copyright  Copyright (C) 2021 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Utilities\Tests;

use Joomla\Utilities\IpHelper;
use PHPUnit\Framework\TestCase;

/**
 * IpHelperTest
 *
 * @since  __DEPLOY_VERSION__
 */
class IpHelperTest extends TestCase
{
	const IPv4_ADDRESS       = '192.168.1.129';
	const IPv4_SUBNET        = '192.168.1.0/8';
	const IPv4_NETMASK       = '255.255.255.0';
	const IPv4_NETWORK_RANGE = '192.168.1.0-192.168.1.255';
	const IPv4_SWAPPED_RANGE = '192.168.1.255-192.168.1.0';
	const IPv4_LOCALHOST     = '127.0.0.1';
	const IPv4_ANY_ADDRESS   = '0.0.0.0';

	const IPv6_EXPANDED_ADDRESS   = '2001:0db8:85a3:08d3:1319:8a2e:0370:7347';
	const IPv6_COMPRESSED_ADDRESS = '2001:db8:85a3:8d3:1319:8a2e:370:7347';
	const IPv6_SUBNET             = '2001:db8:85a3:880:0:0:0:0/57';
	const IPv6_NETMASK            = 'ffff:ffff:ffff:ff80:0:0:0:0';
	const IPv6_NETWORK_RANGE      = '2001:0db8:85a3:0880:0000:0000:0000:0000-2001:0db8:85a3:08ff:ffff:ffff:ffff:ffff';
	const IPv6_SWAPPED_RANGE      = '2001:0db8:85a3:08ff:ffff:ffff:ffff:ffff-2001:0db8:85a3:0880:0000:0000:0000:0000';
	const IPv6_LOCALHOST          = '::1';
	const IPv6_ANY_ADDRESS        = '::';

	/**
	 * @var array
	 */
	private $backupServer;

	/**
	 * @var array
	 */
	private $backupEnv;

	/**
	 * Backup environment
	 */
	protected function setUp()
	{
		$this->backupServer = $_SERVER;
		$this->backupEnv    = $_ENV;

		unset($_SERVER['HTTP_CLIENT_IP']);
		unset($_SERVER['HTTP_X_FORWARDED_FOR']);
		unset($_SERVER['HTTP_X_FORWARDED']);
		unset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']);
		unset($_SERVER['HTTP_FORWARDED_FOR']);
		unset($_SERVER['HTTP_FORWARDED']);
		unset($_SERVER['REMOTE_ADDR']);

		IpHelper::setIP(null);
	}

	/**
	 * Restore environment
	 */
	protected function tearDown()
	{
		$_SERVER = $this->backupServer;
		$_ENV    = $this->backupEnv;
	}

	/**
	 * Sample client IPs
	 *
	 * @return array
	 */
	public function sampleClientIPs()
	{
		$indexes = array(
			'HTTP_X_FORWARDED_FOR',
			'HTTP_CLIENT_IP',
			#'HTTP_X_FORWARDED',
			#'HTTP_X_CLUSTER_CLIENT_IP',
			#'HTTP_FORWARDED_FOR',
			#'HTTP_FORWARDED',
			'REMOTE_ADDR',
		);

		// ip => normalised
		$ips = array(
			'127.0.0.1'                   => '127.0.0.1',
			'192.168.178.32'              => '192.168.178.32',
			'10.194.95.79'                => '10.194.95.79',
			'75.184.124.93, 10.194.95.79' => '10.194.95.79',
			'10.194.95.79, 75.184.124.93' => '75.184.124.93',
			'0.0.0.0'                     => '0.0.0.0',
			'ff05::1'                     => 'ff05::1',
			'fake'                        => '',
		);

		$cases = array();

		foreach ($indexes as $index)
		{
			foreach ($ips as $ip => $normalised)
			{
				$cases[] = array(
					$index,
					$ip,
					$normalised
				);
			}
		}

		return $cases;
	}

	/**
	 * @testdox      IP address is retrieved from $_SERVER global
	 *
	 * @param   string  $index       The index for the $_SERVER global
	 * @param   string  $ip          The IP address in the global
	 * @param   string  $normalised  The IP address to be returned
	 *
	 * @dataProvider sampleClientIPs
	 */
	public function testGetIpFromServerWithOverride($index, $ip, $normalised)
	{
		$_SERVER[$index] = $ip;

		IpHelper::setIP(null);
		IpHelper::setAllowIpOverrides(true);

		$this->assertEquals($normalised, IpHelper::getIP());
	}

	/**
	 * @testdox      IP address is retrieved from $_SERVER['REMOTE_ADDR'] if override is prohibited
	 *
	 * @param   string  $index       The index for the $_SERVER global
	 * @param   string  $ip          The IP address in the global
	 * @param   string  $normalised  The IP address to be returned
	 *
	 * @dataProvider sampleClientIPs
	 */
	public function testGetIpFromServerWithoutOverride($index, $ip, $normalised)
	{
		$_SERVER[$index]        = $ip;
		$_SERVER['REMOTE_ADDR'] = '80.80.80.80';

		IpHelper::setAllowIpOverrides(false);

		$this->assertEquals('80.80.80.80', IpHelper::getIP());
	}

	/**
	 * Sample IPs wit format information
	 *
	 * @return \string[][]
	 */
	public function sampleIPsWithFormat()
	{
		// ip => format
		return array(
			array('127.0.0.1', 'IPv4'),
			array('::1', 'IPv6'),
			array('::127.0.0.1', 'IPv6'),
			array('fake:ip', 'invalid'),
		);
	}

	/**
	 * @param   string  $ip      The IP to check
	 * @param   string  $format  The true format
	 *
	 * @dataProvider sampleIPsWithFormat
	 */
	public function testIsIp6($ip, $format)
	{
		$actual   = IpHelper::isIPv6($ip);
		$expected = $format === 'IPv6';

		$this->assertEquals($expected, $actual);
	}

	/**
	 * Sample IPs with IP Table information
	 *
	 * @return array[]
	 */
	public function sampleIPsWithTable()
	{
		// IP, IP Table, isInTable
		return array(
			'IPv4 address - IPv4 address'                       => array(self::IPv4_ADDRESS, self::IPv4_ADDRESS, true),

			'IPv4 address - IPv4 subnet'                        => array(self::IPv4_ADDRESS, self::IPv4_SUBNET, true),
			'IPv4 address - IPv4 network range'                 => array(self::IPv4_ADDRESS, self::IPv4_NETWORK_RANGE, true),
			'IPv4 address - IPv4 swapped range'                 => array(self::IPv4_ADDRESS, self::IPv4_SWAPPED_RANGE, true),
			'IPv4 address - IPv4 address/netmask'               => array(self::IPv4_ADDRESS, self::IPv4_ADDRESS . '/' . self::IPv4_NETMASK, true),
			'IPv4 localhost - IPv4 subnets (list)'              => array(self::IPv4_LOCALHOST, self::IPv4_SUBNET . ', ' . self::IPv4_LOCALHOST . '/8', true),
			'IPv4 localhost - IPv4 subnets (array)'             => array(self::IPv4_LOCALHOST, array(self::IPv4_SUBNET, self::IPv4_LOCALHOST . '/8'), true),

			'IPv4 address - 1 byte'                             => array(self::IPv4_LOCALHOST, '127.', true),
			'IPv4 address - 2 bytes'                            => array(self::IPv4_LOCALHOST, '127.0.', true),
			'IPv4 address - 3 bytes'                            => array(self::IPv4_LOCALHOST, '127.0.0.', true),

			'IPv4 address - IPv6 expanded address'              => array(self::IPv4_ADDRESS, self::IPv6_EXPANDED_ADDRESS, false),
			'IPv4 address - IPv6 subnet'                        => array(self::IPv4_ADDRESS, self::IPv6_SUBNET, false),
			'IPv4 address - IPv6 network range'                 => array(self::IPv4_ADDRESS, self::IPv6_NETWORK_RANGE, false),

			'IPv4 any address - IPv4 subnet'                    => array(self::IPv4_ANY_ADDRESS, self::IPv4_SUBNET, false),
			'IPv4 localhost - IPv4 subnet'                      => array(self::IPv4_LOCALHOST, self::IPv4_SUBNET, false),

			'empty - IPv4 subnet'                               => array(null, self::IPv4_SUBNET, false),
			'fake.ip - IPv4 subnet'                             => array('fake.ip', self::IPv4_SUBNET, false),
			'IPv4 address - empty range'                        => array(self::IPv4_ADDRESS, null, false),
			'IPv4 address - invalid.ip/range'                   => array(self::IPv4_ADDRESS, 'invalid.ip/range', false),
			'IPv4 address - partial invalid range'              => array(self::IPv4_ADDRESS, self::IPv4_ADDRESS . '-invalid', false),

			'IPv6 expanded address - IPv6 expanded address'     => array(self::IPv6_EXPANDED_ADDRESS, self::IPv6_EXPANDED_ADDRESS, true),
			'IPv6 expanded address - IPv6 compressed address'   => array(self::IPv6_EXPANDED_ADDRESS, self::IPv6_COMPRESSED_ADDRESS, true),
			'IPv6 compressed address - IPv6 expanded address'   => array(self::IPv6_COMPRESSED_ADDRESS, self::IPv6_EXPANDED_ADDRESS, true),
			'IPv6 compressed address - IPv6 compressed address' => array(self::IPv6_COMPRESSED_ADDRESS, self::IPv6_COMPRESSED_ADDRESS, true),

			'IPv6 expanded address - IPv6 subnet'               => array(self::IPv6_EXPANDED_ADDRESS, self::IPv6_SUBNET, true),
			'IPv6 expanded address - IPv6 network range'        => array(self::IPv6_EXPANDED_ADDRESS, self::IPv6_NETWORK_RANGE, true),
			'IPv6 expanded address - IPv6 swapped range'        => array(self::IPv6_EXPANDED_ADDRESS, self::IPv6_SWAPPED_RANGE, true),
			'IPv6 expanded address - IPv6 address/netmask'      => array(self::IPv6_EXPANDED_ADDRESS, self::IPv6_EXPANDED_ADDRESS . '/' . self::IPv6_NETMASK, true),
			'IPv6 compressed address - IPv6 subnet'             => array(self::IPv6_COMPRESSED_ADDRESS, self::IPv6_SUBNET, true),
			'IPv6 compressed address - IPv6 network range'      => array(self::IPv6_COMPRESSED_ADDRESS, self::IPv6_NETWORK_RANGE, true),
			'IPv6 compressed address - IPv6 swapped range'      => array(self::IPv6_COMPRESSED_ADDRESS, self::IPv6_SWAPPED_RANGE, true),
			'IPv6 compressed address - IPv6 address/netmask'    => array(self::IPv6_COMPRESSED_ADDRESS, self::IPv6_EXPANDED_ADDRESS . '/' . self::IPv6_NETMASK, true),
			'IPv6 localhost - IPv6 subnets (list)'              => array(self::IPv6_LOCALHOST, self::IPv6_SUBNET . ', ' . self::IPv6_LOCALHOST . '/128', true),
			'IPv6 localhost - IPv6 subnets (array)'             => array(self::IPv6_LOCALHOST, array(self::IPv6_SUBNET, self::IPv6_LOCALHOST . '/128'), true),

			'IPv6 address - IPv4 address'                       => array(self::IPv6_EXPANDED_ADDRESS, self::IPv4_ADDRESS, false),
			'IPv6 address - IPv4 subnet'                        => array(self::IPv6_EXPANDED_ADDRESS, self::IPv4_SUBNET, false),
			'IPv6 address - IPv4 network range'                 => array(self::IPv6_EXPANDED_ADDRESS, self::IPv4_NETWORK_RANGE, false),

			'IPv6 any address - IPv6 subnet'                    => array(self::IPv6_ANY_ADDRESS, self::IPv6_SUBNET, false),
			'IPv6 localhost - IPv6 subnet'                      => array(self::IPv6_LOCALHOST, self::IPv6_SUBNET, false),

			'empty - IPv6 subnet'                               => array(null, self::IPv6_SUBNET, false),
			'fake:ip - IPv6 subnet'                             => array('fake:ip', self::IPv6_SUBNET, false),
			'IPv6 address - empty range'                        => array(self::IPv6_COMPRESSED_ADDRESS, null, false),
			'IPv6 address - invalid:ip/range'                   => array(self::IPv6_COMPRESSED_ADDRESS, 'invalid:ip/range', false),
			'IPv6 address - partial invalid range'              => array(self::IPv6_COMPRESSED_ADDRESS, self::IPv6_COMPRESSED_ADDRESS . '-invalid', false),
		);
	}

	/**
	 * @param   string   $ip
	 * @param   string   $ipTable
	 * @param   boolean  $expected
	 *
	 * @dataProvider sampleIPsWithTable
	 */
	public function testIpInList($ip, $ipTable, $expected)
	{
		$this->assertEquals($expected, IpHelper::isInRanges($ip, $ipTable));
	}
}
