<?php

namespace Patterns;

/**
 * Represents singletone pattern
 */
trait Singletone
{
    /** @var self $instance Object instance */
    private static self $instance;

    /**
     * Gets object instance
     * @return self
     */
    public static function getInstance(): self
	{
		if (!isset(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
