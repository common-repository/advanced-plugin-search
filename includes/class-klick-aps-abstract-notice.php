<?php

if (!defined('ABSPATH')) die('No direct access allowed');

if (class_exists('Klick_Aps_Abstract_Notice')) return;

/**
 * Class Klick_Aps_Abstract_Notice
 */
abstract class Klick_Aps_Abstract_Notice {
	
	public $notice_data;
	
	public $notice_id,
		$title,
		$klick_aps,
		$notice_text,
		$image_url,
		$dismiss_time,
		$dismiss_interval,
		$display_after_time,
		$dismiss_type,
		$dismiss_text,
		$position,
		$only_on_this_page,
		$button_link,
		$button_text,
		$notice_template_file,
		$validity_function_param,
		$validity_function;
	
	/**
	 * Klick_Aps_Abstract_Notice constructor
	 */
	public function __construct() {
	}
	
	/**
	 * Render notice in templage
	 * 
	 * @return void
	 */
	public function do_notice() {
		
		$notice_data = $this->get_notice_data();
		
		return Klick_Aps()->get_dashboard()->include_template('notices-templates/' . $this->notice_template_file, false, $notice_data);
	}

	/**
	 * Get notice data
	 * 
	 * @return array
	 */
	private function get_notice_data() {
		return array(
			'notice_id' => $this->notice_id,
			'title' => $this->title,
			'klick_aps' => $this->klick_aps,
			'notice_text' => $this->notice_text,
			'image_url' => $this->image_url,
			'dismiss_time' => $this->dismiss_time,
			'dismiss_interval' => $this->dismiss_interval,
			'display_after_time' => $this->display_after_time,
			'dismiss_type' => $this->dismiss_type,
			'dismiss_text' => $this->dismiss_text,
			'position' => $this->position,
			'only_on_this_page' => $this->only_on_this_page,
			'button_link' => $this->button_link,
			'button_text' => $this->button_text,
			'notice_template_file' => $this->notice_template_file,
			'validity_function_param' => $this->validity_function_param,
			'validity_function' => $this->validity_function,
		);
	}
	
	/**
	 * Check if notice is valid by calling validity function
	 * Return false if validity_function is set and a call to that function returns false
	 * otherwise return true
	 * 
	 * @return boolean
	 */
	public function is_valid() {
		
		if ("" === $this->validity_function) return true; // if there is no validity function then always valid, return true
		
		return !call_user_func(array($this, $this->validity_function),$this->validity_function_param);
	}
	
	/**
	 * Check if notice is valid by only_on_this_page
	 * 
	 * @return boolean
	 */
	public function show_on_this_page() {
		
		if ("" === $this->only_on_this_page) return true;
		
		return ($this->only_on_this_page != $GLOBALS['pagenow'] ? false : true);
	}
	
	/**
	 * Check to render notice after fix interval or never
	 * 
	 * @return boolean
	 */
	public function available_at_this_time() {
		if ($this->display_after_time != 0 && time() > $this->display_after_time) {
			return false;
		}

		$display_notice_time = Klick_Aps()->get_options()->get_option('notice-display-time');

		// To check if notice time is 0 (Never render)
		if (isset($display_notice_time[$this->notice_id]) && $display_notice_time[$this->notice_id] == 0) {
			return false;
		}

		if (!isset($display_notice_time[$this->notice_id]) || time() > $display_notice_time[$this->notice_id]) {
				$display_notice_time[$this->notice_id] = time();
				Klick_Aps()->get_options()->update_option('notice-display-time', $display_notice_time);
				return true;
		} else {
				return false;
		}
	}
	
	/**
	 * Plugin specific validity function
	 * @param string 	validity_function_param as $plugin
	 *
	 * @return boolean
	 */
	public function is_plugin_configured($plugin) {
	
		if ($plugin == false) return false;
		
		if (!function_exists('get_plugins')) include_once(ABSPATH . 'wp-admin/includes/plugin.php');
		
		if (!is_plugin_active($plugin)) return false;
		
		// specific tests for specific plugins
		return ($plugin === "Advanced-plugin-search/advanced-plugin-search.php" && Klick_Aps()->get_options()->get_option('send-url'));
	}
}
