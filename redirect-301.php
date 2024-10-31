<?php
/** Plugin Name: Redirect 301
* Description: Easily redirect page to another page or anywhere on web.
* Version: 1.0.0
* Author: jackmore
**/

if (!class_exists("Redirect301")) {
	
	class Redirect301 {
		
		/**
		 * create_menu function
		**/
		function r_create_menu() {
		  add_options_page('Redirect 301', 'Redirect 301', 'manage_options', 'r301options', array($this,'r_options_page'));
		}
		
		/**
		 * options_page function
		**/
		function r_options_page() {
		?>
		<div class="wrap redirect_301">
			<script>
				//todo: This should be enqued
				jQuery(document).ready(function(){	
					jQuery('span.wps301-delete').html('Delete').css({'color':'red','cursor':'pointer'}).click(function(){
						var confirm_delete = confirm('Delete This Redirect?');
						if (confirm_delete) {
							
							// remove element and submit
							jQuery(this).parent().parent().remove();
							jQuery('#redirect_301_form').submit();
							
						}
					});
					
					jQuery('.redirect_301 .documentation').hide().before('<p><a class="reveal-documentation" href="#">Documentation</a></p>')
					jQuery('.reveal-documentation').click(function(){
						jQuery(this).parent().siblings('.documentation').slideToggle();
						return false;
					});
				});
			</script>
		
		<?php
			if (isset($_POST['301_redirects'])) {
				echo '<div id="message" class="updated"><p>Settings saved</p></div>';
			}
		?>
		
			<h2>Redirect 301</h2>
			
			<form method="post" id="redirect_301_form" action="options-general.php?page=r301options&savedata=true">
			
			<?php wp_nonce_field( 'r_save_redirects', '_s301r_nonce' ); ?>

			<table class="widefat">
				<thead>
					<tr>
						<th colspan="2">Request</th>
						<th colspan="2">Destination</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td colspan="2"><small>example: /about.htm</small></td>
						<td colspan="2"><small>example: <?php echo get_option('home'); ?>/about/</small></td>
					</tr>
					<?php echo $this->r_expand_redirects(); ?>
					<tr>
						<td style="width:35%;"><input type="text" name="301_redirects[request][]" value="" style="width:99%;" /></td>
						<td style="width:2%;">&raquo;</td>
						<td style="width:60%;"><input type="text" name="301_redirects[destination][]" value="" style="width:99%;" /></td>
						<td><span class="wps301-delete">Delete</span></td>
					</tr>
				</tbody>
			</table>
			
			<?php $wildcard_checked = (get_option('301_redirects_wildcard') === 'true' ? ' checked="checked"' : ''); ?>
			<p><input type="checkbox" name="301_redirects[wildcard]" id="wps301-wildcard"<?php echo $wildcard_checked; ?> /><label for="wps301-wildcard"> Use Wildcards?</label></p>
			
			<p class="submit"><input type="submit" name="submit_301" class="button-primary" value="<?php _e('Save Changes') ?>" /></p>
			</form>
			<div class="documentation">
				<h2>Documentation</h2>
				<h3>Redirect 301</h3>
				<p>Simple redirects work similar to the format that Apache uses: the request should be relative to your WordPress root. The destination can be either a full URL to any page on the web, or relative to your WordPress root.</p>
				<h4>Example</h4>
				<ul>
					<li><strong>Request:</strong> /old-page/</li>
					<li><strong>Destination:</strong> /new-page/</li>
				</ul>
				
				<h3>Wildcards</h3>
				<p>To use wildcards, put an asterisk (*) after the folder name that you want to redirect.</p>
				<h4>Example</h4>
				<ul>
					<li><strong>Request:</strong> /old-folder/*</li>
					<li><strong>Destination:</strong> /redirect-everything-here/</li>
				</ul>
		
				<p>You can also use the asterisk in the destination to replace whatever it matched in the request if you like. Something like this:</p>
				<h4>Example</h4>
				<ul>
					<li><strong>Request:</strong> /old-folder/*</li>
					<li><strong>Destination:</strong> /some/other/folder/*</li>
				</ul>
				<p>Or:</p>
				<ul>
					<li><strong>Request:</strong> /old-folder/*/content/</li>
					<li><strong>Destination:</strong> /some/other/folder/*</li>
				</ul>
			</div>
		</div>
		<?php
		} // end of function options_page
		
		/**
		 * expand_redirects function
		**/
		function r_expand_redirects() {
			$redirects = get_option('301_redirects');
			$output = '';
			if (!empty($redirects)) {
				foreach ($redirects as $request => $destination) {
					$output .= '
					
					<tr>
						<td><input type="text" name="301_redirects[request][]" value="'.$request.'" style="width:99%" /></td>
						<td>&raquo;</td>
						<td><input type="text" name="301_redirects[destination][]" value="'.$destination.'" style="width:99%;" /></td>
						<td><span class="wps301-delete"></span></td>
					</tr>
					
					';
				}
			} // end if
			return $output;
		}
		
		/**
		 * save_redirects function
		**/
		function r_save_redirects($data) {
			if ( !current_user_can('manage_options') )  { wp_die( 'You do not have sufficient permissions to access this page.' ); }
			check_admin_referer( 'r_save_redirects', '_s301r_nonce' );
			
			$data = sanitize_text_field( $_POST['301_redirects'] );

			$redirects = array();
			
			if(isset($data)){
				for($i = 0; $i < sizeof($data['request']); ++$i) {
					$request = trim( sanitize_text_field( $data['request'][$i] ) );
					$destination = trim( sanitize_text_field( $data['destination'][$i] ) );
				
					if ($request == '' && $destination == '') { continue; }
					else { $redirects[$request] = $destination; }
				}
				
				update_option('301_redirects', $redirects);
			}
			
			if (isset($data['wildcard'])) {
				update_option('301_redirects_wildcard', 'true');
			}
			else {
				delete_option('301_redirects_wildcard');
			}
		}
		
		/**
		 * redirect function
		**/
		function redirect() {
			// this is what the user asked for (strip out home portion, case insensitive)
			$userrequest = str_ireplace(get_option('home'),'',$this->r_get_address());
			$userrequest = rtrim($userrequest,'/');
			
			$redirects = get_option('301_redirects');
			if (!empty($redirects)) {
				
				$wildcard = get_option('301_redirects_wildcard');
				$do_redirect = '';
				
				// compare user request to each 301 stored in the db
				foreach ($redirects as $storedrequest => $destination) {
					// check if we should use regex search 
					if ($wildcard === 'true' && strpos($storedrequest,'*') !== false) {
						// wildcard redirect
						
						// don't allow people to accidentally lock themselves out of admin
						if ( strpos($userrequest, '/wp-login') !== 0 && strpos($userrequest, '/wp-admin') !== 0 ) {
							// Make sure it gets all the proper decoding and rtrim action
							$storedrequest = str_replace('*','(.*)',$storedrequest);
							$pattern = '/^' . str_replace( '/', '\/', rtrim( $storedrequest, '/' ) ) . '/';
							$destination = str_replace('*','$1',$destination);
							$output = preg_replace($pattern, $destination, $userrequest);
							if ($output !== $userrequest) {
								// pattern matched, perform redirect
								$do_redirect = $output;
							}
						}
					}
					elseif(urldecode($userrequest) == rtrim($storedrequest,'/')) {
						// simple comparison redirect
						$do_redirect = $destination;
					}
					
					// redirect. the second condition here prevents redirect loops as a result of wildcards.
					if ($do_redirect !== '' && trim($do_redirect,'/') !== trim($userrequest,'/')) {
						// check if destination needs the domain prepended
						if (strpos($do_redirect,'/') === 0){
							$do_redirect = home_url().$do_redirect;
						}
						header ('HTTP/1.1 301 Moved Permanently');
						header ('Location: ' . $do_redirect);
						exit();
					}
					else { unset($redirects); }
				}
			}
		} // end funcion redirect
		
		/**
		 * getAddress function
		**/
		function r_get_address() {
			// return the full address
			return $this->r_get_protocol().'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		} // end function get_address
		
		function r_get_protocol() {
			// Set the base protocol to http
			$protocol = 'http';
			// check for https
			if ( isset( $_SERVER["HTTPS"] ) && strtolower( $_SERVER["HTTPS"] ) == "on" ) {
    			$protocol .= "s";
			}
			
			return $protocol;
		} // end function get_protocol
		
	} // end class Redirect301
	
} // end check for existance of class

// instantiate
$redirect_plugin = new Redirect301();

if (isset($redirect_plugin)) {
	// add the redirect action, high priority
	add_action('init', array($redirect_plugin,'redirect'), 1);

	// create the menu
	add_action('admin_menu', array($redirect_plugin,'r_create_menu'));

	// if submitted, process the data
	if (isset($_POST['301_redirects'])) {
		add_action('admin_init', array($redirect_plugin,'r_save_redirects'));
	}
}

