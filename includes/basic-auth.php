<?php
/**
 * Based on version 0.1 of the Basic-Auth plugin available at https://github.com/WP-API/Basic-Auth
 * That code has been modified to only allow basic authorization on the WP SSO end points.
 */

/**
 * Check for Basic Authentication headers.
 */
function wpsso_json_basic_auth_handler( $user ) {	
	global $wp_json_basic_auth_error;

	$wp_json_basic_auth_error = null;

	// Don't run if not using SSL
	if ( ! is_ssl() ) {
		return $user;
	}
	
	// Don't run unless using our route.	
	// NOTE: Figure out why this isn't being set in $_REQUEST anymore.
	if ( ! empty( $_REQUEST['rest_route'] ) ) {
		$rest_route = '/' . rest_get_url_prefix() . $_REQUEST['rest_route'];
	} else {
		$rest_route = $_SERVER['REQUEST_URI'];
	}
	/*
		NOTE: This new strpos check could open up an attack
		where this string is pased as a parameter to a different API call.
		To support subfolder multisites, we need to get the path of the subsite
		and append that to the string we're testing against the $rest_route.
		Then we can test if it === the $rest_route specifically.
	*/
	if ( strpos( $rest_route, '/' . rest_get_url_prefix() . '/wp-sso/v1/check' ) === false ) {
		return $user;
	}

	// Don't authenticate twice
	if ( ! empty( $user ) ) {
		return $user;
	}

	// Check that we're trying to authenticate
	if ( !isset( $_SERVER['PHP_AUTH_USER'] ) ) {
		return $user;
	}

	$username = $_SERVER['PHP_AUTH_USER'];
	$password = $_SERVER['PHP_AUTH_PW'];

	/**
	 * In multi-site, wp_authenticate_spam_check filter is run on authentication. This filter calls
	 * get_currentuserinfo which in turn calls the determine_current_user filter. This leads to infinite
	 * recursion and a stack overflow unless the current function is removed from the determine_current_user
	 * filter during authentication.
	 */
	remove_filter( 'determine_current_user', 'wpsso_json_basic_auth_handler', 20 );

	$user = wp_authenticate( $username, $password );

	add_filter( 'determine_current_user', 'wpsso_json_basic_auth_handler', 20 );

	if ( is_wp_error( $user ) ) {
		$wp_json_basic_auth_error = $user;
		return null;
	}

	$wp_json_basic_auth_error = true;

	return $user->ID;
}
add_filter( 'determine_current_user', 'wpsso_json_basic_auth_handler', 20 );

/**
 * Show errors in REST responses.
 * Using the same global as the Basic Auth plugin so we don't duplicate errors.
 */
function wpsso_json_basic_auth_error( $error ) {
	// Passthrough other errors
	if ( ! empty( $error ) ) {
		return $error;
	}

	global $wp_json_basic_auth_error;

	return $wp_json_basic_auth_error;
}
add_filter( 'rest_authentication_errors', 'wpsso_json_basic_auth_error' );
