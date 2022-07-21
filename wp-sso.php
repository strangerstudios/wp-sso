<?php
/**
 * Plugin Name: WP Single Sign On (SSO)
 * Plugin URI: https://www.strangerstudios.com/wp-sso/
 * Description: Connect multiple non-multisite WordPress sites together to share logins.
 * Version: .1
 * Author: strangerstudios
 * Author URI: https://www.strangerstudios.com
 * Text Domain: wp-sso
 */

define( 'WP_SSO_VERSION', .1 );
define( 'WP_SSO_DIR', dirname( __FILE__ ) );
define( 'WP_SSO_BASENAME', plugin_basename( __FILE__ ) );

// Add Basic Authentication for our end points.
require_once( WP_SSO_DIR . '/includes/admin.php' );
require_once( WP_SSO_DIR . '/includes/basic-auth.php' );
require_once( WP_SSO_DIR . '/includes/settings.php' );

/*
	SSO Server
	* Try to log into the API using user/pass
*/

/*
	SSO Client
	* Need setting for domain of the SSO server.
	* Hook into login failing.
	* If user doesn't exist, try user/pass at server.
	* If that works, setup a user with the same user/email/pass here.
	* Hooks for other checks before setting up a new user.
	* Hooks for after setting up a new user.
*/

/**
 * Intercept authentication to checkin with server if needed.
 */
function wpsso_authenticate( $username, $password ) {	
	$options = wpsso_get_options();
	
	// If client is not enabled, bail.
	if ( ! $options['client'] ) {
		return;
	}
	
	// Avoid loops when authenticating over API
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST == true ) {
		return;
	}
		
	// If no username or password, bail.
	if ( empty( $username ) || empty( $password ) ) {
		return;
	}
	
	// Check for user by login.
	$user = get_user_by( 'login', $username );
	
	// Check by email if needed.
	if ( empty( $user ) ) {
		$user = get_user_by( 'email', $username );
	}
		
	// If we have a user, check the password
	if ( ! empty( $user ) && ! empty( $user->ID ) ) {
		$password_works = wp_check_password( $password, $user->user_pass, $user->ID );		
	} else {
		$password_works = false;
	}
	
	// If no user or password is not going to work, then try the server API
	if ( $password_works === false ) {		
		$url = $options['host_url'];
		
		// If URL is not set, bail.
		if ( empty( $url ) ) {
			return;
		}
		
		$args = array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $username . ':' . $password ),
			),
		);
		$response = wp_remote_get( $url, $args );
				
		// Only process if request is successful (200 OK)
		if ( ! empty ( $response ) 
			&& ! is_wp_error( $response )
			&& ! empty( $response['response'] ) 
			&& $response['response']['code'] == '200' ) {
			
			// Check API response
			$remote_user = json_decode( $response['body'] );
						
			if ( $remote_user->success == true ) {
				// Remote login worked. Create or update user.								
				if ( empty( $user ) || empty( $user->ID ) ) {
					// Create user on client site.
					$new_user_array = array(
						'user_login' => $username,
						'user_pass'  => $password,					
						'user_email' => $remote_user->user_email,
						'role'		 => get_option( 'default_role', 'subscriber' ),
						'first_name' => $remote_user->first_name,
						'last_name'  => $remote_user->last_name,					
					);

					$user_id = wp_insert_user( $new_user_array );										
				} else {
					// Update user on client site.
					if ( user_can( $user, 'manage_options' ) ) {
						return;	// We don't want to update passwords for admins
					}
					
					$user_id = $user->ID;
					wp_set_password( $password, $user_id );					
				}
				
				// Log the user in.
				if ( ! empty( $user_id ) ) {					
					$creds                  = array();
					$creds['user_login']    = $new_user_array['user_login'];
					$creds['user_password'] = $new_user_array['user_pass'];
					$creds['remember']      = true;
					$user                   = wp_signon( $creds, false );
					
					wp_set_current_user( $user_id, $username );
					wp_set_auth_cookie( $user_id, true, force_ssl_admin() );
				}
			} else {
				// Remote login failed.
				// Login on local site should fail too.
				if ( is_wp_error( $response ) ) {
					$msgt = 'pmpro_error';
					$message = $response->get_error_message();					
				} else {
					$msgt = 'pmpro_error';
					$message = __( 'Error loggin in. Unknown error.', 'wp-sso' );
				}
				echo '<div class="' . pmpro_get_element_class( 'pmpro_message ' . $msgt, esc_attr( $msgt ) ) . '">'. wp_kses_post( $message ) .'</div>';
			}
		}
	}	
}
add_action( 'wp_authenticate', 'wpsso_authenticate', 1, 2);

/**
 * This is our callback function that embeds our phrase in a WP_REST_Response
 */
function wpsso_check_authentication_endpoint() {
	global $current_user;
	
	if ( ! empty( $current_user ) && ! empty( $current_user->user_login ) ) {
		$r = array(
			'success' => true,
			'message' => sprintf( 'Logged in as %s', $current_user->user_login ),
			'user_login' => $current_user->user_login,
			'user_email' => $current_user->user_email,
			'first_name' => $current_user->first_name,
			'last_name' => $current_user->last_name,
		);		
	} else {
		$r = array(
			'success' => false,
			'message' => 'Not logged in.',
			'user_email' => null,
			'user_login' => $current_user->user_login,
			'first_name' => null,
			'last_name' => null,
		);		
	}
	
	$r = rest_ensure_response( $r );
	
	return $r;
}
 
/**
 * This function is where we register our routes for our example endpoint.
 */
function wpsso_register_routes() {    
    $options = wpsso_get_options();
	
	// Make sure host option is enabled.
	if ( ! $options['host'] ) {
		return;
	}
	
	register_rest_route( 
		'wp-sso/v1',
		'/check',
		array(        
			'methods'  => WP_REST_Server::READABLE,        
			'callback' => 'wpsso_check_authentication_endpoint',
		)
	);
}
add_action( 'rest_api_init', 'wpsso_register_routes' );

