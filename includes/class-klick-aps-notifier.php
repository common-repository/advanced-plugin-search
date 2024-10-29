<?php

if (!defined('ABSPATH')) die('No direct access allowed');

if (class_exists('Klick_Aps_Notifier')) return;

/**
 * Class Klick_Aps_Notifier
 */
class Klick_Aps_Notifier {
	
	protected $_notices = array();

	/**
	 * Klick_Aps_PHP_Notifier constructor
	 * Adding notice file from notices folder, File must have notice- prefix and .php extension
	 *
	 * @return void
	 */
	public function __construct() {
		
		$folder = opendir(KLICK_APS_PLUGIN_MAIN_PATH . 'notices');
		
		if ($folder) {
			while (($file = readdir($folder)) !== false) {
				
				if ('.php' != substr($file, -4, 4) || 'notice-' != substr($file, 0,7)) continue;
				
				$this->add_noitice($file);
			}
			closedir($folder);
		}
		return;
	}
	
	/**
	 * Includes and create instance of notice file
	 *
	 * @param  string  @notice_file
	 * 
	 * @return boolean
	 */
	public function add_noitice($notice_file) {
		
		$notice_class = str_replace( "-", "_", substr($notice_file, 7,-4));
		
		$notice_file = KLICK_APS_PLUGIN_MAIN_PATH . 'notices' . '/' . $notice_file;
		
		if (!class_exists($notice_class)) {
			
			if (is_file($notice_file)) {
				include_once($notice_file);
			}
		}
		
		if (class_exists($notice_class)) {
			
			$notice = new $notice_class();
			
			if (false != $notice) {
				$this->_notices[] = $notice;
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Prepare a list of notices availabel for this position, select 1 at random and render call the notice to render itself
	 *
	 * @param  string  @notice_position
	 *
	 * @return void
	 */
	public function do_notice($notice_position) {
		
		$notices = $this->get_notices();
		
		$available_notices = array();
		
		foreach ($notices as $notice) {
			
			// if position is not same as passed then skip with continue
			if ($notice_position != $notice->position) continue;
			
			// some notices have a validity method specified which will return false if the notice is not valid for display
			if (!$notice->is_valid()) continue;
			
			// some notices have an only on this page value
			if (!$notice->show_on_this_page()) continue;
			
			// some notices are are not available to show due to time limitations either on the notice object or in the options table
			if (!$notice->available_at_this_time()) continue;
			
			$available_notices [] = $notice;
		}
		
		if (!empty($available_notices)) {
			shuffle($available_notices);
			$notice = $available_notices[0];
			$notice->do_notice();
		}
	}
	
	/**
	 * Get notices object as array
	 *
	 * @return array Array of notice objects
	 */
	public function get_notices() {
		return $this->_notices;
	}
}
