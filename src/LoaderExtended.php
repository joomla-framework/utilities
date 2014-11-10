<?php
/**
 * Part of the Joomla Framework Utilities Package
 *
 * @copyright  Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Utilities;

if (!version_compare(PHP_VERSION, '5.5.0', '>='))
{
	throw new \Exception('Extended loader class requires PHP 5.5.0 or higher');
}

/**
 * Memory saving alternative of file loader utility
 *
 * @since  2.0
 */
class LoaderExtended extends Loader
{
	public function getAll($name = null)
	{
		if (!is_null($name))
		{
			$name = $this->correctExtension(Path::clean($name));
		}

		$name = explode(DIRECTORY_SEPARATOR, $name);
		$filename = array_shift($name);
		$filepath = implode(DIRECTORY_SEPARATOR, $name);

		foreach ($this->paths as $path)
		{
			$path .= DIRECTORY_SEPARATOR . $filepath;

			foreach (scandir($path) as $entry)
			{
				if ($entry == '.' || $entry == '..' || !is_readable($path . DIRECTORY_SEPARATOR . $entry))
				{
					continue;
				}
				if (!$name || $name == $entry)
				{
					
					yield [ 
						$filepath . DIRECTORY_SEPARATOR . $entry
						$path . DIRECTORY_SEPARATOR . $entry 
					];
				}
			}
		}
	}

	public function getAllSource($name = null)
	{
		foreach ($this->getAll($name) as list($key, $file))
		{
			yield [ 
				$key, 
				$this->getSource($file) 
			];
		}
	}

	public function getAllObjects($name = null, $format = null, $options = array())
	{
		$handler = AbstractLoaderFormat::getInstance($format);

		foreach ($this->getAll($name) as list($key, $file))
		{
			yield [ 
				$key, 
				$handler->stringToObject($this->loadSource($file), $options) 
			];
		}
	}
}
