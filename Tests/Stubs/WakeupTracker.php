<?php

/**
 * @copyright  Copyright (C) 2005 - 2026 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Utilities\Tests\Stubs;

/**
 * Counts how often instances are unserialized.
 */
class WakeupTracker
{
    public static int $wakeups = 0;

    public function __construct(public string $value)
    {
    }

    public function __wakeup(): void
    {
        self::$wakeups++;
    }
}
