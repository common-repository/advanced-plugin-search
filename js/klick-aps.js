/**
 * Send an action via admin-ajax.php
 * 
 * @param {string} action - the action to send
 * @param * data - data to send
 * @param Callback [callback] - will be called with the results
 * @param {boolean} [json_parse=true] - JSON parse the results
 */
var klick_aps_send_command = function (action, data, callback, json_parse) {
	json_parse = ('undefined' === typeof json_parse) ? true : json_parse;
	var ajax_data = {
		action: 'klick_aps_ajax',
		subaction: action,
		nonce: klick_aps_ajax_nonce,
		data: data
	};
	jQuery.post(ajaxurl, ajax_data, function (response) {
		
		if (json_parse) {
			try {
				var resp = JSON.parse(response);
			} catch (e) {
				return;
			}
		} else {
			var resp = response;
		}
		
		if ('undefined' !== typeof callback) callback(resp);
	});
}

/**
 * When DOM ready
 * 
 */
jQuery(document).ready(function ($) {
	klick_aps = klick_aps(klick_aps_send_command);
	aps_clicked = false;
	
	$("#klick_aps_plugin_data").change(function(e) {
		$("#aps_create_db").prop( "disabled", false );
	});
	
	$("#aps_create_db").click(function(e) {
		if (!aps_clicked) {
			aps_clicked = true;
		}
	});
	
	$("#aps_find_my_plugins").click(function(e) {
		if (!aps_clicked) {
			aps_clicked = true;
			$("#page_number").val(1); // To start from first page
		}
	});
	
	$("#aps_next_page").click(function(e) {
		if (!aps_clicked) {
			aps_clicked = true;
			$("#page_number").val(parseInt($("#page_number").val()) + 1);
		}
	});
	
	$("#klick_aps_go_to_last").click(function(e) {
		if (!aps_clicked) {
			aps_clicked = true;
			$("#page_number").val($("#total_pages").val());
		}
	});

	$("#aps_prev_page").click(function(e) {
		if (!aps_clicked) {
			aps_clicked = true;
			$("#page_number").val(parseInt($("#page_number").val()) - 1);
		}
	});

	$("#klick_aps_go_to_first").click(function(e) {
		if (!aps_clicked) {
			aps_clicked = true;
			$("#page_number").val(1);
		}
	});

	if(klick_aps_admin.advanced_search_toggle != true){
		$(".klick-aps-advanceform").addClass('disabled');
	}
});

/**
 * Function for sending communications
 * 
 * @callable sendcommandCallable
 * @param {string} action - the action to send
 * @param * data - data to send
 * @param Callback [callback] - will be called with the results
 * @param {boolean} [json_parse=true] - JSON parse the results
 */
/**
 * Main klick_aps
 * 
 * @param {sendcommandCallable} send_command
 */
var klick_aps = function (klick_aps_send_command) {
	var $ = jQuery;
	$("#msg_area").hide();
	$("#klick_aps_advanced_Save").attr('disabled','disabled');

	/**
	 * When toggle radio change, Make enable save button
	 *
	 * @return void
	 */
	$(".klick-aps-advance-search-toggle").change(function(){
		$("#klick_aps_advanced_Save").prop('disabled',false);
	});

	/**
	 * Reflects default pagination to page_number (For bug issue)
	 *
	 * @return void
	 */
	$("#current-page-selector").keyup(function(){
		$("#page_number").val($(this).val());
	});

	/**
	 * Proceses the tab click handler
	 *
	 * @return void
	 */
	$('#klick_aps_nav_tab_wrapper .nav-tab').click(function (e) {
		e.preventDefault();
		
		var clicked_tab_id = $(this).attr('id');
	
		if (!clicked_tab_id) { return; }
		if ('klick_aps_nav_tab_' != clicked_tab_id.substring(0, 18)) { return; }
		
		var clicked_tab_id = clicked_tab_id.substring(18);

		$('#klick_aps_nav_tab_wrapper .nav-tab:not(#klick_aps_nav_tab_' + clicked_tab_id + ')').removeClass('nav-tab-active');
		$(this).addClass('nav-tab-active');

		$('.klick-aps-nav-tab-contents:not(#klick_aps_nav_tab_contents_' + clicked_tab_id + ')').hide();
		$('#klick_aps_nav_tab_contents_' + clicked_tab_id).show();
	});

	/**
	 * Gathers the details from form
	 * 
	 * @returns (string) - serialized row data
	 */
	function gather_row(){
		var form_data = $(".klick-aps-form-wrapper form").serialize();
		return form_data;	
	}

	/**
	 * Proceses the advance search toggle save click handler
	 *
	 * @return void
	 */
	$("#klick_aps_advanced_Save").click(function() {
		var form_data = gather_row();
		klick_aps_send_command('klick_aps_save_settings', form_data, function (resp) {
			$('.klick-notice-message').html(resp.status['messages']);
			$('.fade').delay(2000).slideUp(200, function(){
				$("#klick_aps_advanced_Save").prop('disabled','disabled');
			});
		});
	});

	/**
	 * Download plugin data from WordPress.org
	 *
	 * @return void
	 */
	$("#aps_create_db").click(function(e) {
		e.preventDefault();
		$(this).prop( "disabled", 'disabled');
		var page_size = 240;
		var required_plugins = parseInt($("#klick_aps_plugin_data").val());
		var page_count = 1;
		
		init_status();
		update_status(page_size / required_plugins * 100);
		
		build_plugin_table(required_plugins, required_plugins, page_count, page_size);
		
		return;
	});
	
	/**
	 * Click handler to communicate and store plugins in DB, Ajax send request and update status bar
	 *
	 * @return void
	 */
	function build_plugin_table(required_plugins, remaining_plugins, page_count, page_size) {
		var data = 'remaining_plugins=' + remaining_plugins + '&page_count=' + page_count;
		
		klick_aps_send_command('klick_aps_build_plugin_table', data, function (resp) {
			
			if (parseInt(resp.status.remaining_plugins) > 0) {
				remaining_plugins = parseInt(resp.status.remaining_plugins);
				
				success = resp.status.success;
				
				if (success) {
					page_count++;
				} else {
					page_count++;//if error skip the page
				}
				
				status = parseInt((page_size + required_plugins - remaining_plugins) / required_plugins * 100);
				status = Math.min(100,status);
		
				update_status(status);
				build_plugin_table(required_plugins, remaining_plugins, page_count, page_size);
			} else {
				window.location.replace(klick_aps_admin.aps_tab_url);
			}
		});
	};
	
	/**
	 * Intialize status bar
	 *
	 * @return void
	 */	
	function init_status() {
		$(".downloaded-plugin-status").css("display","block");
		$(".downloaded-plugin-status").find("span").css("display","block");
		$(".plugin-status-lebel").html('0%');
		$(".downloaded-plugin-status").find("span").css('width','0%');
		$(".downloaded-plugin-status").find("span").css('background', '#F5821F');
	}

	/**
	 * update status bar
	 *
	 * @param string status
	 * @return void
	 */
	function update_status(status) {
		var status = status;
		$(".plugin-status-lebel").html(Math.ceil(status) + '%');
		$(".downloaded-plugin-status").css("height",27+'px');
		$(".downloaded-plugin-status").find("span").css('width',status+'%');
		$(".downloaded-plugin-status").find("span").css('background', '#F5821F');
		// if(status >= 100){
		// 	window.location.replace(klick_aps_admin.aps_tab_url);
		// 	return false;
		// }
	}

	/**
	 * Change handler on some form control which raised validation
	 *
	 * @return void
	 */
	$('.aps-form').change(function(event) {
		set_notice_message_hide("#msg_area");
		$("#aps_find_my_plugins").attr('disabled',false);
	});

	/**
	 * Create and render notice admin side
	 *
	 * @string string selecoter, e.g. #msg_area
	 * @msg string msg
	 * @return void
	 */
	function set_notice_message_generate(selector, msg){
		$(""+selector+"").addClass('klick-notice-message notice notice-error is-dismissible');
		$(""+selector+"").html("<p>" + msg +  "</p>");
		$(""+selector+"").slideDown();
		$("#aps_find_my_plugins").attr('disabled','disabled');
	}

	/**
	 * Validate before form submit
	 *
	 * @return boolean
	 */
	$("#plugin-filter").submit(function(){
		// avg_ratings
		var avg_ratings_element_val = $.trim($("#avg_ratings").val());
		if(check_for_empty(avg_ratings_element_val) === true){
 				$("#avg_ratings").val("0");
		}	
		else if(check_for_alpha(avg_ratings_element_val) === true){
			set_notice_message_generate('#msg_area',klick_aps_admin.notice_for_avg_ratings);
			return false;
		}
		else if(check_number_gt_100(avg_ratings_element_val) === true){
			set_notice_message_generate('#msg_area',klick_aps_admin.notice_for_avg_ratings);
			return false;
		}	

		// active_installs
		var active_install_element_val = $.trim($("#active_installs").val());
		if(check_for_empty(active_install_element_val) === true){
 				$("#active_installs").val("0");
		}	
		else if(check_for_alpha(active_install_element_val) === true){
			set_notice_message_generate('#msg_area',klick_aps_admin.notice_for_active_installs);
			return false;
		}
		// num_of_screenshots
		var num_of_screenshots = $.trim($("#num_of_screenshots").val());
		if(check_for_empty(num_of_screenshots) === true){
 				$("#num_of_screenshots").val("0");
		}	
		else if(check_for_alpha(num_of_screenshots) === true){
			set_notice_message_generate('#msg_area',klick_aps_admin.notice_for_screenshots);
			return false;
		}
		else if(check_number_gt_100(num_of_screenshots) === true){
			set_notice_message_generate('#msg_area',klick_aps_admin.notice_for_avg_ratings);
			return false;
		}
		// downloaded
		var downloaded = $.trim($("#downloaded").val());
		if(check_for_empty(downloaded) === true){
 				$("#downloaded").val("0");
		}	
		else if(check_for_alpha(downloaded) === true){
			set_notice_message_generate('#msg_area',klick_aps_admin.notice_for_downloaded);
			return false;
		}

		return true;
	});

	/**
	 * Hide admin notice
	 *
	 * @return void
	 */
	function set_notice_message_hide(selector){
		$(""+selector+"").slideUp();
	}

	/**
	 * Test expression if any non numeric is entered
	 *
	 * @return boolean
	 */
	 function check_for_alpha( str ) {
	 	return !/^[0-9]+$/.test(str);
	}

	/**
	 * Checking for empty form control specially texbox
	 *
	 * @return boolean(true)
	 */
	 function check_for_empty(str){
	 	if(str.length < 1) {
	 		return true;
	 	}
	 	return false;
	}

	/**
	 * Checking for if entered number is greater then 100
	 *
	 * @return boolean(true)
	 */
	function check_number_gt_100(str){
		if(str > 100){
			return true;
		}
		return false;
	}

}
