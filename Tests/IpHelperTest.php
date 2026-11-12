<?php

/**
 * @copyright  Copyright (C) 2005 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Utilities\Tests;

use Joomla\Utilities\IpHelper;
use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the IpHelper class.
 */
#[CoversClass(IpHelper::class)]
#[BackupGlobals(true)]
class IpHelperTest extends TestCase
{
    /**
     * Reset the static state the class carries between calls.
     */
    protected function setUp(): void
    {
        parent::setUp();

        IpHelper::setIp(null);
        IpHelper::setAllowIpOverrides(false);
    }

    protected function tearDown(): void
    {
        IpHelper::setIp(null);
        IpHelper::setAllowIpOverrides(false);

        parent::tearDown();
    }

    public function testSetIpAndGetIpRoundTrip()
    {
        IpHelper::setIp('198.51.100.7');

        $this->assertSame('198.51.100.7', IpHelper::getIp());
    }

    public function testGetIpReadsRemoteAddr()
    {
        $_SERVER['REMOTE_ADDR'] = '198.51.100.7';

        $this->assertSame('198.51.100.7', IpHelper::getIp());
    }

    public function testGetIpCachesTheFirstDetection()
    {
        $_SERVER['REMOTE_ADDR'] = '198.51.100.7';

        $this->assertSame('198.51.100.7', IpHelper::getIp());

        $_SERVER['REMOTE_ADDR'] = '203.0.113.9';

        $this->assertSame(
            '198.51.100.7',
            IpHelper::getIp(),
            'getIp() must not re-detect once a value has been resolved'
        );
    }

    public function testGetIpNormalisesAnIPv6Address()
    {
        $_SERVER['REMOTE_ADDR'] = '2001:0db8:0000:0000:0000:0000:0000:0001';

        $this->assertSame('2001:db8::1', IpHelper::getIp());
    }

    public function testGetIpTakesTheFirstOfACommaSeparatedList()
    {
        $_SERVER['REMOTE_ADDR'] = '198.51.100.7, 203.0.113.9';

        $this->assertSame('198.51.100.7', IpHelper::getIp());
    }

    public function testGetIpTakesTheFirstOfASpaceSeparatedList()
    {
        $_SERVER['REMOTE_ADDR'] = '198.51.100.7 203.0.113.9';

        $this->assertSame('198.51.100.7', IpHelper::getIp());
    }

    public function testForwardedHeaderIsIgnoredByDefault()
    {
        $_SERVER['REMOTE_ADDR']          = '198.51.100.7';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.9';

        $this->assertSame(
            '198.51.100.7',
            IpHelper::getIp(),
            'X-Forwarded-For must be ignored unless overrides are explicitly allowed'
        );
    }

    public function testForwardedHeaderIsUsedWhenOverridesAreAllowed()
    {
        IpHelper::setAllowIpOverrides(true);

        $_SERVER['REMOTE_ADDR']          = '198.51.100.7';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.9';

        $this->assertSame('203.0.113.9', IpHelper::getIp());
    }

    public function testClientIpHeaderIsUsedWhenOverridesAreAllowed()
    {
        IpHelper::setAllowIpOverrides(true);

        $_SERVER['REMOTE_ADDR']    = '198.51.100.7';
        $_SERVER['HTTP_CLIENT_IP'] = '203.0.113.9';

        $this->assertSame('203.0.113.9', IpHelper::getIp());
    }

    public function testForwardedHeaderWinsOverClientIp()
    {
        IpHelper::setAllowIpOverrides(true);

        $_SERVER['REMOTE_ADDR']          = '198.51.100.7';
        $_SERVER['HTTP_CLIENT_IP']       = '203.0.113.9';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '192.0.2.4';

        $this->assertSame('192.0.2.4', IpHelper::getIp());
    }

    public static function isIPv6Data(): array
    {
        return [
            'IPv4'             => ['198.51.100.7', false],
            'IPv4 partial'     => ['198.51.', false],
            'IPv6 full'        => ['2001:0db8:0000:0000:0000:0000:0000:0001', true],
            'IPv6 compressed'  => ['2001:db8::1', true],
            'IPv6 loopback'    => ['::1', true],
            'empty string'     => ['', false],
        ];
    }

    #[DataProvider('isIPv6Data')]
    public function testIsIPv6(string $ip, bool $expected)
    {
        $this->assertSame($expected, IpHelper::isIPv6($ip));
    }

    public static function ipInListData(): array
    {
        return [
            // Empty inputs
            'empty list'                  => ['198.51.100.7', '', false],
            'empty array'                 => ['198.51.100.7', [], false],
            'empty IP'                    => ['', '198.51.100.7', false],
            'unset IP 0.0.0.0'            => ['0.0.0.0', '198.51.100.7', false],
            'unparsable IP'               => ['not-an-ip', '198.51.100.7', false],

            // Exact matches
            'exact match, string'         => ['198.51.100.7', '198.51.100.7', true],
            'exact miss, string'          => ['198.51.100.8', '198.51.100.7', false],
            'exact match, array'          => ['198.51.100.7', ['203.0.113.9', '198.51.100.7'], true],
            'exact match, comma list'     => ['198.51.100.7', '203.0.113.9,198.51.100.7', true],
            'comma list is trimmed'       => ['198.51.100.7', '203.0.113.9, 198.51.100.7 ', true],
            'string entry is trimmed'     => ['198.51.100.7', '  198.51.100.7  ', true],

            // Ranges
            'in range'                    => ['198.51.100.7', '198.51.100.1-198.51.100.20', true],
            'below range'                 => ['198.51.99.7', '198.51.100.1-198.51.100.20', false],
            'above range'                 => ['198.51.100.30', '198.51.100.1-198.51.100.20', false],
            'range boundaries included'   => ['198.51.100.1', '198.51.100.1-198.51.100.20', true],
            'reversed range still works'  => ['198.51.100.7', '198.51.100.20-198.51.100.1', true],
            'range with broken endpoint'  => ['198.51.100.7', '198.51.100.1-nonsense', false],

            // CIDR
            'CIDR /24 hit'                => ['198.51.100.7', '198.51.100.0/24', true],
            'CIDR /24 miss'               => ['198.51.101.7', '198.51.100.0/24', false],
            'CIDR /16 hit'                => ['198.51.101.7', '198.51.0.0/16', true],
            'CIDR /8 hit'                 => ['198.51.100.7', '198.0.0.0/8', true],
            'CIDR with broken network'    => ['198.51.100.7', 'nonsense/24', false],

            // Netmask notation
            'netmask /24 hit'             => ['198.51.100.7', '198.51.100.0/255.255.255.0', true],
            'netmask /24 miss'            => ['198.51.101.7', '198.51.100.0/255.255.255.0', false],
            'netmask /16 hit'             => ['198.51.101.7', '198.51.0.0/255.255.0.0', true],

            // Partial addresses
            'partial one dot'             => ['198.51.100.7', '198.', true],
            'partial one dot miss'        => ['198.51.100.7', '203.', false],
            'partial two dots'            => ['198.51.100.7', '198.51.', true],
            'partial two dots miss'       => ['198.52.100.7', '198.51.', false],
            'partial three dots'          => ['198.51.100.7', '198.51.100.', true],
            'partial three dots miss'     => ['198.51.101.7', '198.51.100.', false],

            // Mixed families must not cross-match
            'IPv4 not matched by IPv6'    => ['198.51.100.7', '2001:db8::1', false],
            'IPv6 not matched by IPv4'    => ['2001:db8::1', '198.51.100.7', false],
            'IPv6 not matched by v4 CIDR' => ['2001:db8::1', '198.51.100.0/24', false],
            'IPv4 not matched by v6 CIDR' => ['198.51.100.7', '2001:db8::/32', false],
            'IPv6 range v4 endpoints'     => ['2001:db8::1', '198.51.100.1-198.51.100.20', false],
            'IPv4 range v6 endpoints'     => ['198.51.100.7', '2001:db8::1-2001:db8::20', false],

            // IPv6
            'IPv6 exact match'            => ['2001:db8::1', '2001:db8::1', true],
            'IPv6 exact match expanded'   => ['2001:db8::1', '2001:0db8:0000:0000:0000:0000:0000:0001', true],
            'IPv6 exact miss'             => ['2001:db8::2', '2001:db8::1', false],
            'IPv6 unparsable expression'  => ['2001:db8::1', '2001:db8::zz', false],

            // A later entry still matches after earlier ones are skipped
            'match after skipped entries' => ['198.51.100.7', ['2001:db8::1', 'nonsense/24', '198.51.100.7'], true],
        ];
    }

    #[DataProvider('ipInListData')]
    public function testIPinList(string $ip, $ipTable, bool $expected)
    {
        $this->assertSame($expected, IpHelper::IPinList($ip, $ipTable));
    }

    public function testIPv6CidrMatch()
    {
        $this->assertTrue(IpHelper::IPinList('2001:db8:0:2f3b::1', '2001:db8:0:2f3b::/64'));
    }

    public function testIPv6CidrMiss()
    {
        $this->assertFalse(IpHelper::IPinList('2001:db8:1:2f3b::1', '2001:db8:0:2f3b::/64'));
    }

    public function testWorkaroundIPIssuesRewritesRemoteAddr()
    {
        $_SERVER['REMOTE_ADDR'] = '198.51.100.7, 203.0.113.9';

        IpHelper::workaroundIPIssues();

        $this->assertSame('198.51.100.7', $_SERVER['REMOTE_ADDR'], 'REMOTE_ADDR is replaced with the cleaned IP');
        $this->assertSame(
            '198.51.100.7, 203.0.113.9',
            $_SERVER['JOOMLA_REMOTE_ADDR'],
            'The original value is preserved under JOOMLA_REMOTE_ADDR'
        );
    }

    public function testWorkaroundIPIssuesIsANoOpWhenTheAddressIsAlreadyClean()
    {
        $_SERVER['REMOTE_ADDR'] = '198.51.100.7';
        unset($_SERVER['JOOMLA_REMOTE_ADDR']);

        IpHelper::workaroundIPIssues();

        $this->assertSame('198.51.100.7', $_SERVER['REMOTE_ADDR']);
        $this->assertArrayNotHasKey('JOOMLA_REMOTE_ADDR', $_SERVER);
    }

    public function testSetAllowIpOverridesCoercesToBoolean()
    {
        IpHelper::setAllowIpOverrides('yes');

        $_SERVER['REMOTE_ADDR']          = '198.51.100.7';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.9';

        $this->assertSame('203.0.113.9', IpHelper::getIp());
    }
}
