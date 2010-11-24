<?php
/*
Plugin Name: My Category Excluder
Plugin URI: http://www.techforum.sk/
Description: User can exclude particular category or categories
Version: 0.3
Author: Ján Bočínec
Author URI: http://johnnypea.wp.sk/
License: GPL2
*/

//exclude categories from query
function mce_cat_query( $wp_query )
{
  	global $user_ID;
	if ( $user_ID ) {
	  	$on_home = get_option('on_home');
	  	$on_archives = get_option('on_archives');
	  	$on_feeds = get_option('on_feeds');

		$exc_cats_array = get_user_meta( $user_ID, 'exc_cats_list', TRUE);
		if ( $exc_cats_array ) {
			foreach ( $exc_cats_array as $exc_cat_id ) {
				$exc_cats_list = "-$exc_cat_id,";
			}
		}
	
		if ( isset($exc_cats_list) ) {
		  	if( ( $on_home && (is_home() || is_front_page()) ) || 
		    	( $on_archives && is_archive() && !is_category() ) ||
		  			( $on_feeds && is_feed() ) ) {
						$wp_query->set('cat', $exc_cats_list);
		 	}
		}
	}
}
add_action('pre_get_posts', 'mce_cat_query' );

//add admin setting menu
function mce_menu() {
  	add_submenu_page('options-general.php', 'My Category Excluder', 'My Category Excluder', 'manage_options', 'my-category-excluder', 'mce_page' );

	//call register settings function
	add_action( 'admin_init', 'register_mce_settings' );
}
add_action('admin_menu', 'mce_menu');

function register_mce_settings() {
	//register settings
	register_setting( 'mce-group', 'on_home' );
	register_setting( 'mce-group', 'on_archives' );
	register_setting( 'mce-group', 'on_feeds' );
	register_setting( 'mce-group', 'include_cat' );
	register_setting( 'mce-group', 'exclude_cat' );
}

function mce_page() { ?>
		<div class="wrap"><h1>My Category Excluder Settings</h1>
		<form method="post" action="options.php">
		<?php settings_fields( 'mce-group' ); ?>
		<table class="form-table">					
		<tr><td colspan="2" valign="middle"><h3>Do NOT display excluded categories on:</h3></td></tr>			
		<tr>
		<td width="30%" valign="middle"><strong>Home page</strong></td>
		<td width="70%"><input type="checkbox" name="on_home" value="1" <?php if ( get_option('on_home') ) echo 'checked="checked"'; ?> /></td>
		</tr>
		<tr>
		<td width="30%" valign="middle"><strong>Archive pages (excluding category archives)</strong></td>
		<td width="70%"><input type="checkbox" name="on_archives" value="1" <?php if ( get_option('on_archives') ) echo 'checked="checked"'; ?> /></td>
		</tr>
		<td width="30%" valign="middle"><strong>Feeds</strong></td>
		<td width="70%"><input type="checkbox" name="on_feeds" value="1" <?php if ( get_option('on_feeds') ) echo 'checked="checked"'; ?> /></td>
		</tr>
		<tr><td colspan="2" valign="middle"><h3>Include/exclude options</h3>PLEASE, DO NOT USE BOTH AT THE SAME TIME! ALWAYS LEAVE ONE OF THEM BLANK!</td></tr>							
		<tr>
		<td width="30%" valign="middle"><strong>Categories which can be excluded</strong></td>
		<td width="70%"><input type="text" size="50" name="include_cat" value="<?php if ( get_option('include_cat') ) echo get_option('include_cat'); ?>" /><br />IDs separated by commas e.x.: 1,30,55</td>
		</tr>
		<tr>
		<td width="30%" valign="middle"><strong>Categories which cannot be excluded</strong></td>
		<td width="70%"><input type="text" size="50" name="exclude_cat" value="<?php if ( get_option('exclude_cat') ) echo get_option('exclude_cat'); ?>" /><br />IDs separated by commas e.x.: 1,30,55</td>
		</tr>
		</table>
		<p class="submit">
	    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
	    </p>	
		</form></div>
<?php }

//add fields to user profile
add_action( 'show_user_profile', 'mce_extra_profile_fields' );
add_action( 'edit_user_profile', 'mce_extra_profile_fields' );
 
function mce_extra_profile_fields( $user ) { ?>
 
    <h3>Exclude these categories from the site</h3>
 
    <table class="form-table">
 	<?php 
	$include_cat = get_option('include_cat');
  	$exclude_cat = get_option('exclude_cat');
	$exc_cats_array = get_user_meta( $user->ID, 'exc_cats_list', TRUE); 
	$categories =  get_categories('hide_empty=0&exclude=' . $exclude_cat . '&include=' . $include_cat); 
	$i = 1;
	$cat_count = count($categories);
	foreach ($categories as $category) { ?>
			<?php if ( $i == 1 ) : ?>
			<tr>
			<?php endif; ?>
			
            <td><label for="cat_<?php echo $category->cat_ID ?>"><?php echo $category->cat_name; ?></label></td>
 
            <td>
			<input type="checkbox" name="cat_<?php echo $category->cat_ID ?>" id="cat_<?php echo $category->cat_ID ?>" value="<?php echo $category->cat_ID ?>" <?php if ( $exc_cats_array && in_array($category->cat_ID, $exc_cats_array) ) echo 'checked="checked"'; ?> />                
            </td>			

			<?php if ( ( $i == 3 || $i % 3 == 0 ) && $i != $cat_count ) { ?>
			</tr><tr>	        	
			<?php } elseif ( $i == $cat_count ) { ?>
			</tr>	
			<?php } ?>
				
	<?php $i++; } ?>
 
    </table>
<?php }
 
add_action( 'personal_options_update', 'mce_save_extra_profile_fields' );
add_action( 'edit_user_profile_update', 'mce_save_extra_profile_fields' );
 
function mce_save_extra_profile_fields( $user_id ) {
	
    if ( !current_user_can( 'edit_user', $user_id ) )
        return false;
 	
	$include_cat = get_option('include_cat');
  	$exclude_cat = get_option('exclude_cat');
 	$categories =  get_categories('hide_empty=0&exclude=' . $exclude_cat . '&include=' . $include_cat);
 	$exc_cats_array = array();
	foreach ($categories as $category) {
		if ( isset($_POST['cat_' . $category->cat_ID]) ) {
			$getcatid = $_POST['cat_' . $category->cat_ID];
			$exc_cats_array[] = $getcatid;
		}	
	}
	
    update_user_meta( $user_id, 'exc_cats_list', $exc_cats_array );
}