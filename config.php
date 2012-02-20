<?php

$tnwsc_config = array(
	'services' => array(
		'facebook' => array('url' => "https://graph.facebook.com/fql?q=SELECT url,normalized_url,share_count,like_count,comment_count,total_count,commentsbox_count,+comments_fbid,click_count FROM link_stat WHERE url='%s'"),
		'twitter' => array('url' => "http://urls.api.twitter.com/1/urls/count.json?url=%s"),
		'linkedin' => array('url' => "http://www.linkedin.com/countserv/count/share?url=%s"),
		'google' => array('url' => "https://clients6.google.com/rpc")
	)
);

$tnwsc_wp_options = array(
	'tnwsc_services' => array('facebook' => 1, 'twitter' => 1, 'linkedin' => 1, 'google' => 1),
	'tnwsc_sync_frequency' => 3600, // - Frequency (in seconds) of update the social count; Defaults to one check per hour
	'tnwsc_post_range' => 604800, // - Sync social count for posts published before this number of seconds; Defaults to ( -7 days )
	'tnwsc_active_sync' => 0, // - Whether the sync is active ( 1 or 0 ). Defaults to inactive
	'tnwsc_debug' => 1
); 

?>