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
	public static function getIp($allowOverride = null)
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
	public static function setIp($ip)
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
	 * @param   string        $ip       The IPv4/IPv6 address to check
	 * @param   array|string  $ipTable  An IP expression (or a comma-separated or array list of IP expressions) to check against
	 *
	 * @return  boolean
	 *
	 * @since   1.6.0
	 */
	public static function IPinList($ip, $ipTable = '')
	{
		// No point proceeding with an empty IP list
		if (empty($ipTable))
		{
			return false;
		}

		// If the IP list is not an array, convert it to an array
		if (!\is_array($ipTable))
		{
			if (strpos($ipTable, ',') !== false)
			{
				$ipTable = explode(',', $ipTable);
				$ipTable = array_map('trim', $ipTable);
			}
			else
			{
				$ipTable = trim($ipTable);
				$ipTable = array($ipTable);
			}
		}

		// If no IP address is found, return false
		if ($ip === '0.0.0.0')
		{
			return false;
		}

		// If no IP is given, return false
		if (empty($ip))
		{
			return false;
		}

		// Get the IP's in_adds representation
		$myIP = @inet_pton($ip);

		// If the IP is in an unrecognisable format, quite
		if ($myIP === false)
		{
			return false;
		}

		$ipv6 = static::isIPv6($ip);

		foreach ($ipTable as $ipExpression)
		{
			$ipExpression = trim($ipExpression);

			// Inclusive IP range, i.e. 123.123.123.123-124.125.126.127
			if (strpos($ipExpression, '-') !== false)
			{
				list($from, $to) = explode('-', $ipExpression, 2);

				if ($ipv6 && (!static::isIPv6($from) || !static::isIPv6($to)))
				{
					// Do not apply IPv4 filtering on an IPv6 address
					continue;
				}

				if (!$ipv6 && (static::isIPv6($from) || static::isIPv6($to)))
				{
					// Do not apply IPv6 filtering on an IPv4 address
					continue;
				}

				$from = @inet_pton(trim($from));
				$to   = @inet_pton(trim($to));

				// Sanity check
				if (($from === false) || ($to === false))
				{
					continue;
				}

				// Swap from/to if they're in the wrong order
				if ($from > $to)
				{
					list($from, $to) = array($to, $from);
				}

				if (($myIP >= $from) && ($myIP <= $to))
				{
					return true;
				}
			}
			// Netmask or CIDR provided
			elseif (strpos($ipExpression, '/') !== false)
			{
				$binaryip = static::inetToBits($myIP);

				list($net, $maskbits) = explode('/', $ipExpression, 2);

				if ($ipv6 && !static::isIPv6($net))
				{
					// Do not apply IPv4 filtering on an IPv6 address
					continue;
				}

				if (!$ipv6 && static::isIPv6($net))
				{
					// Do not apply IPv6 filtering on an IPv4 address
					continue;
				}

				if ($ipv6 && strpos($maskbits, ':') !== false)
				{
					// Perform an IPv6 CIDR check
					if (static::checkIPv6CIDR($myIP, $ipExpression))
					{
						return true;
					}

					// If we didn't match it proceed to the next expression
					continue;
				}

				if (!$ipv6 && strpos($maskbits, '.') !== false)
				{
					// Convert IPv4 netmask to CIDR
					$long     = ip2long($maskbits);
					$base     = ip2long('255.255.255.255');
					$maskbits = 32 - log(($long ^ $base) + 1, 2);
				}

				// Convert network IP to in_addr representation
				$net = @inet_pton($net);

				// Sanity check
				if ($net === false)
				{
					continue;
				}

				// Get the network's binary representation
				$expectedNumberOfBits = $ipv6 ? 128 : 24;
				$binarynet            = str_pad(static::inetToBits($net), $expectedNumberOfBits, '0', STR_PAD_RIGHT);

				// Check the corresponding bits of the IP and the network
				$ipNetBits = substr($binaryip, 0, $maskbits);
				$netBits   = substr($binarynet, 0, $maskbits);

				if ($ipNetBits === $netBits)
				{
					return true;
				}
			}
			elseif ($ipv6)
			{
				// IPv6: Only single IPs are supported
				$ipExpression = trim($ipExpression);

				if (!static::isIPv6($ipExpression))
				{
					continue;
				}

				$ipCheck = @inet_pton($ipExpression);

				if ($ipCheck === false)
				{
					continue;
				}

				if ($ipCheck === $myIP)
				{
					return true;
				}
			}
			else
			{
				// Standard IPv4 address, i.e. 123.123.123.123 or partial IP address, i.e. 123.[123.][123.][123]
				$dots = 0;

				if (substr($ipExpression, -1) === '.')
				{
					// Partial IP address. Convert to CIDR and re-match
					foreach (count_chars($ipExpression, 1) as $i => $val)
					{
						if ($i === 46)
						{
							$dots = $val;
						}
					}

					switch ($dots)
					{
						case 1:
							$netmask      = '255.0.0.0';
							$ipExpression .= '0.0.0';

							break;

						case 2:
							$netmask      = '255.255.0.0';
							$ipExpression .= '0.0';

							break;

						case 3:
							$netmask      = '255.255.255.0';
							$ipExpression .= '0';

							break;

						default:
							$dots = 0;
					}

					if ($dots)
					{
						$binaryip = static::inetToBits($myIP);

						// Convert netmask to CIDR
						$long     = ip2long($netmask);
						$base     = ip2long('255.255.255.255');
						$maskbits = 32 - log(($long ^ $base) + 1, 2);

						$net = @inet_pton($ipExpression);

						// Sanity check
						if ($net === false)
						{
							continue;
						}

						// Get the network's binary representation
						$expectedNumberOfBits = $ipv6 ? 128 : 24;
						$binarynet            = str_pad(
							static::inetToBits($net),
							$expectedNumberOfBits,
							'0',
							STR_PAD_RIGHT
						);

						// Check the corresponding bits of the IP and the network
						$ipNetBits = substr($binaryip, 0, $maskbits);
						$netBits   = substr($binarynet, 0, $maskbits);

						if ($ipNetBits === $netBits)
						{
							return true;
						}
					}
				}

				if (!$dots)
				{
					$ip = @inet_pton(trim($ipExpression));

					if ($ip === $myIP)
					{
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Works around the REMOTE_ADDR not containing the user's IP
	 *
	 * @return  void
	 *
	 * @since      1.6.0
	 * @deprecated 2.0 No replacement, this is never used
	 */
	public static function workaroundIPIssues()
	{
		$ip = static::getIp();

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
		self::$allowIpOverrides = (bool)$newState;
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
	protected static function detectAndCleanIP($allowOverride)
	{
		$rawIp = static::detectIP($allowOverride);
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

		return (string) array_pop($ipList);
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
	protected static function detectIP($allowOverride)
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
	 * Converts inet_pton output to bits string
	 *
	 * @param   string  $inet  The in_addr representation of an IPv4 or IPv6 address
	 *
	 * @return  string
	 *
	 * @since   1.6.0
	 */
	protected static function inetToBits($inet)
	{
		if (\strlen($inet) === 4)
		{
			$unpacked = unpack('A4', $inet);
		}
		else
		{
			$unpacked = unpack('A16', $inet);
		}

		$unpacked = str_split($unpacked[1]);
		$binaryip = '';

		foreach ($unpacked as $char)
		{
			$binaryip .= str_pad(decbin(\ord($char)), 8, '0', STR_PAD_LEFT);
		}

		return $binaryip;
	}

	/**
	 * Checks if an IPv6 address $ip is part of the IPv6 CIDR block $cidrnet
	 *
	 * @param   string  $ip       The IPv6 address to check, e.g. 21DA:00D3:0000:2F3B:02AC:00FF:FE28:9C5A
	 * @param   string  $cidrnet  The IPv6 CIDR block, e.g. 21DA:00D3:0000:2F3B::/64
	 *
	 * @return  boolean
	 *
	 * @since   1.6.0
	 */
	protected static function checkIPv6CIDR($ip, $cidrnet)
	{
		$ip       = inet_pton($ip);
		$binaryip = static::inetToBits($ip);

		list($net, $maskbits) = explode('/', $cidrnet);
		$net       = inet_pton($net);
		$binarynet = static::inetToBits($net);

		$ipNetBits = substr($binaryip, 0, $maskbits);
		$netBits   = substr($binarynet, 0, $maskbits);

		return $ipNetBits === $netBits;
	}
}
