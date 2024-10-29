<!-- First Tab content -->
<div id="klick_aps_tab_first">
	<div class="klick-notice-message"></div>
	<div class="klick-aps-data-listing-wrap">
		 <div class="klick-aps-form-wrapper"> <!-- Form wrapper starts -->
			<form>
	            <table class="form-table">
	                <tbody>
	                    <p id="klick_aps_blank_error" class="klick-aps-error"></p>
	     
	                    <tr>
	                        <th>
	                            <label for="aps_advance_search_toggle"><?php _e('Enable/Disable','klick-aps'); ?> : </label>
	                        </th>
	                        <td>
	                        	<?php $urltoggle = $options -> get_option('advanced-search-toggle'); ?>
	                            <?php _e('Enable','klick-aps'); ?> : <input type="radio" name="aps_advance_search_toggle" value="<?php _e('ON','klick-aps'); ?>" class="klick-aps-advance-search-toggle" <?php echo (!empty($urltoggle) ? 'checked = "checked"' : '' ); ?>> 
	                            <?php _e('Disable','klick-aps'); ?> : <input type="radio" name="aps_advance_search_toggle" value="<?php _e('OFF','klick-aps'); ?>" class="klick-aps-advance-search-toggle" <?php echo (empty($urltoggle) ? 'checked = "checked"' : '' ); ?>>
	                            <span class="klick-aps-error-text"></span>
	                        </td>
	                    </tr>
	                </tbody>
	            </table>
	        </form>

	        <p class="submit">
	            <button id="klick_aps_advanced_Save" name="klick_aps_advanced_Save" class="klick_btn button button-primary"><?php _e('Save','klick-aps'); ?></button>
	        </p>
	       </div> <!-- Form wrapper starts -->
	</div>
</div>
<script type="text/javascript">
    var klick_aps_ajax_nonce='<?php echo wp_create_nonce('klick_aps_ajax_nonce'); ?>';
</script>
