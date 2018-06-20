<?php
/**
 * @copyright  Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

use Joomla\Utilities\JsonHelper;

/**
 * JsonHelperTest
 *
 * @since  1.0
 */
class JsonHelperTest extends PHPUnit_Framework_TestCase
{
  /**
   * Test prettify method
   *
   * @return  void
   *
   * @since         1.0
   */
  public function testPrettify()
  {
        
    $input = "{\"foo\":\"bar\",\"sequence\":[1,2,3],\"person\":{\"first_name\":\"John\",\"last_name\":\"Doe\",\"interests\":[\"php\",\"html\"]}}";
    $output = "{\n    \"foo\": \"bar\",\n    \"sequence\": [\n        1,\n        2,\n        3\n".
              "    ],\n    \"person\": {\n        \"first_name\": \"John\",\n        \"last_name\": \"Doe\",\n".
              "        \"interests\": [\n            \"php\",\n            \"html\"\n        ]\n    }\n}";  
    
    $this->assertThat(
      JsonHelper::prettify($input), 
      $this->equalTo($output));
  }
}
