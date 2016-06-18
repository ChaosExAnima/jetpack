<?php
/**
 * Module Name: VideoPress
 * Module Description: Powerful, simple video hosting for WordPress
 * First Introduced: 2.5
 * Free: false
 * Requires Connection: Yes
 * Sort Order: 27
 * Module Tags: Photos and Videos
 * Feature: Writing
 * Additional Search Queries: video, videos, videopress
 */

/**
 * We won't have any videos less than sixty pixels wide. That would be silly.
 */
define( 'VIDEOPRESS_MIN_WIDTH', 60 );

include_once dirname( __FILE__ ) . '/videopress/utility-functions.php';
include_once dirname( __FILE__ ) . '/videopress/shortcode.php';
include_once dirname( __FILE__ ) . '/videopress/videopress.php';

if ( is_admin() ) {
	include_once dirname(__FILE__) . '/videopress/editor-media-view.php';
}
