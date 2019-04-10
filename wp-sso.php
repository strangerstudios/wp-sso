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
	// Avoid loops when authenticating over API
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST == true ) {
		return $user;
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
		$url = 'https://dev.paidmembershipspro.com/wp-json/wp-sso/v1/check';		
		$args = array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $username . ':' . $password ),
			),
		);
		$response = wp_remote_get( $url, $args );
				
		// Only process if request is successful (200 OK)
		if ( ! empty ( $response ) 
			&& ! empty( $response['response'] ) 
			&& $response['response']['code'] == '200' ) {
			
			// Check API response
			$remote_user = json_decode( $response['body'] );
						
			if ( $remote_user->success == true ) {
				// If logged in, create user
				$new_user_array = array(
					'user_login' => $username,
					'user_pass'  => $password,					
					'user_email' => $remote_user->user_email,
					'role'		 => get_option( 'default_role', 'subscriber' ),
					'first_name' => $remote_user->first_name,
					'last_name'  => $remote_user->last_name,					
				);

				$user_id = wp_insert_user( $new_user_array );
				
				// If user creation works, log them in.
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
			}
		}
	}	
}
add_action( 'wp_authenticate', 'wpsso_authenticate', 1, 2);

/**
 * This is our callback function that embeds our phrase in a WP_REST_Response
 */
function wpsso_get_endpoint_check() {
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
function wpsso_register_example_routes() {    
    register_rest_route( 
		'wp-sso/v1',
		'/check',
		array(        
			'methods'  => WP_REST_Server::READABLE,        
			'callback' => 'wpsso_get_endpoint_check',
		)
	);
}
add_action( 'rest_api_init', 'wpsso_register_example_routes' );

