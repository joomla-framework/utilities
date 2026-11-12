<?php

/**
 * Part of the Joomla Framework Utilities Package
 *
 * @copyright   Copyright (C) 2010-2016 Nicholas K. Dionysopoulos / Akeeba Ltd. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @note        This file has been modified by the Joomla! Project and no longer reflects the original work of its author.
 */

namespace Joomla\Utilities;

/**
 * IpHelper is a utility class for processing IP addresses
 *
 * @since  1.6.0
 */
final class IpHelper
{
    /**
     * The IP address of the current visitor
     *
     * @var    string
     * @since  1.6.0
     */
    private static $ip = null;

    /**
     * Should I allow IP overrides through X-Forwarded-For or Client-Ip HTTP headers?
     *
     * @var    boolean
     * @since  1.6.0
     * @note   The default value is false in version 2.0+
     */
    private static $allowIpOverrides = false;

    /**
     * The proxies whose X-Forwarded-For entries are trusted, as IP expressions understood by IPinList()
     *
     * @var    string[]
     * @since  __DEPLOY_VERSION__
     */
    private static $trustedProxies = [];

    /**
     * Private constructor to prevent instantiation of this class
     *
     * @since   1.6.0
     */
    private function __construct()
    {
    }

    /**
     * Get the current visitor's IP address
     *
     * @return  string
     *
     * @since   1.6.0
     */
    public static function getIp()
    {
        if (self::$ip === null) {
            $ip = static::detectAndCleanIP();

            if (!empty($ip) && ($ip != '0.0.0.0') && \function_exists('inet_pton') && \function_exists('inet_ntop')) {
                $myIP = @inet_pton($ip);

                if ($myIP !== false) {
                    $ip = inet_ntop($myIP);
                }
            }

            static::setIp($ip);
        }

        return self::$ip;
    }

    /**
     * Set the IP address of the current visitor
     *
     * @param   string  $ip  The visitor's IP address
     *
     * @return  void
     *
     * @since   1.6.0
     */
    public static function setIp($ip)
    {
        self::$ip = $ip;
    }

    /**
     * Is it an IPv6 IP address?
     *
     * @param   string   $ip  An IPv4 or IPv6 address
     *
     * @return  boolean
     *
     * @since   1.6.0
     */
    public static function isIPv6($ip)
    {
        return strpos($ip, ':') !== false;
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
        if (empty($ipTable)) {
            return false;
        }

        // If the IP list is not an array, convert it to an array
        if (!\is_array($ipTable)) {
            if (strpos($ipTable, ',') !== false) {
                $ipTable = explode(',', $ipTable);
                $ipTable = array_map('trim', $ipTable);
            } else {
                $ipTable = trim($ipTable);
                $ipTable = [$ipTable];
            }
        }

        // If no IP address is found, return false
        if ($ip === '0.0.0.0') {
            return false;
        }

        // If no IP is given, return false
        if (empty($ip)) {
            return false;
        }

        // Sanity check
        if (!\function_exists('inet_pton')) {
            return false;
        }

        // Get the IP's in_adds representation
        $myIP = @inet_pton($ip);

        // If the IP is in an unrecognisable format, quite
        if ($myIP === false) {
            return false;
        }

        $ipv6 = static::isIPv6($ip);

        foreach ($ipTable as $ipExpression) {
            $ipExpression = trim($ipExpression);

            // Inclusive IP range, i.e. 123.123.123.123-124.125.126.127
            if (strstr($ipExpression, '-')) {
                list($from, $to) = explode('-', $ipExpression, 2);

                if ($ipv6 && (!static::isIPv6($from) || !static::isIPv6($to))) {
                    // Do not apply IPv4 filtering on an IPv6 address
                    continue;
                }

                if (!$ipv6 && (static::isIPv6($from) || static::isIPv6($to))) {
                    // Do not apply IPv6 filtering on an IPv4 address
                    continue;
                }

                $from = @inet_pton(trim($from));
                $to   = @inet_pton(trim($to));

                // Sanity check
                if (($from === false) || ($to === false)) {
                    continue;
                }

                // Swap from/to if they're in the wrong order
                if ($from > $to) {
                    list($from, $to) = [$to, $from];
                }

                if (($myIP >= $from) && ($myIP <= $to)) {
                    return true;
                }
            } elseif (strstr($ipExpression, '/')) {
                // Netmask or CIDR provided
                $binaryip = static::inetToBits($myIP);

                list($net, $maskbits) = explode('/', $ipExpression, 2);

                if ($ipv6 && !static::isIPv6($net)) {
                    // Do not apply IPv4 filtering on an IPv6 address
                    continue;
                }

                if (!$ipv6 && static::isIPv6($net)) {
                    // Do not apply IPv6 filtering on an IPv4 address
                    continue;
                }

                if ($ipv6 && strstr($maskbits, ':')) {
                    // Perform an IPv6 CIDR check
                    if (static::checkIPv6CIDR($myIP, $ipExpression)) {
                        return true;
                    }

                    // If we didn't match it proceed to the next expression
                    continue;
                }

                if (!$ipv6 && strstr($maskbits, '.')) {
                    // Convert IPv4 netmask to CIDR
                    $long     = ip2long($maskbits);
                    $base     = ip2long('255.255.255.255');
                    $maskbits = 32 - log(($long ^ $base) + 1, 2);
                }

                // Convert network IP to in_addr representation
                $net = @inet_pton($net);

                // Sanity check
                if ($net === false) {
                    continue;
                }

                // Get the network's binary representation
                $expectedNumberOfBits = $ipv6 ? 128 : 24;
                $binarynet            = str_pad(static::inetToBits($net), $expectedNumberOfBits, '0', STR_PAD_RIGHT);

                // Check the corresponding bits of the IP and the network
                $ipNetBits = substr($binaryip, 0, $maskbits);
                $netBits   = substr($binarynet, 0, $maskbits);

                if ($ipNetBits === $netBits) {
                    return true;
                }
            } else {
                // IPv6: Only single IPs are supported
                if ($ipv6) {
                    $ipExpression = trim($ipExpression);

                    if (!static::isIPv6($ipExpression)) {
                        continue;
                    }

                    $ipCheck = @inet_pton($ipExpression);

                    if ($ipCheck === false) {
                        continue;
                    }

                    if ($ipCheck == $myIP) {
                        return true;
                    }
                } else {
                    // Standard IPv4 address, i.e. 123.123.123.123 or partial IP address, i.e. 123.[123.][123.][123]
                    $dots = 0;

                    if (substr($ipExpression, -1) == '.') {
                        // Partial IP address. Convert to CIDR and re-match
                        foreach (count_chars($ipExpression, 1) as $i => $val) {
                            if ($i == 46) {
                                $dots = $val;
                            }
                        }

                        switch ($dots) {
                            case 1:
                                $netmask = '255.0.0.0';
                                $ipExpression .= '0.0.0';

                                break;

                            case 2:
                                $netmask = '255.255.0.0';
                                $ipExpression .= '0.0';

                                break;

                            case 3:
                                $netmask = '255.255.255.0';
                                $ipExpression .= '0';

                                break;

                            default:
                                $dots = 0;
                        }

                        if ($dots) {
                            $binaryip = static::inetToBits($myIP);

                            // Convert netmask to CIDR
                            $long     = ip2long($netmask);
                            $base     = ip2long('255.255.255.255');
                            $maskbits = 32 - log(($long ^ $base) + 1, 2);

                            $net = @inet_pton($ipExpression);

                            // Sanity check
                            if ($net === false) {
                                continue;
                            }

                            // Get the network's binary representation
                            $expectedNumberOfBits = $ipv6 ? 128 : 24;
                            $binarynet            = str_pad(static::inetToBits($net), $expectedNumberOfBits, '0', STR_PAD_RIGHT);

                            // Check the corresponding bits of the IP and the network
                            $ipNetBits = substr($binaryip, 0, $maskbits);
                            $netBits   = substr($binarynet, 0, $maskbits);

                            if ($ipNetBits === $netBits) {
                                return true;
                            }
                        }
                    }

                    if (!$dots) {
                        $ip = @inet_pton(trim($ipExpression));

                        if ($ip == $myIP) {
                            return true;
                        }
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
     * @since   1.6.0
     */
    public static function workaroundIPIssues()
    {
        $ip = static::getIp();

        if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] === $ip) {
            return;
        }

        if (isset($_SERVER['REMOTE_ADDR'])) {
            $_SERVER['JOOMLA_REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
        } elseif (\function_exists('getenv')) {
            if (getenv('REMOTE_ADDR')) {
                $_SERVER['JOOMLA_REMOTE_ADDR'] = getenv('REMOTE_ADDR');
            }
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
     * @since   1.6.0
     */
    public static function setAllowIpOverrides($newState)
    {
        self::$allowIpOverrides = $newState ? true : false;
    }

    /**
     * Set the proxies whose X-Forwarded-For entries are trusted
     *
     * Only takes effect when IP overrides are allowed. The proxy headers are then only used if REMOTE_ADDR is one
     * of these proxies, and X-Forwarded-For is read from the right, skipping the entries of trusted proxies. An
     * empty list restores the default: the first X-Forwarded-For entry is used, whoever sent it.
     *
     * @param   string[]  $proxies  IP addresses, ranges or CIDR blocks, in any form IPinList() accepts
     *
     * @return  void
     *
     * @since   __DEPLOY_VERSION__
     */
    public static function setTrustedProxies(array $proxies): void
    {
        self::$trustedProxies = array_values($proxies);
    }

    /**
     * Gets the visitor's IP address.
     *
     * Without overrides, this is REMOTE_ADDR. With overrides allowed, the X-Forwarded-For header is used
     * (and, deprecated, the Client-Ip header). If trusted proxies are set, the headers are only used when the
     * request comes from one of them, and X-Forwarded-For is read from the right, skipping trusted proxies.
     *
     * Each candidate is reduced to its first entry if it holds a comma- or space-separated list, and must be a
     * valid IP address; otherwise the next source is tried. An invalid REMOTE_ADDR gives an empty string.
     *
     * @return  string
     *
     * @since   1.6.0
     */
    protected static function detectAndCleanIP()
    {
        $remoteAddr = static::cleanIP(static::getServerVariable('REMOTE_ADDR'));

        if (!self::$allowIpOverrides) {
            return $remoteAddr;
        }

        if (self::$trustedProxies !== []) {
            // Proxy headers are only trustworthy when the request comes from a trusted proxy
            if ($remoteAddr === '' || !static::IPinList($remoteAddr, self::$trustedProxies)) {
                return $remoteAddr;
            }

            $ip = static::getClientFromForwardedFor(static::getServerVariable('HTTP_X_FORWARDED_FOR'));
        } else {
            $ip = static::cleanIP(static::getServerVariable('HTTP_X_FORWARDED_FOR'));
        }

        if ($ip !== '') {
            return $ip;
        }

        $ip = static::cleanIP(static::getServerVariable('HTTP_CLIENT_IP'));

        if ($ip !== '') {
            trigger_deprecation(
                'joomla/utilities',
                '__DEPLOY_VERSION__',
                'Reading the client IP address from the Client-Ip header is deprecated and will be removed in 5.0.'
                . ' Use the X-Forwarded-For header, together with %s::setTrustedProxies().',
                self::class
            );

            return $ip;
        }

        return $remoteAddr;
    }

    /**
     * Reduces a comma- or space-separated list of addresses to its first entry and validates it
     *
     * @param   string  $ip  The raw value
     *
     * @return  string  The address, or an empty string if it is not a valid IP address
     *
     * @since   __DEPLOY_VERSION__
     */
    private static function cleanIP(string $ip): string
    {
        foreach (explode(',', str_replace(' ', ',', $ip)) as $entry) {
            $entry = trim($entry);

            if ($entry !== '') {
                return filter_var($entry, FILTER_VALIDATE_IP) !== false ? $entry : '';
            }
        }

        return '';
    }

    /**
     * Finds the client address in an X-Forwarded-For header sent by a trusted proxy
     *
     * The header is read from the right. Entries of trusted proxies are skipped; the first other entry is the
     * client. If every entry is a trusted proxy, or an entry is not a valid IP address, the last trusted proxy
     * seen is returned.
     *
     * @param   string  $forwardedFor  The X-Forwarded-For header
     *
     * @return  string  The client address, or an empty string if the header holds none
     *
     * @since   __DEPLOY_VERSION__
     */
    private static function getClientFromForwardedFor(string $forwardedFor): string
    {
        $client = '';
        $hops   = array_reverse(explode(',', str_replace(' ', ',', $forwardedFor)));

        foreach ($hops as $hop) {
            $hop = trim($hop);

            if ($hop === '') {
                continue;
            }

            if (filter_var($hop, FILTER_VALIDATE_IP) === false) {
                break;
            }

            $client = $hop;

            if (!static::IPinList($hop, self::$trustedProxies)) {
                break;
            }
        }

        return $client;
    }

    /**
     * Reads a server variable from $_SERVER, falling back to the environment for SAPIs which do not set it
     *
     * @param   string  $name  The name of the variable
     *
     * @return  string
     *
     * @since   __DEPLOY_VERSION__
     */
    private static function getServerVariable(string $name): string
    {
        if (isset($_SERVER[$name])) {
            return (string) $_SERVER[$name];
        }

        // getenv() may be disabled
        if (!\function_exists('getenv')) {
            return '';
        }

        $value = getenv($name);

        return \is_string($value) ? $value : '';
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
        if (\strlen($inet) == 4) {
            $unpacked = unpack('A4', $inet);
        } else {
            $unpacked = unpack('A16', $inet);
        }

        $unpacked = str_split($unpacked[1]);
        $binaryip = '';

        foreach ($unpacked as $char) {
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
        $net                  = inet_pton($net);
        $binarynet            = static::inetToBits($net);

        $ipNetBits = substr($binaryip, 0, $maskbits);
        $netBits   = substr($binarynet, 0, $maskbits);

        return $ipNetBits === $netBits;
    }
}
