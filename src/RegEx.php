<?php

/**
 * Part of the Joomla Framework Utilities Package
 *
 * @copyright  Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Utilities;

/**
 * Utility class for building complex Regular Expressions
 *
 * @since 3.0.0
 */
abstract class RegEx
{
    /**
     * Match the Regular Expression
     *
     * @param   string  $regex    The Regular Expression
     * @param   string  $subject  The string to check
     *
     * @return  array  Captured values
     */
    public static function match(string $regex, string $subject): array
    {
        $match = array();

        preg_match($regex, $subject, $match);

        return array_filter(
            $match,
            static function ($value, $key) {
                return !is_numeric($key) && !empty($value);
            },
            ARRAY_FILTER_USE_BOTH
        );
    }

    /**
     * Assign a key to an expression.
     *
     * @param   string  $regex  The Regular Expression to match
     * @param   string  $as     The name of the component, used as index
     *
     * @return  string  The modified Regular Expression
     */
    public static function capture(string $regex, ?string $as = null): string
    {
        return '(?P<' . $as . '>' . $regex . ')';
    }

    /**
     * Add a 'zero or one' quantifier to an expression.
     *
     * @param   string  $regex  The Regular Expression to match
     *
     * @return  string  The modified Regular Expression
     */
    public static function optional(string $regex): string
    {
        return '(?:' . $regex . ')?';
    }

    /**
     * Add a 'one or more' quantifier to an expression.
     *
     * @param   string  $regex  The Regular Expression to match
     *
     * @return  string  The modified Regular Expression
     */
    public static function oneOrMore(string $regex): string
    {
        return '(?:' . $regex . ')+';
    }

    /**
     * Add a 'zero or more' quantifier to an expression.
     *
     * @param   string  $regex  The Regular Expression to match
     *
     * @return  string  The modified Regular Expression
     */
    public static function noneOrMore(string $regex): string
    {
        return '(?:' . $regex . ')*';
    }

    /**
     * Define a list of alternative expressions.
     *
     * @param   string|array  ...$regexList  Regular Expressions to choose from, or a single array of them
     *
     * @return  string  The modified Regular Expression
     */
    public static function anyOf(string|array ...$regexList): string
    {
        if (\is_array($regexList[0] ?? null)) {
            $regexList = $regexList[0];
        }

        return '(?:' . implode('|', $regexList) . ')';
    }
}
