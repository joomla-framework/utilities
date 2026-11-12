<?php

/**
 * @copyright  Copyright (C) 2005 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Utilities\Tests;

use Joomla\Utilities\IpHelper;
use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the proxy headers IpHelper reads.
 */
#[CoversClass(IpHelper::class)]
#[BackupGlobals(true)]
class IpHelperProxyHeaderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        IpHelper::setIp(null);
        IpHelper::setAllowIpOverrides(true);
    }

    protected function tearDown(): void
    {
        IpHelper::setIp(null);
        IpHelper::setAllowIpOverrides(false);

        parent::tearDown();
    }

    public function testForwardedHeaderIsUsedWhenOverridesAreAllowed()
    {
        $_SERVER['REMOTE_ADDR']          = '198.51.100.7';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.9';

        $this->assertSame('203.0.113.9', IpHelper::getIp());
    }

    public function testClientIpHeaderIsIgnored()
    {
        $_SERVER['REMOTE_ADDR']    = '198.51.100.7';
        $_SERVER['HTTP_CLIENT_IP'] = '203.0.113.9';
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);

        $this->assertSame('198.51.100.7', IpHelper::getIp());
    }

    public function testWorkaroundIPIssuesIsRemoved()
    {
        $this->assertFalse(method_exists(IpHelper::class, 'workaroundIPIssues'));
    }
}
