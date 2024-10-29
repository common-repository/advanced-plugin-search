<!-- Second Tab content -->
<div id="klick_aps_tab_fourth">
	<h1><img src="<?php echo KLICK_APS_PLUGIN_URL . '/images/klick-on-it.png'; ?>"> <?php _e('Our new and upcoming plugins', 'klick-aps'); ?></h1>
	<br>
	<ul class="klick-aps-plugin-list">
		<?php 
		// Array (plugin logos and links)
		$logo_links = array(
			array('link_name' => 'user-activity-logger','link_logo' => 'UAL'),
			array('link_name' => '404-page-management','link_logo' => '404'),
			array('link_name' => 'configuration-and-status','link_logo' => 'CS'),
			array('link_name' => 'cron-viewer-and-manager','link_logo' => 'CVM'),
			array('link_name' => 'make-my-own-shortcodes','link_logo' => 'MOS'),
			array('link_name' => 'statistics-and-analytics','link_logo' => 'SA'),
			array('link_name' => 'short-link-and-traffic','link_logo' => 'SLT'),
			array('link_name' => 'advance-plugin-search','link_logo' => 'APS'),
			array('link_name' => 'advance-plugin-view','link_logo' => 'APV'),
			array('link_name' => 'advance-theme-search','link_logo' => 'ATS'),
		);
		$logo_with_link = "";

		// iternate through $logo_links
		foreach ($logo_links as $key => $value) { ?>
			<li>
				<img src="<?php echo KLICK_APS_PLUGIN_URL . '/images/our-more-plugins/' . $value['link_logo'] . '.svg';?>">
				<h4><a href="http://klick-on-it.com/wordpress-plugins/<?php echo $value['link_name']; ?>"><?php _e('Visit here', 'klick-aps'); ?></a></h4>
			</li>	
		<?php } ?>
	</ul>
</div>
