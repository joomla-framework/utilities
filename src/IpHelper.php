<?php
/**
 * Part of the Joomla Framework Utilities Package
 *
 * @copyright  Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Utilities;

/**
 * Utility class for processing IP addresses
 *
 * @since  1.6.0
 */
abstract class IpHelper
{
	/**
	 * The IP address of the current visitor
	 *
	 * @var    string
	 * @since      1.6.0
	 * @deprecated 2.0 If you want to cache the IP address, you should handle that yourself.
	 */
	private static $ip;

	/**
	 * Should I allow IP overrides through X-Forwarded-For or Client-Ip HTTP headers?
	 *
	 * @var    boolean
	 * @since      1.6.0
	 * @deprecated 2.0 Use the parameter of IpHelper::getIp() instead.
	 */
	private static $allowIpOverrides = true;

	/**
	 * Get the current visitor's IP address
	 *
	 * @param   boolean  $allowOverride  If true, HTTP headers are taken into account
	 *
	 * @return  string
	 *
	 * @since   1.6.0
	 */
	public static function getIP($allowOverride = null)
	{
		// @todo Remove this block in 2.0 and change the parameter's default value from null to false
		if ($allowOverride === null)
		{
			$allowOverride = self::$allowIpOverrides;
		}

		return static::detectAndCleanIP($allowOverride);
	}

	/**
	 * Set the IP address of the current visitor
	 *
	 * @param   string  $ip  The visitor's IP address
	 *
	 * @return  void
	 *
	 * @since      1.6.0
	 * @deprecated 2.0 If you want to cache the IP address, you should handle that yourself.
	 */
	public static function setIP($ip)
	{
		self::$ip = $ip;
	}

	/**
	 * Is it an IPv6 IP address?
	 *
	 * @param   string  $ip  An IPv4 or IPv6 address
	 *
	 * @return  boolean
	 *
	 * @since   1.6.0
	 */
	public static function isIPv6($ip)
	{
		return filter_var(trim($ip), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
	}

	/**
	 * Checks if an IP is contained in a list of IPs or IP expressions
	 *
	 * @param   string        $ip        The IPv4/IPv6 address to check
	 * @param   array|string  $ipRanges  A comma-separated list or array of IP ranges to check against.
	 *                                   Range may be specified as from-to, CIDR or IP with netmask.
	 *
	 * @return  boolean
	 *
	 * @since   1.6.0
	 */
	public static function isInRanges($ip, $ipRanges = '')
	{
		// Reject empty IPs or ANY_ADDRESS
		if (empty($ip) || $ip === '0.0.0.0' || $ip === '::')
		{
			return false;
		}

		// IP can not be in an empty range
		if (empty($ipRanges))
		{
			return false;
		}

		// If the IP list is provided as string, convert it to an array
		if (!\is_array($ipRanges))
		{
			$ipRanges = preg_split('~,\s*~', $ipRanges);
		}

		$ipRanges = array_reduce(
			$ipRanges,
			function ($list, $range) {
				$range = trim($range);

				if (!empty($range))
				{
					$list[] = $range;
				}

				return $list;
			},
			array()
		);

		foreach ($ipRanges as $ipRange)
		{
			if (self::isInRange($ip, $ipRange))
			{
				return true;
			}
		}

		return false;
	}

	private static function isInRange($ip, $ipRange)
	{
		// Inclusive IP range, i.e. 123.123.123.123-124.125.126.127
		if (strpos($ipRange, '-') !== false)
		{
			list($from, $to) = preg_split('~\s*-\s*~', $ipRange, 2);

			return self::isInExplicitRange($ip, $from, $to);
		}

		// Netmask or CIDR provided
		if (strpos($ipRange, '/') !== false)
		{
			list($net, $mask) = explode('/', $ipRange, 2);

			// CIDR
			if (is_numeric($mask))
			{
				return self::isInCidrRange($ip, $net, $mask);
			}

			// Netmask
			return self::isInNetmaskRange($ip, $net, $mask);
		}

		// Partial IP address, i.e. 123.[123.[123.]]
		if (!self::isIPv6($ip) && preg_match('~\.$~', $ipRange))
		{
			$segments = explode('.', $ipRange);

			// Drop empty segment
			array_pop($segments);

			if (count($segments) > 3)
			{
				return false;
			}

			$mask = count($segments) * 8;

			while (count($segments) < 4)
			{
				$segments[] = 0;
			}

			$prefix = implode('.', $segments);

			return self::isInCidrRange($ip, $prefix, $mask);
		}

		// Range is a single IP
		$binaryIp    = self::toBits($ip);
		$binaryRange = self::toBits($ipRange);

		if (empty($binaryIp) || empty($binaryRange))
		{
			return false;
		}

		return $binaryIp === $binaryRange;
	}

	/**
	 * Works around the REMOTE_ADDR not containing the user's IP
	 *
	 * @return  void
	 *
	 * @since      1.6.0
	 * @codeCoverageIgnore
	 * @deprecated 2.0 No replacement, this is never used
	 */
	public static function workaroundIPIssues()
	{
		$ip = static::getIP();

		if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] === $ip)
		{
			return;
		}

		if (isset($_SERVER['REMOTE_ADDR']))
		{
			$_SERVER['JOOMLA_REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
		}
		elseif (getenv('REMOTE_ADDR'))
		{
			$_SERVER['JOOMLA_REMOTE_ADDR'] = getenv('REMOTE_ADDR');
		}

		$_SERVER['REMOTE_ADDR'] = $ip;
	}

	/**
	 * Should I allow the remote client's IP to be overridden by an X-Forwarded-For or Client-Ip HTTP header?
	 *
	 * @param   boolean  $newState  True to allow the override
	 *
	 * @return  void
	 *
	 * @since      1.6.0
	 * @deprecated 2.0 Use the parameter of IpHelper::getIp() instead.
	 */
	public static function setAllowIpOverrides($newState)
	{
		self::$allowIpOverrides = (bool) $newState;
	}

	/**
	 * Get the visitor's IP address.
	 *
	 * Automatically handles reverse proxies reporting the IPs of intermediate devices, like load balancers. Examples:
	 *
	 * - https://stackoverflow.com/questions/2422395/why-is-request-envremote-addr-returning-two-ips
	 *
	 * The solution used is assuming that the last IP address is the external one.
	 *
	 * @param   boolean  $allowOverride
	 *
	 * @return  string   The validated IP address as provided.
	 *                   If no IP is available, an empty string is returned.
	 *
	 * @since   1.6.0
	 */
	private static function detectAndCleanIP($allowOverride)
	{
		$rawIp  = static::detectIP($allowOverride);
		$ipList = preg_split('~,\s*~', $rawIp);

		$ipList = array_reduce(
			$ipList,
			function ($list, $ip) {
				$ip = filter_var(trim($ip), FILTER_VALIDATE_IP);

				if ($ip !== false)
				{
					$list[] = $ip;
				}

				return $list;
			},
			array()
		);

		return (string)array_pop($ipList);
	}

	/**
	 * Gets the visitor's IP address
	 *
	 * @param   boolean  $allowOverride
	 *
	 * @return  string   The IP address(es) as provided without validation.
	 *                   If no IP is available, an empty string is returned.
	 *
	 * @since   1.6.0
	 */
	private static function detectIP($allowOverride)
	{
		// Order matters!
		$indexes = array(
			'REMOTE_ADDR',
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
		);

		if (!$allowOverride)
		{
			$ip = ArrayHelper::getValue($_SERVER, 'REMOTE_ADDR', getenv('REMOTE_ADDR'));
		}
		else
		{
			$ip = '';

			foreach ($indexes as $index)
			{
				$ip = ArrayHelper::getValue($_SERVER, $index, $ip);
			}
		}

		return $ip;
	}

	/**
	 * Converts IP address to bits string
	 *
	 * @param   string  $ip  The IPv4 or IPv6 address
	 *
	 * @return  string
	 *
	 * @since   1.6.0
	 */
	private static function toBits($ip)
	{
		$packedIp = @inet_pton($ip);

		if ($packedIp === false)
		{
			return '';
		}

		$length   = self::isIPv6($ip) ? 16 : 4;
		$unpacked = unpack('A' . $length, $packedIp);
		$unpacked = str_split($unpacked[1]);
		$binaryIp = '';

		foreach ($unpacked as $char)
		{
			$binaryIp .= str_pad(decbin(\ord($char)), 8, '0', STR_PAD_LEFT);
		}

		$binaryIp = str_pad($binaryIp, $length * 8, '0', STR_PAD_RIGHT);

		return $binaryIp;
	}

	/**
	 * Check if two IP addresses have the same IP format
	 *
	 * @param   string  $ip1  The first IP address
	 * @param   string  $ip2  The second IP address
	 *
	 * @return boolean
	 */
	private static function ipVersionMatch($ip1, $ip2)
	{
		return self::isIPv6($ip1) === self::isIPv6($ip2);
	}

	/**
	 * @param   string  $ip    The IP address to check
	 * @param   string  $from  Lower bound of the range
	 * @param   string  $to    Upper bound of the range
	 *
	 * @return  boolean
	 */
	private static function isInExplicitRange($ip, $from, $to)
	{
		if (!self::ipVersionMatch($ip, $from) || !self::ipVersionMatch($ip, $to))
		{
			return false;
		}

		$binaryFrom = self::toBits($from);
		$binaryTo   = self::toBits($to);
		$binaryIp   = self::toBits($ip);

		if (empty($binaryFrom) || empty($binaryTo) || empty($binaryIp))
		{
			return false;
		}

		// Swap from/to if they're in the wrong order
		if ($binaryFrom > $binaryTo)
		{
			list($binaryFrom, $binaryTo) = array($binaryTo, $binaryFrom);
		}

		return $binaryFrom <= $binaryIp && $binaryIp <= $binaryTo;
	}

	/**
	 * @param   string   $ip
	 * @param   string   $prefix
	 * @param   integer  $mask
	 *
	 * @return  boolean
	 */
	private static function isInCidrRange($ip, $prefix, $mask)
	{
		if (!self::ipVersionMatch($ip, $prefix))
		{
			return false;
		}

		$binaryIp     = static::toBits($ip);
		$binaryPrefix = static::toBits($prefix);

		if (empty($binaryIp) || empty($binaryPrefix))
		{
			return false;
		}

		$maskedIp     = substr($binaryIp, 0, $mask);
		$maskedPrefix = substr($binaryPrefix, 0, $mask);

		return $maskedIp === $maskedPrefix;
	}

	/**
	 * @param   string  $ip
	 * @param   string  $prefix
	 * @param   string  $netmask
	 *
	 * @return boolean
	 */
	private static function isInNetmaskRange($ip, $prefix, $netmask)
	{
		$binaryMask = self::toBits($netmask);

		if (empty($binaryMask))
		{
			return false;
		}

		$mask = strlen(str_replace('0', '', $binaryMask));

		return self::isInCidrRange($ip, $prefix, $mask);
	}
}
