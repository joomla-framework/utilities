<?php
/**
 * Part of the Joomla Framework Utilities Package
 *
 * @copyright  Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Utilities;

use Joomla\Filesystem\Path;

/**
 * File loader utility
 *
 * @since  2.0
 */
class Loader
{
	const ASC = 1;
	const DESC = 0;

	/**
	 * Queue of paths to load
	 *
	 * @var  SplPriorityQueue
	 */
    protected $paths = array();

    /** Internal cache
     *
     * @var  array
     */
    protected $cache = array();

	/**
	 * Extension to load
	 *
	 * @var  string
	 */
    protected $extension = null;

    /**
     * Class constructor.
     *
     * @param  string|array $paths A path or an array of paths where to look for files
     */
    public function __construct($paths = array())
    {
        if ($paths) {
            $this->setPaths($paths);
        }
    }

    public function setExtension($ext)
    {
    	$this->cache = array();
    	$this->extension = ltrim($ext, '.');
    }

    public function getExtension()
    {
    	return $this->extension;
    }

    /**
     * Returns the paths loaded.
     *
     * @return  array The array of paths where to look for templates
     */
    public function getPaths()
    {
        return $this->paths;
    }

    /**
     * Sets the paths where templates are stored.
     *
     * @param  string|array  $paths     A path or an array of paths where to look for templates
     * @param  boolean       $order     In which order paths: 1 - ascending, 0 - descending 
     */
    public function setPaths($paths, $order = Loader::DESC)
    {
    	//Clear paths
		$this->paths = array();

        if (!is_array($paths))
        {
            $this->pushPath($paths);
			return;
        }

		$method = ($order === static::DESC) ? 'pushPath' : 'unshiftPath';

		foreach ($paths as $path)
		{
			call_user_func(array($this, $method), $path);
		}
    }

    /**
     * Adds a path to the end of queue.
     *
     * @param  string $path      A path where to look for templates
     *
     * @throws \UnexpectedValueException
     */
    public function pushPath($path)
    {
    	$this->cache = array();

        if (!is_dir($path))
        {
            throw new \UnexpectedValueException(sprintf('The "%s" directory does not exist.', $path));
        }

		array_push($this->paths, Path::clean($path));
    }

    /**
     * Adds a path to the beginning of queue
     *
     * @param string $path      A path where to look for templates
     *
     * @throws \UnexpectedValueException
     */
    public function unshiftPath($path)
    {
    	$this->cache = array();

        if (!is_dir($path))
        {
            throw new \UnexpectedValueException(sprintf('The "%s" directory does not exist.', $path));
        }

		array_unshift($this->paths, Path::clean($path));
    }

    public function getSource($name)
    {
        return $this->loadSource($this->findFile($name));
    }

    public function getObject($name, $format = null, $options = array())
    {
    	if (is_null($format))
    	{
    		$format = $this->extension ?: 'JSON';
    	}

    	// Load a string into the given namespace [or default namespace if not given]
		$handler = AbstractLoaderFormat::getInstance($format);

		return $handler->stringToObject($this->getSource($name), $options);
    }

    public function exists($name)
    {
        $name = $this->correctExtension(Path::clean($name));

        if (isset($this->cache[$name]))
        {
            return true;
        }

        try {
            $this->findFile($name);
            return true;
        }
        catch (\Exception $exception)
        {
            return false;
        }
    }

	public function getAll($name = null)
	{
		$names = array();

		if (!is_null($name))
		{
			$name = $this->correctExtension(Path::clean($name));
		}

		$nameparts = explode(DIRECTORY_SEPARATOR, $name);
		$filename = array_shift($nameparts);
		$filepath = implode(DIRECTORY_SEPARATOR, $nameparts);

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
					$names[] = array($filepath . DIRECTORY_SEPARATOR . $entry, $path . DIRECTORY_SEPARATOR . $entry);
				}
			}
		}

		return $names;
	}

	public function getAllSource($name = null)
	{
		$sources = array();

		foreach ($this->getAll($name) as $file)
		{
			list($key, $file) = $file;
			$sources[] = array($key, $this->getSource($file));
		}

		return $sources;
	}

	public function getAllObjects($name = null, $format = null, $options = array())
	{
		$objects = array();

		$handler = AbstractLoaderFormat::getInstance($format);

		foreach ($this->getAll($name) as $file)
		{
			list($key, $file) = $file;
			$objects[] = array($key, $handler->stringToObject($this->loadSource($file), $options));
		}

		return $objects;
	}

    protected function findFile($name)
    {
        $name = $this->correctExtension(Path::clean($name));

        $filename = Path::find($this->paths, $name);

        if (!$filename)
        {
        	throw new \RuntimeException(sprintf('Unable to find file "%s" (looked into: %s).', $name, implode(', ', $this->paths)));
		}

		return $this->cache[$name] = $filename;
    }

	protected function loadSource($file)
	{
		return file_get_contents($file);
	}

    protected function correctExtension($name)
    {
    	if (empty($this->extension))
    	{
    		return $name;
    	}    	

    	$name = explode(DIRECTORY_SEPARATOR, $name);
		$filename = basename(array_shift(DIRECTORY_SEPARATOR, $name), '.'.$this->extension);
		if (empty($filename))
		{
			throw new \InvalidArgumentException('File name must not be empty');
		}
    	$name[] = $filename . '.' .$this->extension;
    	return implode(DIRECTORY_SEPARATOR, $name);
    }
}
