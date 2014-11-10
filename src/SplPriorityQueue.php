<?php
/**
 * Part of the Joomla Framework Utilities Package
 *
 * @copyright  Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Utilities;

class SplPriorityQueue extends \SplPriorityQueue implements \Serializable
{
	const FIRST = '__first__';
	const LAST  = '__last__';

	/**
	 * Internal counter
	 *
	 * @var    integer
	 *
	 * @since  2.0
	 */
	protected $counter = PHP_INT_MAX;

	/**
	 * Current minimum priority
	 *
	 * @var    integer
	 *
	 * @since  2.0
	 */
	protected $minimum = PHP_INT_MAX;

	/**
	 * Current maximum priority
	 *
	 * @var    integer
	 *
	 * @since  2.0
	 */
	protected $maximum = null;

	/**
	 * Class constructor
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->maximum = ~PHP_INT_MAX;
		parent::__construct();
	}

	/**
	 * Insert method with correct handling of equal priorities and empty priorities
	 *
	 * @var  $entry     mixed  Entry to insert
	 * @var  $priority  mixed  Entry priority
	 *
	 * @since  2.0
	 */
	public function insert($entry, $priority = self::LAST)
	{
		if ($priority === self::LAST)
		{
			$priority = array($this->minimum--, $this->counter--);
		}
		elseif ($priority === self::FIRST)
		{
			$priority = array($this->maximum++, $this->counter--);
		}
		elseif (!is_array($priority))
		{
			if ($priority < $this->minimum)
			{
				$this->minimum = $priority;
			}
			if ($priority > $this->maximum)
			{
				$this->maximum = $priority;
			}

            $priority = array($priority, $this->counter--);
        }

        parent::insert($entry, $priority);
	}

    /**
     * Convert to an array
     *
     * @return  array  Queue converted to an array
     *
	 * @since  2.0
     */
    public function toArray()
    {
        $array = array();

        foreach (clone $this as $item)
        {
            $array[] = $item;
        }

        return $array;
    }

    /**
     * Load an array of values into the queue
     *
     * @var     $array  array  An array of values
     * @return  void
     *
     * @since   2.0
     */
    public function loadArray($array, $order = self::LAST)
    {
    	foreach ($array as $value)
    	{
    		$this->insert($value, $order);
    	}
    }

    /**
     * Serialize
     *
     * @return  string  Serialized queue
     *
	 * @since  2.0
     */
    public function serialize()
    {
        $clone = clone $this;
        $clone->setExtractFlags(self::EXTR_BOTH);

        return serialize($this->toArray());
    }

    /**
     * Deserialize
     *
     * @param   string  $data  Serialized data to insert
     * @return  void
     *
	 * @since  2.0
     */
    public function unserialize($data)
    {
        foreach (unserialize($data) as $item)
        {
            $this->insert($item['data'], $item['priority']);
        }
    }
}
