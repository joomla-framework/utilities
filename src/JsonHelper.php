<?php
/**
 * Part of the Joomla Framework Utilities Package
 *
 * @copyright  Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Utilities;


/**
 * JsonHelper is an utility class for doing all sorts of odds and ends with JSON strings.
 *
 * @since  1.0
 */
final class JsonHelper
{
  /**
   * Private constructor to prevent instantiation of this class
   */
  private function __construct()
  {
  }

  /**
   * Format a flat JSON string to make it more human-readable
   * original code: http://www.daveperrett.com/articles/2008/03/11/format-json-with-php/
   * adapted to allow native functionality in php version >= 5.4.0
   *
   * @param   string  $json       The original JSON string to process
   * @param   string  $indentStr  Identation character
   * @param   string  $newLine    New line character
   * @return string Indented version of the original JSON string
   */
  public static function prettify($json, $indentStr = "    ", $newLine = "\n")
  {
    $result      = '';
    $pos         = 0;               // indentation level
    $strLen      = strlen($json);
    $prevChar    = '';
    $outOfQuotes = true;
  
    for ($i = 0; $i < $strLen; $i++) 
    {
      // Grab the next character in the string
      $char = substr($json, $i, 1);
  
      // Are we inside a quoted string?
      if ($char == '"' && $prevChar != '\\') 
      {
        $outOfQuotes = !$outOfQuotes;
      }
      // If this character is the end of an element,
      // output a new line and indent the next line
      else if (($char == '}' || $char == ']') && $outOfQuotes) 
      {
        $result .= $newLine;
        $pos--;
        for ($j = 0; $j < $pos; $j++) 
        {
          $result .= $indentStr;
        }
      }
      // eat all non-essential whitespace in the input as we do our own here and it would only mess up our process
      else if ($outOfQuotes && false !== strpos(" \t\r\n", $char)) 
      {
        continue;
      }
  
      // Add the character to the result string
      $result .= $char;
      // always add a space after a field colon:
      if ($char == ':' && $outOfQuotes) 
      {
        $result .= ' ';
      }
  
      // If the last character was the beginning of an element,
      // output a new line and indent the next line
      if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) 
      {
        $result .= $newLine;
        if ($char == '{' || $char == '[') 
        {
          $pos++;
        }
        for ($j = 0; $j < $pos; $j++) 
        {
          $result .= $indentStr;
        }
      }
      $prevChar = $char;
    }

    return $result;
  }
}
