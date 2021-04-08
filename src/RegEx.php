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
 * @since __DEPLOY_VERSION__
 */
abstract class RegEx
{
	/**
	 * Math the Regular Expression
	 *
	 * @param   string  $regex    The Regular Expression
	 * @param   string  $subject  The string to check
	 *
	 * @return  array  Captured values
	 */
	public static function match($regex, $subject)
	{
		$match = array();

		preg_match($regex, $subject, $match);

		// @todo Remove this block, once minimum PHP version is raised above PHP 5.6.0
		if (PHP_VERSION_ID < 50600)
		{
			$result = array();

			foreach ($match as $key => $value)
			{
				if (!is_numeric($key) && !empty($value))
				{
					$result[$key] = $value;
				}
			}

			return $result;
		}

		return array_filter(
			$match,
			function ($value, $key) {
				return !is_numeric($key) && !empty($value);
			}, ARRAY_FILTER_USE_BOTH
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
	public static function capture($regex, $as = null)
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
	public static function optional($regex)
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
	public static function oneOrMore($regex)
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
	public static function noneOrMore($regex)
	{
		return '(?:' . $regex . ')*';
	}

	/**
	 * Define a list of alternative expressions.
	 *
	 * @param   string|array  $regexList  A list of Regular Expressions to choose from
	 *
	 * @return  string  The modified Regular Expression
	 */
	public static function anyOf($regexList)
	{
		if (is_string($regexList))
		{
			$regexList = func_get_args();
		}

		return '(?:' . implode('|', $regexList) . ')';
	}
}
