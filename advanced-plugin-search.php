<?php
/**
Plugin Name: Advanced Plugin Search
Description: Free yourself from the limitations of the standard plugin search delivered by WordPress core. List plugins that have been updated within the last X months or with Y number of downloads.
Version: 0.0.2
Author: klick on it
Author URI: http://klick-on-it.com
License: GPLv2 or later
Text Domain: klick-aps
 */

/*
This plugin developed by klick-on-it.com
*/

/*
Copyright 2017 klick on it (http://klick-on-it.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 3 - GPLv3)
as published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

if (!defined('ABSPATH')) die('No direct access allowed');
if (!class_exists('Klick_Aps')) :
define('KLICK_APS_VERSION', '0.0.1');
define('KLICK_APS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('KLICK_APS_PLUGIN_MAIN_PATH', plugin_dir_path(__FILE__));
define('KLICK_APS_PLUGIN_SETTING_PAGE', admin_url() . 'admin.php?page=klick_aps');

class Klick_Aps {

	protected static $_instance = null;

	protected static $_options_instance = null;

	protected static $_notifier_instance = null;

	protected static $_logger_instance = null;

	protected static $_dashboard_instance = null;

	protected static $_db_operations_instance = null;
	
	/**
	 * Constructor for main plugin class
	 */
	public function __construct() {
		
		register_activation_hook(__FILE__, array($this, 'klick_aps_activation_actions'));

		register_deactivation_hook(__FILE__, array($this, 'klick_aps_deactivation_actions'));

		add_action('wp_ajax_klick_aps_ajax', array($this, 'klick_aps_ajax_handler'));
		
		add_action('admin_menu', array($this, 'init_dashboard'));
		
		add_action('plugins_loaded', array($this, 'setup_translation'));
		
		add_action('plugins_loaded', array($this, 'setup_loggers'));

		add_action( 'wp_footer', array($this, 'klick_aps_ui_scripts'));

		add_action( 'wp_head', array($this, 'klick_aps_ui_css'));
		
		add_filter( 'install_plugins_tabs', array($this, 'add_klick_adv_tab')); // add Advanced tab
		
		
		add_filter( 'install_themes_tabs', array($this, 'add_klick_adv_theme_tab')); // add Advanced tab
		
		
		
		
		add_action( 'install_plugins_adv', array($this, 'klick_adv_tab_render')); // Render Advanced data

		add_filter( 'install_plugins_table_api_args_adv', array($this, 'add_klick_adv_tab_args')); // set args for grid display on Advanced tab

		add_filter( 'plugins_api_result', array($this, 'adv_response'),10, 3); // filter api results
	}

	/**
	 * build plugin array as part of response
	 *
	 * @return object
	 */
	public function adv_response($res, $action, $args) {
		require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
		require_once( ABSPATH . 'wp-admin/includes/class-wp-plugin-install-list-table.php' );
		global $tab;

		// Only modify response in advance tab
		if ($tab == "adv") {
			$table_name = "all_plugins";
			$update_db = isset( $_REQUEST['update_db']) ? wp_strip_all_tags($_REQUEST['update_db']) : "No";
			
			if (isset($update_db) &&  ($update_db == "Yes")) {
				$this->db_operations()->drop_table($table_name);
			}

			unset($res->plugins);
			
			$res->plugins = array();
			
			$advanced_search_toggle = $this->get_options()->get_option('advanced-search-toggle');

			// If advance search enable
			if (isset($advanced_search_toggle) && $advanced_search_toggle == 1 ) {
				if ($this->db_operations()->check_table_exist($table_name) == true) {
				
					$results = $this->db_operations()->get_table_data($table_name);

					$res->plugins = $results;
				}
				$this->get_options()->update_option('advanced-search-notice','');
				return $res;
			}

			$this->get_options()->update_option('advanced-search-notice', __('Your advanced search is turned off, <a href=' . admin_url() . '/admin.php?page=klick_aps>Turn on</a>','klick-aps'));
		}

		return $res;
	}
	
	/**
	 * Set args for advanced tab response (the response is regenerated adv_response
	 *
	 * @return object array
	 */
	public function add_klick_adv_tab_args($args) {
		$args['per_page'] = '';
		$args['fields'] = array(
				'icons' => true,
				'active_installs' => false,
				'tested' => true,
				'sections' => false,
				'versions' => false,
				'screenshots' => false,
				'tags' => false,
				);

		return $args;
	}
	
	/**
	 * Draw the advanced tab including the table of plugins
	 *
	 * @return object array
	 */
	public function klick_adv_tab_render() {
		
		$number_of_plugins = $this->get_number_of_plugins(); // total number of plugins in repo

		$table_name = "all_plugins";
		$aps_create_db = isset( $_REQUEST['aps_create_db']) ? wp_strip_all_tags($_REQUEST['aps_create_db']) : "";

		if(isset($aps_create_db)){

			// Create new table if needed
			$klick_aps_plugin_data = isset($aps_create_db) ? $aps_create_db : "0";
			if ($klick_aps_plugin_data != 0) {
				$this->db_operations()->create_table($table_name);
				$this->db_operations()->fill_table($table_name, $klick_aps_plugin_data);
			}		
		}
		
		if ($this->db_operations()->check_table_exist($table_name) == false) {
			?>
			<div class="klick-logo-and-title">
					<img src='<?php echo KLICK_APS_PLUGIN_URL ?>images/aps-banner.png' height='100px'>
			</div>	

			<h3><span class="plugin-status-lebel"></span></h3>
			<div class="downloaded-plugin-status">
				<span></span>
			</div>

			<form id='plugin-db_create' method='get'>

				<input type="hidden" name="tab" value = "adv">

				<BR><BR><?php _e('To use Advanced Plugin Search you need to download the plugin data','klick-aps'); ?> <BR><BR>
				
				<?php _e('Downloading all plugin data can take along time...','klick-aps'); ?>  <BR><BR>
				<p>
				 <select name="klick_aps_plugin_data" id="klick_aps_plugin_data">
					<option selected disabled value=""><?php _e('Select the amount of plugin data to download','klick-aps'); ?> </option>
					<option value="1200"><?php _e('Download 1,200 most popular plugins (30 seconds)','klick-aps'); ?></option>
					<option value="2400"><?php _e('Download 2,400 most popular plugins (60 seconds)','klick-aps'); ?> </option>
					<option value="12000"><?php _e('Download 1,2000 most popular plugins (7 minutes)','klick-aps'); ?> </option>
					<option value="24000"><?php _e('Download 24,000 most popular plugins (15 minutes)','klick-aps'); ?> </option>
					<option value="<?php echo $number_of_plugins ?>"><?php _e('Download ' .$number_of_plugins. ' plugins (30 minutes)','klick-aps'); ?> </option>
				</select>

	 			<script type="text/javascript">
	 			   var klick_aps_ajax_nonce='<?php echo wp_create_nonce('klick_aps_ajax_nonce'); ?>';
	 			</script>

				<input disabled id = "aps_create_db" name = "aps_create_db" type="button" class="button" value="<?php esc_attr_e( 'Download Plugin Data' ); ?>" /><BR><BR>
				</p>
				<?php _e(' The time estimates depend on many factors including your internet connection speed','klick-aps'); ?> <BR><BR>
			</form>
			<?php

		} else {

			require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
			require_once( ABSPATH . 'wp-admin/includes/class-wp-plugin-install-list-table.php' );
			
			// Define globals
			global $wp_list_table;
			$page_number = isset( $_REQUEST['page_number']) ? wp_strip_all_tags($_REQUEST['page_number']) : "1";?>
	
			<form id='plugin-filter' method='get'>
			<input type="hidden" name="tab" value = "adv">
			<input id="page_number" name="page_number" type="hidden" value = "<?php echo $page_number ?>">
			<input id="total_pages" name="total_pages" type="hidden" value = "<?php echo $this->get_options()->get_option('total-pages'); ?>">

			<?php  $data_loaded_time = $this->get_options()->get_option('data-loaded'); ?>
			<div class="klick-logo-and-title">
					<img src='<?php echo KLICK_APS_PLUGIN_URL ?>images/aps-banner.png' height='100px'>
					<h2><?php echo  __('Advanced Search Parameters (you last updated the plugin data','klick-aps') . " " . $this->klick_aps_time_elapsed_string($data_loaded_time) . __(' to re-download','klick-aps'); ?> <a href="<?php echo admin_url() . 'plugin-install.php?tab=adv&update_db=Yes' ?>" ><?php _e('click here','klick-aps'); ?></a>)</h2>
			</div>	
			

			<?php 
			$aps_search_notice = $this->get_options()->get_option('advanced-search-notice');
			 if (isset($aps_search_notice) && !empty($aps_search_notice)) {
			 	echo "<div class='klick-notice-message notice notice-error is-dismissible'><p>" . $aps_search_notice . "</p></div>"; 
			 }
			?>
			<div class="valid-message-area"></div>
			<div id="msg_area"></div>
			<!-- Advanced search form starts-->
			<ul class="klick-aps-advanceform">
				<li>
					<!-- Search by plugin name -->
			 	      <label for="search_by_name"><?php _e('Name','klick-aps'); ?> :
			 	      	<?php $checked = (isset($_REQUEST['allow_exact_name']) && wp_strip_all_tags($_REQUEST['allow_exact_name'], true) == "yes" ? 'checked' : ''); ?> &nbsp;&nbsp;
			 	      	<?php $search_by_name = isset( $_REQUEST['search_by_name']) ? wp_strip_all_tags($_REQUEST['search_by_name']) : "" ?>
			 	      	<span><?php echo "<input type='checkbox' id='allow_exact_name' name='allow_exact_name' value='yes'  $checked /> EXACT"; ?></span>
			 	      </label>
				 	   	<?php echo "<input type='text' id='search_by_name' name='search_by_name' value='" . wp_strip_all_tags($search_by_name, true) . "'>"; ?>
				 	   	
				</li>
				<li>
					<!-- Search by author -->
			  	    <label for="search_by_author"><?php _e('Author','klick-aps'); ?> :
			  	    	<?php $checked = (isset($_REQUEST['allow_exact_author']) && wp_strip_all_tags($_REQUEST['allow_exact_author'], true) == "yes" ? 'checked' : ''); ?> &nbsp;&nbsp;
			  	    	<?php $search_by_author = isset( $_REQUEST['search_by_author']) ? wp_strip_all_tags($_REQUEST['search_by_author']) : "" ?>
			  	    	<span><?php echo "<input type='checkbox' id='allow_exact_author' name='allow_exact_author' value='yes'  $checked /> EXACT"; ?> </span>
			  	    </label>
			 	 	   	<?php echo "<input type='text' id='search_by_author' name='search_by_author' value='" . $search_by_author. "'>"; ?>
				</li>
				<li>
					<!-- Search by tags -->
	 	 	 	      <label for="search_by_tags"><?php _e('Tags','klick-aps'); ?> :</label>
	 	 	 	      <?php $search_by_tags = isset( $_REQUEST['search_by_tags']) ? wp_strip_all_tags($_REQUEST['search_by_tags']) : "" ?>
	 	 		 	   	<?php echo "<input type='text' id='search_by_tags' name='search_by_tags' value='" . $search_by_tags. "'>"; ?>
				</li>
				<li>
					<!-- Search by keywords -->
		 	 	      <label for="search_by_keywords"><?php _e('Keywords','klick-aps'); ?> :</label>
		 	 	      <?php $search_by_keywords = isset( $_REQUEST['search_by_keywords']) ? wp_strip_all_tags($_REQUEST['search_by_keywords']) : "" ?>
		 		 	   	<?php echo "<input type='text' id='search_by_keywords' name='search_by_keywords' value='" . $search_by_keywords. "'>"; ?>
				</li>
				<li>
					<!-- Search by descriptions -->
 		 	 	      <label for="search_by_description"><?php _e('Description','klick-aps'); ?> :</label>
 		 	 	       <?php $search_by_description = isset( $_REQUEST['search_by_description']) ? wp_strip_all_tags($_REQUEST['search_by_description']) : "" ?>
 		 		 	   	<?php echo "<input type='text' id='search_by_description' name='search_by_description' value='" . $search_by_description. "'>"; ?>
				</li>
				<li>
					<!-- Minimum WP Version  -->
					 <label for="required_wp_version"><?php _e('Minimum WP Version','klick-aps'); ?> :</label>
					 <select name="required_wp_version" id="required_wp_version">
						<?php 
						$required_wp_version = isset( $_REQUEST['required_wp_version']) ? wp_strip_all_tags($_REQUEST['required_wp_version']) : "";
						$avail_version = array('0.0','0.7','1.0','2.0','3.0','4.0','4.5','4.6','4.7','4.8','4.8.1','4.8.2'); 
						foreach ($avail_version as $key => $value) {
							$selected = ( $required_wp_version == $value ) ? "selected" : "";
							$selection_string = ($value == 0.0) ? "Any" : $value;
							echo "<option value='$value' $selected >" . $selection_string  . "</option>";
						}
						?>
					 </select>
				</li>
				<li>
					<!-- Requires minimum PHP version  -->
					 <label for="required_php_version"> <?php _e('Minimum PHP version','klick-aps'); ?> :</label>
					 <select name="required_php_version" id="required_php_version">
						<?php 
						$required_php_version = isset( $_REQUEST['required_php_version']) ? wp_strip_all_tags($_REQUEST['required_php_version']) : "";
						$avail_php_version = array('0.0','5.2','5.6','7.0.0','7.1.6','7.1.7','7.1.8','7.1.9','7.1.10'); 
						foreach ($avail_php_version as $key => $value) {
							$selected = ($required_php_version== $value ) ? "selected" : "";
							$selection_string = ($value == 0.0) ? "Any" : $value;
							echo "<option value='$value' $selected >" . $selection_string . "</option>";
						}
						?>
					 </select>
				</li>
				<li>
					<!-- Minimum number of ratings  -->
					 <label for="minimum nunber of ratings"><?php _e('Minimum number of ratings','klick-aps'); ?> :</label>
					 <select name="min_number_of_ratings">
						<?php 
						$min_number_of_ratings = isset( $_REQUEST['min_number_of_ratings']) ? wp_strip_all_tags($_REQUEST['min_number_of_ratings']) : "";
						for ($i=0; $i<=1000; $i=$i+100) {
							 $selected = ($min_number_of_ratings == $i ) ? "selected" : "";
							echo "<option value='$i' $selected >" . $i . "</option>";
						}
						?>
					 </select>
				</li>
				<li>
					<!-- Minimum Avg rating % -->
					 <label for="avg_ratings"><?php _e('Minimum Avg rating %','klick-aps'); ?> :</label>
					 <?php $avg_ratings = isset($_REQUEST["avg_ratings"]) ? wp_strip_all_tags($_REQUEST["avg_ratings"], true) : "0"; ?>
				 	 <?php echo "<input type='text' id='avg_ratings' class='aps-form' name='avg_ratings' value='" . $avg_ratings . "'>"; ?>
				</li>
				<li>
					<!-- Minimum number of screen shots -->
	 	     	     <label for="num_of_screenshots"><?php _e('Minimum number of screen shots','klick-aps'); ?> :</label>
	 	     	     <?php $num_of_screenshots = isset($_REQUEST["num_of_screenshots"]) ? wp_strip_all_tags($_REQUEST["num_of_screenshots"], true) : "0"; ?>
	 	    	 	 <?php echo "<input type='text' id='num_of_screenshots' class='aps-form' name='num_of_screenshots' value='" .$num_of_screenshots. "'>"; ?>
				</li>
				<li>
					<!-- Active installs -->
			 	  <label for="active_installs"><?php _e('Minimum active installs','klick-aps'); ?> :</label>
			 	  	<?php $active_installs = isset($_REQUEST["active_installs"]) ? wp_strip_all_tags($_REQUEST["active_installs"], true) : "0"; ?>
			 	 	<input type="text" name="active_installs" id="active_installs" class="aps-form" value="<?php echo $active_installs; ?> ">
				</li>
				<li>
					<!-- Downloaded -->
			 	    <label for="downloaded"><?php _e('Minimum number of downloads','klick-aps'); ?> :</label>
			 	    <?php $downloaded = isset($_REQUEST["downloaded"]) ? wp_strip_all_tags($_REQUEST["downloaded"], true) : "0"; ?>
				 	   	<input type="text" name="downloaded" id="downloaded" class="aps-form" value="<?php echo $downloaded;  ?>">
				</li>
				<li>
					<!-- Last updated before -->
			 	       <label for="last_updated_before"><?php _e('Last updated before','klick-aps'); ?> :</label>
			 	       <?php $last_updated_before = isset($_REQUEST["last_updated_before"]) ? wp_strip_all_tags($_REQUEST["last_updated_before"], true) : ""; ?>
				 	   	<?php echo "<input type='date' id='last_updated_before' name='last_updated_before' value='" . $last_updated_before . "'>"; ?>
				</li>
				<li>
					<!-- Last updated after-->
			  	       <label for="last_updated_after"><?php _e('Last updated after','klick-aps'); ?> :</label>
			  	       <?php $last_updated_after = isset($_REQUEST["last_updated_after"]) ? wp_strip_all_tags($_REQUEST["last_updated_after"], true) : ""; ?>
			 	 	   	<?php echo "<input type='date' id='last_updated_after' name='last_updated_after' value='" . $last_updated_after. "'>"; ?>
				</li>
				<li>
					<!-- Added before -->
	 	 	 	        <label for="added_before"><?php _e('Added before','klick-aps'); ?> :</label>
	 	 	 	        <?php $added_before = isset($_REQUEST["added_before"]) ? wp_strip_all_tags($_REQUEST["added_before"], true) : ""; ?>
	 	 		 	   	<?php echo "<input type='date' id='added_before' name='added_before' value='" .$added_before. "'>"; ?>
				</li>
				<li>
					<!-- Added after -->
 		 	 	        <label for="added_after"><?php _e('Added after','klick-aps'); ?> :</label>
 		 	 	        <?php $added_after = isset($_REQUEST["added_after"]) ? wp_strip_all_tags($_REQUEST["added_after"], true) : ""; ?>
 		 		 	   	<?php echo "<input type='date' id='added_after' name='added_after' value='" . $added_after. "'>"; ?> 
				</li>

				<li>
					<!-- Advanced search form ends -->
					<input id = "aps_find_my_plugins" type="submit" class="button" value="<?php esc_attr_e( 'Find My Plugins' ); ?>" />
				</li>
			</ul>

			<!-- Top Pagination starts -->
			<?php 
			$total_rows =  $this->get_options()->get_option('affected-total-rows');
			$advanced_search_toggle = $this->get_options()->get_option('advanced-search-toggle');

			// If advanced search enable
			if(!empty($total_rows) && isset($advanced_search_toggle) && $advanced_search_toggle == 1){
				$this->klick_aps_pagination($page_number); 
			}
			?>
			<!-- Pagination ends -->

			<?php $wp_list_table->display();?>

			</form>
			<?php
		}
	}
	
	/**
	 * Define Pagination same as WP default
	 *
	 * @return void
	 */
	public function klick_aps_pagination($page_number) {
		?>
		<div class="tablenav-pages custom-nav"><span class="displaying-num"><?php echo $this->get_options()->get_option('affected-total-rows'); ?> items</span>
			<?php if ($page_number != 1) { ?>
			<input class="tablenav-pages-navspan" id = "klick_aps_go_to_first" name = "klick_aps_go_to_first" type="submit"  value="&laquo;" />
			<?php } else { ?>
			<input class="tablenav-pages-navspan disabled" id = "klick_aps_go_to_first" name = "klick_aps_go_to_first" type="submit"  value="&laquo;" disabled="disabled" />
			<?php } ?>

			<?php if ($page_number != 1) { ?>
			<input class="tablenav-pages-navspan" id = "aps_prev_page" name = "aps_prev_page" type="submit" value="&lsaquo;" />
			<?php } else { ?>
			<input class="tablenav-pages-navspan disabled" id = "aps_prev_page" name = "aps_prev_page" type="submit" value="&lsaquo;" disabled="disabled" />
			<?php } ?>

			<span class="paging-input">
				<label for="current-page-selector" class="screen-reader-text">Current Page</label>
				<input class="current-page" id="current-page-selector" name="current-page-selector" type="text" name="paged" value="<?php echo $page_number; ?>" size="3" aria-describedby="table-paging" readonly>
				<span class="tablenav-paging-text"> of <span class="total-pages"><?php echo $this->get_options()->get_option('total-pages'); ?></span></span>
			</span>

			<?php if ($page_number != $this->get_options()->get_option('total-pages')) { ?>
			<input class="tablenav-pages-navspan" id = "aps_next_page" name = "aps_next_page" type="submit"  value="&rsaquo;" />
			<?php } else { ?>
			<input class="tablenav-pages-navspan disabled" id = "aps_next_page" name = "aps_next_page" type="submit"  value="&rsaquo;" disabled="disabled" />
			<?php } ?>
					
			<?php if ($page_number != $this->get_options()->get_option('total-pages')) { ?>
			<input class="tablenav-pages-navspan" id = "klick_aps_go_to_last" name = "klick_aps_go_to_last" type="submit" value="&raquo;" />
			<?php } else { ?>
			<input class="tablenav-pages-navspan disabled" id = "klick_aps_go_to_last" name = "klick_aps_go_to_last" type="submit" value="&raquo;" disabled="disabled" />
			<?php } ?>
		</div> <?php
	}

	/**
	 * Get response array from Plugin API
	 *
	 * @return array
	 */
	public function get_number_of_plugins() {
		$args = array(
			'per_page' => 1,
			'fields' => array ()
		);

		$response = wp_remote_post(
			'http://api.wordpress.org/plugins/info/1.0/',
			array(
				'body' => array(
					'action' => 'query_plugins',
					'request' => serialize( (object) $args )
				)
			)
		);
	
		$res = unserialize(wp_remote_retrieve_body($response));
		return $res->info['results'];
	}
	
	/**
	 * Create string with 'ago' keywords
	 *
	 * @param  string 	$datetime
	 * @param  boolean 	$full, Default false
	 * @return string
	 */
	public function klick_aps_time_elapsed_string($datetime, $full = false) {
  	   $now = new DateTime;
       $ago = new DateTime($datetime);
       $diff = $now->diff($ago);

       $diff->w = floor($diff->d / 7);
       $diff->d -= $diff->w * 7;

       $string = array(
           'y' => 'year',
           'm' => 'month',
           'w' => 'week',
           'd' => 'day',
           'h' => 'hour',
           'i' => 'minute',
           's' => 'second',
       );

       foreach ($string as $k => &$v) {
           if ($diff->$k) {
               $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
           } else {
               unset($string[$k]);
           }
       }

       if (!$full) $string = array_slice($string, 0, 1);

       return $string ? implode(', ', $string) . ' ago' : 'just now';
	}
	
	/**
	 * Instantiate Klick_Aps if needed
	 *
	 * @return object Klick_Aps
	 */
	public static function instance() {
		if (empty(self::$_instance)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Instantiate Klick_Aps_Db_Operations if needed
	 *
	 * @return object Klick_Aps
	 */
	public static function db_operations() {
		if (empty(self::$_db_operations_instance)) {
			if (!class_exists('Klick_Aps_Db_Operations')) include_once(KLICK_APS_PLUGIN_MAIN_PATH . '/includes/class-klick-aps-db-operations.php');
			self::$_db_operations_instance = new Klick_Aps_Db_Operations();
		}
		return self::$_db_operations_instance;
	}

	/**
	 * Instantiate Klick_Aps_Options if needed
	 *
	 * @return object Klick_Aps_Options
	 */
	public static function get_options() {
		if (empty(self::$_options_instance)) {
			if (!class_exists('Klick_Aps_Options')) include_once(KLICK_APS_PLUGIN_MAIN_PATH . '/includes/class-klick-aps-options.php');
			self::$_options_instance = new Klick_Aps_Options();
		}
		return self::$_options_instance;
	}
	
	/**
	 * Instantiate Klick_Aps_Dashboard if needed
	 *
	 * @return object Klick_Aps_Dashboard
	 */
	public static function get_dashboard() {
		if (empty(self::$_dashboard_instance)) {
			if (!class_exists('Klick_Aps_Dashboard')) include_once(KLICK_APS_PLUGIN_MAIN_PATH . '/includes/class-klick-aps-dashboard.php');
			self::$_dashboard_instance = new Klick_Aps_Dashboard();
		}
		return self::$_dashboard_instance;
	}
	
	/**
	 * Instantiate Klick_Aps_Logger if needed
	 *
	 * @return object Klick_Aps_Logger
	 */
	public static function get_logger() {
		if (empty(self::$_logger_instance)) {
			if (!class_exists('Klick_Aps_Logger')) include_once(KLICK_APS_PLUGIN_MAIN_PATH . '/includes/class-klick-aps-logger.php');
			self::$_logger_instance = new Klick_Aps_Logger();
		}
		return self::$_logger_instance;
	}
	
	/**
	 * Instantiate Klick_Aps_Notifier if needed
	 *
	 * @return object Klick_Aps_Notifier
	 */
	public static function get_notifier() {
		if (empty(self::$_notifier_instance)) {
			include_once(KLICK_APS_PLUGIN_MAIN_PATH . '/includes/class-klick-aps-notifier.php');
			self::$_notifier_instance = new Klick_Aps_Notifier();
		}
		return self::$_notifier_instance;
	}
	
	/**
	 * Establish Capability
	 *
	 * @return string
	 */
	public function capability_required() {
		return apply_filters('klick_aps_capability_required', 'manage_options');
	}
	
	/**
	 * Init dashboard with menu and layout
	 *
	 * @return void
	 */
	public function init_dashboard() {
		$dashboard = $this->get_dashboard();
		$dashboard->init_menu();
		load_plugin_textdomain('klick-aps', false, dirname(plugin_basename(__FILE__)) . '/languages');
	}

	/**
	 * Add advance (APS special tab)
	 *
	 * @return Array
	 */
	public function add_klick_adv_tab($tabs) {
	   $tabs['adv'] = __( 'Advanced Search' );
	   return $tabs;
	}
	
	
	public function add_klick_adv_theme_tab($tabs) {
		error_log('here adding tba');
		$tabs['adv'] = __( 'Advanced Search' );
		return $tabs;
	}
	
	
	/**
	 * To enqueue js at user side
	 *
	 * @return void
	 */
	public function klick_aps_ui_scripts() {
		$dashboard = $this->get_dashboard();
		$dashboard->init_user_end();
	}

	/**
	 * To enqueue css at user side
	 *
	 * @return void
	 */
	public function klick_aps_ui_css(){
		$dashboard = $this->get_dashboard();
		$dashboard->init_user_css();
	}

	/**
	 * Perform post plugin loaded setup
	 *
	 * @return void
	 */
	public function setup_translation() {
		load_plugin_textdomain('klick-aps', false, dirname(plugin_basename(__FILE__)) . '/languages');
	}

	/**
	 * Creates an array of loggers, Activate and Adds
	 *
	 * @return void
	 */
	public function setup_loggers() {
		
		$logger = $this->get_logger();

		$loggers = $logger->klick_aps_get_loggers();
		
		$logger->activate_logs($loggers);
		
		$logger->add_loggers($loggers);
	}
	
	/**
	 * Ajax Handler
	 */
	public function klick_aps_ajax_handler() {

		$nonce = empty($_POST['nonce']) ? '' : $_POST['nonce'];
		if (!wp_verify_nonce($nonce, 'klick_aps_ajax_nonce') || empty($_POST['subaction'])) die('Security check');
		
		$parsed_data = array();
		$data = array();
		
		$subaction = sanitize_key($_POST['subaction']);
		
		$post_data = isset($_POST['data']) ? $_POST['data'] : null;
		
		parse_str($post_data, $parsed_data); // convert string to array

		switch ($subaction) {
			case 'klick_aps_build_plugin_table':
				$data['remaining_plugins'] = sanitize_text_field($parsed_data['remaining_plugins']);
				$data['page_count'] = sanitize_text_field($parsed_data['page_count']);
				break;
			case 'klick_aps_create_db':
				$data['how_many_plugins'] = sanitize_text_field($parsed_data['how_many_plugins']);
				break;
			case 'klick_aps_save_settings':
				$data['aps_advance_search_toggle'] = sanitize_text_field($parsed_data['aps_advance_search_toggle']);
				break;	
			// Add more cases here if you add subaction in plugin
			default:
				error_log("Klick_Aps_Commands: ajax_handler: no such sub-action (" . esc_html($subaction) . ")");
				die('No such sub-action/command');
		}
		
		$results = array();
		
		// Get sub-action class
		if (!class_exists('Klick_Aps_Commands')) include_once(KLICK_APS_PLUGIN_MAIN_PATH . 'includes/class-klick-aps-commands.php');

		$commands = new Klick_Aps_Commands();

		if (!method_exists($commands, $subaction)) {
			error_log("Klick_Aps_Commands: ajax_handler: no such sub-action (" . esc_html($subaction) . ")");
			die('No such sub-action/command');
		} else {
			$results = call_user_func(array($commands, $subaction), $data);

			if (is_wp_error($results)) {
				$results = array(
					'result' => false,
					'error_code' => $results->get_error_code(),
					'error_message' => $results->get_error_message(),
					'error_data' => $results->get_error_data(),
					);
			}
		}
		
		echo json_encode($results);
		die;
	}

	/**
	 * Plugin activation actions.
	 *
	 * @return void
	 */
	public function klick_aps_activation_actions(){
		$this->get_options()->set_default_options();
	}

	/**
	 * Plugin deactivation actions.
	 *
	 * @return void
	 */
	public function klick_aps_deactivation_actions(){
		$this->get_options()->delete_all_options();
		$this->db_operations()->drop_table("all_plugins");

	}
}

register_uninstall_hook(__FILE__,'klick_aps_uninstall_option');

/**
 * Delete data when uninstall
 *
 * @return void
 */
function klick_aps_uninstall_option(){
	Klick_Aps()->get_options()->delete_all_options();
}

/**
 * Instantiates the main plugin class
 *
 * @return instance
 */
function Klick_Aps(){
	 return Klick_Aps::instance();
}

endif;

$GLOBALS['Klick_Aps'] = Klick_Aps();
