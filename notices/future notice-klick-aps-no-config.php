<?php

if (!defined('ABSPATH')) die('No direct access allowed');

if (class_exists('Klick_Aps_No_Config')) return;

require_once(KLICK_APS_PLUGIN_MAIN_PATH . '/includes/class-klick-aps-abstract-notice.php');

/**
 * Class Klick_Aps_No_Config
 */
class Klick_Aps_No_Config extends Klick_Aps_Abstract_Notice {
	
	/**
	 * Klick_Aps_No_Config constructor
	 */
	public function __construct() {
		$this->notice_id = 'advanced-plugin-search';
		$this->title = __('Advanced plugin serach plugin is installed but not configured', 'klick-aps');
		$this->klick_aps = "";
		$this->notice_text = __('Configure it Now', 'klick-aps');
		$this->image_url = '../images/our-more-plugins/cs.svg';
		$this->dismiss_time = 'dismiss-page-notice-until';
		$this->dismiss_interval = 30;
		$this->display_after_time = 0;
		$this->dismiss_type = 'dismiss';
		$this->dismiss_text = __('Hide Me!', 'klick-aps');
		$this->position = 'dashboard';
		$this->only_on_this_page = 'index.php';
		$this->button_link = KLICK_APS_PLUGIN_SETTING_PAGE;
		$this->button_text = __('Click here', 'klick-aps');
		$this->notice_template_file = 'main-dashboard-notices.php';
		$this->validity_function_param = 'Advance-plugin-search/advance-plugin-search.php';
		$this->validity_function = 'is_plugin_configured';
	}
}
