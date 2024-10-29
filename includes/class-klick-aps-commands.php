<?php 

if (!defined('KLICK_APS_PLUGIN_MAIN_PATH')) die('No direct access allowed');

/**
 * Commands available from control interface (e.g. wp-admin) are here
 * All public methods should either return the data, or a WP_Error with associated error code, message and error data
 */
/**
 * Sub commands for Ajax
 *
 */
class Klick_Aps_Commands {
	private $options;
	
	/**
	 * Constructor for Commands class
	 *
	 */
	public function __construct() {
		$this->options = Klick_Aps()->get_options();
	} 

	/**
	 * dis-miss button
	 *
	 * @param  Array 	$data an array of data UI form
	 *
	 * @return Array 	$status
	 */
	public function dismiss_page_notice_until($data) {
		
		return array(
			'status' => $this->options->dismiss_page_notice_until($data),
			);
	}

	/**
	 * dis-miss button
	 *
	 * @param  Array 	$data an array of data UI form
	 *
	 * @return Array 	$status
	 */
	public function dismiss_page_notice_until_forever($data) {
		
		return array(
			'status' => $this->options->dismiss_page_notice_until_forever($data),
			);
	}

	/**
	 * This sends the passed data value over to the save_settings function
	 *
	 * @param  Array 	$data an array of data UI form
	 *
	 * @return Array    $status
	 */
	public function klick_aps_save_settings($data) {
		return array(
			'status' => $this->options->save_settings($data),
			);
	}

	/**
	 * This sends the passed data value over to the create_table function
	 *
	 * @param  Array 	$data an array of data UI form
	 *
	 * @return Array    $status
	 */
	public function klick_aps_create_db($data) {
		return array(
			'status' => Klick_Aps()->db_operations()->create_table("all_plugins"),
			);
	} 
	
	/**
	 * This sends the passed data value over to the build_plugin_table function
	 *
	 * @param  Array 	$data an array of data UI form
	 *
	 * @return Array    $status
	 */
	public function klick_aps_build_plugin_table($data) {
		return array(
			'status' => Klick_Aps()->db_operations()->build_plugin_table($data),
		);
	}
}
