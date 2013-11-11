<?php
/*
Plugin Name: iPermission ( Dashboard Edition )
Plugin URI: http://www.iappi.de/ipermission/
Description: iPermission set Rule for your admin dashboard. Manage Or Hide widgets, Options, Plugins, and Admin Menu.
Version: 1.0
Author: Keyvan Hardani
Author URI: http://www.iappi.de
License: GPL2
*/
/*
    Copyright 2013 Keyvan Hardani
    
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.
    
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
    
    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

add_action('wp_dashboard_setup', 'ipermission_wp_dashboard_setup', 99);

add_action('admin_init', 'ipermission_admin_init');

add_action('admin_menu', 'ipermission_admin_menu');

add_action('admin_notices', 'ipermission_admin_notices');

add_filter("plugin_action_links_".plugin_basename(__FILE__), 'ipermission_plugin_settings_link' );

register_deactivation_hook(__FILE__, 'ipermission_deactivate');

/**
 *	Display admin notifications
 * 	@args	none
 *	@return	string
 */
function ipermission_admin_notices(){
	$widgets = get_option('ipermission_dashboard_widgets');
	if(!$widgets){	
		echo sprintf('<div class="updated"><p>%s</p></div>', 'Thank you for using our plugin, Please click <a href="'.get_admin_url().'index.php"/><strong>Here</strong> to complete the installation.</a>	</br><h3 class="update-nag"> Please visit our own <a href="http://iappi.de/ispam/" target="_blank">support team</a> for any issues.</h3>
		</br><h3 class="update-nag">iAppi.de Softwareentwicklung™</h3>');
	}
}

/**
 *	Initialize plugin
 * 	@args	none
 *	@return	void
 */
function ipermission_admin_init() {
	$widgets =  get_option('ipermission_dashboard_widgets');
	if($widgets){
		foreach($widgets as $widget) {
			register_setting('ipermission_options', 'ipermission_'.$widget['id']);
		}
	}
}

/**
 *	Display Settings link on Plugins admin page
 * 	@args	array
 *	@return	array
 */
function ipermission_plugin_settings_link($links) { 
  $settings_link = '<a href="options-general.php?page='.plugin_basename(__FILE__).'">Settings</a>'; 
  array_unshift($links, $settings_link); 
  return $links; 
}

/**
 *	Deactivate plugin
 * 	@args	none
 *	@return void
 */
function ipermission_deactivate() {
	$widgets = get_option('ipermission_dashboard_widgets');
	foreach($widgets as $widget) {
		delete_option('ipermission_'.$widget['id']);
	}
	delete_option('ipermission_dashboard_widgets');
}

/**
 *	Loop through the dashboard widgets and remove depending on current user capabilities
 * 	@args	none
 *	@return void
 */
function ipermission_wp_dashboard_setup() {
	global $wp_meta_boxes;
	$widgets =  ipermission_get_dashboard_widgets();
	update_option('ipermission_dashboard_widgets', $widgets);
	foreach ($widgets as $widget){
		if(!current_user_can(get_option('ipermission_'.$widget['id']))) { 
			unset($wp_meta_boxes['dashboard'][$widget['context']][$widget['priority']][$widget['id']]);
		}
	}
}

/**
 *	Get array of capabilities or generate dropdown list for options page
 * 	@args	bool (optional) $selectlist, bool (optional) $name
 * 	@return array|string
 */
function ipermission_get_capabilities($selectlist = FALSE, $name = FALSE) {
	global $wp_roles;
	$option = get_option($name);	
	if ($selectlist) {
		$cap = '<select name="' . $name .'">';
		$cap .= '<option value="do-everything">Nobody</option>';	
		foreach ($wp_roles->roles['administrator']['capabilities'] as $key=>$val) {
			$cap .= '<option value="' . $key . '"';
			if ($option == $key) $cap .= ' selected="yes"';
			$cap .= '>' . $key . '</option>';		
		}
		$cap .= '</select>';
		return $cap;
	} else {
		return $wp_roles->roles['administrator']['capabilities'];
	}
}

/**
 *	Generate an array of registered dashboard widgets
 * 	@args 	none
 * 	@return array
 */
function ipermission_get_dashboard_widgets() {
	global $wp_meta_boxes;
	$widgets = array();
	if (isset($wp_meta_boxes['dashboard'])) {
		foreach($wp_meta_boxes['dashboard'] as $context=>$data){
			foreach($data as $priority=>$data){
				foreach($data as $widget=>$data){
					//echo $context.' > '.$priority.' > '.$widget.' > '.$data['title']."\n";
					$widgets[$widget] = array('id' => $widget,
									   'title' => strip_tags(preg_replace('/( |)<span.*span>/im', '', $data['title'])),
									   'context' => $context,
									   'priority' => $priority
									   );
				}
			}
		}
	}
	return $widgets;
}

/**
 *	Add options page to admin menu
 * 	@args 	none
 * 	@return void
 */

	
function ipermission_admin_menu() {
	if (function_exists('add_options_page')) {
		 add_menu_page('iPermission', 'iPermission', 'manage_options', __FILE__, 'ipermission_admin_page', $icon_url = ''.plugins_url().'/ipermission/images/ipermission.png');
	}
 }
 
 if ( empty($icon_url) ) {
		$icon_url = 'none';
		$icon_class = 'menu-icon-generic ';
	} else {
		$icon_url = set_url_scheme( $icon_url );
		$icon_class = '';
	}

/**
 *	Generate options page content
 * 	@args 	none
 * 	@return string
 */
function ipermission_admin_page() {
	if (empty($title)) $title = __('iPermission Properties');
	$widgets = get_option('ipermission_dashboard_widgets'); 
?>
	<div class="wrap">
	<div id="icon-tools" class="icon32"></div>
	<h2><?php echo esc_html($title); ?></h2></br>
	<?php if($widgets): ?>
	<form method="post" action="options.php">
		<?php settings_fields('ipermission_options'); ?>
		<table class="widefat">
		<thead>
			<tr>
				<th>Widget</th>
				<th>Permission</th>
			</tr>
			</thead>
		<?php foreach($widgets as $widget): ?>
				<tr valign="top">
					<th scope="row">
						<label for="<?php echo 'ipermission_'.$widget['id'] ?>"><?php echo $widget['title'] ?></label>
					</th>
					<th><?php echo(ipermission_get_capabilities(TRUE, 'ipermission_'.$widget['id'])); ?></th>
				</tr>
				
		<?php endforeach; ?> 
			</tbody>
			</table>	
		<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
		</p>
	</form>
	</br><h3 class="update-nag"> Please visit our own <a href="http://iappi.de/ispam/" target="_blank">support team</a> for any issues.</h3>
		</br><h3 class="update-nag">iAppi.de Softwareentwicklung™</h3>
	<?php endif; ?>
	</div>
<?php
}