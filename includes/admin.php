<?php
/**
 * Runs only when the plugin is activated.
 *
 * @since 0.1.0
 */
function wpsso_admin_notice_activation_hook() {
	// Create transient data.
	set_transient( 'wpsso-admin-notice', true, 5 );
}
register_activation_hook( WP_SSO_BASENAME, 'wpsso_admin_notice_activation_hook' );

/**
 * Admin Notice on Activation.
 *
 * @since 0.1.0
 */
function wpsso_admin_notice() {
	// Check transient, if available display notice.
	if ( get_transient( 'wpsso-admin-notice' ) ) { ?>
		<div class="updated notice is-dismissible">
			<p><?php printf( __( 'Thank you for activating. <a href="%s">Visit the settings page</a> to set up WP SSO.', 'wp-sso' ), get_admin_url( null, 'admin.php?page=wpsso' ) ); ?></p>
		</div>
		<?php
		// Delete transient, only display this notice once.
		delete_transient( 'wpsso-admin-notice' );
	}
}
add_action( 'admin_notices', 'wpsso_admin_notice' );

/**
 * Function to add links to the plugin action links
 *
 * @param array $links Array of links to be shown in plugin action links.
 */
function wpsso_plugin_action_links( $links ) {
	if ( current_user_can( 'manage_options' ) ) {
		$new_links = array(
			'<a href="' . get_admin_url( null, 'admin.php?page=wpsso' ) . '">' . __( 'Settings', 'wp-sso' ) . '</a>',
		);
	}
	return array_merge( $new_links, $links );
}
add_filter( 'plugin_action_links_' . WP_SSO_BASENAME, 'wpsso_plugin_action_links' );

/**
 * Function to add links to the plugin row meta
 *
 * @param array  $links Array of links to be shown in plugin meta.
 * @param string $file Filename of the plugin meta is being shown for.
 */
function wpsso_plugin_row_meta( $links, $file ) {
	if ( strpos( $file, 'wp-sso.php' ) !== false ) {
		$new_links = array(
			'<a href="' . esc_url( 'https://www.strangerstudios.com/plugins/wp-sso/' ) . '" title="' . esc_attr( __( 'View Documentation', 'wp-sso' ) ) . '">' . __( 'Docs', 'wp-sso' ) . '</a>',			
		);
		$links = array_merge( $links, $new_links );
	}
	return $links;
}
add_filter( 'plugin_row_meta', 'wpsso_plugin_row_meta', 10, 2 );