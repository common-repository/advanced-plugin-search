<?php

if (!defined('ABSPATH')) die('No direct access allowed');

if (class_exists('Klick_Aps_Abstract_Logger')) return;

/**
 * Class Klick_Aps_Abstract_Logger
 */
abstract class Klick_Aps_Abstract_Logger {

	protected $enabled = true;

	/**
	 * Klick_Aps_Abstract_Logger constructor
	 */
	public function __construct() {
	}

	/**
	 * Returns true if logger is active
	 *
	 * @return boolean
	 */
	public function is_enabled() {
		return $this->enabled;
	}

	/**
	 * Enable logger
	 *
	 * @return void
	 */
	public function enable() {
		$this->enabled = true;
	}

	/**
	 * Disable logger
	 * @return void
	 */
	public function disable() {
		$this->enabled = false;
	}

	/**
	 * Returns logger description
	 *
	 * @return mixed
	 */
	abstract function get_description();
}
