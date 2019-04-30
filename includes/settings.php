<?php
/**
 * Add the admin settings page.
 */
function wpsso_admin_add_page() {
    add_options_page( 'WP SSO', 'WP SSO', 'manage_options', 'wpsso', 'wpsso_options_page' );
}
add_action( 'admin_menu', 'wpsso_admin_add_page' );

/**
 * Settings page callback.
 */
function wpsso_options_page() {
?>
<div class="wrap">
	<div id="icon-options-general" class="icon32"><br></div>
	<h2><?php _e( 'WP SSO Settings', 'wp-sso' ); ?></h2>	

	<form action="options.php" method="post">

		<?php settings_fields('wpsso_options'); ?>
		<?php do_settings_sections('wpsso_options'); ?>
		
		<p><br/></p>

		<div class="bottom-buttons">
			<input type="hidden" name="wpsso_options[set]" value="1"/>
			<input type="submit" name="submit" class="button-primary" value="<?php esc_attr_e(__('Save Settings', 'wp-sso')); ?>">
		</div>
		
	</form>

</div>
<?php
}

/**
 * Helper to get settings for this plugin with defaults.
 */
function wpsso_get_options() {
	$default_options = array(
		'client' => false,
		'host_url' => '',
		'host' => false,
	);
	
	$options = get_option( 'wpsso_options', $default_options );
	
	$options = array_merge( $default_options, $options );
	
	return $options;
}

/**
 * Registers settings on admin init.
 * Client Settings:
 * - [ ] Check to run WP SSO as a client on this site.
 * - Enter Host URL: ___
 * Host Settings
 * - [ ] Check to run WP SSO as a host on this site.
 * - Host URL: (for copying)
 */
function wpsso_register_settings() {
	register_setting( 'wpsso_options', 'wpsso_options', 'wpsso_options_validate' );

	add_settings_section('wpsso_section_client', __('Client Settings', 'wp-sso'), 'wpsso_section_client', 'wpsso_options');
	add_settings_field('wpsso_option_client', __('Enable Client', 'wp-sso'), 'wpsso_option_client', 'wpsso_options', 'wpsso_section_client');
	add_settings_field('wpsso_option_host_url', __('Host URL', 'wp-sso'), 'wpsso_option_host_url', 'wpsso_options', 'wpsso_section_client');
	
	add_settings_section('wpsso_section_host', __('Host Settings', 'wp-sso'), 'wpsso_section_host', 'wpsso_options');
	add_settings_field('wpsso_option_host', __('Enable Host', 'wp-sso'), 'wpsso_option_host', 'wpsso_options', 'wpsso_section_host');
	add_settings_field('wpsso_option_this_host_url', __('Host URL', 'wp-sso'), 'wpsso_option_this_host_url', 'wpsso_options', 'wpsso_section_host');
}
add_action( 'admin_init', 'wpsso_register_settings' );

/**
 * Section template for client settings.
 */
function wpsso_section_client() {
	echo '<p>';
	_e( 'Set up this site as an <strong>SSO client</strong> to allow users from the host site to login here.', 'wp-sso' );
	echo '</p>';
}

/**
 * Section template for host settings.
 */
function wpsso_section_host() {
	echo '<p>';
	_e( 'Set up this site as an <strong>SSO host</strong> to allow users from this site to login on different client sites.', 'wp-sso' );
	echo '</p>';
}

/**
 * Enable client option.
 */
function wpsso_option_client() {
	$options = wpsso_get_options();
	?>
	<input id="wpsso_option_client" name="wpsso_options[client]" type="checkbox" value="1" <?php checked( $options['client'], true ); ?>/>
	<label for="wpsso_option_client"><?php _e( 'Check to run WP SSO as a client on this site.', 'wp-sso' ); ?></label>
	<?php
}

/**
 * Host url option.
 */
function wpsso_option_host_url() {
	$options = wpsso_get_options();
	?>
	<input id="wpsso_option_host_url" name="wpsso_options[host_url]" type="text" size="60" value="<?php echo esc_attr( $options['host_url'] );?>" />
	<br /><?php _e( 'Find this URL on the WP SSO settings of the host site.', 'wp-sso' ); ?>
	<?php	
}

/**
 * Enable host option.
 */
function wpsso_option_host() {
	$options = wpsso_get_options();
	?>
	<input id="wpsso_option_host" name="wpsso_options[host]" type="checkbox" value="1" <?php checked( $options['host'], true ); ?>/>
	<label for="wpsso_option_host"><?php _e( 'Check to run WP SSO as a host.', 'wp-sso' ); ?></label>
	<?php
}

/**
 * Host url to copy for clients.
 */
function wpsso_option_this_host_url() {
	?>
	<input type="text" size="60" readonly value="<?php echo esc_url( get_rest_url( null, '/wp-sso/v1/check' ) ); ?>" />
	<br /><p>
	<?php _e( 'Copy this URL and past it into the Host URL setting on your client sites.', 'wp-sso' ); ?>
	</p>
	<?php
}

/**
 * Validate options.
 */
function wpsso_options_validate( $input ) {
	// validate
	
	return $input;
}