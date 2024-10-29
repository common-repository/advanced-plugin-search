<?php
//error_reporting(0);
if (!defined('KLICK_APS_VERSION')) die('No direct access allowed');
/**
 * Access via Klick_Aps()->get_options().
 */
class Klick_Aps_Db_Operations {
	
	private $options;
	
	/**
	 * Constructor for Commands class
	 *
	 */
	public function __construct() {
		$this->options = Klick_Aps()->get_options();
	} 
	
	/**
	 * Create table in DB
	 *
	 * @param  string table_name without prefix
	 * @return void
	 */
	public function create_table($table_name) {

	    // create database
		global $wpdb;
		
		$table_name = $wpdb->prefix . $table_name;

		$sql = "DROP TABLE IF EXISTS $table_name";
		$wpdb->query($sql);
		
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (id mediumint(9) NOT NULL AUTO_INCREMENT,
			name tinytext NOT NULL,
			slug tinytext NOT NULL,
			version tinytext,
			author tinytext,
			author_name varchar(65535),
			author_profile tinytext,
			requires tinytext,
			requires_php tinytext,
			rating tinytext,
			ratings varchar(191),
			screenshots varchar(65535),
			number_of_screenshots tinytext,
			versions varchar(65535),
			num_ratings  tinytext,
			support_threads tinytext,
			support_threads_resolved tinytext,
			active_installs tinytext,
			downloaded tinytext,
			last_updated tinytext,
			added tinytext,
			homepage tinytext,
			short_description tinytext,
			download_link tinytext,
			donate_link tinytext,
			icons varchar(65535),
			contributors varchar(65535),
			tags varchar(65535),
			PRIMARY KEY  (id)) $charset_collate;";
			
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		dbDelta( $sql );
		Klick_Aps()->get_options()->update_option('data-loaded',current_time('mysql'));
	}
	
	/**
	 * Insert plugin row
	 *
	 * @param  object $pluginvalue
	 * @return void
	 */
	public function insert_plugin($pluginvalue) {
		
		global $wpdb;
		$table_name = $wpdb->prefix . "all_plugins";
		
		return $wpdb->insert($table_name, array(
			'name' => $pluginvalue->name,
			'slug' => $pluginvalue->slug,
			'version' => $pluginvalue->version,
			'author' => $pluginvalue->author,
			'author_name' => strip_tags($pluginvalue->author),
			'author_profile' => $pluginvalue->author_profile,
			'requires' => $pluginvalue->requires,
			'requires_php' => $pluginvalue->requires_php,
			'rating' => $pluginvalue->rating,
			'ratings' => serialize($pluginvalue->ratings),
			'screenshots' => serialize($pluginvalue->screenshots),
			'number_of_screenshots' => count($pluginvalue->screenshots),
			'versions' => serialize($pluginvalue->versions),
			'num_ratings' => $pluginvalue->num_ratings,
			'support_threads' => $pluginvalue->support_threads,
			'support_threads_resolved' => $pluginvalue->support_threads_resolved,
			'active_installs' => $pluginvalue->active_installs,
			'downloaded' => $pluginvalue->downloaded,
			'last_updated' => $pluginvalue->last_updated,
			'added' => $pluginvalue->added,
			'homepage' => $pluginvalue->homepage,
			'short_description' => $pluginvalue->short_description,
			'download_link' => $pluginvalue->download_link,
			'donate_link' => $pluginvalue->donate_link,
			'icons' => serialize($pluginvalue->icons),
			'contributors' => serialize($pluginvalue->contributors),
			'tags' => serialize($pluginvalue->tags),
		));
	}
	
	/**
	 * Fetch plugin from API
	 *
	 * @param  string $page_count
	 * @param  string $remaining_plugins
	 * @return mixed
	 */
	public function get_plugins($page_count, $remaining_plugins) {
				
		$args['fields']['icons'] = true;
		
		$args = (object)array(
			'page' => $page_count,
			'per_page' => min(240,$remaining_plugins),
			'versions' => false,
			'fields' =>array(
				'icons' => true,
				'active_installs' => true,
				'sections' => false,
				'versions' => false
				)
			);
		
		$request = array('action' => 'query_plugins', 'request' => serialize($args));
		
		$url = 'http://api.wordpress.org/plugins/info/1.0/';
		
		$response = wp_remote_post($url, array('body' => $request, 'timeout' => 15) );
		
		if (is_wp_error($response)) {
			Klick_Aps()->get_logger()->log(__("Debug", "klick-aps"), __("Klick APS received an error from WordPress.org API on page ".$page_count.". Skipping page.", "klick-aps"), array('php'));
		}
		
		return unserialize(wp_remote_retrieve_body($response));
	}

	/**
	 * Handler function for 'build_plugin_data' ajax request
	 *
	 * @param  string $data
	 * @return array  $response
	 */
	public function build_plugin_table($data) {
		
		$remaining_plugins = $data['remaining_plugins'];
		$page_count = $data['page_count'];
		
		if($page_count == 1){
			$this->create_table("all_plugins");
		}
		
		$res = $this->get_plugins($page_count,$remaining_plugins);
		
		$success = false;
		
		if(is_object($res)) {
			
			$success = true;
			
			foreach ($res->plugins as $pluginkey => $pluginvalue) {
				if ($this->insert_plugin($pluginvalue)) {
					$remaining_plugins--;
				}
			}
		}
		
		$response = array('remaining_plugins'=>$remaining_plugins,'success'=>$success);
		
		return $response;
	}
	
	/**
	 * Fetch rows from table
	 *
	 * @param  string table_name without prefix
	 * @return void
	 */
	public function get_table_data($table_name) {
		global $wpdb;
		$table_name = $wpdb->prefix . $table_name;
		$query_params  = "";
		$query_params .= !empty($_REQUEST['required_wp_version']) ? "requires >= " . wp_strip_all_tags($_REQUEST['required_wp_version'], true) : "requires >= 0.0"; 
		$search_enable = Klick_Aps()->get_options()->get_option('advanced-search-toggle');
		$per_page_limit = 24;
		$navigation = array();

		$page_number = isset( $_REQUEST['page_number']) ? $_REQUEST['page_number'] : "";

		$navigation = $this->set_start_and_limit($page_number, $per_page_limit);

		$start = $navigation['start'];
		$limit = $navigation['limit'];
		
		$form_data = array('min_number_of_ratings'=> isset( $_REQUEST['min_number_of_ratings']) ? $_REQUEST['min_number_of_ratings'] : "",
						'required_php_version' => isset( $_REQUEST['required_php_version']) ? $_REQUEST['required_php_version'] : "",
						'avg_ratings' => isset( $_REQUEST['avg_ratings']) ? $_REQUEST['avg_ratings'] : "" ,
						'active_installs' => isset( $_REQUEST['active_installs']) ? $_REQUEST['active_installs'] : "",
						'downloaded' => isset( $_REQUEST['downloaded']) ? $_REQUEST['downloaded'] : "",
						'num_of_screenshots' => isset( $_REQUEST['num_of_screenshots']) ? $_REQUEST['num_of_screenshots'] : "",
						'last_updated_before' => isset( $_REQUEST['last_updated_before']) ? $_REQUEST['last_updated_before'] : "",
						'last_updated_after' => isset( $_REQUEST['last_updated_after']) ? $_REQUEST['last_updated_after'] : "",
						'added_before' => isset( $_REQUEST['added_before']) ? $_REQUEST['added_before'] : "",
						'added_after' => isset( $_REQUEST['added_after']) ? $_REQUEST['added_after'] : "",
						'search_by_tags' => isset( $_REQUEST['search_by_tags']) ? $_REQUEST['search_by_tags'] : "",
						'search_by_name' => isset( $_REQUEST['search_by_name']) ? $_REQUEST['search_by_name'] : "",
						'search_by_author' => isset( $_REQUEST['search_by_author']) ? $_REQUEST['search_by_author'] : "" ,
						'search_by_description' => isset( $_REQUEST['search_by_description']) ? $_REQUEST['search_by_description'] : "" ,
						'search_by_keywords' => isset( $_REQUEST['search_by_keywords']) ? $_REQUEST['search_by_keywords'] : "",
						'allow_exact_name' => isset( $_REQUEST['allow_exact_name']) ? $_REQUEST['allow_exact_name'] : "" ,
						'allow_exact_author' => isset( $_REQUEST['allow_exact_author']) ? $_REQUEST['allow_exact_author'] : "",
					);

		$sanitized_form_data = $this->create_sanitized_data($form_data);

		if (isset($search_enable) && $search_enable == 1) {

			// By ratings
			$query_params .= !empty($sanitized_form_data['min_number_of_ratings']) ?  " AND num_ratings >= " . $sanitized_form_data['min_number_of_ratings']  :  "";

			// By php version
			$query_params .= !empty($sanitized_form_data['required_php_version']) ?  " AND requires_php >= " . $sanitized_form_data['required_php_version']  :  "";

			// By average ratings
			$query_params .= !empty($sanitized_form_data['avg_ratings']) ?  " AND rating >= " . $sanitized_form_data['avg_ratings']  :  "";

			// By active installs
			$query_params .= !empty($sanitized_form_data['active_installs']) ?  " AND active_installs >= " . $sanitized_form_data['active_installs']  :  "";

			// By downloaded
			$query_params .= !empty($sanitized_form_data['downloaded']) ?  " AND downloaded >= " . $sanitized_form_data['downloaded']  : "";

			// By number of screen shots
			$query_params .= !empty($sanitized_form_data['num_of_screenshots']) ?  " AND number_of_screenshots >= " . $sanitized_form_data['num_of_screenshots']  : "";

			// By last updated before
			$query_params .= !empty($sanitized_form_data['last_updated_before']) ?  " AND last_updated <= '" . $sanitized_form_data['last_updated_before'] . "'" : "";

			// By last updated after
			$query_params .= !empty($sanitized_form_data['last_updated_after']) ?  " AND last_updated >= '" . $sanitized_form_data['last_updated_after'] . "'" : "";

			// By added before
			$query_params .= !empty($sanitized_form_data['added_before']) ?  " AND added <= '" . $sanitized_form_data['added_before'] . "'" : "";

			// By added after
			$query_params .= !empty($sanitized_form_data['added_after']) ?  " AND added >= '" . $sanitized_form_data['added_after'] . "'" : "";

			// By tags
			$query_params .= !empty($sanitized_form_data['search_by_tags']) ?  " AND tags LIKE '%" . $sanitized_form_data['search_by_tags'] . "%'"  : "";

			// By name or exact
			if ($sanitized_form_data['allow_exact_name']== "yes") {
				$query_params .= !empty($sanitized_form_data['search_by_name']) ?  " AND name = '" . $sanitized_form_data['search_by_name'] . "'"  : "";
			} else {
				$query_params .= !empty($sanitized_form_data['search_by_name']) ?  " AND name LIKE '%"  . $sanitized_form_data['search_by_name'] . "%'"  : "";
			}

			// By author or exact
			if ($sanitized_form_data['allow_exact_author']== "yes") {
				$query_params .= !empty($sanitized_form_data['search_by_author']) ?  " AND author_name = '" . $sanitized_form_data['search_by_author'] . "'"  : "";
			} else {
 				$query_params .= !empty($sanitized_form_data['search_by_author']) ?  " AND author_name LIKE '%" . $sanitized_form_data['search_by_author'] . "%'"  : "";
			}

			// By Description
			$query_params .= !empty($sanitized_form_data['search_by_description']) ?  " AND short_description LIKE '%" . $sanitized_form_data['search_by_description'] . "%'"  : "";

			// By Keywords
			$keywords = $sanitized_form_data['search_by_keywords'];
			$query_params .= !empty($keywords) ? " AND (short_description LIKE '%" . $keywords . "%'" . " OR slug LIKE '%" . $keywords . "%'" . " OR name LIKE '%" . $keywords . "%'" . " OR author_name LIKE '%" . $keywords . "%' )" : "";

			// Get total number of rows and page number
			$query_for_num_rows = $wpdb->get_results(
			"SELECT  count(*) as total_num_rows
				FROM  $table_name WHERE $query_params 
				
			");

			Klick_Aps()->get_options()->update_option('affected-total-rows',$query_for_num_rows[0]->total_num_rows);
			Klick_Aps()->get_options()->update_option('total-pages',ceil($query_for_num_rows[0]->total_num_rows/$per_page_limit)); // Divided by per page limit to get total page number.

			$results = $wpdb->get_results(
			"SELECT name,
					slug,
					version,
					author,
					author_profile,
					requires,
					requires_php,
					rating,
					ratings,
					screenshots,
					versions,
					num_ratings,
					support_threads,
					support_threads_resolved,
					active_installs,
					downloaded,
					last_updated,
					added,
					homepage,
					short_description,
					download_link,
					donate_link,
					icons
				FROM  $table_name WHERE $query_params LIMIT $start, $limit
			");

			foreach ($results as $key => $value) {
				$icons = $value->icons; 				
				unset($results[$key]->icons); 			
				$results[$key]->icons = unserialize($icons);  
			}
			return $results;
	    }
	}

	/**
	 * This create starts and limit on every page
	 *
	 * @param  string $page_number
	 * @param  string $per_page_limit
	 * @return array
	 */
	public function set_start_and_limit($page_number = 1, $per_page_limit){
		if (isset($page_number) && !empty($page_number)) {
			// For first page
			if ($page_number == 1) {
				$start = 0;
				$limit= $per_page_limit;
			} else {
			// For all other pages except 1	
				$start = ($page_number - 1) * $per_page_limit;
				$limit= $per_page_limit;
			}
		} else {
			$start = 0;
			$limit = $per_page_limit;
		}

		return $navigation = array('start'=>$start, 'limit' => $limit);
	}

	/**
	 * This function is used to sanitized sensitive data, removes style, script tags and space to requested form params
	 *
	 * @param  arry $form_data
	 * @return array
	 */
	public function create_sanitized_data($form_data){

		return $form_data = array('min_number_of_ratings'=> $form_data['min_number_of_ratings'],
						'required_php_version' => $form_data['required_php_version'],
						'avg_ratings' => $form_data['avg_ratings'],
						'active_installs' => wp_strip_all_tags($form_data['active_installs'], true),
						'downloaded' => $form_data['downloaded'],
						'num_of_screenshots' => wp_strip_all_tags($form_data['num_of_screenshots'], true),
						'last_updated_before' => $form_data['last_updated_before'],
						'last_updated_after' => $form_data['last_updated_after'],
						'added_before' => $form_data['added_before'],
						'added_after' => $form_data['added_after'],
						'search_by_tags' => wp_strip_all_tags($form_data['search_by_tags'], true),
						'search_by_name' => wp_strip_all_tags($form_data['search_by_name'], true),
						'search_by_author' => wp_strip_all_tags($form_data['search_by_author'], true),
						'search_by_description' => wp_strip_all_tags($form_data['search_by_description'], true),
						'search_by_keywords' => wp_strip_all_tags($form_data['search_by_keywords'], true),
						'allow_exact_name' => $form_data['allow_exact_name'],
						'allow_exact_author' => $form_data['allow_exact_author'],
					);
	}

	/**
	 * Drop table in database
	 *
	 * @param  string table_name without prefix
	 * @return void
	 */
	public function drop_table($table_name) {
		global $wpdb;
		$table_name = $wpdb->prefix . $table_name;
		$sql = "DROP TABLE IF EXISTS $table_name";
		$wpdb->query($sql);
	}

	/**
	 * Show table
	 *
	 * @param  string table_name without prefix
	 * @return void
	 */
	public function check_table_exist($table_name){

	    // create database
		global $wpdb;
		
		$table_name = $wpdb->prefix . $table_name;

		if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name){
			return true;
		}

		return false;
	}
}
