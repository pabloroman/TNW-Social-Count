<?php
/*
Plugin Name: TNW Social Count
Plugin URI: http://stijlfabriek.com
Description: Save the number of Twitter retweets, Facebook likes, Google Plus and LinkedIn shares as post_meta information.
Version: 1.0
Author: Pablo Román for The Next Web
Author URI: http://stijlfabriek.com
*/

include dirname(__FILE__).'/config.php';


function tnwsc_setup() 
{
	include dirname(__FILE__).'/config.php';
	foreach($tnwsc_wp_options as $key => $value) {
		update_option($key, $value);
	}
}

function tnwsc_deactivate()
{
	include dirname(__FILE__).'/config.php';
	foreach($tnwsc_wp_options as $key => $value) {	
		delete_option($key);
	}
	
}

function tnwsc_init() 
{
	if ( isset( $_GET['tnwsc_sync'] ) ) {
		tnwsc_process();
		exit;
	}
}


function tnwsc_process() 
{
	error_log(date('Y-m-d H:i:s', time())." - Request: tnwsc_process()\n", 3, dirname(__FILE__).'/tnwsc.log');
	$tnwsc_services = get_option( 'tnwsc_services' );
	$tnwsc_debug = get_option( 'tnwsc_debug' );
	$posts = tnwsc_get_posts();
	if( $posts ) {
		foreach( $posts as $post ) {
			$permalink = get_permalink( $post->ID );
			foreach( $tnwsc_services as $service_name => $enabled ) {
				if( $enabled ) {
					$count = tnwsc_get_count( $permalink, $service_name );
					echo "Request: ".$permalink." / ". $service_name." / ". $count."<br />";
					error_log(date('Y-m-d H:i:s', time())." - Request: ".$permalink." / ". $service_name." / ". $count."\n", 3, dirname(__FILE__).'/tnwsc.log');
					if($debug == 0) {
						tnwsc_update_post_meta( $post->ID, $service_name, $count );
					}
				}
			}
		}
	}
	if( get_option( 'tnwsc_active_sync' ) ) {
		tnwsc_schedule_sync();
	}
}


function filter_by_date( $where = '') 
{
	$post_range = get_option( 'tnwsc_post_range' );
	$where .= " AND post_date > '" . date( 'Y-m-d H:i:s', time() - $post_range ) . "'";
	return $where;
}


function tnwsc_get_posts() 
{
	add_filter( 'posts_where', 'filter_by_date' );
	$posts = query_posts( $query_string.'post_type=post&post_status=publish&posts_per_page=-1&' );
	remove_filter( 'posts_where', 'filter_by_date' );
	return $posts;	
}


function tnwsc_get_count( $permalink, $service ) 
{
	global $tnwsc_config;
	
	if( $service === 'google' ) {
		return tnwsc_do_curl( $permalink, $service );
	} else {
		// If there are extra params in the url, append them to the permalink first
		if( isset( $tnwsc_config['services'][$service]["params"] ) ) {
			$permalink = sprintf( $tnwsc_config['services'][$service]["params"], $permalink );
		}
		$url = sprintf( $tnwsc_config['services'][$service]["url"], urlencode ( $permalink ) );
		return tnwsc_do_curl( $url, $service );
	}
}


function tnwsc_do_curl($url, $service) 
{
	global $tnwsc_config;
	$social_count = 0;
	
	// Google+ is an special, hack-ish case
	if( $service == 'google' ) 
	{
		// GET +1s. Credits to Tom Anthony: http://www.tomanthony.co.uk/blog/google_plus_one_button_seo_count_api/
	    $curl = curl_init();
	    curl_setopt( $curl, CURLOPT_URL, "https://clients6.google.com/rpc" );
	    curl_setopt( $curl, CURLOPT_POST, 1 );
	    curl_setopt( $curl, CURLOPT_POSTFIELDS, '[{"method":"pos.plusones.get","id":"p","params":{"nolog":true,"id":"' . $url . '","source":"widget","userId":"@viewer","groupId":"@self"},"jsonrpc":"2.0","key":"p","apiVersion":"v1"}]' );
	    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
	    curl_setopt( $curl, CURLOPT_HTTPHEADER, array( 'Content-type: application/json' ) );
	    $curl_results = curl_exec ( $curl );
	    curl_close ( $curl );
	 
	    $json = json_decode($curl_results, true);
	    $social_count = intval( $json[0]['result']['metadata']['globalCounts']['count'] );
    	
	} else {
	    $ch = curl_init();
	    curl_setopt ($ch, CURLOPT_URL, $url);
	    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		$return = curl_exec( $ch );
	
		if( curl_errno( $ch ) ) { 
	        $error = print_r( curl_error( $ch ), true );
	        // TODO: Notify curl errors via email or log files 
		} else {
			switch( $service ) {
				case 'facebook':
					$return = json_decode( $return, true );
					$social_count = ( isset( $return['data'][0]['total_count'] ) ) ? $return['data'][0]['total_count'] : 0;
					// TODO: Better handling of errors	
					if(isset($return['error'])) { 
						echo "Error ".$return["code"]." (".$return["type"].") - ".$return["message"]."\n";
					}
				break;
				
				case 'twitter':
					$return = json_decode( $return, true );
					$social_count = ( isset($return['count'] ) ) ? $return['count'] : 0;
				break;
				
				case 'linkedin':
					$return = json_decode( str_replace( 'IN.Tags.Share.handleCount(', '', str_replace( ');', '', $return ) ), true );
					$social_count = ( isset($return['count'] ) ) ? $return['count'] : 0;
				break;
			}
		}
	}
	return intval( $social_count );
}


function tnwsc_update_post_meta( $post_id, $service, $count ) 
{
	$key = 'tnwsc_'.$service;
	$old_count = get_post_meta( $post_id, $key, true);
	// Check if the previous value is smaller. If it is, do not update, it's probably an error from the cURL call to the API
	if( $count > $old_count ) {
		update_post_meta( $post_id, 'tnwsc_'.$service, $count );
	}
}


function tnwsc_schedule_sync( $immediate = false ) 
{	
	global $tnwsc_wp_options;
    $hook = "tnwsc_sync";
    $tnwsc_sync_frequency = get_option( 'tnwsc_sync_frequency' ) ? get_option( 'tnwsc_sync_frequency' ) : $tnwsc_wp_options['tnwsc_sync_frequency'];
    
    error_log(date('Y-m-d H:i:s', time())." - Request: tnwsc_schedule_sync(). Synching in $tnwsc_sync_frequency seconds\n", 3, dirname(__FILE__).'/tnwsc.log');
    
    wp_clear_scheduled_hook( $hook );
    if ( $immediate ) {
        //we should schedule the next sync "immediately" (in 15s)
        wp_schedule_single_event( time() + 15, $hook );
    } else {
        //schedule the next sync in typical fashion
        wp_schedule_single_event( time() + $tnwsc_sync_frequency, $hook );
    }
}



add_action( 'init', 'tnwsc_init' );
add_action( 'tnwsc_sync', 'tnwsc_process' );

register_activation_hook( __FILE__, 'tnwsc_setup' );
register_deactivation_hook( __FILE__, 'tnwsc_deactivate' );



// admin menu hook
function tnwsc_admin_menu() {
    add_options_page( 'TNW Social Count Options', 'TNW Social Count', 'manage_options', 'tnwsc-admin', 'tnwsc_admin_page');
}

/// admin page hook
function tnwsc_admin_page(){
    include dirname(__FILE__).'/admin.php';
}

// register admin pages
add_action('admin_menu', 'tnwsc_admin_menu');

function tnwsc_plugin_action_links( $links, $file ) {			

	if ( $file == plugin_basename( dirname(__FILE__).'/index.php' ) ) {
		$links[] = '<a href="admin.php?page=tnwsc-admin">'.__('Settings').'</a>';
	}
	return $links;

}
add_filter( "plugin_action_links", 'tnwsc_plugin_action_links', 10, 2 );

