<?php
/**
 * Module Name: VideoPress
 * Module Description: Upload and embed videos right on your site. (Subscription required.)
 * First Introduced: 2.5
 * Free: false
 * Requires Connection: Yes
 * Sort Order: 27
 * Module Tags: Photos and Videos
 * Additional Search Queries: video, videos, videopress
 */

/**
 * We won't have any videos less than sixty pixels wide. That would be silly.
 */
defined( 'VIDEOPRESS_MIN_WIDTH' ) or define( 'VIDEOPRESS_MIN_WIDTH', 60 );

include_once dirname( __FILE__ ) . '/videopress/utility-functions.php';
include_once dirname( __FILE__ ) . '/videopress/shortcode.php';
include_once dirname( __FILE__ ) . '/videopress/videopress.php';

if ( is_admin() ) {
	include_once dirname(__FILE__) . '/videopress/editor-media-view.php';
}
